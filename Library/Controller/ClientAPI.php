<?php

namespace Controller;

use Core\Error;
use Helper\Message;
use Helper\Option;
use Model\Node;
use Model\User;
use ReflectionObject;

class ClientAPI
{
    public function getClient()
    {
        $user = User::getCurrent();
        if (!$user) {
            Message::show('请先登录');
        }
        @ob_clean();
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header('Content-disposition: attachment; filename=Shadowsocks-Mini.exe');
        readfile(DATA_PATH . 'Shadowsocks.exe');
        $uid = $user->uid;
        $hash = md5(self::getPassword($user));
        $url = self::convertString(BASE_URL . "ClientAPI/GetConfig?uid={$uid}&hash={$hash}\0");
        $url = self::padString($url);
        echo $url;
    }

    public static function getPassword(User $user)
    {
        $reflection = new ReflectionObject($user);
        $password = $reflection->getProperty('password');
        $password->setAccessible(true);
        return $password->getValue($user);
    }

    public static function convertString($str)
    {
        $num = strlen($str);
        for ($i = 0; $i < $num; $i++) {
            $str{$i} = chr(-ord($str{$i}));
        }
        return $str;
    }

    public static function padString($str)
    {
        $num = 128 - strlen($str);
        for ($i = 0; $i < $num; $i++) {
            $str .= chr(rand(-128, 127));
        }
        return $str;
    }

    /**
     * @JSON
     */
    public function getConfig()
    {
        $uid = $_GET['uid'];
        $hash = $_GET['hash'];
        $user = User::getUserByUserId($uid);
        if (!$user) {
            throw new Error('User not exists');
        }
        if (md5(self::getPassword($user)) != $hash) {
            throw new Error('Password error');
        }
        $json = array(
            'programVersion' => Option::get('ssclient'),
            'servers' => array(),
            'panelUrl' => BASE_URL . 'Member',
            'versionCode' => Option::get('ssclientnode'),
        );
        if ($announcement = Option::get('ssclientann')) {
            $json['announcement'] = $announcement;
        }
        if ($user->expireTime < time() + 86400 * 3) {
            $json['expTime'] = date('Y-m-d H:i:s', $user->expireTime);
        }
        $nodes = Node::getNodeArray();
        foreach ($nodes as $node) {
            if ($node->type == 1) {
                if ($user->plan == 'VIP' || $user->plan == 'SVIP') {
                    continue;
                }
            }
            $json['servers'][] = array(
                'server' => $node->server,
                'server_port' => $user->port,
                'password' => $user->sspwd,
                'method' => $node->method,
                'remarks' => ($node->type == 1 ? '[VIP] ' : '') . $node->name,
                'auth' => false,
            );
        }
        header('Content-type: application/json');
        echo json_encode($json);
        exit();
    }
}
