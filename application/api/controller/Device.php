<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/2
 * Time: 12:02
 */

namespace app\api\controller;
use think\Controller;
use think\Request;
use app\api\model\User;
use app\api\model\Data;

class Device extends Controller
{
    /**
     * 获取用户设备数据的接口
     * _token_validate : 公用函数 token验证器
     * 根据get请求 验证token信息 验证成功返回数据
     * code:1 请求出错 返回错误信息msg   code：0 请求成功 返回数据
     */
    public function getData()
    {
        //获取请求头token信息
        $request = Request::instance();
        $token = $request->get('token');  //值
        $user = $request->get('username');
        
        if(_token_validate($user,$token))
        {
            $res = User::get(['username' => $user]);
            $device_data = Data::get(['uid' => $res['id']]);
            return json(['data' => $device_data, 'code' => 0]);
        }
        return json(['msg' => '登录信息错误或失效', 'code' => 1]);
    }

    /**
     * 获取用户设备状态的接口
     * _token_validate : 公用函数 token验证器
     * 根据get请求 验证token信息 验证成功返回状态
     * code:1 请求出错 返回错误信息msg   code：0 请求成功 返回状态
     */
    public function getDeviceStatus()
    {
        //获取请求头token信息
        $request = Request::instance();
        $token = $request->get('token');  //值
        $user = $request->get('username');

        if(_token_validate($user,$token))
        {
            $res = User::get(['username' => $user]);
            return json(['device_status' => $res['device_status'], 'code' => 0]);
        }
        return json(['msg' => '登录信息错误或失效', 'code' => 1]);
    }
}