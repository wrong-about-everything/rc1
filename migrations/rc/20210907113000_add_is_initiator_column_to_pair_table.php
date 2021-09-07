<?php

use Phinx\Migration\AbstractMigration;

class AddIsInitiatorColumnToPairTable extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<q
alter table meeting_round_pair
    add column is_initiator bool;
q
        );
    }
}
