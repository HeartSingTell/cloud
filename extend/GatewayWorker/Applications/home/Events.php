<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\Db;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据 
        Gateway::sendToClient($client_id, "Hello\r\n");
        // 向所有人发送
        //Gateway::sendToAll("$client_id login\r\n");
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} client_id: $client_id msg: '$client_id login' "."\n";
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {
        // 向所有人发送 
        //Gateway::sendToAll("$client_id said $message\r\n");
        $message_data = json_decode($message, true);
        switch ($message_data['type']) 
        {
            case 'pong':
              //心跳

                return;

            //验证登录信息 {"type":"login","user":"xx","pwd":"xx"}
            case 'login':
                //用户登录信息储存
                $user = $message_data['user'];
                $pwd = $message_data['pwd'];

                //数据验证  

                //返回二维数组
                $res = Db::instance('home')->query("SELECT id,password FROM tb_user WHERE username='$user'");  //从xx筛选 where跟条件
                //用户不存在
                if(!isset($res[0]))
                {
                    Gateway::sendToClient($client_id, 0); //登录失败
                    echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} client_id: $client_id msg: 'Username not exist' "."\n";
                    break;
                }
                if(md5($pwd) === $res[0]['password'])
                {
                    $_SESSION['uid'] = $res[0]['id'];
                    $_SESSION['user'] = $user;
                    Db::instance('home')->query("UPDATE tb_user SET device_status=1 WHERE username='$user'");
                    Gateway::sendToClient($client_id, 1);  //登录成功
                    echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} client_id: $client_id msg: 'login success' "."\n";
                    break;
                }
                Gateway::sendToClient($client_id, 0);  //登录失败
                echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} client_id: $client_id msg: 'Password error' "."\n";
                break;


            //数据处理   {"type":"data","humi":"xx","temp":"xx"}
            case 'data':
                //没有登录
                if(!isset($_SESSION['uid']))
                {
                    Gateway::sendToClient($client_id, 0);
                    echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} client_id: $client_id msg: 'Please login' "."\n";
                    break;
                }

                $uid = $_SESSION['uid'];
                $data_humi = $message_data['humi'];
                $data_temp = $message_data['temp'];
                //设置北京时间
                date_default_timezone_set('PRC');  
                $update_time = date('Y-m-d H:i:s');

                $res_data = Db::instance('home')->query("SELECT id FROM tb_data WHERE uid='$uid'");

                if(!isset($res_data[0]))
                {
                    Db::instance('home')->query("INSERT INTO tb_data(humi,temp,update_time,uid) VALUES ('$data_humi','$data_temp','$update_time','$uid')");
                }

                //更新数据
                Db::instance('home')->query("UPDATE tb_data SET humi='$data_humi',temp='$data_temp',update_time='$update_time' WHERE uid='$uid'");
                break;

            default:
              # code...
                break;
        }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
       // 向所有人发送 
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} client_id: $client_id msg: 'logout' "."\n";
       if(isset($_SESSION['user']))
       {
            $user = $_SESSION['user'];
            Db::instance('home')->query("UPDATE tb_user SET device_status=0 WHERE username='$user'");
       }
   }
}
