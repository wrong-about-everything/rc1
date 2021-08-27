<?php

use Phinx\Migration\AbstractMigration;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\About;

class ChangeRegistrationQuestion extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            sprintf(
                <<<q
update registration_question
set text = 'Напишите пару слов о себе для вашего собеседника.

Например: Сергей, 27, работаю в "Рога и копыта", выстраиваю процесс доставки еды. Развожу хомячков.'
where profile_record_type = %s and bot_id = '%s' and ordinal_number = %s
q
                ,
                (new About())->value(),
                '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016',
                3
            )
        );
    }
}
