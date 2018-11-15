<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Cache\Driver\Redis;

function _token_build($data, $path, $liveTime)
{
    $token = md5($data['username'].time().$path);
    $redis = new Redis();
    $key = 'token_'.$data['username'];   //token:xxx:xxx
    $redis->set($key,$token,$liveTime);
    return $token;
}

function _token_validate($user, $token)
{
    $key =  'token_'.$user;  //键
    $redis = new Redis();
    //校验Token
    if($redis->has($key))
    {
       if($redis->get($key) == $token)
        {
            return true;
        }
    }
    return false;
}
