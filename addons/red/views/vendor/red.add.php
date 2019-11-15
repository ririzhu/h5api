<?php defined('DYMall') or exit('Access Invalid!');?>
<link href="<?php echo ADDONS_URL;?>data/vendor.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/js/jquery-ui/themes/ui-lightness/jquery.ui.css"  />
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/jquery.ajaxContent.pack.js"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/jquery-ui/jquery.ui.js"></script>
<style>
    .pic_list .small_pic ul li {
        height: 100px;
    }
    .ui-sortable-helper {
        border: dashed 1px #F93;
        box-shadow: 2px 2px 2px rgba(153,153,153, 0.25);
        filter: alpha(opacity=75);
        -moz-opacity: 0.75;
        opacity: .75;
        cursor: ns-resize;
    }
    .ui-sortable-helper td {
        background-color: #FFC !important;
    }
    .ajaxload {
        display: block;
        width: 16px;
        height: 16px;
        margin: 100px 300px;
    }
</style>
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
                    <input class=" text w300" name="red_title" type="text" id="red_title" maxlength="30"  />
                    <span></span>
                    <p class="hint">红包名称不能超过30个字符</p>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '红包面值:';?></dt>
                <dd>
                    <input class=" text w60" name="redinfo_money" type="text" id="redinfo_money" maxlength="10"  /> 元
                    <span></span>
                    <p class="hint">单个红包的面值</p>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '红包发放数量:';?></dt>
                <dd>
                    <input class=" text w60" name="red_limit" type="text" id="red_limit" maxlength="10"  />
                    <span></span>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i><?php echo '每人限领数量:';?></dt>
                <dd>
                    <input class=" text w60" name="red_rach_max" type="text" id="red_rach_max" maxlength="10" value="1"  />
                    <span></span>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i>领取有效期:</dt>
                <dd>
                    <input id="red_receive_start" name="red_receive_start" type="text" class="text w130" /><em class="add-on"><i class="iconfontfa fa-calendar"></i></em>  至
                    <input id="red_receive_end" name="red_receive_end" type="text" class="text w130"/><em class="add-on"><i class="iconfontfa fa-calendar"></i></em>
                    <span style="display: block;"></span>
                    <p class="hint"></p>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i>使用有效期:</dt>
                <dd>
                    <input id="redinfo_start" name="redinfo_start" type="text" class="text w130" /><em class="add-on"><i class="iconfontfa fa-calendar"></i></em>  至
                    <input id="redinfo_end" name="redinfo_end" type="text" class="text w130"/><em class="add-on"><i class="iconfontfa fa-calendar"></i></em>
                    <span style="display: block;"></span>
                    <p class="hint"></p>
                </dd>
            </dl>
            <dl style="display: none;">
                <dt><i class="required">*</i><?php echo '优惠券使用范围:';?></dt>
                <dd>
                    <label for="redinfo_type0">所有</label>
                    <input type="radio" name="redinfo_type" checked="checked" value="0" id="redinfo_type0" class="vm mr5 chk_type" />
                    <label for="redinfo_type1">指定商品</label>
                    <input type="radio" name="redinfo_type" value="2" id="redinfo_type1" class="vm mr5 chk_type" />

                    <span></span>
                    <p class="hint"></p>
                </dd>
            </dl>
            <dl id="type_select" style="display: none;">
                <dt><i class="required">*</i><?php echo '商品ID:';?></dt>
                <dd>
                    <textarea type="text" name="redinfo_ids" value="" id="redinfo_ids" class="vm mr5" readonly></textarea>
                    <span>输入商品的gid,以','号分割,例:1,2,3</span>
                </dd>
                <dt>
                </dt>
                <dd>
                    <p>
                        <input id="bundling_goods" type="hidden" value="" name="bundling_goods">
                        <span></span>
                    </p>
                    <a id="bundling_add_goods" href="<?php echo BASE_VENDOR_URL;?>/index.php?app=action_red&mod=bundling_add_goods&sld_addons=red" class="bbc_ms-btn bbc_ms-btn-acidblue">添加商品</a>
                    <div class="div-goods-select-box">
                        <div id="bundling_add_goods_ajaxContent"></div>
                        <a id="bundling_add_goods_delete" class="close" href="javascript:void(0);" style="display: none; right: -10px;">X</a>
                    </div>
                </dd>
            </dl>
<!--           <dl id="type_select" style="display: none;">-->
<!--                <dt><i class="required">*</i>--><?php //echo '分类选择:';?><!--</dt>-->
<!--                <dd>-->
<!--                    --><?php //foreach ($output['all_cate'] as $k=>$v){?>
<!--                        &nbsp;&nbsp;<label for="redinfo_ids--><?php //echo $v['gc_id'];?><!--">--><?php //echo $v['gc_name'];?><!--</label>-->
<!--                        <input type="checkbox" name="redinfo_ids[]" value="--><?php //echo $v['gc_id'];?><!--" id="redinfo_ids--><?php //echo $v['gc_id'];?><!--" class="vm mr5" />-->
<!--                    --><?php //}?>
<!--                    <span></span>-->
<!--                   <p class="hint">是：代表可以与店铺其他优惠活动一起使用</p>-->
<!--                </dd>-->
<!--            </dl>-->
<!--            <dl>-->
<!--                <dt><i class="required">*</i>--><?php ///*echo '与其他优惠共用:';*/?><!--</dt>-->
<!--                <dd>-->
<!--                    <label for="sld_status1">是</label>-->
<!--                    <input type="radio" name="redinfo_together" checked value="1" id="sld_status1" class="vm mr5" />-->
<!--                    <label for="sld_status0">否</label>-->
<!--                    <input type="radio" name="redinfo_together" value="0" id="sld_status0" class="vm mr5" />-->
<!--                    <span></span>-->
<!--                    <p class="hint">是：代表可以与店铺其他优惠活动一起使用</p>-->
<!--                </dd>-->
<!--            </dl>-->
            <dl>
                <dt><i class="required">*</i><?php echo '订单金额限制:';?></dt>
                <dd>
                    <input class=" text w60" name="redinfo_full" type="text" id="redinfo_full" maxlength="10"  /> 元
                    <span></span>
                    <p class="hint">满多少才可以使用</p>
                </dd>
            </dl>


            <div class="bottom"><label class="submit-border">
                    <input type="submit" class="submit" value="<?php echo $lang['提交'];?>"></label>
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

<script src="<?php echo STATIC_SITE_URL;?>/js/common.js"></script>
<script src="<?php echo VENDOR_RESOURCE_SITE_URL;?>/js/vendor_bundling.js"></script>
<script src="<?php echo STATIC_SITE_URL;?>/js/jquery.poshytip.min.js"></script>
<script type="text/javascript">
    var SITEURL = "<?php echo BASE_VENDOR_URL; ?>";

    $(document).ready(function(){

        $('#red_receive_start').datetimepicker({
            controlType: 'select'
        });

        $('#red_receive_end').datetimepicker({
            controlType: 'select'
        });

        $('#redinfo_start').datetimepicker({
            controlType: 'select'
        });

        $('#redinfo_end').datetimepicker({
            controlType: 'select'
        });

        $(".chk_type").change(function () {
            if($('.chk_type:radio:checked').val()==2){
                $("#type_select").show();
            }else{
                $("#type_select").hide();
            }
        });

        jQuery.validator.methods.greaterThanStartDate = function(value, element) {
            var sdate = $("#red_receive_start").val();
            var date1 = new Date(Date.parse(sdate.replace(/-/g, "/")));
            var date2 = new Date(Date.parse(value.replace(/-/g, "/")));
            return date1 < date2;
        };

        jQuery.validator.methods.greaterThanStartDate2 = function(value, element) {
            var sdate = $("#redinfo_start").val();
            var date1 = new Date(Date.parse(sdate.replace(/-/g, "/")));
            var date2 = new Date(Date.parse(value.replace(/-/g, "/")));
            return date1 < date2;
        };

        //页面输入内容验证
        $("#add_form").validate({
            errorPlacement: function(error, element){
                var error_td = element.parent('dd').children('span');
                error_td.append(error);
            },
            submitHandler:function(form){
                ajaxpost('add_form', '', '', 'onerror');
            },
            rules : {
                red_receive_start : {
                    required : true
                },
                red_receive_end : {
                    required : true,
                    greaterThanStartDate : true
                },
                redinfo_start : {
                    required : true
                },
                redinfo_end : {
                    required : true,
                    greaterThanStartDate2 : true
                },
                red_title: {
                    required : true,
                    maxlength : 30
                },
                redinfo_money: {
                    required : true,
                    min : 0.01,
                    max : 9999
                },
                redinfo_full: {
                    required : true,
                    min : 0.01,
                    max : 9999
                },
                red_limit : {
                    required : true,
                    min : 1,
                    digits:true
                },
                red_rach_max : {
                    required : true,
                    min : 1,
                    digits:true
                }
            },
            messages : {
                red_receive_start : {
                    required : '领取开始时间不能为空 '
                },
                red_receive_end : {
                    required : '领取结束时间不能为空 ',
                    greaterThanStartDate : '领取结束时间必须大于开始时间 '
                },
                redinfo_start : {
                    required : '使用开始时间不能为空 '
                },
                redinfo_end : {
                    required : '使用结束时间不能为空 ',
                    greaterThanStartDate2 : '使用结束时间必须大于开始时间 '
                },
                red_title : {
                    required : '优惠券名称不能为空',
                    maxlength : '不能超过30个字符'
                },
                redinfo_money : {
                    required : '优惠券面值不能为空',
                    min : '不能小于0.01',
                    max : '不能大于9999'
                },
                redinfo_full : {
                    required : '订单金额限制不能为空',
                    min : '不能小于0.01',
                    max : '不能大于9999'
                },
                red_limit : {
                    required : '发放数量不能为空',
                    min : '不能小于1',
                    digits : '必须是整数'
                },
                red_rach_max : {
                    required : '每人领取不能为空',
                    min : '不能小于1',
                    digits : '必须是整数'
                }
            }
        });


    });

</script>
