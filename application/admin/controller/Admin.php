<?php
namespace app\admin\controller;

use think\App;
use think\db;
class Admin extends Base
{
    public function __construct(App $app = null)
    {
        parent::__construct($app);
    }

    public function login(){
        if(input("Username")=="Admin" && input("password")=="Brucewoo1979!"){
            session("admin",md5("AdminBrucewoo1979!"));
            $this->success("登录成功","/admin/admin/index");
        }
    }
    public function index(){
        return $this->fetch();
    }
    public function apilist(){
        $p = input("p",0);
        $param['project'] = input("project","horizou H5");
        $param['version'] = input("version","v1");
        $list = db::name("api")->where($param)->paginate(20)->each(function($item, $key) {
            //这句一定要写，不然对象为空
            $a = explode("/",$item['name']);
            return $item;
        });
        $this->assign("list",$list);
        $page = $list->render();
// 模板变量赋值
        $this->assign('page', $page);
// 渲染模板输出
        return $this->fetch();
    }

    /**
     * 对象 转 数组
     *
     * @param object $obj 对象
     * @return array
     */
    function object_to_array($array)
    {

        if(is_object($array)) {
                $array = (array)$array;
            } if(is_array($array)) {
                foreach($array as $key=>$value) {
                    $array[$key] = $this->object_to_array($value);
                }
            }
            return $array['*simple']['*items']['*items'];

    }
    /**
     * 用户列表
     */
    public function memberlist(){
        $p = input("p",0);
        $param=array();
        if(input("groupid")){
            $param['groupid']=input("groupid");
        }
        //$param['project'] = input("project","horizou H5");
        //$param['version'] = input("version","v1");
        $list = db::name("api_user a")->join("bbc_api_group g","a.groupid=g.id")->field("a.name,a.email,a.id,a.status,a.token,a.starttime,a.endtime,g.groupname")->where($param)->paginate(20);
        $this->assign("list",$list);
        $page = $list->render();
// 模板变量赋值
        $this->assign('page', $page);
// 渲染模板输出
        return $this->fetch();
    }
    public function addmember(){
        $this->assign("grouplist",$this->getGrouplist());
       return $this->fetch();
    }
    public function getGrouplist(){
        return db::name("api_group")->select();
    }
    public function grouplist(){
        $list = db::name("api_group")->paginate(20)->each(function($item, $key) {
            //这句一定要写，不然对象为空
            $allowlist = $item['allowlist'];
            if($allowlist==""){
                $item['allowlist'] = "所有";
            }else{
                $allist = explode(",",$allowlist);
                $str = "";
                foreach($allist as $k=>$v){
                    $data = db::name("api_module")->where("id=$v")->find();
                    $str .= $data['project']."/".$data['modulename'].",";
                }
                $item['allowlist']=$str;
            }
            return $item;
        });
    $this->assign("grouplist",$list);
        $page = $list->render();
// 模板变量赋值
        $this->assign('page', $page);
    return $this->fetch();
    }
    public function savemember(){
        $id=input("id","");
        $data['name']=input("name");
        $data['groupid']=input("groupid");
        $data['email']=input("email");
        $data['status']=1;
        $data['starttime']=strtotime(input("starttime"));
        $data['endtime']=strtotime(input("endtime"));
        if($id==""){
            //insert
            $id=db::name("api_user")->insertGetId($data);
            $data['token']=$this->createToken($id);
        }
        return db::name("api_user")->where("id=".$id)->update($data);
    }
    public function addgroup(){
        $modulelist = db::name("api_module")->select();
        $this->assign("list",$modulelist);
        $this->assign("projectlist",$this->getProjects());
        return $this->fetch();
    }
    public function  getProjects(){
        return db::name("api_module")->field("project")->group("project")->select();
    }
    public function savegroup(){
        $id=input("id","");
        $data['groupname']=input("name");
        $data['status']=1;
        $data['allowlist']=input("allowlist");
        if($id==""){
            //insert
            $res=db::name("api_group")->insert($data);
           return $res;
        }else {
            return db::name("api_group")->where("id=" . $id)->update($data);
        }
    }
    public function editgroup(){
        $id=input("id");
        $groupinfo =db::name("api_group")->where("id=$id")->find();
        $modulelist = db::name("api_module")->select();
        $this->assign("list",$modulelist);
        $this->assign("projectlist",$this->getProjects());
        $this->assign("groupinfo",$groupinfo);
        return $this->fetch("addgroup");
    }
    public function editmember(){
        $id=input("id");
        $member =db::name("api_user")->where("id=$id")->find();
        $member['starttime']=date('Y-m-d H:i:s',$member['starttime']);
        $member['endtime']=date('Y-m-d H:i:s',$member['endtime']);
        $this->assign("member",$member);
        $this->assign("grouplist",$this->getGrouplist());
        return $this->fetch("addmember");
    }
}