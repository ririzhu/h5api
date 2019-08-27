$(function() {

function getLeftTimerData(enddate){
  var timer_data = {};

  enddate = enddate.replace(/-/g, '/');
  
  var leftTime = (new Date(enddate)) - new Date(); //计算剩余的毫秒数

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

  return timer_data;
}

function load_countdown_val()
{
  $.each($(".countdown"),function(k,item){
    console.log('in');
    var end_time_str = $(item).data("end_time_str");
    var timer_data ={};

    if (end_time_str) {
      timer_data = getLeftTimerData(end_time_str);
      if (timer_data) {

        var starttime = new Date(end_time_str);
        setInterval(function () {
            timer_data = getLeftTimerData(end_time_str);
            if(timer_data.hours>99){
                timer_data.hours='99';
            }else if(timer_data.hours<0||timer_data.minutes<0||timer_data.seconds<0){
                $(item).find(".countdown-main").html('活动已结束');
            }
            $(item).find(".countdown-main .hours").html(timer_data.hours);
            $(item).find(".countdown-main .min").html(timer_data.minutes);
            $(item).find(".countdown-main .sec").html(timer_data.seconds);
        }, 1000);

        $(item).find(".countdown-main .hours").html(timer_data.hours);
        $(item).find(".countdown-main .min").html(timer_data.minutes);
        $(item).find(".countdown-main .sec").html(timer_data.seconds);
      }
    }
  });
}

function checkTime(i) { //将0-9的数字前面加上0，例1变为01
if (i < 10) {
    i=(i<0)?0:i;
    i = "0" + i;
}
return i;
}

// 获取 首页 展示的分销推广商品
function get_index_goods_list(){
    $.ajax({
        url: ApiUrl+"/index.php?app=ssys_goods&mod=index&sld_addons=spreader",
        type: 'get',
        dataType: 'json',
        async:false,
        success: function(result) {
            if(result.code!='200'){
                return;
            }

            var data = result.datas;
            var goods = template.render("list", data);

            $("#box .box-list").append(goods);
        }
    });
}
get_index_goods_list();

//商品详情
    $(document).on('click','.goods_detail',function(){
        var id = $(this).data('id');
        var idname = 'goods_' + id;
        var amount = $('#'+idname).data('amount');
        location.href = 'cwap_product_detail.html?gid='+id+'&amount='+amount;
    });
// 分享 弹窗
$(".share").on("click",function(e){
    // window.event.preventDefault(); 
    var the_obj = $(this);
    share_tips_show(the_obj);
    $(".cancel").on("click",function(){
        share_tips_close();
    })
});

    //首页头部效果
    $(window).scroll(function(){
        if ($(window).scrollTop() <= 30) {
            $('.ss_header_bg').removeClass('bgshow_top');
            $('.ss_header_bg').addClass('bghide_top');
            $('.with-home-logo .htsearch-input').removeClass('inputtm_top');
            $('.with-home-logo .htsearch-input').addClass('inputntm_top');
        } else {
            $('.ss_header_bg').removeClass('bghide_top');
            $('.ss_header_bg').addClass('bgshow_top');
            $('.with-home-logo .htsearch-input').removeClass('inputntm_top');
            $('.with-home-logo .htsearch-input').addClass('inputtm_top');
        }
    });
    wap_now = 'sld_home';
    //获取首页搜索栏颜色和标题
    $.ajax({
        url: ApiUrl + "/index.php?app=index&mod=index_title&sld_addons=spreader",
        type: 'get',
        dataType: 'json',
        success: function(result) {
            if(result.code == 200){
                document.title=result.datas.title;
                $('.ss_header_bg').css('background','#'+result.datas.sousuo_color);
            }
        }
    });
    var style5 = 0; //是否有图片组合的第5个组合
    var is_tjsp = 0;//是否有推荐商品模块
    $.ajax({
        url: ApiUrl + "/index.php?app=index&sld_addons=spreader",
        type: 'get',
        dataType: 'json',
        success: function(result) {
            var data = result.datas;
            var html = '';
            $.each(data, function(k, v) {
                if(v.type == 'gonggao'){
                    v.true_url = buildUrl(v.lianjie_type, v.lianjie_url);
                }
                if(v.type == 'lunbo'){
                    $.each(v.data, function(key, value) {
                        if(value.url_type){
                            v.data[key].true_url = buildUrl(value.url_type, value.url);
                        }else{
                            v.data[key].true_url = '';
                        }
                    });
                }
                if(v.type == 'nav'){
                    $.each(v.data, function(key, value) {
                        if(value.url_type){
                            v.data[key].true_url = buildUrl(value.url_type, value.url);
                        }else{
                            v.data[key].true_url = '';
                        }
                    });
                }
                
                if (v.type == 'huodong') {
                        if(v.data.top.top[0].url_type){
                            v.data.top.top[0].true_url = buildUrl(v.data.top.top[0].url_type, v.data.top.top[0].url);
                        }else{
                            v.data.top.top[0].true_url = '';
                        }
                        switch(v.sele_style){
                            case '0':
                                // 拼团
                                break;
                            case '1':
                                // 限时折扣
                                break;
                            case '2':
                                // 团购

                                break
                        }
                    html += template.render(v.type+'_'+v.sele_style, v);
                }
                if(v.type == 'tupianzuhe'){
                    $.each(v.data, function(key, value) {
                        if(value.url_type){
                            v.data[key].true_url = buildUrl(value.url_type, value.url);
                        }else{
                            v.data[key].true_url = '';
                        }
                    });
                    if(v.sele_style<4){
                        html += template.render(v.type+"_0123", v);
                    }else{
                        if(v.sele_style == 5){
                            style5 = 1;
                        }
                        html += template.render(v.type+'_'+v.sele_style, v);
                    }
                }else{
                    if(v.type == 'tuijianshangpin'){
                        is_tjsp = 1;
                    }
                    html += template.render(v.type, v);
                }

            });

            $("#main-container").html(html);
            load_countdown_val();
            //如果有推荐商品，则把商品的高度设为跟宽度一样
            if(is_tjsp == 1){
                //屏幕宽度的一半减去2px
                var imgwidth = $(window).width()/2-2;
                if(imgwidth>318){
                    imgwidth = 318;
                }
                $('.index_block.goods .goods-item-pic').css('width',imgwidth);
                $('.index_block.goods .goods-item-pic').css('height',imgwidth);
            }
            //把图片组合的5的宽度重新定义
            var scr_widht = document.body.clientWidth;
            if(style5 == 1){
                var sm_width = (scr_widht*1 - 24)*0.3333333;
                var big_width = (scr_widht*1 - 24)*0.6666666;
                $('.image-ad2 .small').each(function (i,v)
                {
                    $(v).css('width',sm_width);
                });
                $('.image-ad2 .big').each(function (i,v) {
                    $(v).css('width',big_width);
                });
            }
            //对富文本内容展示的处理
            var fuwenbenO = $('.fuwenben_part');
            fuwenbenO.each(function (i,v) {
                $(v).html($(v).text());
            });
            $('.adv_list').each(function() {
                if ($(this).find('.item').length < 2) {
                    return;
                }
                Swipe(this, {
                    startSlide: 2,
                    speed: 400,
                    auto: 3000,
                    continuous: true,
                    disableScroll: false,
                    stopPropagation: false,
                    callback: function(index, elem) {},
                    transitionEnd: function(index, elem) {}
                });
            });
        }
    });


	var $_GET = (function(){
    var url = window.document.location.href.toString();
    var u = url.split("?");
    if(typeof(u[1]) == "string"){
        u = u[1].split("&");
        var get = {};
        for(var i in u){
            var j = u[i].split("=");
            get[j[0]] = j[1];
        }
        return get;
    } else {
        return {};
    }
})();
addcookie('inviteid',$_GET['u']);
    //头部的搜索事件
    $('#keyword').focus(function () {
        window.location.href = './cwap_pro_search.html';
    });
});
