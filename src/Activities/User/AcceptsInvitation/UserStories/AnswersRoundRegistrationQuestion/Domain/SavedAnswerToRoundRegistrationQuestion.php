<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain;

use Exception;
use RC\Domain\UserInterest\InterestId\Pure\Single\FromInterestName;
use RC\Domain\UserInterest\InterestName\Pure\FromString as InterestNameFromString;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Impure\FromRoundRegistrationQuestion as RoundRegistrationQuestionId;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure\FromImpure;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure\RoundRegistrationQuestionId as PureRoundRegistrationQuestionId;
use RC\Domain\UserInterest\InterestId\Impure\Single\FromPure;
use RC\Domain\UserInterest\InterestId\Impure\Single\FromRoundRegistrationQuestion;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\UserInterest\InterestId\Pure\Single\SpecificArea as SpecificAreaId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

class SavedAnswerToRoundRegistrationQuestion
{
    private $userMessage;
    private $invitationId;
    private $answeredQuestion;
    private $connection;

    public function __construct(UserMessage $userMessage, InvitationId $invitationId, RoundRegistrationQuestion $answeredQuestion, OpenConnection $connection)
    {
        $this->userMessage = $userMessage;
        $this->invitationId = $invitationId;
        $this->answeredQuestion = $answeredQuestion;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $roundRegistrationQuestionId = new RoundRegistrationQuestionId($this->answeredQuestion);
        if (!$roundRegistrationQuestionId->value()->isSuccessful()) {
            return $roundRegistrationQuestionId->value();
        }

        $updateProgressResponse = $this->persistenceResponse(new FromImpure($roundRegistrationQuestionId));
        if (!$updateProgressResponse->isSuccessful()) {
            return $updateProgressResponse;
        }

        return new Successful(new Emptie());
    }

    private function persistenceResponse(PureRoundRegistrationQuestionId $roundRegistrationQuestionId)
    {
        return
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        <<<q
insert into user_round_registration_progress (registration_question_id, user_id)
select ?, user_id from meeting_round_invitation where id = ?
q
                        ,
                        [$roundRegistrationQuestionId->value(), $this->invitationId->value()->pure()->raw()],
                        $this->connection
                    ),
                    $this->updateBotUserQuery(),
                ],
                $this->connection
            ))
                ->response();
    }

    private function updateBotUserQuery()
    {
        if ((new FromRoundRegistrationQuestion($this->answeredQuestion))->equals(new FromPure(new Networking()))) {
            return
                new SingleMutating(
                    <<<q
update meeting_round_participant
set interested_in = ?
from meeting_round_invitation mri
where mri.user_id = meeting_round_participant.user_id and mri.meeting_round_id = meeting_round_participant.meeting_round_id and mri.id = ?
q
                    ,
                    [
                        json_encode(
                            [
                                (new FromInterestName(
                                    new InterestNameFromString($this->userMessage->value())
                                ))
                                    ->value()
                            ]
                        ),
                        $this->invitationId->value()->pure()->raw()
                    ],
                    $this->connection
                );
        } elseif ((new FromRoundRegistrationQuestion($this->answeredQuestion))->equals(new FromPure(new SpecificAreaId()))) {
            return
                new SingleMutating(
                    <<<q
update meeting_round_participant
set interested_in_as_plain_text = ?
from meeting_round_invitation mri
where mri.user_id = meeting_round_participant.user_id and mri.meeting_round_id = meeting_round_participant.meeting_round_id and mri.id = ?
q
                    ,
                    [$this->userMessage->value(), $this->invitationId->value()->pure()->raw()],
                    $this->connection
                );
        }

        throw new Exception(sprintf('Unknown interest given: %s', (new FromRoundRegistrationQuestion($this->answeredQuestion))->value()->pure()->raw()));
    }
}