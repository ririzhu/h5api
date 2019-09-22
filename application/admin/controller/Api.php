<?php
namespace app\admin\controller;
use think\db;
class Api extends Base
{
    public function changestatus(){
        $data['status'] = input("status");
        $data['id'] = input("id");
        return db::name("api")->update($data);
    }
    public function changegroupstatus(){
        $data['status'] = input("status");
        $data['id'] = input("id");
        return db::name("api_group")->update($data);
    }
    public function changememberstatus(){
        $data['status'] = input("status");
        $data['id'] = input("id");
        return db::name("api_user")->update($data);
    }
}