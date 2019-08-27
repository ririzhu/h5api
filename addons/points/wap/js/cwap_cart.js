wap_now = 'sld_cart';
$(function() {

    template.helper("isEmpty",
        function(t) {
            for (var a in t) {
                return false
            }
            return true
        });
    template.helper("decodeURIComponent",
        function(t) {
            return decodeURIComponent(t)
        });
    var t = getcookie("key");
    if (!t) {
        var a = decodeURIComponent(getcookie("goods_cart"));
        if (a != null) {
            var e = a.split("|")
        } else {
            e = {}
        }
        var r = new Array;
        var o = 0;
        if (e.length > 0) {
            for (var i = 0; i < e.length; i++) {
                var n = e[i].split(",");
                if (isNaN(n[0]) || isNaN(n[1])) continue;
                data = getGoods(n[0], n[1]);
                if ($.isEmptyObject(data)) continue;
                if (r.length > 0) {
                    var c = false;
                    for (var s = 0; s < r.length; s++) {
                        if (r[s].vid == data.vid) {
                            r[s].goods.push(data);
                            c = true
                        }
                    }
                    if (!c) {
                        var l = {};
                        l.vid = data.vid;
                        l.store_name = data.store_name;
                        var a = new Array;
                        a = [data];
                        l.goods = a;
                        r = [l]
                    }
                } else {
                    var l = {};
                    l.vid = data.vid;
                    l.store_name = data.store_name;
                    var a = new Array;
                    a = [data];
                    l.goods = a;
                    r = [l]
                }
                o += parseFloat(data.goods_sum)
            }
        }
        if(r.length>0){
            for(var i=0;i<r.length;i++){
                r[i][0] = r[i]['vid'];
                r[i][1] = r[i]['store_name'];
                r[i][2] = r[i]['goods'];
            }
        }

        var d = {
            cart_list: r,
            sum: o.toFixed(2),
            cart_count: e.length,
            check_out: false
        };
        d.WapSiteUrl = WapSiteUrl;
        var u = template.render("cart-list", d);
        $("#cart-list").addClass("no-login");
        $("#cart-list-wp").html(u);
        if (d.cart_list.length == 0) {
            // 获取推荐商品
            $.ajax({
                type: 'get',
                url: ApiUrl + "/index.php?app=goods&mod=getRecGoodsList",
                dataType: 'json',
                data: {},
                async: false,
                success: function (e) {
                    var rec_tmp = template.render("rec-goods-list", e.datas);
                    $("#rec_goods_list").html(rec_tmp);
                    Swipe($("#SLDTJGOODS")[0], {
                        startSlide: 0,
                        speed: 800,
                        auto: 3000,
                        autoplay: true,
                        continuous: false,
                        disableScroll: false,
                        stopPropagation: true,
                        callback: function(index, elem) {},
                        transitionEnd: function(index, elem) {}
                    });
                }
            });
            get_footer()
        }
        $(".goto-settlement,.goto-shopping").parent().hide();
        $(".goods-del").click(function() {
            var t = $(this);
            $.sDialog({
                skin: "red",
                content: "确认删除吗？",
                okBtn: true,
                cancelBtn: true,
                okFn: function() {
                    var a = t.attr("cart_id");
                    for (var r = 0; r < e.length; r++) {
                        var o = e[r].split(",");
                        if (o[0] == a) {
                            e.splice(r, 1);
                            break
                        }
                    }
                    addcookie("goods_cart", e.join("|"));
                    delCookie("cart_count");
                    addcookie("cart_count", e.length);

                    location.reload()
                }
            })
        });
        $("#cart-list-wp").on("click", ".check-out > a",
            function() {
                window.location.href='cwap_login.html';
            });
        $(".minus").click(function() {
            var t = $(this).parents(".cart-litemw-cnt");
            var a = t.attr("cart_id");
            var g_s = t.attr("storage");
            for (var r = 0; r < e.length; r++) {
                var o = e[r].split(",");
                if (o[0] == a) {
                    if (o[1] == 1) {
                        return false
                    }
                    o[1] = parseInt(o[1]) - 1;
                    if (o[1] <= g_s) {
                        e[r] = o[0] + "," + o[1];
                        t.find(".buy-num").val(o[1])
                    }else{
                        $.sDialog({
                            skin: "red",
                            content: '不能超过该商品最大库存',
                            okBtn: false,
                            cancelBtn: false
                        })
                    }
                }
            }
            addcookie("goods_cart", e.join("|"))
        });
        $(".add").click(function() {
            var t = $(this).parents(".cart-litemw-cnt");
            var a = t.attr("cart_id");
            var g_s = t.attr("storage");
            for (var r = 0; r < e.length; r++) {
                var o = e[r].split(",");
                if (o[0] == a) {
                    o[1] = parseInt(o[1]) + 1;
                    if (o[1] <= g_s) {
                        e[r] = o[0] + "," + o[1];
                        t.find(".buy-num").val(o[1])   
                    }else{
                        $.sDialog({
                            skin: "red",
                            content: '不能超过该商品最大库存',
                            okBtn: false,
                            cancelBtn: false
                        })
                    }
                }
            }
            addcookie("goods_cart", e.join("|"))
        })
    } else {
        function p() {
            $.ajax({
                url: ApiUrl + "/index.php?app=cart&mod=cart_list_store",
                type: "post",
                dataType: "json",
                data: {
                    key: t
                },
                success: function(t) {
                    if (checklogin(t.login)) {
                        if (!t.datas.error) {
                            var a = t.datas;
                            a.WapSiteUrl = WapSiteUrl;
                            a.check_out = true;
                            template.helper("$getLocalTime",
                                function(t) {
                                    var a = new Date(parseInt(t) * 1e3);
                                    var e = "";
                                    e += a.getFullYear() + "年";
                                    e += a.getMonth() + 1 + "月";
                                    e += a.getDate() + "日 ";
                                    return e
                                });
                            var e = template.render("cart-list", a);
                            $("#cart-list-wp").html(e);
                            if (a.cart_list.length == 0) {
                                // 获取推荐商品
                                $.ajax({
                                    type: 'get',
                                    url: ApiUrl + "/index.php?app=goods&mod=getRecGoodsList",
                                    dataType: 'json',
                                    data: {},
                                    async: false,
                                    success: function (e) {
                                        var rec_tmp = template.render("rec-goods-list", e.datas);
                                        $("#rec_goods_list").html(rec_tmp);
                                        Swipe($("#SLDTJGOODS")[0], {
                                            startSlide: 0,
                                            speed: 800,
                                            auto: 3000,
                                            autoplay: true,
                                            continuous: false,
                                            disableScroll: false,
                                            stopPropagation: true,
                                            callback: function(index, elem) {},
                                            transitionEnd: function(index, elem) {}
                                        });
                                    }
                                });
                                get_footer()
                            }
                            $(".goods-del").click(function() {
                                var t = $(this).attr("cart_id");
                                $.sDialog({
                                    skin: "red",
                                    content: "确认删除吗？",
                                    okBtn: true,
                                    cancelBtn: true,
                                    okFn: function() {
                                        f(t)
                                    }
                                })
                            });
                            $(".minus").click(h);
                            $(".add").click(g);
                            $(".buynum").blur(m);
                            // $.animationUp();
                            $(".bbctouch-voucher-list").on("click", ".btn",
                                function() {
                                    // getFreeVoucher($(this).attr("data-tid"))
                                });
                            $(".store-huodong").click(function() {
                                $(this).css("height", "auto")
                            })
                        } else {
                            alert(t.datas.error)
                        }
                    }
                }
            })
        }
        p();
        function f(a) {
            $.ajax({
                url: ApiUrl + "/index.php?app=cart&mod=cart_del",
                type: "post",
                data: {
                    key: t,
                    cart_id: a
                },
                dataType: "json",
                success: function(t) {
                    if (checklogin(t.login)) {
                        if (!t.datas.error && t.datas == "1") {
                            p();
                            delCookie("cart_count");
                            getCartCount()
                        } else {
                            alert(t.datas.error)
                        }
                    }
                }
            })
        }
        function h() {
            var t = this;
            _(t, "minus")
        }
        function g() {
            var t = this;
            _(t, "add")
        }
        function _(a, e) {
            var r = $(a).parents(".cart-litemw-cnt");
            var o = r.attr("cart_id");
            var i = r.find(".buy-num");
            var n = r.find(".goods-price");
            var c = parseInt(i.val());
            var g_s = r.attr("storage");
            var s = 1;
            if (e == "add") {
                s = parseInt(c + 1)
            } else {
                if (c > 1) {
                    s = parseInt(c - 1)
                } else {
                    return false
                }
            }
            if (s<=g_s) {
                $(".pre-loading").removeClass("hide");
                $.ajax({
                    url: ApiUrl + "/index.php?app=cart&mod=cart_edit_quantity",
                    type: "post",
                    data: {
                        key: t,
                        cart_id: o,
                        quantity: s
                    },
                    dataType: "json",
                    success: function(t) {
                        if (checklogin(t.login)) {
                            if (!t.datas.error) {
                                i.val(s);
                                n.html("￥<em>" + t.datas.goods_price + "</em>");
                                calculateTotalPrice()
                            } else {
                                $.sDialog({
                                    skin: "red",
                                    content: t.datas.error,
                                    okBtn: false,
                                    cancelBtn: false
                                })
                            }
                            $(".pre-loading").addClass("hide")
                        }
                    }
                })
            }else{
                $.sDialog({
                    skin: "red",
                    content: '不能超过该商品最大库存',
                    okBtn: false,
                    cancelBtn: false
                })
            }
        }
        $("#cart-list-wp").on("click", ".check-out > a",
            function() {
                if (!$(this).parent().hasClass("ok")) {
                    return false
                }
                var t = [];
                $(".cart-litemw-cnt").each(function() {
                    if ($(this).find('input[name="cart_id"]').prop("checked")) {
                        var a = $(this).find('input[name="cart_id"]').val();
                        var e = parseInt($(this).find(".value-box").find("input").val());
                        var r = a + "|" + e;
                        t.push(r)
                    }
                });
                var a = t.toString();
                window.location.href = WapSiteUrl + "/cwap_confirm.html?ifcart=1&cart_id=" + a
            });
        $.sValid.init({
            rules: {
                buynum: "digits"
            },
            messages: {
                buynum: "请输入正确的数字"
            },
            callback: function(t, a, e) {
                if (t.length > 0) {
                    var r = "";
                    $.map(a,
                        function(t, a) {
                            r += "<p>" + t + "</p>"
                        });
                    $.sDialog({
                        skin: "red",
                        content: r,
                        okBtn: false,
                        cancelBtn: false
                    })
                }
            }
        });
        function m() {
            $.sValid()
        }
    }
    $("#cart-list-wp").on("click", ".store_checkbox",
        function() {
            $(this).parents(".bbctouch-cart-container").find('input[name="cart_id"]').prop("checked", $(this).prop("checked"));
            is_store_check();
            calculateTotalPrice()
        });
    $("#cart-list-wp").on("click", ".all_checkbox",
        function() {
            $("#cart-list-wp").find('input[type="checkbox"]').prop("checked", $(this).prop("checked"));
            calculateTotalPrice()
        });
    $("#cart-list-wp").on("click", 'input[name="cart_id"]',
        function() {
            var class_name = $(this).attr('class');
            is_all_storegoods(class_name);
            is_store_check();
            calculateTotalPrice()
        })
});
//检测店铺的复选框是否全选中
function is_store_check() {
    $('.store_checkbox').each(function (i,v) {
        if(!$(v).prop("checked")){
            $('.all_checkbox').prop("checked", false);
            return false;
        }
        $('.all_checkbox').prop("checked", true);
    });
}
//检测同一个店铺的商品是否都选中
function is_all_storegoods(classname) {
    var class_name = '.'+classname;
    var parent_ul = $(class_name).parent().parent().parent();
    parent_ul.find('input[name="cart_id"]').each(function (i,v) {
        if(!$(v).prop("checked")){
            parent_ul.prev().find('input').prop("checked", false);
            return false;
        }
        parent_ul.prev().find('input').prop("checked", true);
    });
}

function calculateTotalPrice() {
    var t = parseFloat("0.00");
    $(".cart-litemw-cnt").each(function() {
        if ($(this).find('input[name="cart_id"]').prop("checked")) {
            t += parseFloat($(this).find(".goods-price").find("em").html()) * parseInt($(this).find(".value-box").find("input").val())
        }
    });
    $(".total-money").find("em").html(t.toFixed(2));
    check_button();
    return true
}
function getGoods(t, a) {
    var e = {};
    $.ajax({
        type: "get",
        url: ApiUrl + "/index.php?app=goods&mod=goods_detail&gid=" + t,
        dataType: "json",
        async: false,
        success: function(r) {
            if (r.datas.error) {
                return false
            }
            var o = r.datas.goods_image.split(",");
            e.cart_id = t;
            e.vid = r.datas.store_info.vid;
            e.store_name = r.datas.store_info.store_name;
            e.gid = t;
            e.goods_name = r.datas.goods_info.goods_name;
            e.goods_price = r.datas.goods_info.goods_price;
            e.goods_num = a;
            e.goods_image_url = o[0];
            e.goods_sum = (parseInt(a) * parseFloat(r.datas.goods_info.goods_price)).toFixed(2);
            e.storage = r.datas.goods_info.goods_storage;
        }
    });
    return e
}
function get_footer() {
    footer = true;
    $.ajax({
        url: WapSiteUrl + "/js/cwap_footer.js",
        dataType: "script"
    })
}
function check_button() {
    var t = false;
    $('input[name="cart_id"]').each(function() {
        if ($(this).prop("checked")) {
            t = true
        }
    });
    if (t) {
        $(".check-out").addClass("ok")
    } else {
        $(".check-out").removeClass("ok")
    }
}