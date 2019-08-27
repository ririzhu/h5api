<?php

namespace app\V1\controller;

use app\V1\controller\Base;
use think\db;
use think\captcha\Captcha;

class Index extends Base
{
    public function index()
    {
        $this->isLogin();
        return $this->test();
    }

    public function isLogin()
    {
        $list = Db::table('bbc_goods')->order('gid', 'desc')->select();
        return $list;
    }

    /**
     * 获取图片验证码
     *
     **/
    public function picCode()
    {
        if (!input("id")) {
            $id = microtime(true);
        } else {
            $id = input("id");
        }
        $captcha = new Captcha();
        return $captcha->entry($id);
    }

    /**
     * 验证图片验证码
     *
     **/
    public function veriPicCode()
    {
        if (!input("id") || !input("code") || captcha_check(input("code"), input("id"))) {
            $data['error_code'] = 10006;
            $data['message'] = "验证码错误";
            return json_encode($data, true);
        } else {
            $data['error_code'] = 200;
            $data['message'] = "验证码正确";
            return json_encode($data, true);
        }
    }
}
