var gid = GetQueryString("gid");
var map_list = [];
var map_index_id = "";
var vid;
var buy_type=1; //正常购买
$(function() {
    var e = getcookie("key");
    //添加浏览记录
    if(e){
        $.ajax({
            url: ApiUrl + "/index.php?app=points_goods&mod=addUserBrowserGoods&sld_addons=points",
            type: "post",
            data: {
                gid: gid,
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
            url: ApiUrl + "/index.php?app=points_goods&mod=goods_detail&sld_addons=points",
            type: "get",
            data: {
                gid: r,
                key: e,
            },
            dataType: "json",
            success: function(e) {
                if(e.status == 255){
                    var refer = document.referrer;
                    console.log(refer);
                    Zepto.sDialog({
                        content: e.msg + "！<br>请返回上一页继续操作…",
                        okBtn: false,
                        cancelBtnText: "返回",
                        cancelFn: function() {
                            history.back()
                        }
                    });
                }
                var l = e.data;
                //if (!l.error) {
                if (l) {
                    var _ = template.render("product_detail", l);
                    $("#product_detail_html").html(_);
                    //console.log(111,$('.forList').find('li'));
//--------------------兑换记录---------------------
                    var num=0;
                    var arr=[];
                    var lunbo = e.lunbo;
                    if(lunbo){
                        var lunbostr = '';
                        var lunbonum = lunbo.length;
                        console.log(lunbonum);
                        $.each(lunbo,function(k,v){
                            lunbostr += '<li> <img src="'+ v.avart +'" alt=""><span>'+ v.point_buyername +'</span><span><time>'+ v.time +'</time>兑换了'+ v.point_goodsnum+'件</span> </li>';
                        });
                        $(".forList").html(lunbostr);

                        //$(".forList").find('li').each(function(){
                        //    arr.push($(this));
                        //});

                        setInterval(function(){

                            //arr.push(arr.shift());
                            num+=1;
                            if(num>parseInt($('.forList').find('li').height())*(lunbonum-1)){
                                num=0;
                            }
                            console.log(num);
                            $(".Forrecord ul").css({"margin-top":-num+'px'});
                        },50);
                    }




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
                    console.log(l);
                    var _ = template.render("product_detail_sepc", l);
                    $("#product_detail_spec_html").html(_);

                    var _tags = template.render("product_tags_intro", l);
                    $("#product_detail_tag_html").html(_tags);

                    }
                    $.ajax({
                        url: ApiUrl + "/index.php?app=points_goods&mod=goods_body&sld_addons=points",
                        data: {
                            gid: gid
                        },
                        type: "get",
                        success: function(return_data) {
                            if (return_data) {
                                $(".fixed-tab-pannel").html(return_data);
                            }else{
                                $(".fixed-tab-pannel").html('<div class="no-data">暂无商品详情 </div>');
                            }
                        }
                    });
                    //获取推荐商品
                $.ajax({
                    url: ApiUrl + "/index.php?app=points_goods&mod=points_goods_tuijian&sld_addons=points",
                    type: "get",
                    data: {},
                    dataType: "json",
                    success: function(e) {
                            var _tuijian = template.render("tuijian_script", e);
                            $(".tuijian").html(_tuijian);
                    }

                });
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
                        }else{
                            layer.open({
                                content: '最小数量为1'
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                            });
                        }
                    });
                    $(".add").click(function() {
                        var e = parseInt($(".buy-num").val());

                            if (e < l.pgoods_storage) {
                                $(".buy-num").val(parseInt(e + 1));
                            }else{
                                layer.open({
                                    content: '库存不足'
                                    ,skin: 'msg'
                                    ,time: 2 //2秒后自动关闭
                                });
                            }

                    });

                    $(".pd-collect").click(function() {
                        if ($(this).hasClass("favorate")) {
                            if (dropFavoriteGoods(r)) $(this).removeClass("favorate")
                        } else {
                            if (favoriteGoods(r)) $(this).addClass("favorate")
                        }
                    });

                $(document).on('click','.cartlist',function(){
                    window.location.href = 'cwap_shopping_cart.html';
                });
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
                            if (t > l.pgoods_storage) {
                                Zepto.sDialog({
                                    skin: "red",
                                    content: "库存不足！",
                                    okBtn: false,
                                    cancelBtn: false
                                });
                                return
                            }
                            if (l.pgoods_islimit > 0 && t > l.pgoods_limitnum) {
                                Zepto.sDialog({
                                    skin: "red",
                                    content: "超过限购数量！",
                                    okBtn: false,
                                    cancelBtn: false
                                });
                                return
                            }
                            $.ajax({
                                type: "post",
                                url: ApiUrl + "/index.php?app=userorder&mod=addcart&sld_addons=points",
                                data: {num:t,key:e,gid: l.pgid},
                                dataType: "json",
                                success: function(e) {
                                    if (e.login == 0) {
                                        // 2秒后自动跳转至登录页
                                        //setInterval(function () {
                                            location.href = WapSiteUrl + "/cwap_login.html";
                                        //}, 2000);
                                    }
                                    if (e.status == 255) {
                                        Zepto.sDialog({
                                            skin: "red",
                                            content: e.msg,
                                            okBtn: false,
                                            cancelBtn: false
                                        })

                                    } else {
                                        location.href = 'cwap_shopping_cart.html';
                                    }
                                }
                            });

                        });
                //$('.cart_edit_num').on('click',function(){
                //    $('#buynum').val((parseInt($('#buynum').val())-1)<=0?1:(parseInt($('#buynum').val())-1));
                //});
                //$('.add').on('click',function(){
                //    $('#buynum').val(parseInt($('#buynum').val())+1);
                //});
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
                            $(".goods-price em").html(l.pgoods_points);
                            var e = getcookie("key");
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
                Zepto.animationUp({
                    valve: "#getVoucher",
                    wrapper: "#voucher_html",
                    scroll: "#voucher_roll"
                });
                Zepto.animationUp({
                    valve: ".goods-tags",
                    wrapper: "#product_detail_tag_html",
                    scroll: "#product_tags_roll"
                });
                Zepto.animationUp({
                    valve: ".goods-red",
                    wrapper: "#product_detail_red_html",
                    scroll: "#product_red_roll"
                });
                $("#voucher_html").on("click", ".btn",
                    function() {
                        getFreeVoucher($(this).attr("data-tid"))
                    });
                $(".kefu").click(function() {
                    window.location.href = WapSiteUrl + "/cwap_im_detail.html?gid=" + r + "&t_id=" + e.datas.store_info.vid;
                })
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
    //$("body").on("click", "#goodsEvaluation,#goodsEvaluation1",
    //    function() {
    //        window.location.href = WapSiteUrl + "/cwap_pro_eval_list.html?gid=" + gid
    //    });
    //$("#list-address-scroll").on("click", "dl > a", map);
    //$("#map_all").on("click", map)
});
//分享
wx_share_reday=function () {
    var share_array={
        title : $(".goods-detail-name dt").text(),
        desc : $(".goods-detail-name dd").text(),
        link : location.href,
        imgUrl : encodeURI($(".goods-detail-pic img").eq(0).attr('src'))
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


