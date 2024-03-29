<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Env;

// +----------------------------------------------------------------------
// | Swoole设置 php think swoole命令行下有效
// +----------------------------------------------------------------------
return [
    // 扩展自身配置
    'host'                  => '0.0.0.0', // 监听地址
    'port'                  => 9504, // 监听端口
    'app_path'              => '', // 应用地址 如果开启了 'daemonize'=>true 必须设置（使用绝对路径）

    // 可以支持swoole的所有配置参数
    'mode'                  => SWOOLE_PROCESS,
    'sock_type'             => SWOOLE_SOCK_TCP,
    'pid_file'              => Env::get('runtime_path') . 'swoole.pid',
    'log_file'              => Env::get('runtime_path') . 'swoole.log',
    'document_root'         => Env::get('root_path') . 'public',
    'enable_static_handler' => true,
    'onRequest'    => function ($request, $response) {
        $response->end("<h1>Hello Swoole. #" . rand(1000, 9999) . "</h1>");
    },
    'onClose'      => function ($ser, $fd) {
        echo "client {$fd} closed\n";
    },
];
