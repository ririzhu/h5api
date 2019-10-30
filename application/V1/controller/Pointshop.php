<?php
namespace app\v1\controller;
/*
**积分商城
 */
use app\v1\model\Message;
use app\v1\model\pointprod;
use app\v1\model\pointorder;
use app\v1\model\GoodsClass;
use think\console\command\make\Model;
use think\db;

class Pointshop extends  Base
{
	public function __construct(){
		parent::__construct();
	}
	/*
	**积分商城首页
	 */
	public function index(){
		$GoodsClass=new GoodsClass();
		$pointprod=new pointprod();
		$point_goods_category=$pointprod->getPointClass();
		$field=" pgid,pgoods_name,pgoods_points,pgoods_image ";
		$where='';$order=' order by pgid desc ';$limit=' limit 3 ';
		//分类商品展示
		foreach ($point_goods_category as $key => $value) {
			
			//因优惠卷做的改变
			$gc_id_arr=[];
			$arr_list=$pointprod->getChildClass($value['gc_id']);
			foreach($arr_list as $k => $v){
				$gc_id_arr[]=$v['gc_id'];
			}
			$gc_id_str=implode(',', $gc_id_arr);
			$where=" and goods_gc_id in ( ".$gc_id_str." ) ";

			//$where=" and goods_gc_id = ".$value['gc_id'];
			$point_goods_category[$key]['list']=$pointprod->getlist($field,$where,$order,$limit);
		}
		//新品推荐
		$where='';
		$order=" order by pgoods_commend desc,pgid desc ";
		$point_goods_new=$pointprod->getlist($field,$where,$order,$limit);
		$data_array=array(
			'point_goods_new'=>$point_goods_new,
			'point_goods_category'=>$point_goods_category
		);
        $data['code']=200;
        $data['message']='请求成功';
        $data['data_array']=$data_array;
		echo json_encode($data,true);
	}
	/*
	**单类别列表页
	 */
	public function category(){
        if(!input("gc_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $gc_id=input('gc_id');
        $pointprod=new pointprod();
		$field=" pgid,pgoods_name,pgoods_points,pgoods_price,pgoods_image ";

		//因优惠卷做的改变
		$gc_id_arr=[];
		$arr_list=$pointprod->getChildClass($gc_id);
		foreach($arr_list as $k => $v){
			$gc_id_arr[]=$v['gc_id'];
		}
		$gc_id_str=implode(',', $gc_id_arr);
		$where=" and goods_gc_id in ( ".$gc_id_str." ) ";

		//$where=" and goods_gc_id = ".$gc_id;
		$order=" order by pgoods_commend desc,pgid desc ";
		$parameter=input('parameter');
		if($parameter=='desc'){
			$order=" order by pgoods_points desc ";
		}else if($parameter=='asc'){
			$order=" order by pgoods_points ";
		}
        $data_array=$pointprod->getlist($field,$where,$order);
        $data['code']=200;
        $data['message']='请求成功';
        $data['data_array']=$data_array;
		echo json_encode($data,true);      
	}
	/*
	**商品详情页
	 */
	public function detail(){
        if(!input("pgid")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
		}
		$where['pgid']=input('pgid');
		$pointprod=new pointprod();
		$res=$pointprod->getOne($where);
		//浏览次数自增
		$view_num=$pointprod->setNumInc(input("pgid"));

        $data['code']=200;
        $data['message']='请求成功';
        $data['res']=$res;
		echo json_encode($data,true);  		
	}
	/*
	**积分兑换商品
	 */
	public function pointBuy(){
		$pointprod=new pointprod();
		$pointorder=new pointorder();
        if(!input("member_id")||!input('pgid')||!input('address_id')||!input('allpoint')||!input('goodsnum')){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
		}

		$allpoint=input('allpoint');
		$goodsnum=input('goodsnum');
		
		//礼品信息
		$where=" pgid = ".input('pgid');
		$pgInfo=$pointprod->getOne($where);
		//收货地址信息
		$add_where=" address_id = ".input('address_id');
		$addInfo=$pointprod->getAddInfo($add_where);
		//会员信息
		$member_where=" member_id = ".input('member_id');
		$memberInfo=$pointprod->getMemberInfo($member_where);

		//优惠卷			....暂定,需求不清晰(后台添加优惠卷时，需保证gc_id唯一性)
		if(!empty($pgInfo['goods_gc_id'])){
			$goods_gc_id=$pgInfo['goods_gc_id'];
			$coupon_count=$pointprod->countCoupon($goods_gc_id);
			if($coupon_count<$goodsnum){
				$data['code']=10003;
				$data['message']="库存不足";
				return json($data);				
			}
			$coupon=$pointprod->getCoupon($goods_gc_id,$goodsnum);
		}

		$order_array=[];
		$orderaddress_array=[];
		$ordergoods_array=[];

		//判定数据是否一致
		if($pgInfo['pgoods_points']*$goodsnum!=$allpoint){
			$allpoint=$pgInfo['pgoods_points']*$goodsnum;
		}

		if($pgInfo['pgoods_islimit']==1){
			$order_goods=$pointprod->getOrderGoodsList($memberInfo['member_id'],$pgInfo['pgid']);
			$limitNum=0;
			if(!empty($order_goods)){
				foreach ($order_goods as $key => $value) {
					$limitNum+=$value['point_goodsnum'];
				}
			}
			if($limitNum>=$pgInfo['pgoods_limitnum']){
				$data['code']=10004;
				$data['message']="已达到兑换上限";
				return json_encode($data,true);
			}
		}

		if(!empty($pgInfo['pgoods_storage'])&&$pgInfo['pgoods_storage']<=0){
			$data['code']=10003;
			$data['message']="已兑完";
			return json_encode($data,true);
		}
		
		if(!empty($memberInfo['member_points'])&&$memberInfo['member_points']>=$allpoint){
			$now=TIMESTAMP;
			$sn=$pointorder->point_snOrder();
			$order_array['point_ordersn']=$sn;//生成订单号
			$order_array['point_buyerid']=$memberInfo['member_id'];//兑换会员id
			$order_array['point_buyername']=$memberInfo['member_name'];//兑换会员姓名
			$order_array['point_outsn']=$sn;//订单编号，外部
			$order_array['point_addtime']=$now;//兑换订单生成时间
			$order_array['point_paymenttime']=$now;//支付(付款)时间
			$order_array['point_allpoint']=trim($allpoint);//兑换总积分
			$order_array['point_orderstate']=20;//订单状态：20确认付款;
			if(!empty($coupon)) $order_array['point_orderstate']=50;

			$orderaddress_array['point_truename']=$addInfo['true_name'];//收货人姓名
			$orderaddress_array['point_areaid']=$addInfo['area_id'];//地区id
			$orderaddress_array['point_areainfo']=$addInfo['area_info'];//地区内容
			$orderaddress_array['point_address']=$addInfo['address'];//详细地址
			$orderaddress_array['point_mobphone']=$addInfo['mob_phone'];//手机号码

			$ordergoods_array['point_goodsid']=$pgInfo['pgid'];//	礼品id
			$ordergoods_array['point_goodsname']=$pgInfo['pgoods_name'];//	礼品名称
			$ordergoods_array['point_goodspoints']=$pgInfo['pgoods_points'];//	礼品兑换积分
			$ordergoods_array['point_goodsnum']=trim($goodsnum);//	礼品数量
			$ordergoods_array['point_goodsimage']=$pgInfo['pgoods_image'];//礼品图片

			$dateTime=date("Y-m-d H:i:s",$now);
			$messagge_array['to_member_id']=$memberInfo['member_id'];
			$messagge_array['message_body']="你的账户于".$dateTime."账户积分有变化，描述：兑换礼品，积分变化：-".$allpoint;
			$messagge_array['message_time']=$now;
			$messagge_array['message_update_time']=$now;
			$messagge_array['message_type']=1;
			$messagge_array['system_type']=5;
			$messagge_array2['to_member_id']=$memberInfo['member_id'];
			$messagge_array2['message_body']="关于订单：".$sn."的支付已经收到，请留意出库通知。";
			$messagge_array2['message_time']=$now;
			$messagge_array2['message_update_time']=$now;
			$messagge_array2['message_type']=1;
			$messagge_array2['system_type']=2;

		    $points_log['pl_memberid']=$memberInfo['member_id'];
		    $points_log['pl_membername']=$memberInfo['member_name'];
		    $points_log['pl_points']="-".$allpoint;
		    $points_log['pl_addtime']=$now;
		    $points_log['pl_desc']="兑换礼品信息".$sn."消耗积分";
		    $points_log['pl_stage']="pointorder";
				
			Db::startTrans();
			try {
			    $order_res=$pointprod->insertOrder($order_array);
			    $orderaddress_array['point_orderid']=$order_res;
			    $orderaddress_res=$pointprod->insertOrderAddress($orderaddress_array);
			    $ordergoods_array['point_orderid']=$order_res;
			    $ordergoods_res=$pointprod->insertOrderGoods($ordergoods_array);
			    $member['member_points']=$memberInfo['member_points']-$allpoint;
			    $member_res=$pointprod->updateMember($memberInfo['member_id'],$member);
			    $point_goods['pgoods_storage']=$pgInfo['pgoods_storage']-$goodsnum;

			    //优惠卷
			    if(!empty($coupon)){
			    	foreach($coupon as $k1=>$v1){
			    		$coupon_array['receive_time']=$now;
			    		$coupon_array['member_id']=$memberInfo['member_id'];
			    		$coupon_res=$pointprod->updateConpon($v1['id'],$coupon_array);
			    	}
			    	$point_goods['pgoods_storage']=$coupon_count-$goodsnum;
			    }

			    $point_goods['pgoods_salenum']=$pgInfo['pgoods_salenum']+$goodsnum;
			    $member_res=$pointprod->updatePointGoodsById($pgInfo['pgid'],$point_goods);
			    $message_res=$pointprod->insertMessage($messagge_array);
			    $message_res2=$pointprod->insertMessage($messagge_array2);
			    $pointslog_res=$pointprod->insertPointsLog($points_log);
			    Db::commit();

			    $data['code']=200;
			    $data['message']="兑换成功";
			    return json_encode($data,true);
			} catch (\Exception $e) {
			    Db::rollback();
			    $data['code']=10005;
			    $data['message']="兑换失败";
			    return json_encode($data,true);
			}
		}else{
			$data['code'] = 10002;
            $data['message'] = "积分不足";
            return json_encode($data,true);			
		}
	}
	/*
	**个人兑换积分商品列表
	 */
	public function exchangeList(){
		$pointprod=new pointprod();
		$pointorder=new pointorder();
		$member_id=input("member_id");
        if(empty($member_id)){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json($data);
		}
		$list=$pointprod->getExchange($member_id);
		if(!empty($list)){
			foreach ($list as &$v) {
				if(!empty($v['point_addtime'])) $v['point_addtime']=date('Y.m.d H:i:s',$v['point_addtime']);
				if(!empty($v['point_paymenttime'])) $v['point_paymenttime']=date('Y.m.d H:i:s',$v['point_paymenttime']);
				if(!empty($v['point_shippingtime'])) $v['point_shippingtime']=date('Y.m.d H:i:s',$v['point_shippingtime']);
				if(!empty($v['point_finnshedtime'])) $v['point_finnshedtime']=date('Y.m.d H:i:s',$v['point_finnshedtime']);
			}
			$data['list']=$list;
		}else{
			$data['list']=null;
		}
		$data['code']=200;
		$data['message']="请求成功";
		return json($data);
	}
	/*
	**个人更改订单状况(用于确认收货)
	 */
	public function changeState(){
		$pointprod=new pointprod();
		$pointorder=new pointorder();
		$member_id=input("member_id");
		$orderid=input("orderid");
		$orderstate=input("orderstate");
        if(empty($member_id)||empty($orderstate)||empty($orderid)){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json($data);
		}
		$update['point_orderstate']=$orderstate;
		$res=$pointprod->changeState($member_id,$orderid,$update);
		if($res){
			$data['code']=200;
			$data['message']="更改成功";
		}else{
			$data['code']=10002;
			$data['message']="更改失败";
		}
		return json($data);
	}
}