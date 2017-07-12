<?php

use Phinx\Migration\AbstractMigration;

class ModifyNodeMethod extends AbstractMigration
{
    public function change()
    {
        $this->table('member')
            ->addColumn('protocol', 'string', ['default' => 'auth_sha1_v4', 'after' => 'method', 'limit' => 100, 'null' => true])
            ->addColumn('obfs', 'string', ['default' => 'tls1.2_ticket_auth', 'after' => 'protocol', 'limit' => 100, 'null' => true])
            ->addColumn('obfsparam', 'string', ['default' => 'intl.aliyun.com', 'after' => 'obfs', 'limit' => 100, 'null' => true])
            ->save();
        $this->execute('UPDATE member SET method=\'chacha20\' WHERE 1=1');
        $this->table('node')
            ->addColumn('protocol', 'string', ['default' => 'auth_sha1_v4', 'after' => 'method', 'limit' => 100, 'null' => true])
            ->addColumn('obfs', 'string', ['default' => 'tls1.2_ticket_auth', 'after' => 'protocol', 'limit' => 100, 'null' => true])
            ->save();

        $option = [
            [
                'k' => 'custom_method_list',
                'v' => '["aes-256-cfb","chacha20","chacha20-ietf"]'
            ],
            [
                'k' => 'custom_protocol_list',
                'v' => '["origin","auth_sha1_v4","auth_aes128_md5","auth_aes128_sha1","auth_chain_a"]'
            ],
            [
                'k' => 'custom_obfs_list',
                'v' => '["plain","http_simple","http_post","tls1.2_ticket_auth"]'
            ],
            [
                'k' => 'default_user_method',
                'v' => 'chacha20'
            ],
            [
                'k' => 'default_user_protocol',
                'v' => 'auth_sha1_v4'
            ],
            [
                'k' => 'default_user_obfs',
                'v' => 'tls1.2_ticket_auth'
            ],
            [
                'k' => 'default_user_obfsparam',
                'v' => ''
            ]
        ];
        $this->insert('options', $option, ['after' => 'custom_transfer_repeat']);
    }
}
