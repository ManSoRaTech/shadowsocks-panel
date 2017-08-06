<?php

namespace Controller;

use Core\Error;
use Core\Template;
use Helper\Message;
use Helper\Option;
use Model\Node;
use Model\User;
use ReflectionObject;
use function Sodium\add;

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

    private static function generateSsrAddress($node, $user)
    {
        if ($node->custom_method == 1) {
            //if($user->method != '' && $user->method != null)
            $method = $user->method;
            $protocol = $user->protocol;
            $obfs = $user->obfs;
            $obfsparam = $user->obfsparam;
        } else {
            $method = $node->method;
            $protocol = $node->protocol;
            $obfs = $node->obfs;
            $obfsparam = $node->obfsparam;
        }

        $protocol = preg_replace('/_compatible$/', '', $protocol);
        $obfs = preg_replace('/_compatible$/', '', $obfs);

        // generate ssr detail
        $ssrUrl = "{$node->server}:{$user->port}:{$protocol}:{$method}:{$obfs}:" . Template::base64_url_encode($user->sspwd) . '/?obfsparam=' . Template::base64_url_encode($obfsparam) . '&remarks=' . Template::base64_url_encode($node->name) . '&group=' . Template::base64_url_encode(ManSora);
        $ssrUrl = 'ssr://' . Template::base64_url_encode($ssrUrl);

        return $ssrUrl;
    }

    public function getFeeds()
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
        $nodes = Node::getNodeArray();
        $ssr_feeds = '';
        foreach ($nodes as $node) {
            if ($node->type == 1) {
                if ($user->plan == 'VIP' || $user->plan == 'SVIP') {
                    continue;
                }
            }
            $ssr_feeds .= self::generateSsrAddress($node, $user) . PHP_EOL;
        }
        @ob_clean();
        header('Content-Type: text/plain');
        echo base64_encode($ssr_feeds);
    }

    /**
     * @JSON
     */
    public function getConfig()
    {
        $uid = $_GET['uid'];
        $hash = $_GET['hash'];
        $user = User::getUserByUserId($uid);
        if (!$user || (md5(self::getPassword($user)) != $hash)) {
            throw new Error('User or password is not correct.');
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

    public function getSsrConfig()
    {
        $uid = $_GET['uid'];
        $hash = $_GET['hash'];
        $user = User::getUserByUserId($uid);
        if (!$user || (md5(self::getPassword($user)) != $hash)) {
            throw new Error('User or password is not correct.');
        }

        $json = array(
            'expTime' => $user->expireTime,
            'announcement' => '',
            'servers' => array(),
            'server_port' => 0,
            'password' => ''
        );

        if ($user->expireTime > time()) {
            if ($announcement = Option::get('ssr_client_announcement')) {
                $json['announcement'] = $announcement;
            }

            $nodes = Node::getNodeArray();
            for ($i = 0; $i < count($nodes); $i++) {
                $node = $nodes[$i];
                if ($node->type == 1) {
                    if ($user->plan == 'VIP' || $user->plan == 'SVIP') {
                        continue;
                    }
                }
                
                $json['servers'][$i] = array(
                    'remarks' => ($node->type == 1 ? '[VIP] ' : '') . $node->name,
                    'server' => $node->server);
                if ($node->custom_method == 1) {
                    $json['servers'][$i] = array_merge($json['servers'][$i], array(
                        'method' => $user->method,
                        'obfs' => $user->obfs,
                        'obfsparam' => $user->obfsparam,
                        'protocol' => $user->protocol));
                } else {
                    $json['servers'][$i] = array_merge($json['servers'][$i], array(
                        'server' => $node->server,
                        'method' => $node->method,
                        'obfs' => $node->obfs,
                        'obfsparam' => $node->obfsparam,
                        'protocol' => $node->protocol));
                }
            }

            $json['server_port'] = $user->port;
            $json['password'] = $user->sspwd;
        }
        header('Content-type: application/json');
        echo json_encode($json);

        exit();
    }
}