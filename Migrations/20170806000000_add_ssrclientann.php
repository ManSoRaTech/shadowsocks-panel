<?php

use Phinx\Migration\AbstractMigration;

class AddSsrClientAnn extends AbstractMigration
{
    public function change()
    {
        $this->insert('options', ['k' => 'ssr_client_announcement', 'v' => '']);
    }
}