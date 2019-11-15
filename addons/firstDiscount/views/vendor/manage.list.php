<?php defined('DYMall') or exit('Access Invalid!'); ?>
<link href="<?php echo STATIC_SITE_URL; ?>/js/layer/theme/default/layer.css" rel="stylesheet" type="text/css"/>
<style>
    .layui-layer-prompt .layui-layer-title {
        display: block !important;
    }

    .bd-line.active {
        transform: scale(1.05);
        box-shadow: 0 0 20px #ccc
    }

    .first ul:after {
        clear: both;
        content: ' ';
        display: block;
    }

    .first li {
        float: left;
        width: 140px;
        padding: 10px;
    }
    .first li img { width: 140px; height: 140px; }
    .first li h4{ font-size: 12px; color: #999; }
    .first li h2{ font-size: 14px; color: #444; line-height: 24px; font-weight: bold; width: 100%; height: 24px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .first li h3 { font-size: 12px; color: #f60; text-indent: -13px; }
    .first li h3 em{ font-size: 18px; color: #f60; font-weight: bold; }
    .first li span { float: right; background-color: #47C370; padding:0px 5px; border-radius: 2px;  font-size: 13px; color: #fff; border:1px #29993F solid; width: 40px; text-align: right; cursor: pointer; }
    .first li span i{ font-weight: normal; font-size: 12px; }
    .first li span.ok{ background-color: #fb3c68; border-color: #c92c44; }
    .mn{ position: absolute; right: 140px; top: 13px; font-size: 14px; width: 200px; text-align: right; }
</style>
<div class="tabmenu">
    <?php include template('layout/submenu'); ?>
    <span class="mn">当前优惠金额：￥<b><?php echo floatval($output['money']);?></b></span>
    <input type="hidden" value="<?php echo floatval($output['money']);?>"  id="money">
    <a href="javascript:void(0);" id="addGoods" class="bbc_ms-btn bbc_ms-btn-green" title="设置优惠金额"><i
                class="iconfontfa fa-edit add_iconcolor"></i>设置优惠金额</a>

</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">
    <div class="alert alert-block mt10">
        <ul class="mt5">
            <li>首单优惠可以给商品设置一个减免金额，所有用户只可购买一次，设置0则为关闭。</li>
        </ul>
    </div>
    <table class="search-form">
        <form method="get">
            <input type="hidden" name="app" value="manage"/>
            <input type="hidden" name="mod" value="index"/>
            <input type="hidden" name="sld_addons" value="firstDiscount"/>
            <tr>
                <th>商品名称</th>
                <td class="w160"><input class="text" type="text" name="tuan_name"
                                        value="<?php echo $_GET['tuan_name']; ?>"/></td>
                <td class="w70 tc"><label class="submit-border"><input type="submit" class="submit submit_search"
                                                                       value="<?php echo $lang['搜索']; ?>"/></label>
                </td>
                <td>&nbsp;</td>
            </tr>
        </form>
    </table>


    <?php if (!empty($output['list']) && is_array($output['list'])) { ?>
        <div class="first">
            <ul>
                <?php foreach ($output['list'] as $key => $v) { ?>
                    <li data-cid="<?php echo $v['goods_commonid'];?>">
                        <a target="_blank" href="<?php echo urlShop('goods', 'index', array('gid' => $v['gid']));?>">
                            <img alt="<?php echo $v['goods_image']; ?>" src="<?php echo thumb($v); ?>"/>
                        </a>
                        <a target="_blank" href="<?php echo urlShop('goods', 'index', array('gid' => $v['gid']));?>">
                        <h4><?php echo goods_type($v['gc_id_1']); ?></h4>
                            <h2><?php echo $v['goods_name']; ?></h2>
                        </a>
                            <h3>￥<em><?php echo $v['goods_price']; ?></em>
                                <?php if ($v['reduction']) { ?>
                                    <span class="ok"><i class="iconfontfa fa-check"></i> 已选</span>
                                <?php } else { ?>
                                    <span><i class="iconfontfa fa-plus"></i> 选择</span>
                                <?php } ?>


                            </h3>
                    </li>
                <?php } ?>
            </ul>
        </div>
    <?php } else { ?>
        <table class="bbc_ms-table-style ">

            <tr>
                <td colspan="20" class="norecord">
                    <div class="warning-option"><i
                                class="iconfontfa fa-exclamation-triangle"></i><span><?php echo $lang['无数据']; ?></span>
                    </div>
                </td>
            </tr>
        </table>
    <?php } ?>
    <div class="pagination"><?php echo $output['show_page']; ?></div>
</div>
<div class="vendor_bottom_logo"><?php include template('footer'); ?></div>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL; ?>/js/layer/layer.js" charset="utf-8"></script>
<script>
    $(document).ready(function () {
        $("#addGoods").click(function () {
            layer.prompt({
                formType: 3 ,
                value: $("#money").val(),
                title: '请输入优惠额度',

            }, function(value, index, elem){
                $.ajax({
                    url:'index.php?app=manage&mod=set&sld_addons=firstDiscount',
                    type: 'get',
                    data:{money:value},
                    success: function(data){
                        if(data>0){
                            layer.msg('设置成功');
                            $(".mn b").html(value);
                            layer.close(index);
                        }else{
                            layer.msg('设置失败');
                        }
                    }
                });
            });
        });
        $(".first li h3 span").click(function () {
            var cid = $(this).parents('li').data('cid');
            var money = $("#money").val();
            var elels = $(this);
            $.ajax({
                url:'index.php?app=manage&mod=toggle&sld_addons=firstDiscount',
                type: 'get',
                data:{cid:cid,money:money},
                success: function(data){
                    if(data=='1'){
                        $(elels).addClass('ok').html('<i class="iconfontfa fa-check"></i> 已选');
                    }else{
                        $(elels).removeClass('ok').html('<i class="iconfontfa fa-plus"></i> 选择');
                    }
                }
            });
        });
    });
</script>
