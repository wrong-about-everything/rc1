<?php

use Phinx\Migration\AbstractMigration;

class TestMigration extends AbstractMigration
{
    public function change()
    {
        $this->execute('select 1');
    }
}
