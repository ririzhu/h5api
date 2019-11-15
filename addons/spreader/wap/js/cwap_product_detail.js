var gid = GetQueryString("gid");
var key = getcookie("key");
var map_list = [];
var map_index_id = "";
var vid;
var buy_type=1; //正常购买   2  拼团买
//门店相关 开始
var map = new AMap.Map('mapContainer', {
    resizeEnable: true
});
var lat = '';
var lng = '';
var page = pagesize;
var key = getcookie("key");
var pn = 1;
var hasmore = true;
var isgaode = 0;
//门店相关 结束
$(function() {
    var e = getcookie("key");
    //添加浏览记录
    if(e){
        $.ajax({
            url: ApiUrl + "/index.php?app=usercenter&mod=addUserBrowserGoods",
            type: "post",
            data: {
                gid: gid,
                key: e
            },
            dataType: "json",
            success: function (s) {
            }
        });
    }
    var t = function(e, t) {
        e = parseFloat(e) || 0;
        if (e < 1) {
            return ""
        }
        var o = new Date;
        o.setTime(e * 1e3);
        var a = "" + o.getFullYear() + "-" + (1 + o.getMonth()) + "-" + o.getDate();
        if (t) {
            a += " " + o.getHours() + ":" + o.getMinutes() + ":" + o.getSeconds()
        }
        return a
    };
    var o = function(e, t) {
        e = parseInt(e) || 0;
        t = parseInt(t) || 0;
        var o = 0;
        if (e > 0) {
            o = e
        }
        if (t > 0 && o > 0 && t < o) {
            o = t
        }
        return o
    };
    template.helper("isEmpty",
        function(e) {
            for (var t in e) {
                return false
            }
            return true
        });
    function a() {
        var e = $("#mySwipe")[0];
        window.mySwipe = Swipe(e, {
            continuous: false,
            stopPropagation: true,
            callback: function(e, t) {
                $(".goods-detail-turn-right-number .right-number-slider-num").find(".slider-num-current").text(e+1);
                // $(".goods-detail-turn").find("li").eq(e).addClass("cur").siblings().removeClass("cur")
            }
        })
    }
    r(gid);
    function i(e, t) {
        $(e).addClass("current").siblings().removeClass("current");
        var o = $(".spec").find("a.current");
        var a = [];
        $.each(o,
            function(e, t) {
                a.push(parseInt($(t).attr("specs_value_id")) || 0)
            });
        var i = a.sort(function(e, t) {
            return e - t
        }).join("|");
        gid = t.spec_list[i];
        r(gid)
    }
    function s(e, t) {
        var o = e.length;
        while (o--) {
            if (e[o] === t) {
                return true
            }
        }
        return false
    }
    Zepto.sValid.init({
        rules: {
            buynum: "digits"
        },
        messages: {
            buynum: "请输入正确的数字"
        },
        callback: function(e, t, o) {
            if (e.length > 0) {
                var a = "";
                $.map(t,
                    function(e, t) {
                        a += "<p>" + e + "</p>"
                    });
                Zepto.sDialog({
                    skin: "red",
                    content: a,
                    okBtn: false,
                    cancelBtn: false
                })
            }
        }
    });
    function n() {
        $.sValid()
    }
    function r(r) {
        $.ajax({
            url: ApiUrl + "/index.php?app=goods&mod=goods_detail",
            type: "get",
            data: {
                gid: r,
                key: e,
                team_id: GetQueryString('team_id')?GetQueryString('team_id'):'',
            },
            dataType: "json",
            success: function(e) {
                var l = e.datas;
                if (!l.error) {
                    if (l.goods_image) {
                        var d = l.goods_image.split(",");
                        l.goods_image = d
                    } else {
                        l.goods_image = []
                    }
                    if (l.goods_info.spec_name) {

                        var c = $.map(l.goods_info.spec_name,
                            function(e, t) {
                                var o = {};
                                o["goods_spec_id"] = t;
                                o["goods_spec_name"] = e;
                                if (l.goods_info.spec_value) {
                                    $.map(l.goods_info.spec_value,
                                        function(e, a) {
                                            if (o["goods_spec_name"] == a) {
                                                o["goods_spec_value"] = $.map(e,
                                                    function(e, t) {
                                                        var o = {};
                                                        o["specs_value_id"] = t;
                                                        o["specs_value_name"] = e;
                                                        return o
                                                    })
                                            }
                                        });
                                    return o
                                } else {
                                    l.goods_info.spec_value = []
                                }
                            });
                        l.goods_map_spec = c
                    } else {
                        l.goods_map_spec = []
                    }
                    if (l.goods_info.is_virtual == "1") {
                        l.goods_info.virtual_indate_str = t(l.goods_info.virtual_indate, true);
                        l.goods_info.buyLimitation = o(l.goods_info.virtual_limit, l.goods_info.upper_limit)
                    }
                    if (l.goods_info.is_presell == "1") {
                        l.goods_info.presell_deliverdate_str = t(l.goods_info.presell_deliverdate)
                    }

                    var _ = template.render("product_detail", l);
                    $("#product_detail_html").html(_);
                    //分享数据填充
                    var sharestr = '';
                    sharestr += '<div class="share_div" data-id="'+ e.datas.goods_info.gid +'" data-title="'+ e.datas.goods_info.goods_name +'" data-desc="'+ e.datas.goods_info.goods_jingle +'" data-img="'+ e.datas.goods_image[0] +'" data-url="'+ e.datas.goods_info.goods_url +'">分享</div>';
                    $('.goods-detail-foot').html(sharestr);
                    //奖励展示
                    var reward = '';
                    var amount = GetQueryString("amount");
                    //var amount = '25,100';
                    if(amount.indexOf(',') == -1){
                        reward = '奖励:&nbsp;¥&nbsp;'+amount;
                    }else{
                        var amount_arr = amount.split(',');
                        reward = '奖励:&nbsp;¥&nbsp;'+amount_arr[0]+' ~ '+ amount_arr[1];
                        console.log(reward);
                    }
                    $('.amount_class').html(reward)



                    // 限时购 倒计时 start

                    function checkTime(i) { //将0-9的数字前面加上0，例1变为01
                        if (i < 10) {
                            i = "0" + i;
                        }
                        return i;
                    }
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
                    if ($(".xianshi-div .countdown").size()) {
                        var end_time_str = $(".countdown").data("end_time_str");
                        var timer_data ={};

                        if (end_time_str) {
                            timer_data = getLeftTimerData(end_time_str);
                            if (timer_data) {

                                var starttime = new Date(end_time_str);
                                setInterval(function () {
                                    timer_data = getLeftTimerData(end_time_str);
                                    $(".countdown").find(".countdown-main .hours").html(timer_data.hours);
                                    $(".countdown").find(".countdown-main .min").html(timer_data.minutes);
                                    $(".countdown").find(".countdown-main .sec").html(timer_data.seconds);
                                }, 1000);

                                $(".countdown").find(".countdown-main .hours").html(timer_data.hours);
                                $(".countdown").find(".countdown-main .min").html(timer_data.minutes);
                                $(".countdown").find(".countdown-main .sec").html(timer_data.seconds);
                            }
                        }
                    }
                    // 限时购倒计时 end

                    var _fav = template.render("header_top_r", l);
                    $("#header-r").html(_fav);

                    $('.goods-option-foot').css('display','block');
                    //店铺推荐商品  控制图片在方形内展示
                    if($('.goods-detail-recom ul li').length>0){

                        var imgwidth = $(window).width()/2-2;
                        if(imgwidth>318){
                            imgwidth = 318;
                        }
                        $('.index_block.goods .goods-item-pic').css('width',imgwidth);
                        $('.goods-detail-recom ul li .pic').css('height',imgwidth);
                    }

                    if (l.goods_info.is_virtual == "0") {
                        $(".goods-detail-o2o").remove()
                    }
                    var _ = template.render("product_detail_sepc", l);
                    $("#product_detail_spec_html").html(_);

                    var _tags = template.render("product_tags_intro", l);
                    $("#product_detail_tag_html").html(_tags);

                    //有门店的话 查询门店
                    //if(l.dian_list>0){
                    //    have_dian();
                    //}else{
                    //    wx_ready = function(){
                    //        wx_share_reday();
                    //    }
                    //}

                    //可领优惠券
                    if(l.red) {
                        var _redstr = template.render("product_red", l);
                        $("<link>").attr({
                            rel: "stylesheet",
                            type: "text/css",
                            href: "../addons/red/data/css/red_wap.css"
                        }).appendTo("head");
                        $("#product_detail_red_html").html(_redstr);

                        load_countdown_val();

                        //绑定领取按钮点击事件
                        $("a.a.weiling").unbind('click');
                        $("a.a.weiling").each(function () {
                            $(this).click(function () {
                                var red_id = $(this).data('id');
                                //loading带文字
                                layer.closeAll();
                                layer.open({
                                    type: 2
                                    ,content: '领取中'
                                });
                                var e = getcookie("key");
                                $.ajax({
                                    url:ApiUrl+"/index.php?app=red&mod=send_red&sld_addons=red",
                                    data:{key:e,red_id:red_id},
                                    dataType:'json',
                                    success:function(result){
                                        if(result.code!='200'){
                                            return;
                                        }
                                        if(result.datas == 1) {
                                            layer.open({
                                                content: '领取成功！'
                                                , skin: 'msg'
                                                , time: 2 //2秒后自动关闭
                                            });
                                            setInterval(function () {
                                                location.reload();
                                            }, 2000);
                                        }else{
                                            layer.closeAll();
                                            layer.open({
                                                content: result.datas
                                                , skin: 'msg'
                                                , time: 2 //2秒后自动关闭
                                            });
                                        }

                                    },
                                    complete:function(XMLHttpRequest,textStatus){
                                        if(textStatus=='timeout'){
                                            var xmlhttp = window.XMLHttpRequest ? new window.XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHttp");
                                            xmlhttp.abort();
                                            layer.open({
                                                content: '网络超时，请刷新重试！'
                                                , skin: 'msg'
                                                , time: 2 //2秒后自动关闭
                                            });
                                        }
                                    },
                                });
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
                                        setInterval(function () {
                                            timer_data = getLeftTimerData(end_time_str);
                                            if( timer_data.hours>0 || timer_data.minutes>0 || timer_data.seconds>0) {
                                                if ( 1 == 2 && timer_data.day && timer_data.day > 0 ) {
                                                    $(item).html('<em>领取</em>' + timer_data.day + '天');
                                                } else {
                                                    $(item).html('<em>领取</em>' + timer_data.hours + ':' + timer_data.minutes + ':' + timer_data.seconds);
                                                }
                                            }else{
                                                $(item).html('<em>领取</em>' + timer_data.day + '已结束');
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
                                i = "0" + i;
                            }
                            return i;
                        }
                    }
                    $.ajax({
                        url: ApiUrl + "/index.php?app=goods&mod=goods_body",
                        data: {
                            gid: gid
                        },
                        type: "get",
                        success: function(return_data) {
                            if (return_data) {
                                $(".fixed-tab-pannel").html(return_data);

                            }else{

                            }
                        }
                    });


                    if (l.goods_info.is_virtual == "1") {
                        vid = l.store_info.vid;
                        virtual()
                    }
                    if (getcookie("cart_count")) {
                        if (getcookie("cart_count") > 0) {
                            $("#cart_count,#cart_count1").html("<sup>" + getcookie("cart_count") + "</sup>")
                        }
                    }
                    a();
                    $(".pddcp-arrow").click(function() {
                        $(this).parents(".pddcp-one-wp").toggleClass("current")
                    });
                    var p = {};
                    p["spec_list"] = l.spec_list;
                    $(".spec a").click(function() {
                        var e = this;
                        i(e, p)
                    });
                    $(".minus").click(function() {
                        var e = $(".buy-num").val();
                        if (e > 1) {
                            $(".buy-num").val(parseInt(e - 1))
                        }
                    });
                    $(".add").click(function() {
                        var e = parseInt($(".buy-num").val());
                        if(l.pin && $(".goods-price em").html()==pin.sld_pin_price && pin.sld_max_buy != 0) { //拼团增加判断
                            if( e >= l.pin.sld_max_buy || e>=l.pin.sld_stock) {
                                return;
                            }
                            $(".buy-num").val(parseInt(e + 1));
                        }else {
                            if (e < l.goods_info.goods_storage) {
                                $(".buy-num").val(parseInt(e + 1));
                            }
                        }
                    });
                    if (l.goods_info.is_fcode == "1") {
                        $(".minus").hide();
                        $(".add").hide();
                        $(".buy-num").attr("readOnly", true)
                    }
                    $(".pd-collect").click(function() {
                        if ($(this).hasClass("favorate")) {
                            if (dropFavoriteGoods(r)) $(this).removeClass("favorate")
                        } else {
                            if (favoriteGoods(r)) $(this).addClass("favorate")
                        }
                    });
                    if(l.pin){
                        //分享
                        wx_ready=function () {
                            var share_array={
                                title : '我正在拼团，有好东西推荐给你，觉得好就一起加入吧！',
                                desc : $(".goods-detail-name dt").html(),
                                link : location.href,
                                imgUrl : encodeURI($(".img_slide img").first().attr('src')),
                                img_url : encodeURI($(".img_slide img").first().attr('src')),
                                success:function(){
                                    // 分享成功增加会员积分
                                    sharedAction(getcookie("key"),'goods',gid,$(".goods-detail-name dt").text());
                                }
                            };
                            wx.onMenuShareTimeline(share_array); //分享朋友圈
                            wx.onMenuShareAppMessage(share_array); //发送给朋友

                            $(".team_icon .swiper-slide").eq(1).click(function () {
                                WeixinJSBridge.invoke('sendAppMessage',share_array);
                            });
                        }

                        var pin = l.pin;
                        $("body").addClass('addon_pin');
                        $("<link>").attr({ rel: "stylesheet",type: "text/css",href: "../addons/pin/data/css/pin.css"}).appendTo("head");

                        var xiaoshuwei = pin.sld_pin_price_arr[1]!=''?'<em>'+'.'+pin.sld_pin_price_arr[1]+'</em>':'';
                        $('<div><h1>￥<b>'+pin.sld_pin_price_arr[0]+xiaoshuwei+'</b></h1><h2>团长返￥'+pin.sld_return_leader+'<span><i class="iconfonts fa-group"></i> '+pin.sld_team_count+'人团</span></h2><h3>已拼'+pin.sales+'件 <span>距结束<b>00</b>:<b>00</b>:<b>00</b></span></h3></div>').insertBefore('.goods-detail-cnt .goods-detail-name').addClass('goods-detail-pin');
                        var starttime = new Date(pin.sld_end_time);
                        var ddd = window.setInterval(function () {
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

                            if(hour > 99){
                                $('.goods-detail-pin span b').eq(0).html(99);
                                $('.goods-detail-pin span b').eq(1).html(minute);
                                $('.goods-detail-pin span b').eq(2).html(seconds);
                                //window.clearTimeout(ddd);
                            }else {
                                $('.goods-detail-pin span b').eq(0).html(hour);
                                $('.goods-detail-pin span b').eq(1).html(minute);
                                $('.goods-detail-pin span b').eq(2).html(seconds);
                            }
                        }, 1000);

                        //队伍倒计时
                        $(".teams > a").each(function (ind,ele) {
                            var starttime2 = new Date($(ele).data('time'));
                            setInterval(function () {
                                var nowtime = new Date();
                                var time = starttime2 - nowtime;
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
                                var gtime='';
                                if(hour>0)
                                    gtime+=hour+'小时'+minute+'分 后结束';
                                else
                                    gtime+=minute+'分钟'+seconds+'秒 后结束';
                                $(ele).find('h5').html(gtime);
                            }, 1000);
                        });

                        // $("<h6>3人团<span>省29元</span></h6>").appendTo(".goods-detail-pic");
                        $(".goods-detail-price").remove();
                        $('<span>团长返￥'+pin.sld_return_leader+'元</span>').appendTo(".goods-detail-price dt");
                        $(".goods-detail-foot .buy-handle a").last().html('￥'+l.goods_info.goods_price+'<br>单独买');
                        $(".goods-detail-price em").html(pin.sld_pin_price);



                        //显示更多队伍
                        if(pin.team.length>2){
                            console.log(pin.team);
                            $('<b>查看更多</b>').appendTo(".goods-detail-team").click(function () {
                                show_more_team();
                            });
                        }
                        $('.animation-up.add-cart').click(function () {//点击开团做修改
                            buy_type = 2;
                        });
                        $('.animation-up.buy-now').click(function () {//点击直接买
                            buy_type = 1;
                        });

                        if(pin.pinging>0){
                            $(".goods-detail-foot .buy-handle a").first().html('￥'+pin.sld_pin_price+"<br>拼团中");
                            $(".goods-detail-foot .buy-handle a").first().attr('href','pin_detail.html?id='+e.datas.pin.pinging);
                            $("#add-cart").html('￥' + pin.sld_pin_price + "<br>拼团中").attr('href','pin_detail.html?id='+e.datas.pin.pinging);
                            $("#buy-now").html('￥'+$(".goods-detail-price em").html()+'<br>确定购买');
                        }else {
                            if(pin.team_id && pin.sld_tuan_status == 0){
                                $(".goods-detail-foot .buy-handle a").first().html('￥'+pin.sld_pin_price+"<br>参团购买");
                            }else{
                                $(".goods-detail-foot .buy-handle a").first().html('￥'+pin.sld_pin_price+"<br>去开团");
                            }
                            $("#add-cart").html('￥' + pin.sld_pin_price + "<br>确定购买");
                            $("#add-cart").click(function() { //开团按钮
                                if(buy_type==1) {
                                    buy_type=2;
                                    $(".buy-num").val(1);
                                    $(".goods-price em").html(pin.sld_pin_price);
                                    if(pin.sld_max_buy>0) {
                                        $(".goods-option-value em").html('[ 限购<span>'+pin.sld_max_buy+'件 ]</span>');
                                    }
                                    $(".goods-storage").html('库存：'+pin.sld_stock+'件');
                                    $("#buy-now").html('￥'+l.goods_info.goods_price+'<br>单独买');
                                    $("#add-cart").html('￥' + pin.sld_pin_price + "<br>确定购买");
                                    return false;
                                }
                                var e = getcookie("key");
                                if (!e) {
                                    window.location.href = WapSiteUrl + "/cwap_login.html"
                                } else {
                                    var t = parseInt($(".buy-num").val()) || 0;
                                    if (t < 1) {
                                        Zepto.sDialog({
                                            skin: "red",
                                            content: "参数错误！",
                                            okBtn: false,
                                            cancelBtn: false
                                        });
                                        return
                                    }
                                    if (t > pin.sld_stock) {
                                        Zepto.sDialog({
                                            skin: "red",
                                            content: "库存不足！",
                                            okBtn: false,
                                            cancelBtn: false
                                        });
                                        return
                                    }
                                    var team_id = $(".goods-detail-team a.on").attr('tid')?$(".goods-detail-team a.on").attr('tid'):''
                                    location.href = WapSiteUrl + "/cwap_confirm.html?gid=" + r + "&buynum=" + t +'&pin=' + pin.id + '&team_id='+team_id;
                                    return false;
                                }
                            });

                        }
                    }else {
                        $("body").removeClass('addon_pin');
                        $("#add-cart").click(function () {//添加购物车按钮
                            var e = getcookie("key");
                            var t = parseInt($(".buy-num").val());
                            if (!e) {
                                var o = decodeURIComponent(getcookie("goods_cart"));
                                if (o == null) {
                                    o = ""
                                }
                                if (r < 1) {
                                    show_tip();
                                    return false
                                }
                                var a = 0;
                                if (!o) {
                                    o = r + "," + t;
                                    a = 1
                                } else {
                                    var i = o.split("|");
                                    for (var n = 0; n < i.length; n++) {
                                        var l = i[n].split(",");
                                        if (s(l, r)) {
                                            show_tip();
                                            return false
                                        }
                                    }
                                    o += "|" + r + "," + t;
                                    a = i.length + 1
                                }
                                addcookie("goods_cart", o);
                                addcookie("cart_count", a);
                                show_tip();
                                getCartCount();
                                $("#cart_count,#cart_count1").html("<sup>" + a + "</sup>");
                                return false
                            } else {
                                $.ajax({
                                    url: ApiUrl + "/index.php?app=cart&mod=cart_add",
                                    data: {
                                        key: e,
                                        gid: r,
                                        quantity: t
                                    },
                                    type: "post",
                                    success: function (e) {
                                        var t = $.parseJSON(e);
                                        if (checklogin(t.datas)) {
                                            if (!t.datas.error) {
                                                show_tip();
                                                delCookie("cart_count");
                                                getCartCount();
                                                $("#cart_count,#cart_count1").html("<sup>" + getcookie("cart_count") + "</sup>")
                                            } else {
                                                Zepto.sDialog({
                                                    skin: "red",
                                                    content: t.datas.error,
                                                    okBtn: false,
                                                    cancelBtn: false
                                                })
                                            }
                                        }
                                    }
                                })
                            }
                        })
                    }
                    if (l.goods_info.is_virtual == "1") {
                        $("#buy-now").click(function() {
                            var e = getcookie("key");
                            if (!e) {
                                window.location.href = WapSiteUrl + "/cwap_login.html";
                                return false
                            }
                            var t = parseInt($(".buy-num").val()) || 0;
                            if (t < 1) {
                                Zepto.sDialog({
                                    skin: "red",
                                    content: "参数错误！",
                                    okBtn: false,
                                    cancelBtn: false
                                });
                                return
                            }
                            if (t > l.goods_info.goods_storage) {
                                Zepto.sDialog({
                                    skin: "red",
                                    content: "库存不足！",
                                    okBtn: false,
                                    cancelBtn: false
                                });
                                return
                            }
                            if (l.goods_info.buyLimitation > 0 && t > l.goods_info.buyLimitation) {
                                Zepto.sDialog({
                                    skin: "red",
                                    content: "超过限购数量！",
                                    okBtn: false,
                                    cancelBtn: false
                                });
                                return
                            }
                            var o = {};
                            o.key = e;
                            o.cart_id = r;
                            o.quantity = t;
                            $.ajax({
                                type: "post",
                                url: ApiUrl + "/index.php?app=vr_buy&mod=confirm",
                                data: o,
                                dataType: "json",
                                success: function(e) {
                                    if (e.datas.error) {
                                        Zepto.sDialog({
                                            skin: "red",
                                            content: e.datas.error,
                                            okBtn: false,
                                            cancelBtn: false
                                        });
                                        if (e.login == 0) {
                                            // 2秒后自动跳转至登录页
                                            setInterval(function () {
                                                location.href = WapSiteUrl + "/cwap_login.html";
                                            }, 2000);
                                        }
                                    } else {
                                        location.href = WapSiteUrl + "/vr_buy_step1.html?gid=" + r + "&quantity=" + t
                                    }
                                }
                            })
                        })
                    } else {
                        $("#buy-now").click(function() { //直接购买按钮
                            if(buy_type==2){
                                buy_type=1;
                                $(".goods-price em").html(l.goods_info.show_price);
                                var e = getcookie("key");
                                $("#add-cart").html('￥' + pin.sld_pin_price + "<br>开团买");
                                $("#buy-now").html('￥'+l.goods_info.goods_price+'<br>确定购买');
                                $(".goods-storage").html('库存：'+l.goods_info.goods_storage+'件');
                                $(".goods-option-value em").html('');
                                return false;
                            }
                            var e = getcookie("key");
                            if (!e) {
                                window.location.href = WapSiteUrl + "/cwap_login.html"
                            } else {
                                var t = parseInt($(".buy-num").val()) || 0;
                                if (t < 1) {
                                    Zepto.sDialog({
                                        skin: "red",
                                        content: "参数错误！",
                                        okBtn: false,
                                        cancelBtn: false
                                    });
                                    return
                                }
                                if (t > l.goods_info.goods_storage) {
                                    Zepto.sDialog({
                                        skin: "red",
                                        content: "库存不足！",
                                        okBtn: false,
                                        cancelBtn: false
                                    });
                                    return
                                }
                                var o = {};
                                o.key = e;
                                o.cart_id = r + "|" + t;
                                $.ajax({
                                    type: "post",
                                    url: ApiUrl + "/index.php?app=buy&mod=confirm",
                                    data: o,
                                    dataType: "json",
                                    success: function(e) {
                                        if (e.datas.error) {
                                            Zepto.sDialog({
                                                skin: "red",
                                                content: e.datas.error,
                                                okBtn: false,
                                                cancelBtn: false
                                            })
                                            if (e.login == 0) {
                                                // 2秒后自动跳转至登录页
                                                setInterval(function () {
                                                    location.href = WapSiteUrl + "/cwap_login.html";
                                                }, 2000);
                                            }
                                        } else {
                                            location.href = WapSiteUrl + "/cwap_confirm.html?gid=" + r + "&buynum=" + t
                                        }
                                    }
                                })
                            }
                        });
                    }
                } else {
                    Zepto.sDialog({
                        content: l.error + "！<br>请返回上一页继续操作…",
                        okBtn: false,
                        cancelBtnText: "返回",
                        cancelFn: function() {
                            history.back()
                        }
                    })
                }
                $("#buynum").blur(n);
                Zepto.animationUp({
                    valve: ".animation-up,#goods_spec_selected",
                    wrapper: "#product_detail_spec_html",
                    scroll: "#product_roll",
                    start: function() {
                        $(".buy-num").val(1);
                        if(buy_type == 2){
                            $(".buy-num").val(1);
                            $(".goods-price em").html(pin.sld_pin_price);
                            if(pin.sld_max_buy>0) {
                                $(".goods-option-value em").html('[ 限购<span>'+pin.sld_max_buy+'件 ]</span>');
                            }
                            $("#buy-now").html('￥'+l.goods_info.goods_price+'<br>单独买');
                            $(".goods-storage").html('库存：'+pin.sld_stock+'件');
                        }else{
                            $(".goods-price em").html(l.goods_info.show_price);
                            var e = getcookie("key");
                            if(pin) {
                                $("#add-cart").html('￥' + pin.sld_pin_price + "<br>开团买");
                                $("#buy-now").html('￥' + l.goods_info.goods_price + '<br>确定购买');
                                $(".goods-storage").html('库存：' + l.goods_info.goods_storage + '件');
                            }
                            $(".goods-option-value em").html('');
                        }

                        $(".goods-detail-foot").addClass("hide").removeClass("block");
                        $(".goods-option-foot").show();
                        // document.styleSheets[0].addRule('.goods-option-foot .otreh-handle a.cart:after','height: 150%');
                    },
                    close: function() {
                        $(".goods-detail-foot").removeClass("hide").addClass("block");
                        $(".goods-option-foot").hide();
                    }
                });

                // 分享 弹窗
                $(".share_div").on("click",function(e){
                    // window.event.preventDefault();
                    var the_obj = $(this);
                    share_tips_show(the_obj);
                    $(".cancel").on("click",function(){
                        share_tips_close();
                    })
                });
            }
        })
    }
    Zepto.scrollTransparent();
    $("#product_detail_html").on("click", "#get_area_selected",
        function() {
            $.areaSelected({
                success: function(e) {
                    $("#get_area_selected_name").html(e.area_info);
                    var t = e.area_id_2 == 0 ? e.area_id_1: e.area_id_2;
                    $.getJSON(ApiUrl + "/index.php?app=goods&mod=calc", {
                            gid: gid,
                            area_id: t
                        },
                        function(e) {
                            $("#get_area_selected_whether").html(e.datas.if_store_cn);
                            $("#get_area_selected_content").html(e.datas.content);
                            if (!e.datas.if_store) {
                                $(".buy-handle").addClass("no-buy")
                            } else {
                                $(".buy-handle").removeClass("no-buy")
                            }
                        })
                }
            })
        });
    reloadTopNavCurrentStatus();
    $("body").on("click", ".header-nav li a",
        function() {
            var hash_v = $(this).attr('href');
            reloadTopNavCurrentStatus(hash_v);
        });

    $("#list-address-scroll").on("click", "dl > a", map);
    $("#map_all").on("click", map)
});
//分享
wx_share_reday=function () {
    var share_array={
        title : $(".goods-detail-name dt").text(),
        desc : $(".goods-detail-name dd").text(),
        link : location.href,
        imgUrl : encodeURI($(".goods-detail-pic img").eq(0).attr('src')),
        success:function(){
            // 分享成功增加会员积分
            sharedAction(getcookie("key"),'goods',gid,$(".goods-detail-name dt").text());
        }
    };
    wx.onMenuShareTimeline(share_array); //分享朋友圈
    wx.onMenuShareAppMessage(share_array); //发送给朋友
}
// 返回上一级
function backParentRoload(){
    var referurl = document.referrer;//上级网址
    if (referurl) {
        // if(referurl==WapSiteUrl+'/cwap_product_detail.html?gid='+gid){
        window.location.href = WapSiteUrl;
        // }else{
        //     window.location.href = referurl;
        // }
        // window.location.href = referurl;
    }else{
        window.location.href = WapSiteUrl;
    }
    // history.back();
}
// 顶部菜单标签 状态 选中
function reloadTopNavCurrentStatus(now_hash){
    $(".header-nav li").removeClass("cur");
    var check_hash = location.hash;
    if (now_hash) {
        check_hash = now_hash;
    }
    if (check_hash == "#sld_goods_body") {
        $(".header-nav #goodsBody").addClass("cur");
    }else{
        $(".header-nav li").eq(0).addClass("cur");
    }
}
function show_tip() {

    var e = Zepto(".goods-pic .table-set> img").clone().css({
        "z-index": "999",
        height: "3rem",
        width: "3rem"
    });
    e.fly({
        start: {
            left: $(".goods-pic .table-set> img").offset().left,
            top: $(".goods-pic .table-set> img").offset().top - $(window).scrollTop()
        },
        end: {
            left: $("#cart_count1").offset().left + 40,
            top: $("#cart_count1").offset().top - $(window).scrollTop(),
            width: 0,
            height: 0
        },
        onEnd: function() {
            e.remove()
        }
    })
}
function virtual() {
    $("#get_area_selected").parents(".goods-detail-item").remove();
    $.getJSON(ApiUrl + "/index.php?app=goods&mod=store_o2o_addr", {
            vid: vid
        },
        function(e) {
            if (!e.datas.error) {
                if (e.datas.addr_list.length > 0) {
                    $("#list-address-ul").html(template.render("list-address-script", e.datas));
                    map_list = e.datas.addr_list;
                    var t = "";
                    t += '<dl index_id="0">';
                    t += "<dt>" + map_list[0].name_info + "</dt>";
                    t += "<dd>" + map_list[0].address_info + "</dd>";
                    t += "</dl>";
                    t += '<p><a href="tel:' + map_list[0].phone_info + '"></a></p>';
                    $("#goods-detail-o2o").html(t);
                    $("#goods-detail-o2o").on("click", "dl", map);
                    if (map_list.length > 1) {
                        $("#store_addr_list").html("查看全部" + map_list.length + "家分店")
                    } else {
                        $("#store_addr_list").html("查看商家地址")
                    }
                    $("#map_all > em").html(map_list.length)
                } else {
                    $(".goods-detail-o2o").hide()
                }
            }
        });
    $.animationLeft({
        valve: "#store_addr_list",
        wrapper: "#list-address-wrapper",
        scroll: "#list-address-scroll"
    });
    Zepto.animationUp({
        valve: ".goods-red",
        wrapper: "#product_detail_red_html",
        scroll: "#product_red_roll"
    });
}
function map() {
    $("#map-wrappers").removeClass("hide").removeClass("right").addClass("left");
    $("#map-wrappers").on("click", ".header-l > a",
        function() {
            $("#map-wrappers").addClass("right").removeClass("left")
        });
    $("#baidu_map").css("width", document.body.clientWidth);
    $("#baidu_map").css("height", document.body.clientHeight);
    map_index_id = $(this).attr("index_id");
    if (typeof map_index_id != "string") {
        map_index_id = ""
    }
    if (typeof map_js_flag == "undefined") {
        $.ajax({
            url: WapSiteUrl + "/js/map.js",
            dataType: "script",
            async: false
        })
    }
    if (typeof BMap == "object") {
        baidu_init()
    } else {
        load_script()
    }
}
//显示更多拼团
function show_more_team(){
    var htm="<div id=\"more_team\" class=\"bbctouch-bottom-mask up\"><div class=\"bbctouch-bottom-mask-bg\"></div>\n" +
        "\t<div class=\"bbctouch-bottom-mask-block\">\n" +
        "\t\t<div class=\"bbctouch-bottom-mask-top\">\n" +
        "\t\t\t<a href=\"javascript:void(0);\" class=\"bbctouch-bottom-mask-close\"><i></i></a>\n" +
        "\t\t</div><h1>已发起的团</h1>\n" +
        "\t\t<div class='goods-detail-team'></div>\n" +
        "\t\t</div></div>";
    $("body").append(htm).addClass('visibly');
    $(".goods-detail-team a").clone().removeAttr('style').appendTo("#more_team .goods-detail-team");
    $("#more_team").find('.bbctouch-bottom-mask-close').click(function () {
        $("#more_team").remove();
        $("body").removeClass("visibly");
    });
}
//选中某个拼团
function pick_team(team_id) {
    if($("#more_team").size()>0) {
        $("#more_team").remove();
        $("body").removeClass("visibly");
    }
    var str = $(".animation-up.add-cart").html();
    if(str.substr(str.indexOf('<br')+4)=='拼团中'){
        Zepto.sDialog({
            content: '此商品您正在拼团',
            okBtn: true,
            cancelBtn:true,
            okBtnText: "查看",
            okFn: function() {
                location.href=$('.animation-up.add-cart').attr('href');
            }
        });
    }else {
        if ($(".goods-detail-team a.team_" + team_id).hasClass('on')) {
            str = str.substr(0, str.indexOf('<br>'));
            $(".animation-up.add-cart").html(str + '<br>去开团');
            $(".goods-detail-team a.team_" + team_id).removeClass("on");
        } else {
            $(".goods-detail-team > a").removeClass("on");
            var dd = $(".goods-detail-team a.team_" + team_id);
            // $(".goods-detail-team > a.team_" + team_id).remove();
            $(dd).addClass('on');
            // $(".goods-detail-team > a").eq(2).hide();
            str = str.substr(0, str.indexOf('<br>'));
            $(".animation-up.add-cart").html(str + '<br>参团购买');
            buy_type=2;
            $(".goods-detail-sel").click();
        }
    }

}

//打开拼团
function open_team(team_id) {
    location.href = 'pin_detail.html?id='+team_id;
}
/*如果有门店的话单独处理*/
function have_dian() {
    if (window.navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == 'micromessenger' && typeof jQuery != 'undefined') {  //微信
        wx_ready = function () {
            wx.getLocation({
                complete: function (res) {
                    if (res.errMsg) {
                        lng = res.longitude;
                        lat = res.latitude;
                    }
                    console.log(res);
                    start_get_dian();
                }
            });
            wx_share_reday();
        }
    } else {
        var geolocation;
        map.plugin('AMap.Geolocation', function () {
            geolocation = new AMap.Geolocation({
                enableHighAccuracy: true,//是否使用高精度定位，默认:true
                timeout: 10000,          //超过10秒后停止定位，默认：无穷大
                buttonPosition: 'LB'
            });
            map.addControl(geolocation);
            geolocation.getCurrentPosition();
            AMap.event.addListener(geolocation, 'complete', onComplete);//返回定位信息
            AMap.event.addListener(geolocation, 'error', onError);      //返回定位出错信息
        });

        //解析定位结果
        function onComplete(data) {
            var str = ['定位成功'];
            str.push('经度：' + data.position.getLng());
            str.push('纬度：' + data.position.getLat());
            if (data.accuracy) {
                str.push('精度：' + data.accuracy + ' 米');
            }//如为IP精确定位结果则没有精度信息
            str.push('是否经过偏移：' + (data.isConverted ? '是' : '否'));
            lng = data.position.getLng();
            lat = data.position.getLat();
            isgaode = 1;
            console.log(str.join(' '));
            start_get_dian();
        }

        //解析定位错误信息
        function onError(data) {
            console.log('定位失败');
            start_get_dian();
        }
    }
}
//获取门店
function start_get_dian() {
    if (hasmore==false) {
        return false
    }
    $(".pickup-bomb-box .loading").remove();

    hasmore = false;
    param = {};
    param.page = page;
    if($(".pickup-bomb-box").size() >0){
        pn++;
    }
    param.pn = pn;

    if (key != "") {
        param.key = key
    }
    if (lng != "") {
        param.lng = lng
    }
    if (lat != "") {
        param.lat = lat
    }
    param.gid=gid;
    param.isgaode=isgaode;
    $.getJSON(ApiUrl + "/index.php?app=goods&mod=get_dians_by_gid",param,function(e){
        if(e.code==200) {
            hasmore = e.hasmore;
            if ( $(".pickup-bomb-box").size() < 1 && e.datas.data.length>0) {
                var i = template.render("dian_tpl", e);
                $("#goodsDian").click(function () {
                    $(".mask-div").click(function () {
                        $('.pickup-bomb-box').removeClass('pickup-bomb-show').addClass('pickup-bomb-hide');
                        $(".mask-div").hide();
                        $("body").removeClass("visibly");
                    });
                    $("body").addClass('visibly');
                    $(".mask-div").show();
                    $(".pickup-bomb-box").removeClass('pickup-bomb-hide').addClass('pickup-bomb-show');
                });
                $("body").append(i);
                $('.logistics-store-list').on('scroll',function(){
                    if($(".pickup-bomb-box .loading").size()>0){
                        var lod = $(".pickup-bomb-box .loading").last();
                        // console.log(lod.offset().top - $(".logistics-store-list").scrollTop() - -1*lod.height()-1);
                        // $(".box-title").html(lod.offset().top - $(".logistics-store-list").scrollTop() - -1*lod.height()-1);
                        if (lod.offset().top - $(".logistics-store-list").scrollTop() < -1*lod.height()-1) {
                            start_get_dian();
                        }
                    }
                });


                // 自提点选择弹框关闭事件
                $(".pickup-bomb-box").on('click', '.box-title .close', function () {
                    $('.pickup-bomb-box').removeClass('pickup-bomb-show').addClass('pickup-bomb-hide');
                    $(".mask-div").hide();
                    $("body").removeClass("visibly");
                });

            }else{
                var i = template.render("dian_list_tpl", e);
                $(".logistics-store-list").append(i);
            }

            // 添加查看地图按钮点击事件
            $(".logistics-item .i").unbind('click');
            $(".logistics-item .i").each(function (indd,ele) {
                $(ele).click(function () {
                    $(".pickup-bomb-box a").trigger('click');
                    var dians = new Array();
                    $(".pickup-bomb-box .logistics-item").each(function (ind, ele) {
                        if (ind < 10) {
                            var dian_info = {
                                lat: $(ele).data('lat'),
                                lng: $(ele).data('lng'),
                                name: $(ele).find('.logistics-name').html(),
                                addr: $(ele).find('.logistics-address').html(),
                                position: [$(ele).data('lng'), $(ele).data('lat')]
                            };
                            dians.push(dian_info);
                        }
                    });
                    var pageii = layer.open({
                        type: 1
                        ,
                        content: '<div id="mapContainer" ></div>'
                        ,
                        success: function () {
                            init(dians,indd);
                        }
                        ,
                        anim: 'up'
                        ,
                        style: 'position:fixed; left:0; top:0; width:100%; height:100%; border: none; -webkit-animation-duration: .5s; animation-duration: .5s;'
                    });
                });
            });
        }
    })
}

//地图
function init(info,indd) {
    map = new AMap.Map("mapContainer", {
        zoom: 18,
        center:[info[0].lng,info[0].lat]
    });
    info.forEach(function(tt,ind) {
        marker = new AMap.Marker({
            map: map,
            icon: 'http://webapi.amap.com/theme/v1.3/markers/n/mark_'+(ind==indd?'r':'b')+(ind+1)+'.png',
            position: [tt.lng, tt.lat]
        });
        marker.setLabel({
            offset: new AMap.Pixel(20, 20),//修改label相对于maker的位置
            content: "<h2>"+tt.name+"</h2><h4>"+tt.addr+"</h4>"
        });
        marker.on('click',function(e){
            // marker.markOnAMAP({
            //     name:tt.name,
            //     position:marker.getPosition()
            // });
            // $(".pickup-bomb-box .logistics-item").eq(ind).children('label').trigger('click');
            // layer.closeAll();
        });
    });

    var geolocation;
    map.plugin('AMap.Geolocation', function() {
        geolocation = new AMap.Geolocation({
            enableHighAccuracy: true,//是否使用高精度定位，默认:true
            timeout: 10000,          //超过10秒后停止定位，默认：无穷大
            buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
            zoomToAccuracy: true,      //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
            buttonPosition:'LB'
        });
        map.addControl(geolocation);
    });

    map.on('complete', function() {
        var newCenter = map.setFitView();
        $("#mapContainer").append('<a id="close" href="javascript:;"  class="button" onclick="layer.closeAll();"><i class="fa fa-times"></i></a>');
    });
    map.addControl(new AMap.ToolBar());
}