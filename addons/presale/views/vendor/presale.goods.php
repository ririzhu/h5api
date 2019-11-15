<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/26
 * Time: 22:25
 */
?>

<?php if(!empty($output['goods_list']) && is_array($output['goods_list'])){?>
    <ul class="goods-list">
        <?php foreach($output['goods_list'] as $key=>$val){?>
            <li>
                <div class="goods-thumb"><img src="<?php echo thumb($val);?>"/></div>
                <dl class="goods-info">
                    <dt><?php echo $val['goods_name'];?></dt>
                    <dd><?php echo $lang['currency'].$val['goods_price'];?>
                        <a bbctype="btn_add_tuan_goods" data-goods-commonid="<?php echo $val['goods_commonid'];?>" href="javascript:void(0);" class="bbc_ms-btn-mini fr bbc_ms-btn-green">选择</a>
                    </dd>
                </dl>
            </li>
        <?php } ?>
    </ul>
    <div class="pagination"><?php echo $output['show_page']; ?></div>
<?php } else { ?>
    <div><?php echo $lang['无数据'];?></div>
<?php } ?>
