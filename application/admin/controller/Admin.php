<?php
namespace app\admin\controller;

use think\App;

class Admin extends Base
{
    public function __construct(App $app = null)
    {
        parent::__construct($app);
    }

    public function login(){
        print_r(input());
        if(input("Username")=="Admin" && input("password")=="Brucewoo1979!"){
            session("admin",md5("AdminBrucewoo1979!"));
            $this->success("登录成功","/admin/admin/index");
        }
    }
    public function index(){
        return $this->fetch();
    }
    public function apilist(){
        return $this->fetch();
    }
}