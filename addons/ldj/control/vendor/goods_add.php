<?php
defined('DYMall') or exit ('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/11
 * Time: 20:41
 */
class goods_addAdd extends BaseSellerCtl {

    public function __construct()
    {
        parent::__construct();
    }

    // 编辑/添加商品 存储 本店分类 一级分类
    public function save_goods($par)
    {
        $stc_ids_val = '';
        $sgcate_ids = array_unique($par['sgcate_id']);
        // 获取一级分类名称
        $my_condition['stc_id'] = array("IN",$sgcate_ids);
        $my_condition['stc_state'] = 1;
        $my_data = Model()->table('vendor_innercategory')->where($my_condition)->select();
        // 获取 父级分类ID
        $parent_ids = low_array_column($my_data,'stc_parent_id','stc_id');

        foreach ($parent_ids as $p_key => $p_value) {
            if ($p_value == 0) {
                $p_value = $p_key;
            }
            $parent_ids[$p_key] = $p_value;
        }
        // 去重
        $parent_ids = array_flip($parent_ids);
        $parent_ids = array_flip($parent_ids);
        $parent_ids = array_values($parent_ids);

        // 获取父级分类名称
        unset($my_condition);
        $my_condition['stc_id'] = array("IN",$parent_ids);
        $my_condition['stc_state'] = 1;
        $my_data = Model()->table('vendor_innercategory')->where($my_condition)->field("stc_id,stc_name")->select();
        $stc_ids = low_array_column($my_data,'stc_id');
        if (!empty($stc_ids)) {
            $stc_ids_val = implode(',', $stc_ids);
            $stc_ids_val = $stc_ids_val ? ','.$stc_ids_val.',' : $stc_ids_val;
        }

        $model_goods = model();
        if ($par['commonid']) {
            // 获取当前 commonid 下的所有 gid
            $common_condition['goods_commonid'] = $par['commonid'];
            $gids = Model('goods')->where($common_condition)->field('gid')->select();
            // 清除旧数据
            $clear_condition['goods_common_id'] = $par['commonid'];
//            $model_goods->clearGoodsExtend($clear_condition);
            $model_goods->table('ldj_goods_extend')->where($clear_condition)->delete();
            foreach ($gids as $g_key => $g_value) {
                $save_data['goods_id'] = $g_value['gid'];
                $save_data['goods_common_id'] = $par['commonid'];
                $save_data['goods_stcids_1'] = $stc_ids_val;
//                $model_goods->saveGoodsExtend($save_data);
                $model_goods->table('ldj_goods_extend')->insert($save_data);
            }
        }
    }

}