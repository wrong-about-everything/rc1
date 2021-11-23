<?php

use Phinx\Migration\AbstractMigration;

class AddSupportBotColumnToBotTable extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<q
alter table bot
    add column support_bot_name text;
q
        );
    }
}
