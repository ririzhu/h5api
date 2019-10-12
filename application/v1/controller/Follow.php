<?php
namespace app\v1\controller;

use app\v1\model\Favorites;
use app\v1\model\GoodsActivity;
use app\v1\model\Stats;
use app\v1\model\UserCart;
use app\v1\model\VendorInfo;

class Follow extends Base
{

    /**
     * 增加商品收藏
     */
    public function followgoods(){
        if(!input("member_id") || !input("gid") || !input("token")){
            $data['error_code']=10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $fav_id = intval(input('gid'));
        $token = input("token");
        if ($fav_id <= 0){
            echo json_encode(array('done'=>false,'msg'=>lang('收藏失败','UTF-8')));
            die;
        }
        $favorites_model = new Favorites();
        //判断是否已经收藏
        $favorites_info = $favorites_model->getOneFavorites(array('fav_id'=>"$fav_id",'fav_type'=>'goods','member_id'=>input("member_id")));
        if(!empty($favorites_info)){
            echo json_encode(array('done'=>false,'msg'=>lang('您已收藏过该商品','UTF-8')));
            die;
        }
        //判断商品是否为当前会员所有
        $goods_model = new \app\v1\model\Goods();
        $goods_info = $goods_model->getGoodsInfo(array('gid' => $fav_id));
        if ($goods_info['vid'] == input("member_id")){
            echo json_encode(array('done'=>false,'msg'=>lang('不能收藏自己的商品','UTF-8')));
            die;
        }
        //添加收藏
        $insert_arr = array();
        $insert_arr['member_id'] = input("member_id");
        $insert_arr['fav_id'] = $fav_id;
        $insert_arr['fav_type'] = 'goods';
        $insert_arr['fav_time'] = time();
        $result = $favorites_model->addFavorites($insert_arr);


        $fav_id = '';
        //收藏统计记录
        $stats = new Stats();
        $stats->put_goods_stats(1,$goods_info['gid'],'favorite',$token,1,input("member_id"));
        if(!input("cart_id")) {
            if ($result) {
                //增加收藏数量
                $goods_model->editGoods(array('goods_collect' => array('inc', 'goods_collect + 1')), array('gid' => $fav_id));
                echo json_encode(array('done' => true, 'msg' => lang('收藏成功', 'UTF-8')));
                die;
            } else {
                echo json_encode(array('done' => false, 'msg' => lang('收藏失败', 'UTF-8')));
                die;
            }
        }else{
            if(!input("cart_id") || !input("is_supplier") || !input("member_id")|| !input("gid")|| !input("ismini")){
                $data['error_code']=10016;
                $data['message'] = "缺少参数";
                return json_encode($data);exit;
            }
            $memberId = input("member_id");
            $cart_id = intval(input("cart_id"));
            $gid = intval(input("gid"));
            $is_supplier = isset($_POST["is_supplier"]) ? intval(input("is_supplier")) : 0;
            $ismini = strip_tags(trim($_POST['ismini']));
            if($cart_id < 0 || $gid < 0) return ;
            $model_cart	= new UserCart();
            $data = array();
            $delete	= $model_cart->delCart('db',array('cart_id'=>$cart_id,'buyer_id'=>$memberId,'is_supplier'=>$is_supplier),['ismini'=>$ismini]);
            if($delete) {
                $data['error_code'] = 200;
                $data['message'] = "移入收藏夹成功";
            } else {
                $data['error_code'] = 10022;
                $data["message"] = "删除失败";
            }


            exit(json_encode($data,true));
        }
    }
    /**
     * 增加店铺收藏
     */
    public function followvendor(){
        if(!input("member_id") || !input("vid") || !input("token")){
            $data['error_code']=10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $memberId = input("member_id");
        $fav_id = input("vid");
        if ($fav_id <= 0){
            echo json_encode(array('done'=>false,'msg'=>lang('收藏失败','UTF-8')));
            die;
        }
        $favorites_model = new Favorites();
        //判断是否已经收藏
        $favorites_info = $favorites_model->getOneFavorites(array('fav_id'=>"$fav_id",'fav_type'=>'store','member_id'=>input("member_id")));
        if(!empty($favorites_info)){
            echo json_encode(array('done'=>false,'msg'=>lang('您已收藏过该店铺','UTF-8')));
            die;
        }
        //判断店铺是否为当前会员所有
        if ($fav_id == $memberId){
            echo json_encode(array('done'=>false,'msg'=>lang('不能收藏自己的店铺','UTF-8')));
            die;
        }
        //添加收藏
        $insert_arr = array();
        $insert_arr['member_id'] = $memberId;
        $insert_arr['fav_id'] = $fav_id;
        $insert_arr['fav_type'] = 'store';
        $insert_arr['fav_time'] = time();
        $result = $favorites_model->addFavorites($insert_arr);
        if ($result){
            //增加收藏数量
            $store_model = new VendorInfo();
            $store_model->editStore(array('store_collect'=>array('inc', 'store_collect+1')), array('vid' => $fav_id));
            echo json_encode(array('done'=>true,'msg'=>lang('收藏成功','UTF-8')));
            die;
        }else{
            echo json_encode(array('done'=>false,'msg'=>lang('收藏失败','UTF-8')));
            die;
        }
    }

    /**
     * 商品收藏列表
     *
     * @param
     * @return
     */
    public function fglist(){
        if(!input("member_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data);
        }
        $page = input("page",0);
        $data['error_code'] = 200;
        $memberId = input("member_id");
        $favorites_model = new Favorites();
        $show_type = 'favorites_goods_picshowlist';//默认为图片横向显示
        //$show = $_GET['show'];
        $store_array = array('list'=>'favorites_goods_index','pic'=>'favorites_goods_picshowlist','store'=>'favorites_goods_shoplist');
        //if (array_key_exists($show,$store_array)) $show_type = $store_array[$show];

        $favorites_list = $favorites_model->getGoodsFavoritesList(array('member_id'=>$memberId), '*', $page);
        foreach($favorites_list as $k=>$v){
            $favorites_list[$k]['count'] = ($favorites_model->getOneFavorites(array("goods_name"=>$v['goods_name']),'count(1) as count'))['count'];
        }
        //Template::output('show_page',$favorites_model->showpage(2));
        if (!empty($favorites_list) && is_array($favorites_list)){
            $favorites_id = array();//收藏的商品编号
            $ids = "";
            foreach ($favorites_list as $key=>$favorites){
                $fav_id = $favorites['fav_id'];
                $favorites_id[] = $favorites['fav_id'];
                $ids .=$favorites['fav_id'].",";
                $favorites_key[$fav_id] = $key;
            }
            $ids =substr($ids,0,strlen($ids)-1);
            $goods_model = new \app\v1\model\Goods();
            $field = 'goods.gid,goods.goods_name,goods.vid,goods.goods_image,goods.goods_price,goods.evaluation_count,goods.goods_salenum,goods.goods_collect,goods.goods_price,vendor.store_name,vendor.member_id,vendor.member_name,vendor.store_qq,vendor.store_ww,vendor.store_domain';
            $goods_list = $goods_model->getGoodsStoreList(array('gid' => array('in', $ids)), $field);

            // 获取最终价格
            $goodsActivityModel = new GoodsActivity();
            $goods_list = $goodsActivityModel->rebuild_goods_data($goods_list,'pc');

            $store_array = array();//店铺编号
            if (!empty($goods_list) && is_array($goods_list)){
                $store_goods_list = array();//店铺为分组的商品
                foreach ($goods_list as $key=>$fav){
                    $fav_id = $fav['gid'];
                    $fav['goods_member_id'] = $fav['member_id'];
                    $key = $favorites_key[$fav_id];
                    $favorites_list[$key]['goods'] = $fav;
                    $vid = $fav['vid'];
                    if (!in_array($vid,$store_array)) $store_array[] = $vid;
                    $store_goods_list[$vid][] = $favorites_list[$key];
                }
            }
            //print_r($store_goods_list);die;
            $data['store_goods_list']=$store_goods_list;
            $store_favorites = array();//店铺收藏信息
            if (!empty($store_array) && is_array($store_array)){
                $store_list = $favorites_model->getStoreFavoritesList(array('bbc_favorites.member_id'=>$memberId, 'fav_id'=> array('in', arrayToString($store_array))));
                if (!empty($store_list) && is_array($store_list)){
                    foreach ($store_list as $key=>$val){
                        $vid = $val['fav_id'];
                        $store_favorites[] = $vid;
                    }
                }
            }
            $data['favorites_list'] = $favorites_list;

            $data['store_favorites']=$store_favorites;
        }
        return json_encode($data,true);
    }
    /**
     * 店铺收藏列表
     *
     * @param
     * @return
     */
    public function fvlist(){
        if(!input("member_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data);
        }
        $inputorder = input("order",0);
        if($inputorder==0){
            $order = "log_id desc";
        }
        else{
            $order = "sale desc";
        }
        $page = input("page",0);
        $memberId = input("member_id");
        $data['error_code'] = 200;
        $favorites_model = new Favorites();
        $favorites_list = $favorites_model->getStoreFavoritesList(array('bbc_favorites.member_id'=>$memberId), '*', $page,$order);
        if (!empty($favorites_list) && is_array($favorites_list)){
            $favorites_id = array();//收藏的店铺编号
            foreach ($favorites_list as $key=>$favorites){
                $fav_id = $favorites['fav_id'];
                $favorites_id[] = $favorites['fav_id'];
                $favorites_key[$fav_id] = $key;
                $favorites_list[$key]['count'] = ($favorites_model->getOneFavorites(array("vid"=>$favorites['vid']),'count(1) as count'))['count'];
            }
            $store_model = new VendorInfo();
            $store_list = $store_model->getStoreList(array('vid'=>array('in', arrayToString($favorites_id))));
            if (!empty($store_list) && is_array($store_list)){
                foreach ($store_list as $key=>$fav){
                    $fav_id = $fav['vid'];
                    $key = $favorites_key[$fav_id];
                    $favorites_list[$key]['store'] = $fav;
                }
            }
        }
        $data['favorites_list']=$favorites_list;
        return json_encode($data);
    }
    /**
     * 删除收藏
     *
     * @param
     * @return
     */
    public function delfollow(){
        if (!input('fav_id') || !input('type') || !input("member_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang('删除失败');
        }
        if (!preg_match_all('/^[0-9,]+$/',input('fav_id'), $matches)) {
            $data['error_code'] = 10016;
            $data['message'] = lang('参数错误');
        }
        $fav_id = trim(input('fav_id'),',');
        if (!in_array(input('type'), array('goods', 'store'))) {
            $data['error_code'] = 10016;
            $data['message'] = lang(lang('参数错误'));
        }
        $type = input("type");
        $memberId = input("member_id");
        $favorites_model = new Favorites();
        $fav_arr = explode(',',$fav_id);
        if (!empty($fav_arr) && is_array($fav_arr)){
            //批量删除
            $fav_str = "'".implode("','",$fav_arr)."'";
            $result = $favorites_model->delFavorites(array("fav_id_in"=>arrayToString($fav_arr),'fav_type'=>"$type",'member_id'=>"$memberId"));
            if ($result){
                //剔除删除失败的记录
                $favorites_list = $favorites_model->getFavoritesList(array('fav_id'=>array('in', $fav_arr),'fav_type'=>"$type",'member_id'=>$memberId));
                if (!empty($favorites_list)){
                    foreach ($favorites_list as $k=>$v){
                        unset($fav_arr[array_search($v['fav_id'],$fav_arr)]);
                    }
                }
                if (!empty($fav_arr)){
                    if ($type=='goods'){
                        //更新收藏数量
                        $goods_model = new \app\v1\model\Goods();
                        $goods_model->editGoods(array('goods_collect'=>array('exp', 'goods_collect - 1')), array('gid' => array('in', $fav_arr)));
                        $data['error_code'] = 200;
                        $data['message'] = lang('删除成功');
                    }else {
                        $fav_str = "'".implode("','",$fav_arr)."'";
                        //更新收藏数量
                        $store_model = new VendorInfo();
                        $store_model->editStore(array('store_collect'=>array('exp', 'store_collect - 1')),array('vid'=>array('in', $fav_str)));
                        $data['error_code'] = 200;
                        $data['message'] = lang('删除成功');
                    }
                }
            }else {
                $data['error_code'] = 200;
                $data['message'] = lang('删除失败');
            }

        }else {
            $data['error_code'] = 200;
            $data['message'] = (lang('删除失败'));
        }
        return json_encode($data);
    }
    /**
     * 店铺新上架的商品列表
     *
     * @param
     * @return
     */
    public function store_goods(){
        $vid = intval($_GET["vid"]);
        if($vid > 0) {
            $condition = array();
            $add_time_from = TIMESTAMP-60*60*24*30;//30天
            $condition['vid'] = $vid;
            $condition['goods_addtime']	= array('between', $add_time_from.','.TIMESTAMP);
            $goods_model = Model('goods');
            $goods_list = $goods_model->getGoodsOnlineList($condition,'gid,goods_name,vid,goods_image,goods_price', 0, 'gid desc', 50);
            //Template::output('goods_list',$goods_list);
            //Template::showpage('favorites_store_goods','null_layout');
        }
    }
    /**
     * 用户中心右边，小导航
     *
     * @param string	$menu_type	导航类型
     * @param string 	$menu_key	当前导航的menu_key
     * @return
     */
    private function profile_menu($menu_type,$menu_key='') {
        $menu_array = array(
            1=>array('menu_key'=>'fav_goods','menu_name'=>lang('收藏商品'),	'menu_url'=>'index.php?app=userfollow&mod=fglist'),
            2=>array('menu_key'=>'fav_store','menu_name'=>lang('关注店铺'), 'menu_url'=>'index.php?app=userfollow&mod=fvlist')
        );
        //Template::output('member_menu',$menu_array);
        //Template::output('menu_key',$menu_key);
    }
}