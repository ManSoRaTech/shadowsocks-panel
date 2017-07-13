<?php
/**
 * Project: shadowsocks-panel
 * Author: Sendya <18x@loacg.com>
 * Time: 2016/05/02 2:42
 */


namespace Controller;
use Core\Model;
use Core\Template;
use Helper\Option;
use Model\User;
use Model\Order as MOrder;

use Core\Database as DB;

/**
 * 订单系统
 * Class Order
 * @Authorization
 * @package Controller
 */
class Order
{
    public function index()
    {
        Template::putContext('user', User::getCurrent());
        Template::setView('panel/order');
    }

    public function lists()
    {
        Template::putContext('user', User::getCurrent());
        Template::setView('panel/order_lists');
    }

    /**
     * 创建订单
     *
     */
    public function create()
    {
        @include 'Order.config.php';

        $user = User::getCurrent();
        $data['custom_plan_name'] = json_decode(Option::get('custom_plan_name'), true);
        $data['custom_transfer_level'] = json_decode(Option::get('custom_transfer_level'), true);
        $plan = strtoupper($_GET['plan']);
        $money = 0;
        switch ($plan) {
            case 'B':
                $money = 8;
                break;
            case 'C':
                $money = 13;
                break;
            case 'D':
                $money = 20;
                break;
            case 'VIP':
                $money = 32;
                break;
                
            case 'B1':
                $money = 22.5;
                break;
            case 'C1':
                $money = 35.5;
                break;
            case 'D1':
                $money = 54;
                break;
            case 'VIP1':
                $money = 86;
                break;
                	
            case 'B2':
                $money = 85;
                break;
            case 'C2':
                $money = 133;
                break;
            case 'D2':
                $money = 197;
                break;
            case 'VIP2':
                $money = 288;
                break;
        }
        /*
        if(count(MOrder::getByUserId($user->uid)) > 0) {
            header("Location: /order/lists");
            exit();
        }
        */
        $order = new MOrder();
        $order->uid = $user->uid;
        $order->createTime = time();
        $order->money = $money;
        $order->plan = $plan;
        $order->status = 0;
        $order->type = 0; // 类型： 0 - 购买套餐 1 - 账户金额充值 2 - 购买卡号
        $remark = $order->type==0?"购买套餐 ": $order->type==1?"金额充值 ":"购买卡号 ";
        $remark.= $plan . ', ' . $money . '元';
        $order->remark = $remark;
        
        //$order->sig = $api_sig;
        $order->save(Model::SAVE_INSERT);
        Template::putContext("order_id", $order->id);
        Template::putContext('transfer', $data['custom_transfer_level'][$plan]);
        Template::putContext('plan', $plan);
        Template::putContext('plan_name', $data['custom_plan_name'][$plan]);
        Template::putContext('money', $money);
        Template::putContext('user', $user);

        //生成Payssion支付api_sig
        $api_key = PAYSSION_API_KEY;
        $secret_key = PAYSSION_SECRET_KEY;
        $pm_id = PAYSSION_PM_ID;
        $return_url = PAYSSION_RETURN_URL;
        $msg = implode("|", array($api_key, $pm_id, $money, 'CNY', $order->id, $secret_key));
        $api_sig = md5($msg);
        ////////////////////////
        Template::putContext('api_key', $api_key);
        Template::putContext('pm_id', $pm_id);
        Template::putContext('return_url', $return_url);
        Template::putContext('api_sig', $api_sig);
        
        Template::setView('panel/order_create');
    }

    /**
     * 更新订单
     */
    public function update()
    {

    }

    /**
     * 删除订单
     */
    public function delete()
    {

    }

    /**
     * 订单API接收回调 消息通知
     *
     */
    public function notice()
    {
        
    }

}