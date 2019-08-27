<?php
/**
 * 文章管理
 *
 *
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');

class ssys_articleModel{
	/**
	 * 列表
	 *
	 * @param array $condition 检索条件
	 * @param obj $page 分页
	 * @return array 数组结构的返回结果
	 */
	public function getArticleList($condition,$page=''){
		$condition_str = $this->_condition($condition);
		$param = array();
		$param['table'] = 'ssys_article';
		$param['where'] = $condition_str;
		$param['limit'] = $condition['limit'];
		$param['order']	= (empty($condition['order'])?'article_sort asc,article_time desc':$condition['order']);
		$result = Db::select($param,$page);
		return $result;
	}

	/**
	 * 连接查询列表
	 *
	 * @param array $condition 检索条件
	 * @param obj $page 分页
	 * @return array 数组结构的返回结果
	 */
	public function getJoinList($condition,$page=''){
		$result	= array();
		$condition_str	= $this->_condition($condition);
		$param	= array();
		$param['table'] = 'ssys_article,ssys_article_class';
		$param['field']	= empty($condition['field'])?'*':$condition['field'];;
		$param['join_type']	= empty($condition['join_type'])?'left join':$condition['join_type'];
		$param['join_on']	= array('ssys_article.acid=ssys_article_class.acid');
		$param['where'] = $condition_str;
		$param['limit'] = $condition['limit'];
		$param['order']	= empty($condition['order'])?'ssys_article.article_sort':$condition['order'];
		$result = Db::select($param,$page);
		return $result;
	}

	/**
	 * 构造检索条件
	 *
	 * @param int $id 记录ID
	 * @return string 字符串类型的返回结果
	 */
	private function _condition($condition){
		$condition_str = '';

		if ($condition['article_show'] != ''){
			$condition_str .= " and ssys_article.article_show = '". $condition['article_show'] ."'";
		}
		if ($condition['acid'] != ''){
			$condition_str .= " and ssys_article.acid = '". $condition['acid'] ."'";
		}
		if ($condition['ac_ids'] != ''){
			//if(is_array($condition['ac_ids']))$condition['ac_ids']	= implode(',',$condition['ac_ids']);
			$condition_str .= " and ssys_article.acid in(". $condition['ac_ids'] .")";
		}
		if ($condition['like_title'] != ''){
			$condition_str .= " and ssys_article.article_title like '%". $condition['like_title'] ."%'";
		}
		if ($condition['home_index'] != ''){
			$condition_str .= " and (ssys_article_class.acid <= 7 or (ssys_article_class.ac_parent_id > 0 and ssys_article_class.ac_parent_id <= 7))";
		}

		return $condition_str;
	}

	/**
	 * 取单个内容
	 *
	 * @param int $id ID
	 * @return array 数组类型的返回结果
	 */
	public function getOneArticle($id){
		if (intval($id) > 0){
			$param = array();
			$param['table'] = 'ssys_article';
			$param['field'] = 'id';
			$param['value'] = intval($id);
			$result = Db::getRow($param);
			return $result;
		}else {
			return false;
		}
	}

	/**
	 * 新增
	 *
	 * @param array $param 参数内容
	 * @return bool 布尔类型的返回结果
	 */
	public function add($param){
		if (empty($param)){
			return false;
		}
		if (is_array($param)){
			$tmp = array();
			foreach ($param as $k => $v){
				$tmp[$k] = $v;
			}
			$result = Db::insert('ssys_article',$tmp);
			return $result;
		}else {
			return false;
		}
	}

	/**
	 * 更新信息
	 *
	 * @param array $param 更新数据
	 * @return bool 布尔类型的返回结果
	 */
	public function update($param){
		if (empty($param)){
			return false;
		}
		if (is_array($param)){
			$tmp = array();
			foreach ($param as $k => $v){
				$tmp[$k] = $v;
			}
			$where = " id = '". $param['id'] ."'";
			$result = Db::update('ssys_article',$tmp,$where);
			return $result;
		}else {
			return false;
		}
	}

	/**
	 * 删除
	 *
	 * @param int $id 记录ID
	 * @return bool 布尔类型的返回结果
	 */
	public function del($id){
		if (intval($id) > 0){
			$where = " id = '". intval($id) ."'";
			$result = Db::delete('ssys_article',$where);
			return $result;
		}else {
			return false;
		}
	}
}