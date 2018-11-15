<?php 

namespace app\api\controller;
use think\Request;
use think\Controller;


class User extends Controller
{
    /**
     * 根据post请求 验证用户信息 验证成功返回对应token
     * _token_build : token生成 利用redis缓存 过期时间 24 小时
     * code:1 请求出错 返回错误信息msg   code：0 请求成功 返回token
     */
	public function login()
	{
		//获取post请求信息
        $request = Request::instance();
		$user = $request->post('username');
		$pwd = $request->post('password');
		$path = $request->path();

		//账户验证
		$data = \app\api\model\User::get(['username' => $user]);
		if(empty($data))
		{
			return json(['msg' => '用户不存在，请重新尝试登录', 'code' => 1]);
		}

		if(md5($pwd) != $data['password'])
		{
			return json(['msg' => '密码错误，请重新尝试登录', 'code' => 1]);
		}

		$token = _token_build($data, $path, 86400);
		return json(['token' => $token,'code' => 0]);
	}

}