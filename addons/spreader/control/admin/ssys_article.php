<?php
/**
 * 文章管理
 *
 * 
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_articleCtl extends SystemCtl{
	public function __construct(){
		parent::__construct();
		Language::read('article','spreader');
	}
	/**
	 * 文章管理
	 */
	public function getSysArticleList(){
		$lang	= Language::getLangContent();
		$model_article = M('ssys_article');
		/**
		 * 删除
		 */
		if ($_POST['type'] == 'del'){
		    if(!is_array($_POST['del_id'])){
                $_POST['del_id'] = array($_POST['del_id']);
            }
			if (is_array($_POST['del_id']) && !empty($_POST['del_id'])){
				$model_upload = M('ssys_upload');
				foreach ($_POST['del_id'] as $k => $v){
					$v = intval($v);
					/**
					 * 删除图片
					 */
					$condition['upload_type'] = '1';
					$condition['item_id'] = $v;
					$upload_list = $model_upload->getUploadList($condition);
					if (is_array($upload_list)){
						foreach ($upload_list as $k_upload => $v_upload){
							$model_upload->del($v_upload['upload_id']);
							delete_file(BASE_UPLOAD_PATH.DS.ATTACH_ARTICLE.DS.$v_upload['file_name']);
						}
					}
					$model_article->del($v);
				}
				$this->log(L('article_index_del_succ').'[ID:'.implode(',',$_POST['del_id']).']',null);
                echo json_encode(array('state'=>200,'msg'=>$lang['article_index_del_succ']));die;
			}else {
                echo json_encode(array('state'=>255,'msg'=>$lang['article_index_choose']));die;
			}
		}
        $search = array();
        //分页检索条件(如果传值 按照传的值 否则默认10页)
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);
        /**
		 * 检索条件
		 */
        if(!empty($_GET['search_ac_id'])){
            $search['search_ac_id'] = $_GET['search_ac_id'];
            $condition['acid'] = intval($_GET['search_ac_id']);
        }
        if(!empty($_GET['search_title'])){
            $search['search_title'] = $_GET['search_title'];
            $condition['like_title'] = trim($_GET['search_title']);
        }
		/**
		 * 分页
		 */
		$page	= new Page();
		$page->setEachNum($pageSize);
		$page->setStyle('admin');
		/**
		 * 列表
		 */
		$article_list = $model_article->getArticleList($condition,$page);
		/**
		 * 整理列表内容
		 */
		if (is_array($article_list)){
			/**
			 * 取文章分类
			 */
			$model_class = M('ssys_article_class');
			$class_list = $model_class->getClassList($condition);
			$tmp_class_name = array();
			if (is_array($class_list)){
				foreach ($class_list as $k => $v){
					$tmp_class_name[$v['acid']] = $v['ac_name'];
				}
			}
			
			foreach ($article_list as $k => $v){
				/**
				 * 发布时间
				 */
				$article_list[$k]['article_time'] = date('Y-m-d H:i:s',$v['article_time']);
				/**
				 * 所属分类
				 */
				if (@array_key_exists($v['acid'],$tmp_class_name)){
					$article_list[$k]['ac_name'] = $tmp_class_name[$v['acid']];
				}
			}
		}
		/**
		 * 分类列表
		 */
		$model_class = M('ssys_article_class');
		$parent_list = $model_class->getTreeClassList(2);
		if (is_array($parent_list)){
			$unset_sign = false;
			foreach ($parent_list as $k => $v){
				$parent_list[$k]['ac_name'] = $v['ac_name'];
			}
		}
        echo json_encode(array('list' => $article_list, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($page->gettotalnum())),'searchlist'=>$search,'typelist'=>$parent_list));
	}
	
	/**
	 * 文章添加
	 */
	public function addArticle(){
		$lang	= Language::getLangContent();
		$model_article = M('ssys_article');
        /**
         * 验证
         */
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$_POST["article_title"], "require"=>"true", "message"=>$lang['article_add_title_null']),
            array("input"=>$_POST["acid"], "require"=>"true", "message"=>$lang['article_add_class_null']),
            //array("input"=>$_POST["article_url"], 'validator'=>'Url', "message"=>$lang['article_add_url_wrong']),
            array("input"=>$_POST["article_content"], "require"=>"true", "message"=>$lang['article_add_content_null']),
            array("input"=>$_POST["article_sort"], "require"=>"true", 'validator'=>'Number', "message"=>$lang['article_add_sort_int']),
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            echo json_encode(array('state'=>255,'msg'=>str_replace("<br/>"," ",$error)));die;
        }else {

            $insert_array = array();
            $insert_array['article_title'] = trim($_POST['article_title']);
            $insert_array['acid'] = intval($_POST['acid']);
            $insert_array['article_url'] = trim($_POST['article_url']);
            $insert_array['article_show'] = trim($_POST['article_show']);
            $insert_array['article_sort'] = trim($_POST['article_sort']);
            $insert_array['article_content'] = trim($_POST['article_content']);
            $insert_array['article_time'] = time();
            $result = $model_article->add($insert_array);
            if ($result){
                /**
                 * 更新图片信息ID
                 */
                $model_upload = M('ssys_upload');
                if (is_array($_POST['file_id'])){
                    foreach ($_POST['file_id'] as $k => $v){
                        $v = intval($v);
                        $update_array = array();
                        $update_array['upload_id'] = $v;
                        $update_array['item_id'] = $result;
                        $model_upload->update($update_array);
                        unset($update_array);
                    }
                }
                $this->log(L('article_add_ok').'['.$_POST['article_title'].']',null);
                echo json_encode(array('state'=>200,'msg'=>$lang['article_add_ok']));die;
            }else {
                echo json_encode(array('state'=>255,'msg'=>$lang['article_add_fail']));die;
            }
        }
	}
	
	/**
	 * slodon_文章编辑
	 */
	public function editArticle(){
		$lang	 = Language::getLangContent();
		$model_article = M('ssys_article');
		
		if ($_POST['type'] == 'edit'){
			/**
			 * 验证
			 */
			$obj_validate = new Validate();
			$obj_validate->validateparam = array(
				array("input"=>$_POST["article_title"], "require"=>"true", "message"=>$lang['article_add_title_null']),
				array("input"=>$_POST["acid"], "require"=>"true", "message"=>$lang['article_add_class_null']),
				//array("input"=>$_POST["article_url"], 'validator'=>'Url', "message"=>$lang['article_add_url_wrong']),
				array("input"=>$_POST["article_content"], "require"=>"true", "message"=>$lang['article_add_content_null']),
				array("input"=>$_POST["article_sort"], "require"=>"true", 'validator'=>'Number', "message"=>$lang['article_add_sort_int']),
			);
			$error = $obj_validate->validate();
			if ($error != ''){
			    echo json_encode(array('state'=>255,'msg'=>str_replace("<br/>"," ",$error)));die;
			}else {
				
				$update_array = array();
				$update_array['id'] = intval($_POST['id']);
				$update_array['article_title'] = trim($_POST['article_title']);
				$update_array['acid'] = intval($_POST['acid']);
				$update_array['article_url'] = trim($_POST['article_url']);
				$update_array['article_show'] = trim($_POST['article_show']);
				$update_array['article_sort'] = trim($_POST['article_sort']);
				$update_array['article_content'] = trim($_POST['article_content']);
				
				$result = $model_article->update($update_array);
				if ($result){
					/**
					 * 更新图片信息ID
					 */
					$model_upload = M('ssys_upload');
					if (is_array($_POST['file_id'])){
						foreach ($_POST['file_id'] as $k => $v){
							$update_array = array();
							$update_array['upload_id'] = intval($v);
							$update_array['item_id'] = intval($_POST['id']);
							$model_upload->update($update_array);
							unset($update_array);
						}
					}

					$this->log(L('article_edit_succ').'['.$_POST['article_title'].']',null);
                    echo json_encode(array('state'=>200,'msg'=>$lang['article_edit_succ']));die;
				}else {
                    echo json_encode(array('state'=>255,'msg'=>$lang['article_edit_fail']));die;
				}
			}
		}

		if(intval($_GET['id'])>0){
            $article_array = $model_article->getOneArticle(intval($_GET['id']));
            if (empty($article_array)){
                echo json_encode(array('state'=>255,'msg'=>$lang['参数错误']));die;
            }
        }else{
            $article_array = array();
        }
		/**
		 * 文章类别模型实例化
		 */
		$model_class = M('ssys_article_class');
		/**
		 * 父类列表，只取到第一级
		 */
		$parent_list = $model_class->getTreeClassList(2);

        echo json_encode(array('content'=>$article_array,'typelist'=>$parent_list));
	}
    /**
     * slodon_平台文章显示事件
     */
    public function sldChangeAriticleState(){
        $lang	 = Language::getLangContent();
        $model_article = M('ssys_article');
        $update_array = array();
        $update_array['id'] = intval($_POST['id']);
        $update_array['article_show'] = trim($_POST['article_show']);
        $result = $model_article->update($update_array);
        if ($result){
            echo json_encode(array('state'=>200,'msg'=>$lang['article_edit_succ']));
        }else {
            echo json_encode(array('state'=>255,'msg'=>$lang['article_edit_fail']));
        }
    }
	/**
	 * 文章图片上传
	 */
	public function article_pic_upload(){
		/**
		 * 上传图片
		 */
		$upload = new UploadFile();
		$upload->set('default_dir',ATTACH_ARTICLE);
		$result = $upload->upfile('fileupload');
		if ($result){
			$_POST['pic'] = $upload->file_name;
		}else {
			echo 'error';exit;
		}
		/**
		 * 模型实例化
		 */
		$model_upload = M('ssys_upload');
		/**
		 * 图片数据入库
		 */
		$insert_array = array();
		$insert_array['file_name'] = $_POST['pic'];
		$insert_array['upload_type'] = '1';
		$insert_array['file_size'] = $_FILES['fileupload']['size'];
		$insert_array['upload_time'] = time();
		$insert_array['item_id'] = intval($_POST['item_id']);
		$result = $model_upload->add($insert_array);
		if ($result){
			$data = array();
			$data['file_id'] = $result;
			$data['file_name'] = $_POST['pic'];
			$data['file_path'] = $_POST['pic'];
			/**
			 * 整理为json格式
			 */
			$output = json_encode($data);
			echo $output;
		}
		
	}
	/**
	 * ajax操作
	 */
	public function ajax(){
		switch ($_GET['branch']){
			/**
			 * 删除文章图片
			 */
			case 'del_file_upload':
				if (intval($_GET['file_id']) > 0){
					$model_upload = M('ssys_upload');
					/**
					 * 删除图片
					 */
					$file_array = $model_upload->getOneUpload(intval($_GET['file_id']));
					delete_file(BASE_UPLOAD_PATH.DS.ATTACH_ARTICLE.DS.$file_array['file_name']);
					/**
					 * 删除信息
					 */
					$model_upload->del(intval($_GET['file_id']));
					echo 'true';exit;
				}else {
					echo 'false';exit;
				}
				break;
		}
	}
}