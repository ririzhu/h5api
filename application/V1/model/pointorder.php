<?php
namespace app\v1\model;

use think\Model;
use think\db;

class pointorder extends Model
{
	private $product_sn;	//订单编号
	
	/**
	 * 生成积分兑换订单编号
	 * @return string
	 */
	public function point_snOrder() {
		$this->product_sn = 'gift'.date('Ymd').substr( implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))) , -8 , 8);
		return $this->product_sn;
	}
	/**
	 * 生成外部积分兑换订单
	 *
	 * @return string
	 */
	public function point_outSnOrder() {
		if($this->product_sn) {
			return $this->product_sn;
		}
	}

    /**
     * 通过状态标识获取兑换订单状态
     */
    public function getPointOrderStateBySign(){
        $pointorderstate_arr = array();
        $pointorderstate_arr['canceled'] = array(2,'已取消');
        $pointorderstate_arr['waitship'] = array(20,'待发货');
        $pointorderstate_arr['waitreceiving'] = array(30,'待收货');
        $pointorderstate_arr['finished'] = array(40,'已完成');
        return $pointorderstate_arr;
    }
    /**
     * 通过状态值获取兑换订单状态
     */
    public function getPointOrderState($order_state){
        $pointorderstate_arr = array();
        $pointorderstate_arr[2] = array('canceled','已取消');
        $pointorderstate_arr[20] = array('waitship','待发货');
        $pointorderstate_arr[30] = array('waitreceiving','待收货');
        $pointorderstate_arr[40] = array('finished','已完成');
        if ($pointorderstate_arr[$order_state]){
            return $pointorderstate_arr[$order_state];
        } else {
            return array('unknown','未知');
        }
    }
	/**
	 * 兑换礼品加入订单
	 *
	 * @param array	$param
	 * @return bool
	 */
	public function addPointOrder($param) {
		if(is_array($param) and !empty($param)) {
			$result = Db::insert('points_order',$param);
			return $result;
		} else {
			return false;
		}
	}
	/**
	 * 订单积分礼品添加
	 * @param array	$param	订单礼品信息
	 * @return bool
	 */
	public function addPointOrderProd($param) {
		if(is_array($param) && count($param)>0) {
			$result = Db::insert('points_ordergoods',$param);
			return $result;
		} else {
			return false;
		}
	}
	/**
	 * 订单积分礼品列表
	 * @param array	$param	订单礼品信息
	 * @return bool
	 */
	public function getPointOrderProdList($condition,$page,$field='*',$type='simple') {		
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$param	= array();
		switch($type){
			case 'all':
				$param['table']	= 'points_ordergoods,points_order';
				$param['join_type']	= 'left join';
				$param['join_on']	= array('`points_ordergoods`.point_orderid = `points_order`.point_orderid');
				break;
			case 'simple':
				$param['table']	= 'points_ordergoods';
				break;
		}
		$param['where']	= $condition_str;
		$param['field']	= $field;
		$param['order'] = $condition['order'] ? $condition['order'] : 'points_ordergoods.point_recid desc ';
		$param['limit'] = $condition['limit'];
		$param['group'] = $condition['group'];
		$prod_list	= Db::select($param,$page);
		return $prod_list;
	}
	/**
	 * 删除礼品兑换信息
	 * @param	array 删除条件
	 */
	public function dropPointOrderProd($condition){
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$result = Db::delete('points_ordergoods',$condition_str);
		return $result;
	}
	/**
	 * 删除礼品兑换地址信息
	 * @param	array 删除条件
	 */
	public function dropPointOrderAddress($condition){
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$result = Db::delete('points_orderaddress',$condition_str);
		return $result;
	}
	/**
	 * 添加积分兑换订单地址
	 * @param array	$param	订单收货地址信息
	 * @return bool
	 */
	public function addPointOrderAddress($param){
		if(is_array($param) and count($param)) {
			$result = Db::insert('points_orderaddress',$param);
			return $result;
		} else {
			return false;
		}
	}
	/**
	 * 根据兑换订单编号查询指定订单
	 *
	 * @param int $order_id 订单序号
	 * @param string $type 查询类型
	 * @return array
	 */
	public function getPointOrderInfo($condition,$type='all',$field='*'){
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$param	= array();
		switch($type){
			case 'all':
				$param['table']	= 'points_order,points_orderaddress';
				$param['join_type']	= 'left join';
				$param['join_on']	= array('`points_order`.point_orderid=`points_orderaddress`.point_orderid');
				break;
			case 'simple':
				$param['table']	= 'points_order';
				break;
		}
		$param['where']	= $condition_str;
		$param['field']	= $field;
		$order_list	= Db::select($param);
		return $order_list[0];
	}
	/**
	 * 根据兑换订单编号查询指定订单
	 *
	 * @param int $order_id 订单序号
	 * @param string $type 查询类型
	 * @return array
	 */
	public function getPointOrderList($condition,$page,$type='all',$field='*'){
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$param	= array();
		switch($type){
			case 'all':
				$param['table']	= 'points_order,points_orderaddress';
				$param['join_type']	= 'left join';
				$param['join_on']	= array('`points_order`.point_orderid=`points_orderaddress`.point_orderid');
				break;
			case 'simple':
				$param['table']	= 'points_order';
				break;
		}
		$param['where']	= $condition_str;
		$param['field']	= $field;
		$param['order'] = $condition['order'] ? $condition['order'] : 'points_order.point_orderid desc';
		$param['limit'] = $condition['limit'];
		$param['group'] = $condition['group'];
		$order_list	= Db::select($param,$page);
		return $order_list;
	}
	/**
	 * 积分礼品兑换订单信息修改
	 *
	 * @param	array $param 修改信息数组
	 * @param	array $condition
	 */
	public function updatePointOrder($condition,$param) {
		if(empty($param)) {
			return false;
		}
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$result	= Db::update('points_order',$param,$condition_str);
		return $result;
	}
	/**
	 * 删除礼品兑换信息
	 * @param	array 删除条件
	 */
	public function dropPointOrder($condition){
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$result = Db::delete('points_order',$condition_str);
		return $result;
	}
	
	/**
	 * 将条件数组组合为SQL语句的条件部分
	 *
	 * @param	array $condition_array
	 * @return	string
	 */
	private function getCondition($condition_array){
		$condition_sql = '';
		//订单序号
		if ($condition_array['point_orderid']) {
			$condition_sql	.= " and `points_order`.point_orderid = '{$condition_array['point_orderid']}'";
		}
		//订单序号
		if ($condition_array['point_orderid_del']) {
			$condition_sql	.= " and point_orderid = '{$condition_array['point_orderid_del']}'";
		}
		//订单序号in
		if (isset($condition_array['point_orderid_in'])) {
			if ($condition_array['point_orderid_in'] == ''){
				$condition_sql	.= " and point_orderid in('') ";
			}else {
				$condition_sql	.= " and point_orderid in({$condition_array['point_orderid_in']}) ";
			}
		}
		//订单会员编号
		if ($condition_array['point_buyerid']) {
			$condition_sql	.= " and `points_order`.point_buyerid = '{$condition_array['point_buyerid']}'";
		}
		//订单运费承担方式
		if ($condition_array['point_shippingcharge']) {
			$condition_sql	.= " and `points_order`.point_shippingcharge = '{$condition_array['point_shippingcharge']}'";
		}
		//订单状态
		if ($condition_array['point_orderstate']) {
			$condition_sql	.= " and `points_order`.point_orderstate = '{$condition_array['point_orderstate']}'";
		}
		//订单状态
		if ($condition_array['point_orderstatetxt']) {
			switch ($condition_array['point_orderstatetxt']){
				case 'canceled':
					$condition_sql	.= " and `points_order`.point_orderstate = 2 ";
					break;
				case 'waitpay':
					$condition_sql	.= " and `points_order`.point_orderstate = 10 ";
					break;
				case 'waitconfirmpay':
					$condition_sql	.= " and `points_order`.point_orderstate = 11 ";
					break;
				case 'waitship':
					$condition_sql	.= " and `points_order`.point_orderstate = 20 ";
					break;
				case 'waitreceiving':
					$condition_sql	.= " and `points_order`.point_orderstate = 30 ";
					break;
				case 'finished':
					$condition_sql	.= " and `points_order`.point_orderstate = 40 ";
					break;
			}
		}
		//订单编号like
		if ($condition_array['point_ordersn']) {
			$condition_sql	.= " and `points_order`.point_ordersn = '{$condition_array['point_ordersn']}' ";
		}
		//订单编号like
		if ($condition_array['point_ordersn_like']) {
			$condition_sql	.= " and `points_order`.point_ordersn like '%{$condition_array['point_ordersn_like']}%' ";
		}
		//会员名称like
		if ($condition_array['point_buyername_like']) {
			$condition_sql	.= " and `points_order`.point_buyername like '%{$condition_array['point_buyername_like']}%' ";
		}
		//支付方式
		if ($condition_array['point_paymentcode']) {
			$condition_sql	.= " and `points_order`.point_paymentcode = '{$condition_array['point_paymentcode']}' ";
		}
		//订单状态 in
		if (isset($condition_array['point_orderstate_in'])) {
			if ($condition_array['point_orderstate_in'] == ''){
				$condition_sql	.= " and point_orderstate in ('') ";
			}else {
				$condition_sql	.= " and point_orderstate in ({$condition_array['point_orderstate_in']}) ";
			}
		}
		//兑换商品订单编号
		if ($condition_array['prod_orderid']) {
			$condition_sql	.= " and points_ordergoods.point_orderid = '{$condition_array['prod_orderid']}' ";
		}
		//兑换商品订单编号in
		if (isset($condition_array['prod_orderid_in'])) {
			if ($condition_array['prod_orderid_in'] == ''){
				$condition_sql	.= " and points_ordergoods.point_orderid in('')";
			}else{
				$condition_sql	.= " and points_ordergoods.point_orderid in({$condition_array['prod_orderid_in']})";
			}
		}
		//兑换商品订单编号删除
		if ($condition_array['prod_orderid_del']) {
			$condition_sql	.= " and point_orderid = '{$condition_array['prod_orderid_del']}' ";
		}
		//兑换商品订单编号in删除
		if (isset($condition_array['prod_orderid_in_del'])) {
			if ($condition_array['prod_orderid_in_del'] == ''){
				$condition_sql	.= " and point_orderid in('')";
			}else{
				$condition_sql	.= " and point_orderid in({$condition_array['prod_orderid_in_del']})";
			}
		}
		//兑换商品商品编号
		if ($condition_array['prod_goodsid']) {
			$condition_sql	.= " and points_ordergoods.point_goodsid = '{$condition_array['prod_goodsid']}' ";
		}
		//可取消兑换信息
		if ($condition_array['point_order_enablecancel']) {
			$condition_sql	.= " and ((points_order.point_shippingcharge = 1 and points_order.point_orderstate =10) or (points_order.point_shippingcharge = 0 and points_order.point_orderstate =20))";
		}
		//兑换商品订单编号删除
		if ($condition_array['address_orderid_del']) {
			$condition_sql	.= " and point_orderid = '{$condition_array['address_orderid_del']}' ";
		}
		//兑换商品订单编号in删除
		if (isset($condition_array['address_orderid_in_del'])) {
			if ($condition_array['address_orderid_in_del'] == ''){
				$condition_sql	.= " and point_orderid in('')";
			}else{
				$condition_sql	.= " and point_orderid in({$condition_array['address_orderid_in_del']})";
			}
		}
		return $condition_sql;
	}


    /**
     * 查询兑换订单总数
     */
    public function getPointOrderCount($where, $group = ''){
        return $this->table('points_order')->where($where)->group($group)->count();
    }

    /**
     * 查询积分兑换商品订单及商品列表
     */
    public function getPointOrderAndGoodsList($where, $field = '*', $page = 0, $limit = 0,$order = '', $group = '') {
        if (is_array($page)){
            if ($page[1] > 0){
                return $this->table('points_ordergoods,points_order')->field($field)->join('left')->on('points_ordergoods.point_orderid=points_order.point_orderid')->where($where)->group($group)->page($page[0],$page[1])->limit($limit)->order($order)->select();
            } else {
                return $this->table('points_ordergoods,points_order')->field($field)->join('left')->on('points_ordergoods.point_orderid=points_order.point_orderid')->where($where)->group($group)->page($page[0])->limit($limit)->order($order)->select();
            }
        } else {
            return $this->table('points_ordergoods,points_order')->field($field)->join('left')->on('points_ordergoods.point_orderid=points_order.point_orderid')->where($where)->group($group)->page($page)->limit($limit)->order($order)->select();
        }
    }

    /**
     * 查询积分兑换商品订单及商品详细
     */
    public function getPointOrderAndGoodsInfo($where, $field = '*', $order = '', $group = '') {
        return $this->table('points_ordergoods,points_order')->field($field)->join('left')->on('points_ordergoods.point_orderid=points_order.point_orderid')->where($where)->group($group)->order($order)->find();
    }


    /**
     * 查询会员已经兑换商品数
     * @param int $member_id 会员编号
     */
    public function getMemberPointsOrderGoodsCount($member_id){
        $info = rcache($member_id, 'm_pointorder', 'pointordercount');
        if (empty($info['pointordercount']) && $info['pointordercount'] !== 0) {
            //获取兑换订单状态
            $pointorderstate_arr = $this->getPointOrderStateBySign();

            $where = array();
            $where['point_buyerid'] = $member_id;
            $where['point_orderstate'] = array('neq',$pointorderstate_arr['canceled'][0]);
            $list = $this->getPointOrderAndGoodsList($where,'SUM(point_goodsnum) as goodsnum');
            $pointordercount = 0;
            if ($list){
                $pointordercount = intval($list[0]['goodsnum']);
            }
            wcache($member_id, array('pointordercount' => $pointordercount), 'm_pointorder');
        } else {
            $pointordercount = intval($info['pointordercount']);
        }
        return $pointordercount;
    }
}