<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultFalseValueToSorryIsSentColumnInDropoutTable extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<q
alter table meeting_round_dropout
    alter column sorry_is_sent set default false;
q
        );
    }
}
