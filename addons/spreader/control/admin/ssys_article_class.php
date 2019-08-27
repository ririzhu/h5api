<?php
/**
 * 文章分类
 *
 * 
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_article_classCtl extends SystemCtl{
	public function __construct(){
		parent::__construct();
		Language::read('article_class','spreader');
	}

	/**
	 * slodon_文章分类列表
	 */
	public function getArticleCat(){
		$lang	= Language::getLangContent();
		$model_class = M('ssys_article_class','spreader');
		//删除
//		if (chksubmit()){
//			if (!empty($_POST['check_ac_id'])){
//				if (is_array($_POST['check_ac_id'])){
//					$del_array = $model_class->getChildClass($_POST['check_ac_id']);
//					if (is_array($del_array)){
//						foreach ($del_array as $k => $v){
//							$model_class->del($v['acid']);
//						}
//					}
//				}
//				$this->log(l('bbc_del,article_class_index_class'),1);
//				showMsg($lang['article_class_index_del_succ']);
//			}else {
//				showMsg($lang['article_class_index_choose']);
//			}
//		}
		/**
		 * 父ID
		 */
		$parent_id = $_GET['ac_parent_id']?intval($_GET['ac_parent_id']):0;
		/**
		 * 列表
		 */
		$tmp_list = $model_class->getTreeClassList(2);

        $data = array();
        if (is_array($tmp_list)){
            foreach ($tmp_list as $k => $v){
                $new_data = array();
                if($v['ac_parent_id'] == 0){
                    $new_data['key'] = $v['acid'];
                    $new_data['ac_name'] = $v['ac_name'];
                    $new_data['ac_sort'] = $v['ac_sort'];
                    $new_data['ac_code'] = $v['ac_code'];
                    $new_data['deep'] = 1;
                    //判断是否有子类
                    if ($tmp_list[$k+1]['deep'] > $v['deep']){
                        $v['have_child'] = 1;
                        //根据acid获取它的下级
                        $child_array = array();
                        $child_array = $model_class->getChildClass($v['acid']);
                        $child_array_new = array();
                        foreach ($child_array as $key => $val){
                            if($val['acid'] != $v['acid']){
                                $child_array_new[] = array('key'=>$val['acid'],'ac_name'=>$val['ac_name'],'ac_sort'=>$val['ac_sort'],'ac_code'=>$val['ac_code'],'deep'=>2);
                            }
                        }
                        $new_data['children'] = $child_array_new;
                    }else{
                        $new_data['children'] = [];
                    }
                    $data[] = $new_data;
                }
            }
        }
        //父类列表，只取到第三级
        $parent_list = $model_class->getTreeClassList(1);

		if ($_GET['ajax'] == '1'){
			/**
			 * 转码
			 */
			if (strtoupper(CHARSET) == 'GBK'){
				$class_list = Language::getUTF8($data);
			}
            echo json_encode(array('list'=>$class_list,'catlist'=>$parent_list));die;
		}else {
            echo json_encode(array('list'=>$data,'catlist'=>$parent_list));die;
		}
	}
	
	/**
	 * slodon_文章分类 新增
	 */
	public function addArticleCat(){
		$lang	= Language::getLangContent();
		$model_class = M('ssys_article_class','spreader');
        /**
         * 验证
         */
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$_POST["ac_name"], "require"=>"true", "message"=>$lang['article_class_add_name_null']),
            array("input"=>$_POST["ac_sort"], "require"=>"true", 'validator'=>'Number', "message"=>$lang['article_class_add_sort_int']),
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            echo json_encode(array('state'=>255,'msg'=>str_replace("<br/>"," ",$error)));die;
        }else {

            $insert_array = array();
            $insert_array['ac_name'] = trim($_POST['ac_name']);
            $insert_array['ac_parent_id'] = intval($_POST['ac_parent_id']);
            $insert_array['ac_sort'] = trim($_POST['ac_sort']);

            $result = $model_class->add($insert_array);
            if ($result){
                $this->log(l('bbc_add,article_class_index_class').'['.$_POST['ac_name'].']',1);
                echo json_encode(array('state'=>200,'msg'=>$lang['article_class_add_succ']));
            }else {
                echo json_encode(array('state'=>255,'msg'=>$lang['article_class_add_fail']));
            }
        }

	}
	
	/**
	 * slodomn_文章分类编辑
	 */
	public function editArticleCat(){
		$lang	= Language::getLangContent();
		$model_class = M('ssys_article_class','spreader');

        /**
         * 验证
         */
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$_POST["ac_name"], "require"=>"true", "message"=>$lang['article_class_add_name_null']),
            array("input"=>$_POST["ac_sort"], "require"=>"true", 'validator'=>'Number', "message"=>$lang['article_class_add_sort_int']),
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            echo json_encode(array('state'=>255,'msg'=>str_replace("<br/>"," ",$error)));die;
        }else {

            $update_array = array();
            $update_array['acid'] = intval($_POST['acid']);
            $update_array['ac_name'] = trim($_POST['ac_name']);
            $update_array['ac_sort'] =trim($_POST['ac_sort']);

            $result = $model_class->update($update_array);
            if ($result){
                $this->log(l('bbc_edit,article_class_index_class').'['.$_POST['ac_name'].']',1);
                echo json_encode(array('state'=>200,'msg'=>$lang['article_class_edit_succ']));
            }else {
                echo json_encode(array('state'=>255,'msg'=>$lang['article_class_edit_fail']));
            }
        }
	}

	/**
	 * slodon_删除单个分类
	 */
	public function delArticleCat(){
		$lang	= Language::getLangContent();
		$model_class = M('ssys_article_class','spreader');
		if (intval($_GET['acid']) > 0){
			$array = array(intval($_GET['acid']));
			
			$del_array = $model_class->getChildClass($array);
			if (is_array($del_array)){
				foreach ($del_array as $k => $v){
					$model_class->del($v['acid']);
				}
			}
			$this->log(l('bbc_add,article_class_index_class').'[ID:'.intval($_GET['acid']).']',1);
            echo json_encode(array('state'=>200,'msg'=>$lang['article_class_index_del_succ']));
		}else {
            echo json_encode(array('state'=>255,'msg'=>$lang['article_class_index_choose']));
		}
	}
	/**
	 * ajax操作
	 */
	public function ajax(){
		switch ($_GET['branch']){
			/**
			 * 分类：验证是否有重复的名称
			 */
			case 'article_class_name':
				$model_class = M('ssys_article_class','spreader');
				$class_array = $model_class->getOneClass(intval($_GET['id']));
				
				$condition['ac_name'] = trim($_GET['value']);
				$condition['ac_parent_id'] = $class_array['ac_parent_id'];
				$condition['no_ac_id'] = intval($_GET['id']);
				$class_list = $model_class->getClassList($condition);
				if (empty($class_list)){
					$update_array = array();
					$update_array['acid'] = intval($_GET['id']);
					$update_array['ac_name'] = trim($_GET['value']);
					$model_class->update($update_array);
					echo 'true';exit;
				}else {
					echo 'false';exit;
				}
				break;
			/**
			 * 分类： 排序 显示 设置
			 */
			case 'article_class_sort':
				$model_class = M('ssys_article_class','spreader');
				$update_array = array();
				$update_array['acid'] = intval($_GET['id']);
				$update_array[$_GET['column']] = trim($_GET['value']);
				$result = $model_class->update($update_array);
				echo 'true';exit;
				break;
			/**
			 * 分类：添加、修改操作中 检测类别名称是否有重复
			 */
			case 'check_class_name':
				$model_class = M('ssys_article_class','spreader');
				$condition['ac_name'] = trim($_GET['ac_name']);
				$condition['ac_parent_id'] = intval($_GET['ac_parent_id']);
				$condition['no_ac_id'] = intval($_GET['acid']);
				$class_list = $model_class->getClassList($condition);
				if (empty($class_list)){
					echo 'true';exit;
				}else {
					echo 'false';exit;
				}
				break;
		}
	}
}
