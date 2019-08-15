<?php
namespace app\V1\controller;
use app\V1\controller\Base;
class Index extends Base
{
    public function index()
    {
        return $this->test();
    }
    public function isLogin()
    {
        return "";
    }
}
