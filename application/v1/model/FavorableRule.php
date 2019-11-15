<?php
namespace app\v1\model;

use think\Model;
use think\db;
class FavorableRule extends Model
{
    public function __construct(){
        parent::__construct('p_mansong_rule');
    }

    /**
     * 读取满即送规则列表
     * @param array $mansong_id 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 满即送套餐列表
     *
     */
    public function getMansongRuleListByID($mansong_id) {
        $condition = array();
        $condition['mansong_id'] = $mansong_id;
        $mansong_rule_list = DB::table("bbc_p_mansong_rule")->where($condition)->order('price desc')->select();
        if(!empty($mansong_rule_list)) {
            $model_goods = new Goods();

            for($i =0, $j = count($mansong_rule_list); $i < $j; $i++) {
                $gid = intval($mansong_rule_list[$i]['gid']);
                if(!empty($gid)) {
                    $goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$gid));
                    if(!empty($goods_info)) {
                        if(empty($mansong_rule_list[$i]['mansong_goods_name'])) {
                            $mansong_rule_list[$i]['mansong_goods_name'] = $goods_info['goods_name'];
                        }
                        $mansong_rule_list[$i]['goods_image'] = $goods_info['goods_image'];
                        $mansong_rule_list[$i]['goods_image_url'] = cthumb($goods_info['goods_image'], $goods_info['vid']);
                        $mansong_rule_list[$i]['goods_storage'] = $goods_info['goods_storage'];
                        $mansong_rule_list[$i]['gid'] = $gid;
                        $mansong_rule_list[$i]['goods_url'] = urlShop('goods', 'index', array('gid' => $gid));
                        $mansong_rule_list[$i]['goods_price'] = $goods_info['goods_price'];
                    }
                }
            }
        }
        return $mansong_rule_list;
    }

    /*
     * 增加
     * @param array $param
     * @return bool
     *
     */
    public function addMansongRule($param){
        return $this->insert($param);
    }

    /*
     * 批量增加
     * @param array $array
     * @return bool
     *
     */
    public function addMansongRuleArray($array){
        return $this->insertAll($array);
    }

    /*
     * 删除
     * @param array $condition
     * @return bool
     *
     */
    public function delMansongRule($condition){
        return $this->where($condition)->delete();
    }
}