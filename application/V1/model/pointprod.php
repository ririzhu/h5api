<?php
namespace app\v1\model;

use think\Model;
use think\db;

class pointprod extends Model
{
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 礼品保存
	 *
	 * @param	array $param 商品资料
	 */
	public function addPointGoods($param) {
		if(empty($param)) {
			return false;
		}
		$result	= Db::insert('points_goods',$param);
		if($result) {
			return $result;
		} else {
			return false;
		}
	}
	/*
	 * cwap端积分商品查询
	 *
	 */
	public function cwap_GetPointsGoodsList($condition,$field='*',$page='',$order='')
	{
		return $this->table('points_goods')->where($condition)->field($field)->page($page)->order($order)->select();
	}
	/**
	 * 礼品信息列表
	 *
	 * @param array $condition 条件数组
	 * @param array $page   分页
	 * @param array $field   查询字段
	 * @param array $page   分页  
	 */
	public function getPointProdList($condition,$page='',$field='*'){
		$condition_str	= $this->getCondition($condition);
		$param	= array();
		$param['table']	= 'points_goods';
		$param['where']	= $condition_str;
		$param['field'] = $field;
		$param['order'] = $condition['order'] ? $condition['order'] : 'points_goods.pgid desc';
		$param['limit'] = $condition['limit'];
		$param['group'] = $condition['group'];
		return Db::select($param,$page);
	}
	/**
	 * 礼品信息列表
	 *
	 * @param array $condition 条件数组
	 * @param array $page   分页
	 * @param array $field   查询字段
	 * @param array $page   分页  
	 */
	public function getPointProdListNew($field='*',$where='',$order='',$limit='',$page=''){
		if (empty($order)){
			$order = 'pgoods_sort asc';
		}
		$list = $this->table('points_goods')->field($field)->where($where)->order($order)->limit($limit)->page($page)->select();
		if (is_array($list) && count($list)>0){
			foreach ($list as $k=>$v){
				$v['pgoods_image'] = pointprodThumb($v['pgoods_image']);
				$v['ex_state'] = $this->getPointProdExstate($v);
				$list[$k] = $v;
			}
		}
		return $list;
	}
	/**
	 * 礼品信息单条
	 *
	 * @param array $condition 条件数组
	 * @param array $field   查询字段
	 */
	public function getPointProdInfo($condition,$field='*'){
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$array			= array();
		$array['table']	= 'points_goods';
		$array['where']	= $condition_str;
		$array['field']	= $field;
		$prod_info		= Db::select($array);
		return $prod_info[0];
	}
	/**
	 * 礼品信息单条
	 *
	 * @param array $condition 条件数组
	 * @param array $field   查询字段
	 */
	public function getPointProdInfoNew($where = '',$field='*'){
		$prodinfo = $this->table('points_goods')->where($where)->find();
		if (!empty($prodinfo)){
			$prodinfo['pgoods_image_small'] = pointprodThumb($prodinfo['pgoods_image'], 'small');
			$prodinfo['pgoods_image'] = pointprodThumb($prodinfo['pgoods_image']);
			$prodinfo['ex_state'] = $this->getPointProdExstate($prodinfo);
		}
		return $prodinfo;
	}
	/**
	 * 获得礼品兑换状态
	 * @param array $condition 礼品数组
	 * return array $field   查询字段
	 */
	public function getPointProdExstate($prodinfo){
		$datetime = time();
		$ex_state = 'end';//兑换按钮的可用状态
		if ($prodinfo['pgoods_islimittime'] == 1){
			//即将开始
			if ($prodinfo['pgoods_starttime']>$datetime && $prodinfo['pgoods_storage']>0){
				$ex_state = 'willbe';
			}
			//时间进行中
			if ($prodinfo['pgoods_starttime'] <= $datetime && $datetime < $prodinfo['pgoods_endtime'] && $prodinfo['pgoods_storage']>0){
				$ex_state = 'going';
			}
		}else {
			if ($prodinfo['pgoods_storage']>0){
				$ex_state = 'going';
			}
		}
		return $ex_state;
	}
	/**
	 * 获得礼品可兑换数量
	 * @param array $condition 礼品数组
	 * return array $field   查询字段
	 */
	public function getPointProdExnum($prodinfo,$quantity){
		if ($quantity <= 0){
			$quantity = 1;
		}
		if ($prodinfo['pgoods_islimit'] == 1 && $prodinfo['pgoods_limitnum'] < $quantity ){
			//如果兑换数量大于限兑数量，则兑换数量为限兑数量
			$quantity = $prodinfo['pgoods_limitnum'];
		}
		if ($prodinfo['pgoods_storage'] < $quantity){
			//如果兑换数量大于库存，则兑换数量为库存数量
			$quantity = $prodinfo['pgoods_storage'];
		}
		return $quantity;
	}
	/**
	 * 删除礼品信息
	 * @param	mixed $ztc_id 删除申请记录编号
	 */
	public function dropPointProdById($pg_id){
		if(empty($pg_id)) {
			return false;
		}
		$condition_str = ' 1=1 ';
		if (is_array($pg_id) && count($pg_id)>0){
			$pg_idStr = implode(',',$pg_id);
			$condition_str .= " and	pgid in({$pg_idStr}) ";
		}else {
			$condition_str .= " and pgid = '{$pg_id}' ";
		}
		$result = Db::delete('points_goods',$condition_str);
		//删除积分礼品下的图片信息
		if ($result){
			//删除积分礼品下的图片信息
			$upload_model = Model('upload');
			if (is_array($pg_id) && count($pg_id)>0){
				$pg_idStr = implode(',',$pg_id);
				$upload_list = $upload_model->getUploadList(array('upload_type_in' =>'5,6','item_id_in'=>$pg_idStr));
			}else {
				$upload_list = $upload_model->getUploadList(array('upload_type_in' =>'5,6','item_id'=>$pg_id));
			}			
			if (is_array($upload_list) && count($upload_list)>0){
				$upload_idarr = array();
				foreach ($upload_list as $v){
					delete_file(BASE_UPLOAD_PATH.DS.ATTACH_POINTPROD.DS.$v['file_name']);
					delete_file(BASE_UPLOAD_PATH.DS.ATTACH_POINTPROD.DS.$v['file_thumb']);
					$upload_idarr[] = $v['upload_id'];
				}
				//删除图片
				$upload_model->dropUploadById($upload_idarr);
			}
		}
		return $result;
	}
	/**
	 * 编辑积分礼品信息
	 */
	public function editPointProd($update_arr, $where){
		if (empty($update_arr)) {
			return true;
		}
		$result	= $this->table('points_goods')->where($where)->update($update_arr);
		return $result;
	}

	/**
	/**
	 * 积分礼品信息修改
	 *
	 * @param	array $param 修改信息数组
	 * @param	int $pg_id 团购商品id
	 */
	public function updatePointProd($param,$condition) {
		if(empty($param)) {
			return false;
		}
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$result	= Db::update('points_goods',$param,$condition_str);
		return $result;
	}
	/*
	 * 修改
	 */
	public function updatePointgoods($where=[],$data=[])
	{
		return $this->table('points_goods')->where($where)->update($data);
	}
	/**
	 * 将条件数组组合为SQL语句的条件部分
	 *
	 * @param	array $condition_array
	 * @return	string
	 */
	private function getCondition($condition_array){
		$condition_sql = '';
		//积分礼品名称
		if ($condition_array['pgoods_name_like']) {
			$condition_sql	.= " and `points_goods`.pgoods_name like '%{$condition_array['pgoods_name_like']}%'";
		}
		//状态搜索
		if ($condition_array['pg_liststate']) {
			switch ($condition_array['pg_liststate']){
				case 'show':
					$condition_sql	.= " and `points_goods`.pgoods_show = 1 ";
					break;
				case 'nshow':
					$condition_sql	.= " and `points_goods`.pgoods_show = 0 ";
					break;
				case 'commend':
					$condition_sql	.= " and `points_goods`.pgoods_commend = 1 ";
					break;
				case 'forbid':
					$condition_sql	.= " and `points_goods`.pgoods_state = 1 ";
					break;
			}
		}
		//积分礼品记录编号
		if (isset($condition_array['pgids_id_in'])) {
			if ($condition_array['pgid_in'] == ''){
				$condition_sql	.= " and `points_goods`.pgid in('') ";
			}else {
				$condition_sql	.= " and `points_goods`.pgid in({$condition_array['pgid_in']})";
			}
		}
		//积分礼品记录编号
		if (isset($condition_array['pgid'])) {
			$condition_sql	.= " and `points_goods`.pgid = '{$condition_array['pgid']}'";
		}
		//上架状态
		if (isset($condition_array['pgoods_show'])) {
			$condition_sql	.= " and `points_goods`.pgoods_show = '{$condition_array['pgoods_show']}'";
		}
		//禁售状态
		if (isset($condition_array['pgoods_state'])) {
			$condition_sql	.= " and `points_goods`.pgoods_state = '{$condition_array['pgoods_state']}'";
		}
		//推荐状态
		if (isset($condition_array['pgoods_commend'])) {
			$condition_sql	.= " and `points_goods`.pgoods_commend = '{$condition_array['pgoods_commend']}'";
		}
		return $condition_sql;
	}
    /**
     * 获取热门推荐积分礼品
     * @param int $num 查询条数
     */
    public function getTuijianPointProd($num){
        $where = array();
        $where['pgoods_show'] = 1;
        $where['pgoods_state'] = 0;
        $where['pgoods_commend'] = 1;
        $where['limit'] = $num;
        $recommend_pointsprod = $this->getPointProdList($where,'','*');
        if (is_array($recommend_pointsprod) && count($recommend_pointsprod)>0){
            foreach ($recommend_pointsprod as $k=>$v){
                $v['pgoods_image'] = pointprodThumb($v['pgoods_image'], '');
                $recommend_pointsprod[$k] = $v;
            }
        }
        return $recommend_pointsprod;
    }

    /**
	 * getDocOfEnterin
	 * 
	 * 获取当前用户已兑换商品数量
	 *
	 *
	 **/
    public function getOrderPointProdNum($condition)
    {
    	$return = $this->table('points_order,points_ordergoods')->on('points_order.point_orderid = points_ordergoods.point_orderid')->where($condition)->SUM('points_ordergoods.point_goodsnum');

    	return $return['jmys_SUM'] ? $return['jmys_SUM'] : 0 ;
    }


    /*
    **获取列表
     */
    public function getlist($field=' * ',$where='',$order='',$limit=''){
    	$now=time();
    	$sql="select ";
    	$sql.=$field;
    	$sql.=" from bbc_points_goods ";
    	$sql.=" where pgoods_show = 1 and pgoods_state = 0 and (pgoods_islimittime = 0 or (pgoods_islimittime = 1 and pgoods_starttime < ".$now." and pgoods_endtime > ".$now.")) ";
    	$sql.=$where;
    	$sql.=$order;
    	$sql.=$limit;
    	return Db::query($sql);
    }
    /*
    **获取礼品详情
     */
    public function getOne($where){
    	return Db::name('points_goods')->field('*')->where($where)->find();
    }
    /*
    **获取礼品类别
     */
    public function getPointClass(){
    	$sql="select ";
    	$sql.=" gc_id,gc_name,gc_sld_pc_picture ";
    	$sql.=" from bbc_goods_class ";
    	$sql.=" where is_points = 1 and gc_show = 1 ";
    	return Db::query($sql);
    }
    /*
    **获取收获地址
     */
    public function getAddInfo($where){
    	return Db::name('address')->field('*')->where($where)->find();
    }
    /*
    **获取会员信息
     */
    public function getMemberInfo($where){
    	return Db::name('member')->field('*')->where($where)->find();
    }
    /*
    **生成订单,返回自增id
     */
    public function insertOrder($data){
    	return Db::name('points_order')->insertGetId($data);
    }
    /*
    **插入兑换订单地址表
     */
    public function insertOrderAddress($data){
    	return Db::name('points_orderaddress')->insert($data);
    }
    /*
    **插入兑换订单商品表
     */
    public function insertOrderGoods($data){
    	return Db::name('points_ordergoods')->insert($data);
    }
    /*
    **升级会员数据
     */
    public function updateMember($id,$data){
    	return Db::name('member')->where(['member_id'=>$id])->update($data);
    }
    /*
    **升级积分商品数据
     */
    public function updatePointGoodsById($id,$data){
    	return Db::name('points_goods')->where(['pgid'=>$id])->update($data);
    }
    /*
    **浏览自增
    *setDec(‘score’,5);setInc(‘score’,5);
     */
    public function setNumInc($id){
    	return Db::name('points_goods')->where(['pgid'=>$id])->setInc('pgoods_view');
    }
    /*
    **积分日志
     */
    public function insertPointsLog($data){
    	return Db::name('points_log')->insert($data);
    }
    /*
    **插入系统消息
     */
    public function insertMessage($data){
    	return Db::name('message')->insert($data);
    }
    /*
    **获取个人积分兑换商品数列表
     */
    public function getOrderGoodsList($id,$pgid){
    	$sql=" select b.point_goodsid,b.point_goodsnum ";
    	$sql.=" from bbc_points_order a ";
    	$sql.=" left join bbc_points_ordergoods b on a.point_orderid = b.point_orderid ";
    	$sql.=" where a.point_buyerid = ".$id." and b.point_goodsid = ".$pgid;
    	return Db::query($sql);
    }
    /*
    **个人兑换de积分商品订单表
     */
    public function getExchange($id){
    	//return Db::name("points_order")->where(["point_buyerid"=>$id])->select();
    	$sql=" select a.*,b.point_goodsid,b.point_goodsname,b.point_goodspoints,b.point_goodsnum,b.point_goodsimage ";
    	$sql.=" from bbc_points_order a ";
    	$sql.=" left join bbc_points_ordergoods b on a.point_orderid = b.point_orderid ";
    	$sql.=" where a.point_buyerid = ".$id." order by a.point_orderid desc ";
    	return Db::query($sql);
    }
    /*
    **个人更改订单状况
     */
    public function changeState($member_id,$orderid,$data){
    	return Db::name('points_order')->where(['point_buyerid'=>$member_id,'point_orderid'=>$orderid])->update($data);
    }

    /*
    **可用优惠卷数量
     */
    public function countCoupon($gc_id){
    	return Db::name('points_coupon')->where(['goods_gc_id'=>$gc_id,'receive_time'=>0])->count();
    }
    /*
    **需要操作的优惠卷
     */
    public function getCoupon($gc_id,$goodsnum){
    	return Db::name('points_coupon')->where(['goods_gc_id'=>$gc_id,'receive_time'=>0])->limit($goodsnum)->select();
    }
    /*
    **更新优惠卷状态
     */
    public function updateConpon($id,$coupon_array){
    	return Db::name('points_coupon')->where(['id'=>$id])->update($coupon_array);
    }

}
