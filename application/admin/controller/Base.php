<?php
namespace app\admin\controller;

use app\admin\model\Token;
use think\App;
use think\Controller;
use think\Exception;
use think\Request;
use think\db;
class Base extends Controller
{
    public function __construct(App $app = null)
    {
        parent::__construct();
        $request = new Request();
        $allow = array("index","login","logout");
        //echo in_array(request()->action(),$allow);die;
        /*if(!session("admin") &&  request()->controller(false)!="Index" &&  !in_array(request()->action(),$allow)){
            $this->error('请先登录！',"/admin/index/index");
        }*/
    }
    public function getAllcontroller(){
        //if(empty($module)) return null;
        $module_path = '../application/v1/controller/';  //控制器路径
        //if(!is_dir($module_path)) return null;
        $module_path .= '/*.php';
        $ary_files = glob($module_path);
        //$files = array();
        foreach ($ary_files as $file) {
            if (is_dir($file)) {
                continue;
            }else {
                $filesname = basename($file, '.php');
                if(count(db::name("api_module")->where("modulename='$filesname'")->select())==0){
                    $data['modulename']=$filesname;
                    $data['project']="horizou H5";
                    db::name("api_module")->insert($data);
                }
            }
        }

    }
    public function getclasses()
    {
        $a = $this->GetAllaction();
        foreach($a as $k=>$v){
            if(count(db::name("api")->where("name='".addslashes($a[$k]['name'])."'")->select())==0){
                $data['name'] = addslashes($v['name']);
                $modulename=explode("/",$data['name'])[0];
                $data['moduleid']=(db::name("api_module")->field("id")->where("modulename='$modulename'")->find())['id'];
                $data['status'] = 1;
                $data['version'] = 'v1';
                $data['project'] = "horizou H5";
                try {
                    $res=db::name("api")->insert($data);
                }catch(Exception $e){
                    echo $e->getMessage();
                }

            }
        }
    }
    public function GetAllaction(){
        $modules = array('v1');  //模块名称
        $i = 0;
        foreach ($modules as $module) {
            $all_controller = $this->getController($module);
            foreach ($all_controller as $controller) {
                $controller_name = $controller;
                if(strstr($controller_name,"Base")){
                    continue;
                }
                $all_action = $this->getAction($module, $controller_name);
                foreach ($all_action as $action) {
                    $data[$i] = array(
                        'name' => $controller . "/" . $action,
                        'status' => 1
                    );
                    $i++;
                }
            }
        }
        $auth=$data;
        return $auth;
    }

    //获取所有控制器名称
    public function getController($module){
        if(empty($module)) return null;
        $module_path = '../application/' . $module . '/controller/';  //控制器路径
        //if(!is_dir($module_path)) return null;
        $module_path .= '/*.php';
        $ary_files = glob($module_path);
        $files = array();
        foreach ($ary_files as $file) {
            if (is_dir($file)) {
                continue;
            }else {
                $files[] = basename($file, '.php');
            }
        }
        return $files;
    }
    //获取所有方法名称
    public function getAction($module, $controller){
        if(empty($controller)) return null;
        $content = file_get_contents( '../application/'.$module.'/Controller/'.$controller.'.php');
        preg_match_all("/.*?public.*?function(.*?)\(.*?\)/i", $content, $matches);
        $functions = $matches[1];
        array_push($functions,'*');
        //排除部分方法
        $inherents_functions = array('_initialize','__construct','getActionName','isAjax','display','show','fetch','buildHtml','assign','__set','get','__get','__isset','__call','error','success','ajaxReturn','redirect','__destruct','_empty');
        foreach ($functions as $func){
            $func = trim($func);
            if(!in_array($func, $inherents_functions)){
                $customer_functions[] = $func;
            }
        }
        //print_r($functions);
        return $customer_functions;
    }
    public function createToken(){
        $id = 1;
        $token=new Token();
        return $str = $token->signToken($id,60); //生成一个不会重复的字符串
    }

    public  function checkToken(){
        $token = new Token();
        $tokens = request()->header('Authorization');
        if($token->checkToken($tokens)){
            return true;
        }else{
            return false;
        };
    }
    public function addmember(){

    }
}