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

    var extend_param_str = '';
    // 当前城市分站
    if (current_data.bid > 0) {
        extend_param_str += '&bid='+current_data.bid;
    }

    //获取首页搜索栏颜色和标题
    $.ajax({
        url: ApiUrl + "/index.php?app=index&mod=index_title"+extend_param_str,
        type: 'get',
        dataType: 'json',
        success: function(result) {
            if(result.code == 200){
                document.title=result.datas.title;
                $('.ss_header_bg').css('background','#'+result.datas.sousuo_color);
            }
        }
    });

    // load_tmp_data(extend_param_str,_sld_stats);
    // function load_tmp_data(extend_param_str,_sld_stats,lp=0){
        var lp = 0;
        var style5 = 0; //是否有图片组合的第5个组合
        var is_tjsp = 0;//是否有推荐商品模块

        var data_lp_str = '';
        if (lp > 0) {
            data_lp_str += '&lp='+lp;
        }
        var load_html_obj;
        $.ajax({
            url: ApiUrl + "/index.php?app=index&mod=index_data&sld_addons=pin_ladder",
            type: 'get',
            dataType: 'json',
            async: false,
            beforeSend: function(){

                var load_html = '<div class="sld-loading"><div class="sld-loading-img">';
                load_html += '<i class="fa fa-spinner fa-spin"></i>';
                load_html += '</div></div>';
                
                $("#main-container").html(load_html);
            },
            success: function(result) {
                // 关闭 加载中提示
                $(".sld-loading").remove();
                $("header").show();

                var data = result.datas.tmp_data;
                var has_more = result.datas.has_more;
                // var html = '';
                if (data.length > 0) {
                    $.each(data, function(k, v) {
                        var html = '';
                        _sld_stats.gids = getGidsFromTemplateData(_sld_stats.gids,v);
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

                        $("#main-container").append(html);
                    });

                    // if (lp == 0) {
                    //     $("#main-container").html(html);
                    // }else{
                    //     $("#main-container").append(html);
                    // }
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
                    if (has_more) {
                        load_tmp_data(extend_param_str,_sld_stats,++lp);
                    }
                }else{
                    var city_empty_redirect_second_num = 3;
                    var empty_tips_str = '首页暂无装修';
                    if (current_data.bid > 0) {
                        // 城市分站 空装修模板 提示
                        empty_tips_str = '该分站未装修，'+city_empty_redirect_second_num+'秒后自动跳回主站';
                    }
                    var empty_html = '<div class="sld-no-data" style="">';
                    empty_html += '<div class="sld-no-data-pic"><img src="./images/no-data.png" /></div>';
                    empty_html += '<div class="sld-no-data-tips">'+empty_tips_str+'</div>';
                    empty_html += '</div>';
                    $("#main-container").append(empty_html);
                    if (current_data.bid > 0) {
                        var new_current_data = {};
                        new_current_data.bid = 0;
                        new_current_data.area_name = '全国';
                        new_current_data.site_id = 0;
                        change_current_city_site_data(new_current_data);
                        setInterval(function() {
                            window.location.href="./index.html";
                        },
                        city_empty_redirect_second_num*1000)
                    }
                }
            }
        });
    // }


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

if (check_city_site_open()) {
    get_location_position();
}