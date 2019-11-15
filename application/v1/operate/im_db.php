<?php
/**
 * 聊天_新版
 *
 */
defined('DYMall') or exit('Access Invalid!');
class im_db {


    private $con = '';
    private $service_name = '';
    private $service_dbuser = '';
    private $service_dbpwd = '';
    private $service_dbname = '';

    public function __construct(){

        $data_info = C('db');
        $this -> service_name = $data_info[1]['dbhost'];
        $this -> service_dbuser = $data_info[1]['dbuser'];
        $this -> service_dbpwd = $data_info[1]['dbpwd'];
        $this -> service_dbname = C('service_dbname');
        //连接数据库
        $con = mysqli_connect($this -> service_name,$this -> service_dbuser,$this -> service_dbpwd,$this -> service_dbname);
        $this -> con = $con;
        mysqli_set_charset($this -> con, "utf8");
    }

    //关闭数据库连接
    public function close_db(){
        mysqli_close($this -> con);
    }

    //执行sql
    public function query_db($sql){

       $res = mysqli_query($this -> con,$sql);
       return $res;
    }

}