<?php

declare(strict_types=1);

namespace RC\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\AcceptsOrDeclinesFeedbackInvitation;
use RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\AnswersFeedbackQuestion;
use RC\Activities\User\RepliesToRoundInvitation\UserStories\AnswersRoundRegistrationQuestion\AnswersRoundRegistrationQuestion;
use RC\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\AcceptsOrDeclinesInvitation;
use RC\Domain\Bot\ById as BotById;
use RC\Domain\BotUser\ReadModel\ByInternalTelegramUserIdAndBotId;
use RC\Domain\FeedbackInvitation\ReadModel\LatestByFeedbackDate;
use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Domain\FeedbackInvitation\Status\Impure\FromFeedbackInvitation;
use RC\Domain\FeedbackInvitation\Status\Impure\FromPure as FromPureFeedbackInvitationStatus;
use RC\Domain\FeedbackInvitation\Status\Pure\Accepted;
use RC\Domain\FeedbackInvitation\Status\Pure\Sent as FeedbackInvitationSent;
use RC\Domain\FeedbackQuestion\FirstNonAnsweredFeedbackQuestion;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromInvitation as MeetingRoundFromInvitation;
use RC\Domain\MeetingRound\ReadModel\ById;
use RC\Domain\MeetingRound\StartDateTime;
use RC\Domain\Participant\ParticipantId\Impure\FromFeedbackInvitation as ParticipantIdFromFeedbackInvitation;
use RC\Domain\Participant\ReadModel\ByInvitationId;
use RC\Domain\Participant\Status\Impure\FromPure as ParticipantStatus;
use RC\Domain\Participant\Status\Impure\FromReadModelParticipant;
use RC\Domain\Participant\Status\Pure\RegistrationInProgress;
use RC\Domain\RoundInvitation\InvitationId\Impure\FromInvitation as InvitationId;
use RC\Domain\RoundInvitation\ReadModel\Invitation;
use RC\Domain\RoundInvitation\ReadModel\InvitationForMeetingRoundWithLatestPassedInvitationDate;
use RC\Domain\RoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromPure;
use RC\Domain\RoundInvitation\Status\Pure\Sent;
use RC\Domain\SentReplyToUser\InCaseOfAnyUncertainty;
use RC\Domain\SentReplyToUser\NoRoundsAhead;
use RC\Domain\BotUser\UserStatus\Impure\FromReadModelBotUser;
use RC\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use RC\Domain\BotUser\UserStatus\Pure\Registered;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\SentReplyToUser\Sorry;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Infrastructure\Uuid\FromString as UuidFromString;
use RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\AnswersRegistrationQuestion;

class SendsArbitraryMessage extends Existent
{
    private $now;
    private $message;
    private $botId;
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(ISO8601DateTime $now, array $message, string $botId, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->now = $now;
        $this->message = $message;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario started'));

        $userStatus = $this->userStatus();
        if (!$userStatus->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($userStatus->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        if ($userStatus->equals(new ImpureUserStatusFromPure(new RegistrationIsInProgress()))) {
            $this->answersRegistrationQuestion();
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new Registered()))) {
            $latestRoundInvitation = $this->latestRoundInvitation();
            $feedbackInvitation = $this->feedbackInvitation();

            if ($this->thereIsAPendingExpiredRoundInvitation($latestRoundInvitation)) {
                $this->noRoundsAhead()->value();
            } elseif ($this->thereIsAPendingNonExpiredRoundInvitation($latestRoundInvitation)) {
                $this->acceptsOrDeclinesRoundInvitation();
            } elseif ($this->thereIsAUserRegisteringForARound($latestRoundInvitation)) {
                $this->answersRoundRegistrationQuestion();
            } elseif ($this->thereIsAPendingFeedbackInvitation($feedbackInvitation)) {
                $this->acceptsOrDeclinesFeedbackInvitation();
            } elseif ($this->userIsAnsweringFeedbackQuestions($feedbackInvitation)) {
                $this->answersFeedbackQuestion();
            } else {
                $this->replyInCaseOfAnyUncertainty()->value();
            }
        } else {
            $this->replyInCaseOfAnyUncertainty()->value();
        }

        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario finished'));

        return new Successful(new Emptie());
    }

    private function userStatus()
    {
        return
            new FromReadModelBotUser(
                new ByInternalTelegramUserIdAndBotId(
                    new FromParsedTelegramMessage($this->message),
                    $this->botId(),
                    $this->connection
                )
            );
    }

    private function answersRegistrationQuestion()
    {
        return
            (new AnswersRegistrationQuestion(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function noRoundsAhead()
    {
        return
            new NoRoundsAhead(
                new FromParsedTelegramMessage($this->message),
                new BotById(
                    $this->botId(),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function answersRoundRegistrationQuestion()
    {
        return
            (new AnswersRoundRegistrationQuestion(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function sorry()
    {
        return
            new Sorry(
                new FromParsedTelegramMessage($this->message),
                new BotById(
                    $this->botId(),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function replyInCaseOfAnyUncertainty()
    {
        return
            new InCaseOfAnyUncertainty(
                new FromParsedTelegramMessage($this->message),
                $this->botId(),
                $this->connection,
                $this->httpTransport
            );
    }

    private function botId()
    {
        return new FromUuid(new UuidFromString($this->botId));
    }

    private function thereIsAPendingExpiredRoundInvitation(Invitation $invitation)
    {
        $latestInvitationStatus = new FromInvitation($invitation);
        if (!$latestInvitationStatus->exists()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($latestInvitationStatus->value()));
            return false;
        }
        if ($latestInvitationStatus->exists()->pure()->raw() === false) {
            return false;
        }

        return $this->meetingRoundAlreadyStarted($invitation) && $latestInvitationStatus->equals(new FromPure(new Sent()));
    }

    private function thereIsAPendingNonExpiredRoundInvitation(Invitation $invitation)
    {
        $latestInvitationStatus = new FromInvitation($invitation);
        if (!$latestInvitationStatus->exists()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($latestInvitationStatus->value()));
            return false;
        }
        if ($latestInvitationStatus->exists()->pure()->raw() === false) {
            return false;
        }

        return !$this->meetingRoundAlreadyStarted($invitation) && $latestInvitationStatus->equals(new FromPure(new Sent()));
    }

    private function thereIsAPendingFeedbackInvitation(FeedbackInvitation $feedbackInvitation)
    {
        $feedbackInvitationStatus = new FromFeedbackInvitation($feedbackInvitation);
        if (!$feedbackInvitationStatus->exists()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($feedbackInvitationStatus->value()));
            return false;
        }
        if ($feedbackInvitationStatus->exists()->pure()->raw() === false) {
            return false;
        }

        return $feedbackInvitationStatus->equals(new FromPureFeedbackInvitationStatus(new FeedbackInvitationSent()));
    }

    private function userIsAnsweringFeedbackQuestions(FeedbackInvitation $feedbackInvitation)
    {
        $feedbackInvitationStatus = new FromFeedbackInvitation($feedbackInvitation);
        if (!$feedbackInvitationStatus->exists()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($feedbackInvitationStatus->value()));
            return false;
        }
        if ($feedbackInvitationStatus->exists()->pure()->raw() === false) {
            return false;
        }

        return $feedbackInvitationStatus->equals(new FromPureFeedbackInvitationStatus(new Accepted())) && $this->thereIsAtLeastOneFeedbackQuestionLeft($feedbackInvitation);
    }

    private function thereIsAtLeastOneFeedbackQuestionLeft(FeedbackInvitation $feedbackInvitation)
    {
        return
            (new FirstNonAnsweredFeedbackQuestion(
                new ParticipantIdFromFeedbackInvitation($feedbackInvitation),
                $this->connection
            ))
                ->value()->pure()->isPresent();
    }

    private function meetingRoundAlreadyStarted(Invitation $invitation)
    {
        return
            (new StartDateTime(
                new ById(
                    new MeetingRoundFromInvitation($invitation),
                    $this->connection
                )
            ))
                ->earlierThan(
                    $this->now
                );
    }

    private function latestRoundInvitation(): Invitation
    {
        return
            new InvitationForMeetingRoundWithLatestPassedInvitationDate(
                new FromParsedTelegramMessage($this->message),
                $this->botId(),
                $this->connection
            );
    }

    private function feedbackInvitation(): FeedbackInvitation
    {
        return new LatestByFeedbackDate(new FromParsedTelegramMessage($this->message), $this->botId(), $this->connection);
    }

    private function acceptsOrDeclinesRoundInvitation()
    {
        return
            (new AcceptsOrDeclinesInvitation(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function acceptsOrDeclinesFeedbackInvitation()
    {
        return
            (new AcceptsOrDeclinesFeedbackInvitation(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function answersFeedbackQuestion()
    {
        return
            (new AnswersFeedbackQuestion(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function thereIsAUserRegisteringForARound(Invitation $latestInvitation)
    {
        $participant =
            new ByInvitationId(
                new InvitationId($latestInvitation),
                $this->connection
            );
        if (!$participant->exists()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($participant->value()));
            return false;
        }
        if ($participant->exists()->pure()->raw() === false) {
            return false;
        }

        return (new FromReadModelParticipant($participant))->equals(new ParticipantStatus(new RegistrationInProgress()));
    }
}