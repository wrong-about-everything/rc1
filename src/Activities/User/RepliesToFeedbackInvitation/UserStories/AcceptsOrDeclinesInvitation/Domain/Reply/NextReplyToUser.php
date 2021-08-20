<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply;

use RC\Activities\User\RepliesToFeedbackInvitation\Domain\Reply\NextFeedbackQuestionReplyToUser;
use RC\Activities\User\RepliesToFeedbackInvitation\Domain\Reply\ThanksForFeedback;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Domain\FeedbackInvitation\Status\Impure\FromFeedbackInvitation;
use RC\Domain\FeedbackInvitation\Status\Impure\FromPure;
use RC\Domain\FeedbackInvitation\Status\Pure\Declined;
use RC\Domain\FeedbackQuestion\FirstNonAnsweredFeedbackQuestion;
use RC\Domain\Participant\ParticipantId\Impure\FromFeedbackInvitation as ParticipantIdFromFeedbackInvitation;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\SentReplyToUser\SentReplyToUser;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class NextReplyToUser implements SentReplyToUser
{
    private $feedbackInvitation;
    private $telegramUserId;
    private $botId;
    private $httpTransport;
    private $connection;

    public function __construct(FeedbackInvitation $feedbackInvitation, InternalTelegramUserId $telegramUserId, BotId $botId, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->feedbackInvitation = $feedbackInvitation;
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->feedbackInvitation->value()->isSuccessful()) {
            return $this->feedbackInvitation->value();
        }

        $nextFeedbackQuestion = $this->nextFeedbackQuestion();
        if ((new FromFeedbackInvitation($this->feedbackInvitation))->equals(new FromPure(new Declined()))) {
            return $this->seeYouNextTime();
        } elseif (!$nextFeedbackQuestion->value()->pure()->isPresent()) {
            return $this->thanksForFeedback();
        } else {
            return
                (new NextFeedbackQuestionReplyToUser(
                    $nextFeedbackQuestion,
                    $this->telegramUserId,
                    $this->botId,
                    $this->connection,
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function seeYouNextTime()
    {
        return
            (new FeedbackInvitationDeclinedAndSeeYouNextTime(
                $this->telegramUserId,
                new ByBotId($this->botId, $this->connection),
                $this->httpTransport
            ))
                ->value();
    }

    private function thanksForFeedback()
    {
        return
            (new ThanksForFeedback(
                $this->telegramUserId,
                $this->botId,
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function nextFeedbackQuestion()
    {
        return
            new FirstNonAnsweredFeedbackQuestion(
                new ParticipantIdFromFeedbackInvitation($this->feedbackInvitation),
                $this->connection
            );
    }
}