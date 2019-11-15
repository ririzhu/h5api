<?php defined('DYMall') or exit('Access Invalid!');?>
<link href="addons/red/data/css/red_pc.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/layer/layer.js" charset="utf-8"></script>
<!--<div class="wrap">-->
<a href="javascript:void(0);" class="red_ling_index" target="_blank">
    <img border="0" src="addons/red/data/images/pc_ling_index.png" alt="1">
</a>

<div class="voucherinfo">

  <script type="text/javascript">
    $(".sous").on("click", function ()
    {
      $("#search_form").submit();
    });
  </script>
  <div class="sldm-default-table annoc_con bbctouch-red-list ling" style=" width: 1210px; margin: 30px auto; border: 0;">
    <?php  if (count($output['list'])>0) { ?>
        <ul class="fixclear">
    <?php foreach($output['list'] as $v) { ?>
            <li class="ticket-item">
                <?php if($v['have']>=$v['red_rach_max'] || $v['prent']>=100){ ?>
                <a class="a" href="index.php?app=goodslist">
                    <?php }else{ ?>
                    <a class="a weiling" href="javascript:void(0)" data-id="<?php echo $v['id']; ?>">
                        <?php } ?>
                        <div>
                            <?php if($v['red_type']==2){ ?><s></s><?php } ?>
                            <h1><em><?php echo $v['redinfo_money']; ?></em> <?php w('单位元')?></h1>
                            <?php if($v['have']>=$v['red_rach_max']){ ?>
                            <h6 class="a1"><?php w('您已领券')?></h6>
                            <h6 class="a2"><?php w('去使用')?></h6>
                            <?php }else{ ?>
                            <?php if($v['prent']){ ?>
                            <h6 class="a3"><?php w('已抢')?><?php if($v['prent']>=100){ ?><?php w('完')?><?php }else{?><?php echo $v['prent']; ?>%<?php }?> <em><i style="width: <?php echo $v['prent']; ?>%"></i></em></h6>
                            <?php } ?>
                            <?php if($v['prent']<100){ ?>
                            <h6 class="a4" data-id="<?php echo $v['id']; ?>" end_time_str="<?php echo $v['red_receive_end_text']; ?>"><em><?php w('领取')?></em>-</h6>
                            <?php }?>
                            <?php } ?>
                            <h2><em><?php if(!$v['redinfo_full'] || $v['redinfo_money']>=$v['redinfo_full']){ ?><?php w('无门槛优惠券')?><?php }else{ ?><?php w('满')?><?php echo $v['redinfo_full']; ?><?php w('减')?><?php echo $v['redinfo_money']; ?><?php } ?></em><?php echo $v['redinfo_start_text'];  ?>-<?php echo $v['redinfo_end_text'];?></h2>
                        </div>
                    </a>
                    <p><?php echo $v['str'] ?></p>
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
    //查看优惠券更多说明
    $(".bbctouch-red-list li p").on('click',function () {
        $(".bbctouch-red-list li p").removeClass('on');
        $(this).toggleClass('on');
    });

    load_countdown_val();

    //绑定领取按钮点击事件
    $("a.a.weiling").each(function () {
        $(this).click(function () {
            lingqu($(this));
        });
    });

    function load_countdown_val()
    {
        $.each($("h6.a4"),function(k,item){
            var end_time_str = $(item).attr("end_time_str");

            var timer_data ={};

            if (end_time_str) {
                timer_data = getLeftTimerData(end_time_str);
                if (timer_data) {

                    var starttime = new Date(end_time_str);
                    setInterval(function () {
                        timer_data = getLeftTimerData(end_time_str);
                        if(timer_data.day && timer_data.day>0 &&1==2){
                            $(item).html('<em><?php w('领取')?></em>'+timer_data.day+'<?php w('天')?>');
                        }else {
                            $(item).html('<em><?php w('领取')?></em>' + timer_data.hours + ':' + timer_data.minutes + ':' + timer_data.seconds);
                        }
                    }, 1000);

                }
            }
        });
    }

    function getLeftTimerData(enddate){
        var timer_data = {};

        enddate = enddate.replace(/-/g, '/');

        var leftTime = (new Date(enddate)) - new Date(); //计算剩余的毫秒数

        var day = parseInt(leftTime / 1000 / 60 / 60 / 24 , 10);
        var hours = parseInt(leftTime / 1000 / 60 / 60, 10); //计算总小时
        var minutes = parseInt(leftTime / 1000 / 60 % 60, 10);//计算剩余的分钟
        var seconds = parseInt(leftTime / 1000 % 60, 10);//计算剩余的秒数

        hours = checkTime(hours);
        minutes = checkTime(minutes);
        seconds = checkTime(seconds);
        if (hours >= 0 || minutes >= 0 || seconds >= 0){
            timer_data.hours = hours;
            timer_data.minutes = minutes;
            timer_data.seconds = seconds;
        }
        if(day > 0 ){
            timer_data.day = day;
        }

        return timer_data;
    }

    function checkTime(i) { //将0-9的数字前面加上0，例1变为01
        if (i < 10) {
            i=(i<0)?0:i;
            i = "0" + i;
        }
        return i;
    }

    function lingqu(ele) {

        var red_id = ele.data('id');
        //loading带文字
        layer.closeAll();
        var index = layer.load(1, {
            shade: [0.4,'#fff'] //0.1透明度的白色背景
        });
        $.ajax({
            url:"index.php?app=red&sld_addons=red&mod=send_red",
            data:{red_id:red_id},
            success:function(result){
                layer.closeAll();
                if(result == 1) {
                    layer.msg('<?php w('领取成功')?>');
                    setInterval(function () {
                        location.reload();
                    }, 2000);
                }else{
                    layer.msg(result);
                }

            },
            complete:function(XMLHttpRequest,textStatus){
                if(textStatus=='timeout'){
                    var xmlhttp = window.XMLHttpRequest ? new window.XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHttp");
                    xmlhttp.abort();
                    layer.open({
                        content: '<?php w('网络超时，请刷新重试')?>！'
                        , skin: 'msg'
                        , time: 2 //2秒后自动关闭
                    });
                }
            },
        });
    }
</script>
