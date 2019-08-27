<?php defined('DYMall') or exit('Access Invalid!');?>
<?php if(!empty($output['goods_list']) && is_array($output['goods_list'])){ ?>

    <ul class="search-goods-list">
        <?php foreach($output['goods_list'] as $key => $value){ ?>
            <li>
                <div class="goods-name" alt="<?php echo $value['pgoods_name'];?>"><?php echo $value['pgoods_name'];?></div>
                <div class="goods-price">积分<?php echo $value['pgoods_points']; ?></div>
                <div class="goods-pic"><img title="<?php echo $value['pgoods_name'];?>" src="<?php echo $value['pgoods_image'];?>" /></div>
                <div class="sele_input"><input type="checkbox" name="sele_goods" class="btn_add_goods" bbctype="btn_add_goods" data-goods-id="<?php echo $value['pgid'];?>" data-goods-name="<?php echo $value['pgoods_name'];?>" data-goods-price="<?php echo $value['pgoods_points'];?>" data-goods-image="<?php echo $value['pgoods_image'];?>" data-goods-marketprice="<?php echo $value['goods_marketprice']; ?>" data-promotion-type="<?php echo isset($value['promotion_type']) ? $value['promotion_type'] : ''; ?>" data-promotion-price="<?php echo isset($value['promotion_type']) ? ($value['promotion_price'] ? $value['promotion_price'] : $value['pgoods_points']) : '0.00'; ?>" data-goods-p_num="<?php echo isset($value['extend_data']['sld_team_count']) ? $value['extend_data']['sld_team_count'] : ''; ?>" data-goods-end_time="<?php echo isset($value['extend_data']['sld_end_time']) ? $value['extend_data']['sld_end_time'] : ''; ?>"  data-goods-buyed_quantity="<?php echo isset($value['extend_data']['buyed_quantity']) ? $value['extend_data']['buyed_quantity'] : '0'; ?>" href="javascript:;">
                </div>
            </li>
        <?php } ?>
    </ul>
    <div class="add_goods_zdy"><span class="has_sele">已选择 <span class="sele_num">0</span> 个商品</span><span class="add_goods_pic"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/add_goods.png"></span></div>
    <div id="goods_pagination" class="pagination"> <?php echo $output['show_page'];?> </div>


<?php }else { ?>
    <div>
        <p class="no-record"><?php echo $lang['bbc_no_record'];?></p>
        <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/no_goods_tip.png">
    </div>
<?php } ?>
<script type="text/javascript">
    $(document).ready(function(){
        $('#goods_pagination').find('.demo').ajaxContent({
            event:'click',
            loaderType:"img",
            loadingMsg:"<?php echo ADMIN_TEMPLATES_URL;?>/images/transparent.gif",
            target:'.mb_special_goods_list'
        });
    });
</script>
