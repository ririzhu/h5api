<?php
defined('DYMall') or exit('Access Invalid!');
class pin_typesCtl extends SystemCtl
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * slodon_获取拼团类别列表
     */
    public function getClassList() {
        $model_tuan_class = M('pin');
        $param = array();
        $tuan_class_list = $model_tuan_class->table('pin,pin_type')->join('right')->on('pin.sld_type = pin_type.id ')->where(array('sld_parent_id'=>0))->field('count( pin.id ) AS c,pin_type.*')->order('sld_sort asc')->group('pin_type.id')->select();
        echo json_encode(array('list'=>$tuan_class_list));die;
    }

    /**
     * slodon_保存添加的拼团类别
     */
    public function saveSldTuanCat() {

        $pin_id = intval($_POST['id']);
        $param = array();
        $param['sld_typename'] = trim($_POST['sld_typename']);
        if(empty($param['sld_typename'])) {
            echo json_encode(array('state'=>255,'msg'=>'拼团名称不能为空'));die;
        }
        $param['sld_sort'] = intval($_POST['sld_sort']);
        $param['sld_status'] = intval($_POST['sld_status']);

        $pin_model = M('pin');

        if(empty($pin_id)) {
            //新增
            if($pin_model->table('pin_type')->insert($param)) {
                H('pin_type',null);
                $this->log('添加拼团分类'.'['.$_POST['sld_typename'].']',null);
                echo json_encode(array('state'=>200,'msg'=>'拼团分类添加成功'));
            }
            else {
                echo json_encode(array('state'=>255,'msg'=>'拼团分类添加成功'));
            }
        }
        else {
            //编辑
            if($pin_model->table('pin_type')->update($param,array('where'=>array('id'=>$pin_id)))) {
                H('pin_type',null);
                $this->log('编辑拼团分类'.'['.$_POST['sld_typename'].']',null);
                echo json_encode(array('state'=>200,'msg'=>'编辑拼团分类成功'));
            }
            else {
                echo json_encode(array('state'=>255,'msg'=>'编辑拼团分类失败'));
            }
        }
    }

    /*ajax修改单字段 参数分类id id  字段名称 key 值 val */
    public function ajax_edit(){
        $result = true;
        $update_array = array();
        $id=$_GET['id'];
        $val=$_GET['val'];
        $model = M('pin')->table('pin_type');
        $where_array = array('id'=>$id);

        switch ($_GET['key']){
            case 'sld_typename':
                $update_array['sld_typename'] = $_GET['sld_typename'];
                break;
            case 'sld_status':
                $update_array['sld_status'] = $_GET['sld_status'] ? $_GET['sld_status'] : $_GET['val'];
                break;
            case 'sld_sort':
                $update_array['sld_sort'] = $_GET['sld_sort'];
                break;
        }
        $result = $model->update($update_array,array('where'=>$where_array));
        if($result) {
            H('pin_type',null);
            echo json_encode(array('state'=>200,'msg'=>'编辑拼团分类成功'));
        }
        else {
            echo json_encode(array('state'=>255,'msg'=>'编辑拼团分类失败'));
        }
    }

    /**
     * 删除拼团类别 参数 多个分类id ids 例如 1,2,3
     */
    public function delSldTuanCat() {

        $ids = trim($_POST['ids']);
        if(empty($ids)) {
            echo json_encode(array('state'=>255,'msg'=>Language::get('参数错误')));die;
        }

        $model_tuan_class = M('pin')->table('pin_type');
        //获得所有下级类别编号
        $ids = explode(',',$ids);
        foreach ($ids as $k=>$v){
            if(!$v ||$v<1){
                unset($ids[$k]);
            }
        }
        if(!$ids ||count($ids)<1){
            echo json_encode(array('state'=>255,'msg'=>Language::get('参数错误')));die;
        }
        $ids = join(',',$ids);
        if($model_tuan_class->where(array('id'=>array('in',$ids)))->delete()) {
            H('pin_type',null);
            $this->log('删除拼团分类'.'[ID:'.$ids.']',null);
            echo json_encode(array('state'=>200,'msg'=>'删除拼团分类成功'));
        }
        else {
            echo json_encode(array('state'=>255,'msg'=>'删除拼团分类失败'));
        }

    }
}