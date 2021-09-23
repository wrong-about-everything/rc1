<?php

use Phinx\Migration\AbstractMigration;

class AddSorryIsSentColumnToDropoutTable extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<q
alter table meeting_round_dropout
    add column sorry_is_sent bool;
q
        );
    }
}
