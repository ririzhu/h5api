<?php
namespace app\v1\model;

use app\v1\controller\Base;
use think\Model;
use think\db;
class EvaluateGoods extends Model
{
    public function __construct(){
        parent::__construct('evaluate_goods');
    }

    /**
     * 查询评价列表
     *
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getEvaluateGoodsList($condition, $page = null, $order = 'geval_id desc', $field = '*') {
        if((isset($condition['goods.province_id']) && $condition['goods.province_id']>0)||(isset($condition['goods.city_id']) && $condition['goods.city_id']>0)||(isset($condition['goods.area_id']) && $condition['goods.area_id']>0)){
            $list = DB::name('evaluate_goods')->join('goods','evaluate_goods.geval_goodsid=goods.gid')->field($field)->where($condition)->page($page)->order($order)->select();
        }else{

                $list = DB::name('evaluate_goods')->field($field)->where($condition)->page($page)->order($order)->select();

        }
        return $list;
    }

    /**
     * 根据编号查询商品评价
     */
    public function getEvaluateGoodsInfoByID($geval_id, $vid = 0) {
        if(intval($geval_id) <= 0) {
            return null;
        }

        $info = $this->where(array('geval_id' => $geval_id))->find();

        if($vid > 0 && intval($info['geval_storeid']) !== $vid) {
            return null;
        } else {
            return $info;
        }
    }

    /**
     * 根据商品编号查询商品评价信息
     */
    public function getEvaluateGoodsInfoByGoodsID($gid) {
        $prefix = 'evaluation_goods_info';
        $base =new Base();
        $info = $base->rcache($gid, $prefix);
        if(empty($info)) {
            $info = array();
            $good = DB::table("bbc_evaluate_goods")->field('*')->where(array('geval_goodsid'=>$gid,'geval_scores' => array('in', '4,5')))->count();
            $info['good'] = $good;
            $normal = DB::table("bbc_evaluate_goods")->field('*')->where(array('geval_goodsid'=>$gid,'geval_scores' => array('in', '2,3')))->count();
            $info['normal'] = $normal;
            $bad = DB::table("bbc_evaluate_goods")->field('*')->where(array('geval_goodsid'=>$gid,'geval_scores' => array('in', '1')))->count();
            $info['bad'] = $bad;
            $info['all'] = $info['good'] + $info['normal'] + $info['bad'];
            if(intval($info['all']) > 0) {
                $info['good_percent'] = intval($info['good'] / $info['all'] * 100);
                $info['normal_percent'] = intval($info['normal'] / $info['all'] * 100);
                $info['bad_percent'] = intval($info['bad'] / $info['all'] * 100);
                $info['good_star'] = ceil($info['good'] / $info['all'] * 5);
            } else {
                $info['good_percent'] = 100;
                $info['normal_percent'] = 0;
                $info['bad_percent'] = 0;
                $info['good_star'] = 5;
            }

            //更新商品表好评星级和评论数
            $model_goods = new Goods();
            $update = array();
            $update['evaluation_good_star'] = $info['good_star'];
            $update['evaluation_count'] = $info['all'];
            $model_goods->editGoods($update, array('gid' => $gid));
            $base->wmemcache($gid, $info, $prefix);
        }
        return $info;
    }

    /**
     * 批量添加商品评价
     */
    public function addEvaluateGoodsArray($param) {
        return $this->insertAll($param);
    }

    /**
     * 更新商品评价
     */
    public function editEvaluateGoods($update, $condition) {
        return $this->where($condition)->update($update);
    }

    /**
     * 删除商品评价
     */
    public function delEvaluateGoods($condition) {
        return $this->where($condition)->delete();
    }
}