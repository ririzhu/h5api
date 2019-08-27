<?php defined('DYMall') or exit('Access Invalid!');?>
<link href="<?php echo ADDONS_URL;?>data/vendor.css" rel="stylesheet" type="text/css">
<div class="tabmenu">
    <?php include template('layout/submenu');?>
</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">
    <div class="bbc_ms-form-default pin_add">
        <form id="add_form" action="<?php echo urlAddons('red_add');?>" method="post">
            <input type="hidden" name="red_type" value="5">
            <dl>
                <dt><i class="required">*</i><?php echo '红包名称:';?></dt>
                <dd>
                    <?php echo $output['info']['red_title'];?>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '红包面值:';?></dt>
                <dd>
                    <?php echo $output['info']['info']['redinfo_money'];?>元
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '红包发放数量:';?></dt>
                <dd>
                    <?php echo $output['info']['red_limit'];?>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '每人限领数量:';?></dt>
                <dd>
                    <?php echo $output['info']['red_rach_max'];?>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i>领取有效期:</dt>
                <dd>
                    <?php echo $output['info']['red_receive_start_text'];?> ~
                    <?php echo $output['info']['red_receive_end_text'];?>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i>使用有效期:</dt>
                <dd>
                    <?php echo $output['info']['info']['redinfo_start_text'];?> ~
                    <?php echo $output['info']['info']['redinfo_end_text'];?>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '优惠券使用范围:';?></dt>
                <dd>
                    <?php if($output['info']['info']['redinfo_type']){?>
                        <?php echo $output['info']['cate_str'];?>
                    <?php }else{?>
                        无限制
                    <?php }?>
                </dd>
            </dl>
            <dl id="type_select" style="display: none;">
                <dt><i class="required">*</i><?php echo '分类选择:';?></dt>
                <dd>
                    <?php foreach ($output['all_cate'] as $k=>$v){?>
                        &nbsp;&nbsp;<label for="redinfo_ids<?php echo $v['gc_id'];?>"><?php echo $v['gc_name'];?></label>
                        <input type="checkbox" name="redinfo_ids[]" value="<?php echo $v['gc_id'];?>" id="redinfo_ids<?php echo $v['gc_id'];?>" class="vm mr5" />
                    <?php }?>
                    <span></span>
                    <p class="hint">是：代表可以与店铺其他优惠活动一起使用</p>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '可以与其他优惠共用:';?></dt>
                <dd>
                    <?php if($output['info']['info']['redinfo_together']){?>
                        可以
                    <?php }else{?>
                        不可以
                    <?php }?>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '订单金额限制:';?></dt>
                <dd>
                    <?php echo $output['info']['info']['redinfo_full'];?>元
                </dd>
            </dl>
            <dl>
                <dt><?php echo '活动优惠券:';?></dt>
                <dd>

                    <?php echo $output['info']['red_front_show'] == 1?'不是':'是';?>
                </dd>
            </dl>
<!--            不是活动优惠券显示优惠券链接-->
            <?php if($output['info']['red_front_show'] == 1){?>
                <dl>
                    <dt><?php echo '优惠券链接:';?></dt>
                    <dd>
                        <?php echo C('mall_url');?>/index.php?app=red&sld_addons=red&mod=red_get_list&red_id=<?php echo $output['info']['id']; ?>
                    </dd>
                </dl>
            <?php } ?>


            <div class="bottom"><label class="submit-border">
                    <input type="button" class="submit" onclick="window.history.go(-1);" value="返回"></label>
            </div>
        </form>
    </div>
</div>
<div class="vendor_bottom_logo"><img src="<?php echo VENDOR_TEMPLATES_URL;?>/images/vendor_bottom_logo.png"/></div>
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/js/jquery-ui/themes/ui-lightness/jquery.ui.css"  />
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/js/jquery-ui-timepicker-addon/jquery-ui-timepicker-addon.min.css"  />
<script src="<?php echo STATIC_SITE_URL;?>/js/jquery.ajaxContent.pack.js"></script>
<script src="<?php echo STATIC_SITE_URL;?>/js/jquery-ui/i18n/jquery.ui.datepicker-zh-CN.js"></script>
<script src="<?php echo STATIC_SITE_URL;?>/js/jquery-ui-timepicker-addon/jquery-ui-timepicker-addon.min.js"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/fileupload/jquery.iframe-transport.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/fileupload/jquery.ui.widget.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/fileupload/jquery.fileupload.js" charset="utf-8"></script>
<script type="text/javascript">
    var SITEURL = "<?php echo BASE_VENDOR_URL; ?>";

</script>
