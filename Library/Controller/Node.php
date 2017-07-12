<?php
/**
 * SS-Panel
 * A simple Shadowsocks management system
 * Author: Sendya <18x@loacg.com>
 */

namespace Controller;


use Core\Template;
use Model\User;
use Model\Node as MNode;

/**
 * Class Node
 * @Authorization
 * @package Controller
 */
class Node
{
    public function Index()
    {
        $data['user'] = User::getCurrent();
        $data['nodes'] = MNode::getNodeArray(0);
        $data['nodeVip'] = MNode::getNodeArray(1);

        Template::setContext($data);
        Template::setView("panel/node");
    }

    /**
     * @JSON
     * @return array
     */
    public function getNodeInfo()
    {
        $id = trim($_REQUEST['id']);
        $result = array('error' => -1, 'message' => 'Request failed');
        $user = User::getUserByUserId(User::getCurrent()->uid);
        $node = MNode::getNodeById($id);
        if ($node->custom_method == 1) {
            if ($user->protocol != 'origin' || $user->obfs != 'plain')
                return array('error' => -1, 'message' => 'SSR字段已设置，无法导出SS节点信息！');
            else
                $method = $user->method;
        } else
            $method = $node->method;

        // generate ss detail
        $ssurl = $method . ":" . $user->sspwd . "@" . $node->server . ":" . $user->port;
        $ssurl = "ss://" . base64_encode($ssurl);
        $ssjsonAry = array("server" => $node->server, "server_port" => $user->port, "password" => $user->sspwd, "timeout" => 600, "method" => $method, "remarks" => $node->name);
        $ssjson = json_encode($ssjsonAry, JSON_PRETTY_PRINT);
        $info = array("ssurl" => $ssurl, "ssjson" => $ssjson);

        if (self::verifyPlan($user->plan, $node->type)) {
            $result = array('error' => 0, 'message' => '获取成功', 'info' => $info, 'node' => $node);
        } else {
            $result = array('error' => -1, 'message' => '你不是 VIP, 无法使用高级节点！');
        }
        return $result;
    }

    public function getSsrInfo()
    {
        $id = trim($_REQUEST['id']);
        $result = array('error' => -1, 'message' => 'Request failed');
        $user = User::getUserByUserId(User::getCurrent()->uid);
        $node = MNode::getNodeById($id);

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

        // generate ssr detail
        $ssurl = $node->server . ":" . $user->port . ":" . $protocol . ":" . $method . ":" . $obfs . ":" . Template::base64_url_encode($user->sspwd) . "/?obfsparam=" . Template::base64_url_encode($obfsparam) . "&remarks=" . Template::base64_url_encode($node->name) . "&group=" . Template::base64_url_encode(ManSora);
        $ssurl = "ssr://" . Template::base64_url_encode($ssurl);
        $ssjsonAry = array("server" => $node->server, "server_port" => $user->port, "password" => $user->sspwd, "timeout" => 600, "method" => $method, "protocol" => $protocol, "obfs" => $obfs, "obfsparam" => $obfsparam, "group" => "ManSora", "remarks" => $node->name);
        $ssjson = json_encode($ssjsonAry, JSON_PRETTY_PRINT);

        $info = array("ssurl" => $ssurl, "ssjson" => $ssjson);
        if (self::verifyPlan($user->plan, $node->type)) {
            $result = array('error' => 0, 'message' => '获取成功', 'info' => $info, 'node' => $node);
        } else {
            $result = array('error' => -1, 'message' => '你不是 VIP, 无法使用高级节点！');
        }
        return $result;
    }

    private static function verifyPlan($plan, $nodeType)
    {
        if ($nodeType == 1) {
            if ($plan == 'VIP' || $plan == 'SVIP') {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

}
