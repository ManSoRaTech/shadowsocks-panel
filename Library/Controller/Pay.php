<?php

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
class Pay
{
    public function index()
    {
    	echo "<p style='color:red;'>参数缺失</p>";
    }
    
    public function notify()
    {
    	echo "支付异步回调接口";
    }
    
    public function back()
    {
    	$pm_id = $_GET['pm_id'];
    	$state = $_GET['state'];
    	$amount = $_GET['amount'];
    	$currency = $_GET['currency'];
    	$order_id = $_GET['order_id'];
    	$notiy_sig = $_GET['notify_sig'];
    	
    	$sig = DB::getInstance()->prepare('SELECT uid,plan FROM `orders` WHERE id = :id');
    	$sig->bindValue(':id', $order_id);
    	$sig->execute();
    	$tmp = $sig->fetch();
    	$uid = $tmp[0];
    	$plan = $tmp[1];
    	
    	$msg = implode("|", array('3bf0014c6357ac0d', $pm_id, $amount, $currency,$order_id, $state, '40d18cf6c21b18fcc21e194d9b373459'));
    	$math_sig = md5($msg);
    	//校验支付凭证
    	if($math_sig == $notiy_sig)
    	{
    		$use = DB::getInstance()->prepare('SELECT count(*) FROM `orders` WHERE id = :id AND `status` = 0');
    		$use->bindValue(':id', $order_id);
    		$use->execute();
    		$is_use = $use->fetch(DB::FETCH_NUM)[0];
    		//检测订单是否曾交易完成
    		//交易已完成
    		if($is_use == 0)
    		{
    			echo "<p style='color:red;'>此订单已成功支付，请勿重复提交！</p>";
    		}
    		//交易未完成
    		else if($is_use == 1)
    		{
    			if(isset($order_id))
    			{
    				//修改订单状态
    				$up = DB::getInstance()->prepare("UPDATE orders SET `status` = 1 WHERE id = :id");
    				$up->bindValue(':id', $order_id);
    				$up->execute();
    				//根据套餐计算流量
    				$GB = 1073741824;
    				$EXT = 30*24*3600;
    				//查询用户当前套餐
    				$nowplan = DB::getInstance()->prepare('SELECT plan,expireTime,flow_up,flow_down,transfer FROM `member` WHERE uid = :uid');
    				$nowplan->bindValue(':uid', $uid);
    				$nowplan->execute();
    				$tmp = $nowplan->fetch();
    				$np = $tmp[0];
    				$expire = $tmp[1];
    				$fup = (int)$tmp[2];
    				$fdown = (int)$tmp[3];
    				$tran = (int)$tmp[4];
    				//判定套餐起始时间
    				if($plan == $np)		//套餐相同
    				{
    					//检测账户是否超额或过期
    					//是
    					if($tran < $fup+$fdown || $expire < time())
    					{
    						$stime = time();
    					}
    					//否
    					else 
    					{
    						$stime = $expire;
    					}
    				}
    				else
    				{
    					$stime = time();
    				}
    				
    				switch ($plan) {
    					case 'B':
    						$GB = $GB * 15;
    						$EXT = $stime + $EXT;
    						break;
    					case 'C':
    						$GB = $GB * 25;
    						$EXT = $stime + $EXT;
    						break;
    					case 'D':
    						$GB = $GB * 55;
    						$EXT = $stime + $EXT;
    						break;
    					case 'VIP':
    						$GB = $GB * 120;
    						$EXT = $stime + $EXT;
    						break;
    						
    					case 'B1':
    						$GB = $GB * 16;
    						$EXT = $stime + $EXT * 3;
    						break;
    					case 'C1':
    						$GB = $GB * 27;
    						$EXT = $stime + $EXT * 3;
    						break;
    					case 'D1':
    						$GB = $GB * 60;
    						$EXT = $stime + $EXT * 3;
    						break;
    					case 'VIP1':
    						$GB = $GB * 135;
    						$EXT = $stime + $EXT * 3;
    						break;
    						
    					case 'B2':
    						$GB = $GB * 18;
    						$EXT = $stime + $EXT * 12;
    						break;
    					case 'C2':
    						$GB = $GB * 30;
    						$EXT = $stime + $EXT * 12;
    						break;
    					case 'D2':
    						$GB = $GB * 65;
    						$EXT = $stime + $EXT * 12;
    						break;
    					case 'VIP2':
    						$GB = $GB * 145;
    						$EXT = $stime + $EXT * 12;
    						break;
    				}
    				$mb = DB::getInstance()->prepare("UPDATE member SET `flow_up` = 0,`flow_down` = 0,`transfer` = :tra,`plan` = :plan,`expireTime` = :ext,`enable` = 1 WHERE uid = :uid");
    				$mb->bindValue(':tra', $GB);
    				$mb->bindValue(':ext', $EXT);
    				$mb->bindValue(':plan', $plan);
    				$mb->bindValue(':uid', $uid);
    				$mb->execute();
    		
    				echo "支付成功，<a href='https://ss.ime.moe/member'>点我返回查看流量面板</a>";
    			}
    			else
    			{
    				echo "<p style='color:red;'>参数缺失</p>";
    			}
    		}
    	}
    	else 
    	{
    		echo "<p style='color:red;'>支付凭证校验失败</p>";
    	}
    	
    }
}