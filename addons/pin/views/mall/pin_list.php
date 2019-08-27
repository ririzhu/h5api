<link href="<?php echo MALL_TEMPLATES_URL;?>/css/home_point.css" rel="stylesheet" type="text/css">
<link href="<?php echo MALL_TEMPLATES_URL;?>/css/home_login.css" rel="stylesheet" type="text/css">
<link href="<?php echo ADDONS_URL;?>/data/css/pc_pin.css" rel="stylesheet" type="text/css">
<?php echo loadadv(37);?>
<div class="jmys-layout-all pin_list">
    <div class="pin_index_title p_clear">
        <h2>今日拼团</h2>
        <ul>
            <li><a <?php echo !$_GET['tid']?'class="on"':'';?> href="<?php echo urlShop('pin', 'index', array('sld_addons'=>'pin'));?>">全部</a></li>
            <?php foreach ($output['pin_types'] as $v){?>
                <li class="ajax-load" data-tid="<?php echo $v['id'];?>"><a <?php echo $_GET['tid']==$v['id']?'class="on"':'';?> href="<?php echo urlShop('pin', 'index', array('tid' => $v['id'],'sld_addons'=>'pin'));?>"><?php echo $v['sld_typename'];?></a></li>
            <?php }?>
        </ul>
    </div>
  <div class="jmys-layout-right">
      <?php if(count($output['type_list'])>0 ){?>
        <?php foreach ($output['type_list'] as $k=>$pointprod_list){?>
        <div class="jmys-exchange-list">
            <?php if(!$_GET['tid']){?><h2 class="type_tit"><?php echo $pointprod_list['sld_typename'];?> <a href="<?php echo urlShop('pin', 'index', array('tid' => $pointprod_list['id'],'sld_addons'=>'pin'));?>"></a> </h2><?php }?>
            <div class="box_bd clearfix"><div class="J_sk_list_wrapper sk_list_wrapper">
            <?php if (is_array($pointprod_list['data']) && count($pointprod_list['data'])){?>
            <ul class="J_sk_list sk_list clearfix" >
              <?php $wqi=0;?>
            <?php foreach ($pointprod_list['data'] as $v){ ?>
              <li data-date="<?php echo $v['sld_end_time']?>" class="J_sk_item sk_item sk_item_<?php echo $v['id'];?>" <?php if($wqi%4===3){?>style=" margin-right: 0;"<?php }?>>
                <div class="sk_item_pic">
                  <a href="<?php echo urlShop('goods', 'index', array('gid' => $v['gid']));?>" target="_blank" class="sk_item_pic_lk">
                    <img src="<?php echo $v['goods_image']; ?>" onload="javascript:DrawImage(this,293,290);"  alt="<?php echo $v['goods_name']; ?>" title="<?php echo $v['goods_name']; ?>" class="sk_item_img">
                    <span class="sk_item_shadow"><img src="http://qr.liantu.com/api.php?text=<?php echo COMMON_URL;?>/cwap/cwap_product_detail.html?gid=<?php echo $v['gid']?>" /> 扫我拼团</span></div>
                <p class="sk_item_name"><?php echo $v['goods_name']; ?></p>
                  <h3><span><?php echo $v['sld_team_count'];?>人团</span> 已拼<?php echo $v['sales'];?>件</h3>
                  <div class="goods-detail-pin">
                      <h1>￥<em><?php echo $v['sld_pin_price'];?></em><s>￥<?php echo $v['goods_price'];?></s></h1>
                      <h2>距结束 <span><b>00</b>:<b>00</b>:<b>00</b></span></h2>
                  </div>
                  </a>
              </li>
            <?php $wqi++; } ?>
                <?php if($output['page_count']>1){?><div class="wqload">加载中</div><?php }?>
            </ul>
            <?php } ?>
            </div>
        </div>

      </div>
      <?php }?>
      <?php }?>
      <div class="norecord" style="display: none;">
          <div class="norecord_body">
              <img src="<?php echo ADDONS_URL;?>/data/img/no_record_pic.png"><span>抱歉，没有找到您想要的拼团商品~</span>
          </div>

      </div>
    </div>
</div>
<script>
    var tid=0;
    var now=1;
    if($(".pin_list .sk_item").size()>0) {
        $(".pin_list .sk_item").each(function (ind, ele) {
            var starttime = new Date($(ele).data('date'));
            setInterval(function () {
                var nowtime = new Date();
                var time = starttime - nowtime;
                var day = parseInt(time / 1000 / 60 / 60 / 24);
                var hour = parseInt(time / 1000 / 60 / 60 % 24);
                hour = hour + day * 24;
                var minute = parseInt(time / 1000 / 60 % 60);
                var seconds = parseInt(time / 1000 % 60);
                if (seconds.toString().length < 2) {
                    seconds = '0' + seconds;
                }
                if (minute.toString().length < 2) {
                    minute = '0' + minute;
                }

                $(ele).find('.goods-detail-pin span b').eq(0).html(hour);
                $(ele).find('.goods-detail-pin span b').eq(1).html(minute);
                $(ele).find('.goods-detail-pin span b').eq(2).html(seconds);
            }, 1000);
        });
    }else{
        $(".norecord").show();
    }
</script>
<script>
    $(".pin_index_title li.ajax-load a").click(function () {
        $(".pin_index_title li a").removeClass('on');
        $(this).addClass('on');
        tid=$(this).parent().data('tid');
        $()
        $(".jmys-exchange-list").not(":first").remove();
        $(".type_tit").remove();
        $(".J_sk_list").html('<div class="wqload">加载中</div>');
        now=0;
        get_list();
        return false;
    });
    //王强添加ajax加载
    var page_count='<?php echo $output['page_count'];?>';
    var hasmore=page_count>1;
    $(document).ready(function () {
        if(page_count>1){
            var loadingY= $(".wqload").offset().top;
            $(window).scroll(function () {
                if($(document).scrollTop()>=loadingY-$(window).height()){
                    if(!hasmore){
                        return false;
                    }
                    hasmore = false;
                    get_list()
                }
            });
        }
    });
    function get_list() {
        $.getJSON("<?php echo urlShop('pin', 'ajax', array('sld_addons'=>'pin'));?>",{
            tid:tid,
            pn:now+1
        },function (re) {
            if(re && re.data.length>0){
                var tpl='';
                re.data.forEach(function (v,i) {
                    var m = i%4==3?'style=" margin-right: 0;"':'';
                    tpl+='<li '+m+' data-date="'+v.sld_end_time+'" class="J_sk_item sk_item sk_item_'+v.id+'">\n' +
                        '                <div class="sk_item_pic">\n' +
                        '                  <a href="http://site1.slodon.cn/index.php?app=goods&amp;gid='+v.sld_goods_id+'" target="_blank" class="sk_item_pic_lk">\n' +
                        '                    <img src="'+v.goods_image+'" onload="javascript:DrawImage(this,293,290);" alt="'+v.goods_name+'" class="sk_item_img" height="290" width="290">\n' +
                        '                    <span class="sk_item_shadow"><img src="http://qr.liantu.com/api.php?text=http://qr.liantu.com/api.php?text=<?php echo COMMON_URL;?>/cwap/cwap_product_detail.html?gid='+v.sld_goods_id+'"> 扫我拼团</span></a></div><a href="http://site1.slodon.cn/index.php?app=goods&amp;gid='+v.gid+'" target="_blank" class="sk_item_pic_lk">\n' +
                        '                <p class="sk_item_name">'+v.goods_name+'</p>\n' +
                        '                  <h3><span>'+v.sld_team_count+'人团</span> 已拼'+v.sales+'件</h3>\n' +
                        '                  <div class="goods-detail-pin">\n' +
                        '                      <h1>￥<em>'+v.sld_pin_price+'.00</em><s>￥'+v.goods_price+'.00</s></h1>\n' +
                        '                      <h2>距结束 <span><b>00</b>:<b>00</b>:<b>00</b></span></h2>\n' +
                        '                  </div>\n' +
                        '                  </a>\n' +
                        '              </li>';
                });
                $(tpl).insertBefore('.wqload').each(function (ind,ele) {
                    var starttime = new Date($(ele).data('date'));
                    setInterval(function () {
                        var nowtime = new Date();
                        var time = starttime - nowtime;
                        var day = parseInt(time / 1000 / 60 / 60 / 24);
                        var hour = parseInt(time / 1000 / 60 / 60 % 24);
                        hour = hour +day*24;
                        var minute = parseInt(time / 1000 / 60 % 60);
                        var seconds = parseInt(time / 1000 % 60);
                        if(seconds.toString().length < 2 ){
                            seconds = '0'+seconds;
                        }
                        if(minute.toString().length < 2 ){
                            minute = '0'+minute;
                        }

                        $(ele).find('.goods-detail-pin span b').eq(0).html(hour);
                        $(ele).find('.goods-detail-pin span b').eq(1).html(minute);
                        $(ele).find('.goods-detail-pin span b').eq(2).html(seconds);
                    }, 1000);
                });
                $(".norecord").hide();
                now++;
            }
            hasmore = re.hasmore;
            if(!hasmore){
                $(".wqload").remove();
                if($(".J_sk_list.sk_list li").size()<1){
                    $(".norecord").show();
                }
            }

        });
    }
</script>