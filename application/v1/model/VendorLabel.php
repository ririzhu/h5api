<?php
namespace app\v1\model;

use think\Model;

class VendorLabel extends Model
{
    const EXPORT_SIZE = 5000;
    public function __construct() {
        parent::__construct ();
        //Language::read('vendor_label');
    }

    /**
     * slodon_获取商品标签列表
     */
    public function getGoodsLabelList() {
        $model_vendor_label = new VendorLabel();

        if($_GET['all']!=1) {
            /**
             * 查询条件
             */
            $search = array();
            //分页检索条件(如果传值 按照传的值 否则默认10页)
            $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
            $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);
            $where = array();
            if ($_GET['search_label_name'] != '') {
                $search['search_label_name'] = $_GET['search_label_name'];
                $where['label_name'] = array('like', '%' . trim($_GET['search_label_name']) . '%');
            }
        }else{
            $pageSize = 1000;
        }


        $vendor_label_list = $model_vendor_label->getGoodsLabelList($where, '*', $pageSize);

        foreach ($vendor_label_list as $key=>$value){
            $vendor_label_list[$key]['create_time']=date('Y-m-d H:i:s',$value['create_time']);
        }

        echo json_encode(array('list' => $vendor_label_list, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($model_vendor_label->gettotalnum())),'searchlist'=>$search));
    }



    /**
     * slodon_删除商品标签
     */
    public function delGoodsLabel() {
        $label_id = $_POST['id'];
        foreach ($label_id as $value) {
            if ( !is_numeric($value)) {
                echo json_encode(array('state'=>255,'msg'=>lang('操作失败')));die;
            }
        }

        if(!is_array($label_id)){
            $label_id=array($label_id);
        }

        $condition=array();
        $condition['id']=array('in',$label_id);

        $label_list=$this->getGoodsLabelList($condition);
        $result=$this->delGoodsLabel(array('id' => array('in', $label_id)));

        if($result){
            foreach ($label_list as $key=>$value){
                $path=BASE_UPLOAD_PATH. '/' . ATTACH_COMMON . '/' . $value['img'];
                if(!empty($value['img'])){
                    delete_file($path);
                }
            }
            echo json_encode(array('state'=>200,'msg'=>lang('操作成功')));
        }else{
            echo json_encode(array('state'=>200,'msg'=>lang('操作失败')));
        }


    }





    /**
     * slodon_商品标签修改
     */
    public function editGoodsLabel() {
        $lang	= Language::getLangContent();

        if(empty($_POST['id'])) {
            echo json_encode(array('state'=>255,'msg'=>$lang['参数错误']));die;
        }

        /**
         * 规格模型
         */
        $vendor_label = Model('vendor_label');

        /**
         * 编辑保存
         */
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$_POST["label_name"], "require"=>"true", "message"=>$lang['label_add_name_no_null'])
        );
        $error = $obj_validate->validate();
        if ($error != '') {
            echo json_encode(array('state'=>255,'msg'=>str_replace("<br/>"," ",$error)));die;
        } else {


            $update=array();
            if (!empty($_FILES['pic']['name'])) {//上传图片
                $upload	= new UploadFile();
                $upload->set('default_dir',ATTACH_COMMON);
                $result = $upload->upfile('pic');
                if(!$result){
                    showMsg($upload->error);
                }

                $update['img']=$upload->file_name;
            }


            $update['label_name']=$_POST['label_name'];
            $update['label_desc']=$_POST['label_desc'];


            $condition=array();
            $condition['id']=$_POST['id'];

            $return=$vendor_label->editGoodsLabel($update,$condition);

            //更新商品标签表

            if ($return) {
                echo json_encode(array('state'=>200,'msg'=>$lang['保存成功']));die;
            } else {
                echo json_encode(array('state'=>255,'msg'=>$lang['保存成功']));die;
            }
        }

    }



    /**
     * slodon_商品标签添加
     */
    public function addGoodsLabel() {
        $lang	= Language::getLangContent();


        /**
         * 规格模型
         */
        $vendor_label = Model('vendor_label');

        /**
         * 编辑保存
         */
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$_POST["label_name"], "require"=>"true", "message"=>$lang['label_add_name_no_null'])
        );
        $error = $obj_validate->validate();
        if ($error != '') {
            echo json_encode(array('state'=>255,'msg'=>str_replace("<br/>"," ",$error)));die;
        } else {

            $insert=array();
            if (!empty($_FILES['pic']['name'])) {//上传图片
                $upload	= new UploadFile();
                $upload->set('default_dir',ATTACH_COMMON);
                $result = $upload->upfile('pic');
                if(!$result){
                    showMsg($upload->error);
                }

                $insert['img']=$upload->file_name;

            }


            $insert['label_name']=$_POST['label_name'];
            $insert['label_desc']=$_POST['label_desc'];
            $insert['create_time']=time();


            $return=$vendor_label->addGoodsLabel($insert);

            //更新商品标签表

            if ($return) {
                echo json_encode(array('state'=>200,'msg'=>$lang['保存成功']));die;
            } else {
                echo json_encode(array('state'=>255,'msg'=>$lang['保存失败']));die;
            }
        }

    }
}