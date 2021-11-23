<?php

use Phinx\Migration\AbstractMigration;

class AddMeetingTipsColumnToBotTable extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<q
alter table bot
    add column meeting_tips text;
q
        );
    }
}
