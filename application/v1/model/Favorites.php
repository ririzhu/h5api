<?php
namespace app\v1\model;

use think\Model;
use think\db;
class Favorites extends Model
{
    public function __construct() {
        parent::__construct('favorites');
    }

    /**
     * 收藏列表
     *
     * @param array $condition
     * @param treing $field
     * @param int $page
     * @param string $order
     * @return array
     */
    public function getFavoritesList($condition, $field = '*', $page = 0 , $order = 'fav_time desc') {
        if ($condition['fav_type'] == 'goods') {
            $list = DB::table('bbc_favorites')->join('bbc_goods','bbc_goods.gid = bbc_favorites.fav_id')->field('bbc_favorites.*')->where($condition)->page($page)->select();
            return $list;
        }else if ($condition['fav_type'] == 'store') {
            return DB::table('bbc_favorites')->alias('f')->join('bbc_vendor v','v.vid = f.fav_id')->field('f.*')->where($condition)->order($order)->page($page)->select();
        }else{
            return DB::table("bbc_favorites")->where($condition)->order($order)->page($page)->select();
        }
    }

    /**
     * 收藏商品列表
     * @param array $condition
     * @param treing $field
     * @param int $page
     * @param string $order
     * @return array
     */
    public function getGoodsFavoritesList($condition, $field = '*', $page = 10, $order = 'fav_time desc') {
        $condition['fav_type'] = 'goods';
        return $this->getFavoritesList($condition, '*', $page, $order);
    }

    /**
     * 收藏店铺列表
     * @param array $condition
     * @param treing $field
     * @param int $page
     * @param string $order
     * @return array
     */
    public function getStoreFavoritesList($condition, $field = '*', $page = 0, $order = 'fav_time desc') {
        $condition['fav_type'] = 'store';
        return $this->getFavoritesList($condition, $field,$page, $order);
    }
// 	/**
// 	 * 收藏列表
// 	 *
// 	 * @param array $condition 检索条件
// 	 * @param obj $obj_page 分页对象
// 	 * @return array 数组类型的返回结果
// 	 */
// 	public function getFavoritesList($condition,$page = ''){
// 		$condition_str = $this->_condition($condition);
// 		$param = array(
// 					'table'=>'favorites',
// 					'where'=>$condition_str,
// 					'order'=>$condition['order'] ? $condition['order'] : 'fav_time desc'
// 				);
// 		$result = Db::select($param,$page);
// 		return $result;
// 	}
    /**
     * 取单个收藏的内容
     *
     * @param array $condition 查询条件
     * @param string $field 查询字段
     * @return array 数组类型的返回结果
     */
    public function getOneFavorites($condition,$field='*'){
        $param = array();
        $param['table'] = 'favorites';
        $param['field'] = array_keys($condition);
        $param['value'] = array_values($condition);
        return Db::table("bbc_favorites")->field($field)->where($condition)->find();
    }

    /**
     * 新增收藏
     *
     * @param array $param 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addFavorites($param){
        if (empty($param)){
            return false;
        }
        $user = new User();
        $uid = $param['member_id'];
        if ($param['fav_type'] == 'store') {
            $vid = intval($param['fav_id']);
            $model_store = new VendorInfo();
            $userinfo = $user->infoMember(array("member_id"=>$uid));
            $param['member_name'] = $userinfo['member_name'];
            $store = $model_store->getStoreInfoByID($vid);
            $param['store_name'] = $store['store_name'];
            $param['vid'] = $store['vid'];
            $param['sc_id'] = $store['sc_id'];
            $param['goods_image'] = $store['store_label'];
        }
        if ($param['fav_type'] == 'goods') {
            $gid = intval($param['fav_id']);
            $model_goods = new Goods();
            $userinfo = $user->infoMember(array("member_id"=>$uid));
            $param['member_name'] = $userinfo['member_name'];
            $fields = 'gid,vid,goods_name,goods_image,goods_price,goods_promotion_price';
            $goods = $model_goods->getGoodsInfoByID($gid,$fields);
            $param['goods_name'] = $goods['goods_name'];
            $param['goods_image'] = $goods['goods_image'];
            $param['log_price'] = $goods['goods_promotion_price'];//商品收藏时价格
            $param['log_msg'] = $goods['goods_promotion_price'];//收藏备注，默认为收藏时价格，可修改
            if(isset($goods['gc_id']))
            $param['gc_id'] = $goods['gc_id'];

            $vid = intval($goods['vid']);
            $model_store = new VendorInfo();
            $store = $model_store->getStoreInfoByID($vid);
            $param['store_name'] = $store['store_name'];
            $param['vid'] = $store['vid'];
            $param['sc_id'] = $store['sc_id'];
        }

        return Db::table("bbc_favorites")->insert($param);
    }

    /**
     * 更新收藏数量
     *
     *
     * @param string $table 表名
     * @param array  $update 更新内容
     * @param array  $param  相应参数
     * @return bool 布尔类型的返回结果
     */
    public function updateFavoritesNum($table, $update, $param){
        $where = $this->_condition($param);
        return Db::update($table,$update,$where);
    }

    /**
     * 验证是否为当前用户收藏
     *
     * @param array $param 条件数据
     * @return bool 布尔类型的返回结果
     */
    public function checkFavorites($fav_id,$fav_type,$member_id){
        if (intval($fav_id) == 0 || empty($fav_type) || intval($member_id) == 0){
            return true;
        }
        $result = self::getOneFavorites($fav_id,$fav_type,$member_id);
        if ($result['member_id'] == $member_id){
            return true;
        }else {
            return false;
        }
    }

    /**
     * 删除
     *
     * @param int $id 记录ID
     * @return array $rs_row 返回数组形式的查询结果
     */
    public function delFavorites($condition){
        if (empty($condition)){
            return false;
        }
        $condition_str = '1=1 ';
        if (isset($condition['fav_id']) && $condition['fav_id'] != ''){
            $condition_str .= " and fav_id='{$condition['fav_id']}' ";
        }
        if ($condition['member_id'] != ''){
            $condition_str .= " and member_id='{$condition['member_id']}' ";
        }
        if ($condition['fav_type'] != ''){
            $condition_str .= " and fav_type='{$condition['fav_type']}' ";
        }
        if (isset($condition['fav_id_in']) && $condition['fav_id_in'] !=''){
            $condition_str .= " and fav_id in({$condition['fav_id_in']}) ";
        }
        return Db::table("bbc_favorites")->where("1=1 and $condition_str")->delete();
    }
    /**
     * 构造检索条件
     *
     * @param array $condition 检索条件
     * @return string 字符串类型的返回结果
     */
    public function _condition($condition){
        $condition_str = '';

        if ($condition['member_id'] != ''){
            $condition_str .= " and member_id = '".$condition['member_id']."'";
        }
        if ($condition['fav_type'] != ''){
            $condition_str .= " and fav_type = '".$condition['fav_type']."'";
        }
        if ($condition['gid'] != ''){
            $condition_str .= " and gid = '".$condition['gid']."'";
        }
        if ($condition['vid'] != ''){
            $condition_str .= " and vid = '".$condition['vid']."'";
        }
        if ($condition['fav_id_in'] !=''){
            $condition_str .= " and favorites.fav_id in({$condition['fav_id_in']}) ";
        }
        return $condition_str;
    }

    public function getFavorotesNum($member_id){
        $Num=array();
        $Num['goodsNum']=$this->table('bbc_favorites,bbc_goods')->on('goods.gid = favorites.fav_id')->field('count(*) as count')->where(array('member_id'=>$member_id,'fav_type'=>'goods','goods.goods_type'=> 0))->find();
        $Num['storeNum']=$this->table('bbc_favorites,bbc_vendor')->on('vendor.vid = favorites.fav_id')->field('count(*) as count')->where(array('favorites.member_id'=>$member_id,'fav_type'=>'store','vendor.sld_is_supplier'=> 0))->find();
        $Num['BrowseHistoryNum']=$this->table('bbc_goods_browsehistory')->field('count(*) as count')->where(array('member_id'=>$member_id))->find();
        $Num['voucherNum']=$this->table('bbc_quan')->field('count(*) as count')->where(array('voucher_owner_id'=>$member_id,'voucher_state'=>array('lt',4)))->find();
        $Num['return_num']=$this->table('bbc_refund_return')->field('count(*) as count')->where(array('buyer_id'=>$member_id))->find();
        return $Num;

    }

    /**
     * add by zhengyifan 2019-09-10
     * 收藏列表
     * @param array $condition
     * @param string $fav_type
     * @param int $limit
     * @param string $order
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getFavorites($condition, $fav_type = '', $limit = 20 , $order = 'fav_time desc') {
        if ($fav_type == 'goods') {
            return DB::name('favorites')->alias('f')->join('bbc_goods g','g.gid = f.fav_id')->field('f.*')->where($condition)->order($order)->limit($limit)->select();
        }else if ($fav_type == 'store') {
            return DB::name('favorites')->alias('f')->join('bbc_vendor v','v.vid = f.fav_id')->field('f.*')->where($condition)->order($order)->limit($limit)->select();
        }else{
            return DB::name("favorites")->where($condition)->order($order)->limit($limit)->select();
        }
    }
}