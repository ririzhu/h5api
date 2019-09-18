<?php
namespace app\admin\controller;

use think\App;
use think\Controller;
use think\Request;
class Base extends Controller
{
    public function __construct(App $app = null)
    {
        $request = new Request();
        $allow = array("index","login","logout");
        //echo in_array(request()->action(),$allow);die;
        if(!session("admin") &&  request()->controller(false)!="Index" &&  !in_array(request()->action(),$allow)){
            $this->error('请先登录！',"/admin/index/index");
        }
    }
}