<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/26
 * Time: 22:06
 */
?>
<link href="<?php echo ADDONS_URL; ?>data/vendor.css" rel="stylesheet" type="text/css">
<style>
    .clearfix{
        display:inline-block!important;
    }
    .p_list {
        padding-bottom: 30px;
        /*border-bottom: 1px solid #E6E6E6;*/
        /*padding-left: 30px;*/
    }

    .p_list:first-child {
        margin-top: 30px;
    }

    .p_list:last-child {
        border: none;
    }

    .p_left, .p_right {
        float: left;
    }

    .p_left {
        width: 188px;
        margin-right: 25px;
    }

    .p_left .p_goods {
        width: 188px;
        min-height: 217px;
        background-color: #fff;
        box-shadow: 0 0 3px #999;
    }

    .p_goods .img {
        position: relative;
        width: 100%;
        height: 170px;
    }

    .p_goods .img img {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        max-width: 100%;
        max-height: 100%;
    }

    .p_goods .name, .p_goods .price {
        padding-left: 10px;
        font-size: 12px;
        line-height: 20px;
    }

    .p_goods .name {
        color: #0082F1;
    }

    .p_goods .price {
        color: #666;
    }

    .p_in_item {
        margin-top: 10px;
        color: #666;
        font-size: 12px;
    }

    .p_in_item p {
        line-height: 30px;
    }

    .p_in_item input {
        width: 168px;
        line-height: 35px;
        padding-left: 8px;
        border: 1px solid #d2cece;
        box-sizing: border-box;
    }

    .p_right .jt_setting {
        line-height: 44px;
        color: #333;
        font-size: 13px;
        font-weight: 600;
    }

    .p_right .p_btns {
        margin-bottom: 10px;
    }

    .p_btns button {
        width: 66px;
        height: 26px;
        color: #666666;
        border: 1px solid #EBEBEB;
        -webkit-border-radius: 2px;
        -moz-border-radius: 2px;
        border-radius: 2px;
        margin-right: 10px;
    }

    .p_btns button.blue {
        background-color: #0082F1;
        color: #fff !important;
        border-color: #0082F1;
    }

    .p_right .p_tab {
        width: 783px;
    }

    .p_tab {
        font-size: 11px;
        color: #333333;
        border-width: 1px;
        border-color: #E6E6E6;
        border-collapse: collapse;
    }

    .p_tab th {
        border-width: 1px;
        padding: 8px;
        border-style: solid;
        border-color: #E6E6E6;
    }

    .p_tab td {
        border-width: 1px;
        padding: 8px;
        border-style: solid;
        border-color: #E6E6E6;
        text-align: center;
    }

    .p_tab td input {
        text-align: center;
        border: none;
    }

    .p_tab td:nth-child(1), .p_tab th:nth-child(1) {
        width: 84px;
        font-weight: 600;
    }

    .p_tab td:nth-child(2), .p_tab th:nth-child(2) {
        width: 254px;
    }

    .p_tab td:nth-child(3), .p_tab th:nth-child(3) {
        width: 239px;
    }

    .p_tab td:nth-child(3) {
        color: #FF2626;
    }

    .p_tab td a {
        text-decoration: none;
        color: #0082F1;
        font-size: 13px;
        margin-right: 30px;
    }

    .p_tab td a:last-child {
        margin: 0;
    }
</style>
<div class="tabmenu">
    <ul class="tab pngFix">
        <li class="normal"><a href="index.php?app=presale&mod=index&sld_addons=presale">预售列表</a></li>
        <li class="active"><a href="javascript:void(0)"><?php if(isset($_GET['id']) && !empty($_GET['id'])){?>预售编辑<?php }else{?>新增预售<?php }?></a></li>
    </ul>
</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">
    <div class="bbc_ms-form-default pin_add">
        <form id="add_form" action="<?php echo urlAddons('save'); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" value="<?php echo $output['presale_info']['pre_id']; ?>" name="id">
            <dl>
                <dt><i class="required">*</i>活动图片:</dt>
                <dd class="wq_upfile">
                    <div class="bbc_ms-upload-thumb voucher-pic" style="width: 215px;">
                        <p style="width: 215px;"><i class="fa-image iconfontfa add_iconcolor"></i>
                            <img bbctype="img_tuan_image" width="100%"
                                 <?php if (!$output['presale_info']['pre_pic']){ ?>style="display:none;"<?php } ?>
                                 src="<?php echo gthumb($output['presale_info']['pre_pic'], 'max'); ?>"/></p>
                    </div>
                    <input bbctype="tuan_image" name="tuan_image" type="hidden"
                           value="<?php echo $output['presale_info']['pre_pic']; ?>" id="tuan_image">
                    <div class="bbc_ms-upload-btn">
                        <a href="javascript:void(0);">
                <span style="width: 215px;">
                    <input style="width:100%;" type="file" hidefocus="true" size="1" class="input-file"
                           name="tuan_image" bbctype="btn_upload_image"/>
                </span>

                        </a>
                    </div>
                    <span></span>
                    <p class="hint">用于预售活动页面的图片,请使用宽度730*340像素、大小1M内的图片，支持jpg、jpeg、gif、png格式上传。</p>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i>预售类别:</dt>
                <dd>
                    <select id="class_id" name="class_id" class="w80">
                        <?php if (is_array($output['class_list'])) { ?>
                            <?php foreach ($output['class_list'] as $tuan_class) { ?>
                                <option <?php echo $output['presale_info']['pre_category'] == $tuan_class['id'] ? 'selected' : ''; ?>
                                    value="<?php echo $tuan_class['id']; ?>"><?php echo $tuan_class['class_name']; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                    <span></span>
                    <p class="hint">请选择预售商品的所属类别</p>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i>预售有效期:</dt>
                <dd>
                    <input id="start_time" name="pre_start_time"
                           value="<?php echo $output['presale_info']['pre_start_time']; ?>" type="text" class="text w130"/><em
                        class="add-on"><i class="iconfontfa fa-calendar"></i></em> 至
                    <input id="end_time" name="pre_end_time" value="<?php echo $output['presale_info']['pre_end_time']; ?>"
                           type="text" class="text w130"/><em class="add-on"><i class="iconfontfa fa-calendar"></i></em>
                    <span style="display: block;"></span>
                    <p class="hint"></p>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i>限购:</dt>
                <dd>
                    <input class=" text w60" name="pre_max_buy" type="text" id="sld_max_buy"
                           value="<?php echo $output['presale_info']['pre_max_buy']>0 ? $output['presale_info']['pre_max_buy'] : 1; ?>" /> 件
                    <span></span>
                    <p class="hint">为0则不限购买数量</p>
                </dd>
            </dl>
<!--            <dl>
                <dt><i class="required">*</i>退还定金:</dt>
                <dd>
                    否:<input type="radio" name="is_tui"
                             value="0" <?php /*echo $output['presale_info']['is_tui'] == 0 ? 'checked' : ''; */?>>
                    是: <input type="radio" name="is_tui"
                              value="1" <?php /*echo $output['presale_info']['is_tui'] == 1 ? 'checked' : ''; */?>>
                    <p class="hint">是否退换定金(针对库存不足或未达到第一阶梯情况下)</p>
                </dd>
            </dl>-->
            <dl>
                <dt><i class="required">*</i>支付时间:</dt>
                <dd>
                    <input class=" text w60" name="pre_limit_time" type="text" id="sld_success_time"
                           value="<?php echo $output['presale_info']['pre_limit_time'] ? $output['presale_info']['pre_limit_time'] : 24; ?>" /> 小时
                    <span></span>
                    <p class="hint">限制时间内交付尾款成功</p>
                </dd>
            </dl>

            <dl>
                <dt><i class="required">*</i>活动商品:</dt>
                <dd style="position: relative;">
                    <a href="javascript:void(0);" id="btn_show_search_goods"
                       class="bbc_ms-btn bbc_ms-btn-acidblue">选择商品</a>
                    <input id="tuan_goods_id" name="tuan_goods_id" type="hidden"
                           value="<?php echo $output['presale_info']['pre_goods_commonid']; ?>"/>
                    <span></span>
                    <div id="div_search_goods" class="div-goods-select mt10 slideDown">
                        <table class="search-form" style="margin-bottom: 0">
                            <tr>
                                <th class="w150">
                                    <strong>第一步：搜索店内商品</strong>
                                </th>
                                <td class="w160">
                                    <input id="search_goods_name" type="text w150" class="text" value=""/>
                                </td>
                                <td class="w70 tc">
                                    <a href="javascript:void(0);" id="btn_search_goods" class="bbc_ms-btn"/><i
                                        class="icon-search"></i><?php echo $lang['搜索']; ?></a></td>
                                <td class="w10"></td>
                                <td>
                                    <p class="hint">不输入名称直接搜索将显示店内所有出售中的商品</p>
                                </td>
                            </tr>
                        </table>
                        <div id="div_goods_search_result" class="search-result"></div>
                        <a id="btn_hide_search_goods" class="close" href="javascript:void(0);">X</a>
                    </div>
                    <p class="hint">请选择预售商品</p>
                </dd>
            </dl>
            <dl>
                <dt><i class="required">*</i>产品设置:</dt>
                <dd>
                    <div class="mutiGoods_panel">
                        <div class="mutiGoods">
                            <?php if (is_array($output['presale_info']['goods_list'])) { ?>
                                <?php foreach ($output['presale_info']['goods_list'] as $g_info) { ?>
                                    <div class="p_list clearfix">
                                        <ul>
                                            <li>
                                                <div bbctype="tuan_goods_info" class="selected-group-goods">
                                                    <div class="goods-thumb"><img id="tuan_goods_image" src="<?php echo cthumb($g_info['goods_image']); ?>">
                                                    </div>
                                                    <div class="goods-name">
                                                        <a bbctype="tuan_goods_href"
                                                           title="<?php echo $g_info['goods_name']; ?>"
                                                           id="tuan_goods_name"
                                                           href="<?php echo C('main_url').'/index.php?app=goods&gid='.$g_info['gid']; ?>"
                                                           target="_blank"><?php echo $g_info['goods_name']; ?></a>
                                                    </div>

                                                    <div class="goods-price">原价：￥<span
                                                            bbctype="tuan_goods_price"><?php echo $g_info['goods_price']; ?></span>
                                                    </div>
                                                </div>
                                                <input type="hidden" value="<?php echo $g_info['gid']; ?>" name="gid[]">
                                            </li>
                                            <li>
                                                <p>* 预售库存（件）</p>
                                                <input type="text" value="<?php echo $g_info['goods_stock']; ?>" name="sld_stock[<?php echo $g_info['gid']; ?>]" >
                                            </li>
                                            <li>
                                                <p>* 预售定金（元）</p>
                                                <input type="text" value="<?php echo $g_info['pre_deposit_price']; ?>"
                                                       name="sld_ding_price[<?php echo $g_info['gid']; ?>]" >
                                            </li>

                                            <li>
                                                <p>* 预售价（元）</p>
                                                <input type="text" value="<?php echo $g_info['pre_sale_price']; ?>"
                                                       name="sld_presale_price[<?php echo $g_info['gid']; ?>]" >
                                            </li>
                                        </ul>
                                    </div>
                                <?php } }?>
                        </div>
                    </div>
                </dd>
            </dl>

            <!--    <dl>-->
            <!--      <dt>--><?php //echo '活动介绍:';?><!--</dt>-->
            <!--      <dd>-->
            <!--        --><?php //showEditor('tuan_description','','740px','360px','','false',false);?>
            <!--        <p class="hr8"><a class="des_demo bbc_ms-btn" href="-->
            <?php //echo BASE_VENDOR_URL;?><!--/index.php?app=imagespace&mod=pic_list&item=tuan"><i class="icon-picture"></i>-->
            <?php //echo $lang['插入相册图片'];?><!--</a></p>-->
            <!--        <p id="des_demo" style="display:none;"></p>-->
            <!--      </dd>-->
            <!--    </dl>-->
            <div class="bottom"><label class="submit-border">
                    <input type="submit" class="submit" value="<?php echo $lang['提交']; ?>"></label>
            </div>
        </form>
    </div>
</div>
<div class="vendor_bottom_logo"><img src="<?php echo VENDOR_TEMPLATES_URL; ?>/images/vendor_bottom_logo.png"/></div>
<link rel="stylesheet" type="text/css"
      href="<?php echo STATIC_SITE_URL; ?>/js/jquery-ui/themes/ui-lightness/jquery.ui.css"/>
<link rel="stylesheet" type="text/css"
      href="<?php echo STATIC_SITE_URL; ?>/js/jquery-ui-timepicker-addon/jquery-ui-timepicker-addon.min.css"/>
<script src="<?php echo STATIC_SITE_URL; ?>/js/jquery.ajaxContent.pack.js"></script>
<script src="<?php echo STATIC_SITE_URL; ?>/js/jquery-ui/i18n/jquery.ui.datepicker-zh-CN.js"></script>
<script src="<?php echo STATIC_SITE_URL; ?>/js/jquery-ui-timepicker-addon/jquery-ui-timepicker-addon.min.js"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL; ?>/js/fileupload/jquery.iframe-transport.js"
        charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL; ?>/js/fileupload/jquery.ui.widget.js"
        charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL; ?>/js/fileupload/jquery.fileupload.js"
        charset="utf-8"></script>
<script type="text/javascript">
    var SITEURL = "<?php echo BASE_VENDOR_URL; ?>";

    $(document).ready(function () {
            $('#start_time').datetimepicker({
                controlType: 'select'
            });

            $('#end_time').datetimepicker({
                controlType: 'select'
            });

            //点击选择商品
            $('#btn_show_search_goods').on('click', function () {
                if ($("#div_goods_search_result").html() == '') {
                    $("#btn_search_goods").click();
                }
                $('#div_search_goods').show();
            });

            $('#btn_hide_search_goods').on('click', function () {
                $('#div_search_goods').hide();
            });

            //点击搜索商品 ajax
            $('#btn_search_goods').on('click', function () {
                var url = "<?php echo urlAddons('search_goods');?>";
                url += '&' + $.param({goods_name: $('#search_goods_name').val()});
                $('#div_goods_search_result').load(url);
            });

            $('#div_goods_search_result').on('click', 'a.demo', function () {
                $('#div_goods_search_result').load($(this).attr('href'));
                return false;
            });

            //确定选择商品
            $('#div_goods_search_result').on('click', '[bbctype="btn_add_tuan_goods"]', function () {
                var goods_commonid = $(this).attr('data-goods-commonid');
                //先AJAX判断商品是否可选
                $.getJSON('<?php echo urlAddons('check_goods_repeat')?>', {gid: goods_commonid}, function (re) {
                    if (re.result) {
                        showError('商品已在其他活动中添加过了！');
                        return false;
                    } else {
                        $("#tuan_goods_id").val(goods_commonid);
                        $.get('<?php echo urlAddons('goods_info')?>', {goods_commonid: goods_commonid}, function (data) {
                            console.log(data)
                            if (data && typeof data == 'object' && data.length > 0) {
                                $(".mutiGoods").html('');
                                 $(".mutiGoods").width(220 * data.length);
                                for (var x = 0 in data) {
                                    var v = data[x];
                                    var htmstr = '<div class="p_list clearfix">\n' +
                                        '    <div class="p_left">\n' +
                                        '        <div class="p_goods">\n' +
                                        '            <div class="img">\n' +
                                        '                <img src="' + v.goods_image + '" alt="' + v.goods_name + '" title="' + v.goods_name + '">\n' +
                                        '            </div>\n' +
                                        '            <p class="name">' + v.goods_name + '</p>\n' +
                                        '            <p class="price">原价：￥' + v.goods_price + '</p>\n' +
                                        '        </div>\n' +
                                        '        <div class="p_in_item">\n' +
                                        '            <p>*预售库存（件）</p>\n' +
                                        '            <input type="text" value="' + v.goods_storage + '" name=\"sld_stock[' + v.gid + ']\">' +
                                        '        <p style="color: #bbb;">预售库存不能大于商场库存</p></div>\n' +
                                        '        <div class="p_in_item y_price">\n' +
                                        '            <p>预付定金（元）</p>\n' +
                                        '            <input type="text" value="0" name=\"sld_ding_price[' + v.gid + ']\">' +
                                        '        <p style="color: #bbb;">预付定金不能大于预售价</p></div>\n' +
                                        '        <div class="p_in_item y_price">\n' +
                                        '            <p>预售价（元）</p>\n' +
                                        '            <input type="text" value="0" name=\"sld_presale_price[' + v.gid + ']\">' +
                                        '         <p style="color: #bbb;">预售价不能大于商城原价</p></div>\n' +
                                        '        <input type="hidden" value="' + v.gid + '" name="gid[]">' +
                                        '    </div>\n' +
                                        '</div>'
                                    $(".mutiGoods").append(htmstr);
                                }
                                $('#div_search_goods').hide();

                            } else {
                                showError(data.message);
                            }
                        }, 'json');
                    }
                });
            });

            //图片上传
            $('[bbctype="btn_upload_image"]').fileupload({
                dataType: 'json',
                url: "<?php echo urlAddons('image_upload');?>",
                add: function (e, data) {
                    $parent = $(this).parents('dd');
                    $input = $parent.find('[bbctype="tuan_image"]');
                    $img = $parent.find('[bbctype="img_tuan_image"]');
                    data.formData = {old_tuan_image: $input.val()};
                    $img.attr('src', "<?php echo VENDOR_TEMPLATES_URL . '/images/loading.gif';?>");
                    data.submit();
                },
                done: function (e, data) {
                    var result = data.result;
                    $parent = $(this).parents('dd');
                    $input = $parent.find('[bbctype="tuan_image"]');
                    $img = $parent.find('[bbctype="img_tuan_image"]');
                    if (result.result) {
                        $img.prev('i').hide();
                        $img.attr('src', result.file_url);
                        $img.show();
                        $input.val(result.file_name);
                    } else {
                        showError(data.message);
                    }
                }
            });

            jQuery.validator.methods.greaterThanStartDate = function (value, element) {
                var sdate = $("#start_time").val();
                var date1 = new Date(Date.parse(sdate.replace(/-/g, "/")));
                var date2 = new Date(Date.parse(value.replace(/-/g, "/")));
                return date1 < date2;
            };

            //页面输入内容验证
            $("#add_form").validate({
                errorPlacement: function (error, element) {
                    var error_td = element.parent('dd').children('span');
                    error_td.append(error);
                },
                submitHandler: function (form) {
                    ajaxpost('add_form', '', '', 'onerror');
                },
                rules: {
                    start_time: {
                        required: true,
                    },
                    end_time: {
                        required: true,
                        greaterThanStartDate: true
                    },
                    tuan_goods_id: {
                        required: true
                    },
                    class_id: {
                        required: true,
                        min: 1
                    },
                    tuan_image: {
                        required: true
                    },
                    sld_max_buy: {
                        min: 0,
                        digits: true
                    },
                    sld_team_count: {
                        min: 1,
                        required: true,
                        digits: true
                    },
                    sld_success_time: {
                        min: 0.5,
                        required: true
                    },
                },
                messages: {
                    start_time: {
                        required: '<i class="icon-exclamation-sign"></i>开始时间不能为空',
                    },
                    end_time: {
                        required: '<i class="icon-exclamation-sign"></i>结束时间不能为空',
                        greaterThanStartDate: '<i class="icon-exclamation-sign"></i>结束时间必须大于开始时间'
                    },
                    tuan_goods_id: {
                        required: '<i class="icon-exclamation-sign"></i>请选择预售商品',
                        checkTuanGoods: '该商品已经参加了同时段的活动'
                    },
                    sld_max_buy: {
                        min: '限购不能小于零',
                        digits: '限购必须是整数'
                    },
                    class_id: {
                        required: '请完善分类',
                        min: '请完善分类',
                    },
                    tuan_image: {
                        required: '请上传活动图片'
                    },
                    sld_success_time: {
                        required: '请输入支付时间',
                        min: '最少半小时'
                    },
                }
            });

            $('#li_1').click(function () {
                $('#li_1').attr('class', 'active');
                $('#li_2').attr('class', '');
                $('#demo').hide();
            });

            $('#goods_demo').click(function () {
                $('#li_1').attr('class', '');
                $('#li_2').attr('class', 'active');
                $('#demo').show();
            });

            $('.des_demo').click(function () {
                if ($('#des_demo').css('display') == 'none') {
                    $('#des_demo').show();
                } else {
                    $('#des_demo').hide();
                }
            });

            $('.des_demo').ajaxContent({
                event: 'click', //mouseover
                loaderType: "img",
                loadingMsg: "<?php echo VENDOR_TEMPLATES_URL;?>/images/loading.gif",
                target: '#des_demo'
            });
        }
    );

    function insert_editor(file_path) {
        KE.appendHtml('goods_body', '<img src="' + file_path + '">');
    }
</script>

