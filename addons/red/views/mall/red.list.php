<?php defined('DYMall') or exit('Access Invalid!');?>
<link href="addons/red/data/css/red_pc.css" rel="stylesheet" type="text/css">
<!--<div class="wrap">-->
  <div class="vourcherpart">
  <div class="tabmenu">
    <?php include template('layout/submenu');?>
  </div>
  <div class="order_content_title clearff">
    <div style="margin-top: 50px;" class="">
      <form id="search_form" method="get" style="display: none;">
        <input type="hidden" id='app' name='app' value='userquan' />
        <input type="hidden" id='mod' name='mod' value='voucher_list' />
        <p class="pright">
          <select name="state">
            <option value="" <?php if (!$_GET['state']){echo 'selected=selected';}?>><?php w('选择状态')?></option>
            <option value="1" <?php if ($_GET['state'] == '1'){echo 'selected=selected';}?>><?php w('未用')?></option>
            <option value="2" <?php if ($_GET['state'] == '2'){echo 'selected=selected';}?>><?php w('已用')?></option>
            <option value="3" <?php if ($_GET['state'] == '3'){echo 'selected=selected';}?>><?php w('过期')?></option>
          </select>
          <a class="button btn_search_goods sous" href="javascript:void(0);">
            <i class="iconfont icon-btnsearch  icon_size18"></i><?php w('搜索')?></a></p>
      </form>
    </div>
  </div>
  <script type="text/javascript">
    $(".sous").on("click", function ()
    {
      $("#search_form").submit();
    });
  </script>
      <div class="red_status fixclear">
          <a class="col <?php if($_GET['red_status']=='not_used'||!isset($_GET['red_status'])){?>on<?php }?>" href="?app=member_red&sld_addons=red&red_status=not_used"><?php w('未使用')?></a>
          <a class="col <?php if($_GET['red_status']=='used'){?>on<?php }?>" data-key="used" href="?app=member_red&sld_addons=red&red_status=used"><?php w('已使用')?></a>
          <a class="col <?php if($_GET['red_status']=='expired'){?>on<?php }?>" data-key="expired" href="?app=member_red&sld_addons=red&red_status=expired"><?php w('已失效')?></a>
          <a class="fr" href="index.php?app=red&sld_addons=red&mod=red_get_list"><img src="addons/red/data/images/pc_ling.png" /></a>
      </div>
  <div class="sldm-default-table annoc_con bbctouch-red-list">
    <?php  if (count($output['list'])>0) { ?>
        <ul class="fixclear">
    <?php foreach($output['list'] as $v) { ?>

            <li class="ticket-item <?php if($v['reduser_use']!=0){?>dis<?php }?>">
                <a class="a" href="javascript:void(0)">
                    <div>
                        <?php if($v['red_type']==2){?><s></s><?php }?>
                        <h1><em><?php echo $v['redinfo_money'] ?></em> <?php w('单位元')?></h1>
                        <?php if($v['reduser_use']==0){?><h3 data-rid="<?php echo $v['redinfo_id'];?>" class="use_red"><?php w('去使用')?></h3><?php }?>
                        <?php if ($v['sheng']!=''&&$v['sheng']>0){?><h5><?php w('仅剩')?><em><?php echo $v['sheng']?></em><?php w('天')?></h5><?php }?>
                        <h2><em><?php if(!$v['redinfo_full'] || $v['redinfo_money']>=$v['redinfo_full']){?><?php w('无门槛优惠券')?><?php }else{ ?><?php w('满')?><?php echo $v['redinfo_full']; ?><?php w('减')?><?php echo $v['redinfo_money']; ?><?php }?></em><?php echo  $v['redinfo_start_text']; ?>-<?php echo $v['redinfo_end_text']; ?></h2>
                    </div>
                </a>
                <p><?php echo $v['str'];?></p>
            </li>
      <?php }?>
        </ul>
    <?php } else { ?>
    <div id="list_norecord">
      <div colspan="20" class="norecord">
        <div class="no_account">
          <img src="<?php echo MALL_TEMPLATES_URL;?>/images/ico_none.png"/>
          <p><?php w('暂无数据')?></p>
        </div>
      </div>
    </div>

    <?php } ?>
  </div>
      <?php  if (count($output['list'])>0) { ?>
          <div class="pagination fixclear"><?php echo $output['show_page'];?></div>
      <?php } ?>
</div>
<!--</div>-->
<script>
    //点击去使用
    function use_red(redinfo_id){
        console.log(redinfo_id);
        $.ajax({//index.php?app=goodslist&keyword=
            type: "GET",
            url:'index.php?app=member_red&mod=use_red&sld_addons=red&redinfo_id='+redinfo_id,
            async: false,
            success: function(res){
                res = JSON.parse(res);
                window.location.href = "<?php echo MALL_URL;?>/index.php?app=goodslist&keyword=&red_gids="+res.red_ids+'&red_vid='+res.red_vid+'&red_gc_id='+res.red_gc_id+'&store_self='+res.store_self;
                return false;
            }
        })
        return false;
    }
    $('.use_red').click(function(){
        var redinfo_id = $(this).attr('data-rid');
        use_red(redinfo_id);
        return false;
    })
    //查看优惠券更多说明
    $(".bbctouch-red-list li p").on('click',function () {
        $(".bbctouch-red-list li p").removeClass('on');
        $(this).toggleClass('on');
    });
</script>
