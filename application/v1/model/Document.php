<?php
namespace app\v1\model;

use think\Model;
use think\db;
class Document extends Model
{
    /**
     * 查询所有系统文章
     */
    public function getList(){
        return Db::name("document")->select();
    }
    /**
     * 根据编号查询一条
     *
     * @param unknown_type $id
     */
    public function getOneById($id){
        return Db::name("document")->where("doc_id=$id")->find();
    }
    /**
     * 根据标识码查询一条
     *
     * @param unknown_type $id
     */
    public function getOneByCode($code){
       return Db::name("document")->where("doc_code='$code'")->find();
    }

}