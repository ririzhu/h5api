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
			$where=" and goods_gc_id = ".$value['gc_id'];
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
		$where=" and goods_gc_id = ".$gc_id;
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
	}
}