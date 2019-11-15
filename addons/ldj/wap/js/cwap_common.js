var wx_ready = function () {

}

function GetQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return decodeURIComponent(r[2]);
    return "";
}

//cookie修改为localstorage
function addcookie(name, value, expireHours) {
    window.localStorage.setItem(name, value);
}

function getcookie(name) {
    var value = window.localStorage.getItem(name);
    if (value) {
        return value;
    } else {
        return ""
    }
}

function delCookie(name) {//删除cookie
    window.localStorage.removeItem(name);
}

function delHisCookie(name) {//删除历史记录cookie
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    name = cookie_pre + name;
    var cval = getcookie(name);
    if (cval != null) document.cookie = name + "=" + cval + "; path=/;expires=" + exp.toGMTString();
}


function checklogin(state) {
    if (state == 0) {
        location.href = WapSiteUrl + '/cwap_the_login.html';
        return false;
    } else {
        return true;
    }
}

//检测权限接口,是否可以进入推手系统
function check_Jurisdiction(key) {
    if (key) {
        $.ajax({
            url: ApiUrl + '/index.php?app=index&mod=check_Jurisdiction&sld_addons=spreader',
            data: {ssys_key: key},
            type: 'post',
            dataType: 'json',
            success: function (res) {
                if (res.status == 255) {
                    location.href = WapSiteUrl + '/cwap_the_login.html';
                } else if (res.status == 155) {
                    location.href = WapSiteUrl + '/cwap_to_apply_for.html';
                }
            },
            complete: function (xhr) {
                xhr = null;
            }
        });
    }
}

function contains(arr, str) {
    var i = arr.length;
    while (i--) {
        if (arr[i] === str) {
            return true;
        }
    }
    return false;
}

//返回logo图片全路径
function getSldLogoUrl() {
    $.ajax({
        type: 'post',
        url: ApiUrl + "/index.php?app=login&mod=getSldWapLogo",
        data: {},
        dataType: 'json',
        success: function (result) {
            $('.login_logo img').attr('src', result.sldwaplogo);
        }
    });
}

function buildUrl(type, data) {
    switch (type) {
        case 'keyword':
            return WapSiteUrl + '/cwap_product_list.html?keyword=' + encodeURIComponent(data);
        case 'special':
            return WapSiteUrl + '/cwap_subject.html?topic_id=' + data;
        case 'goods':
            return WapSiteUrl + '/cwap_product_detail.html?gid=' + data;
        case 'url':
            return data;
    }
    return WapSiteUrl;
}

$(function () {
    setTimeout(function () {
        if ($("#content .container").height() < $(window).height()) {
            $("#content .container").css("min-height", $(window).height());
        }
    }, 300);
    $("#bottom .nav .get_down").click(function () {
        $("#bottom .nav").animate({"bottom": "-50px"});
        $("#nav-tab").animate({"bottom": "0px"});
    });
    $("#nav-tab-btn").click(function () {
        $("#bottom .nav").animate({"bottom": "0px"});
        $("#nav-tab").animate({"bottom": "-40px"});

    });
    setTimeout(function () {
        $("#bottom .nav .get_down").click();
    }, 500);
    $("#scrollUp").click(function (t) {
        $("html, body").scrollTop(300);
        $("html, body").animate({
            scrollTop: 0
        }, 300);
        t.preventDefault()
    });
    $("#header_user").on("click", "#header-nav",
        function () {
            if ($(".bbctouch-nav-layout").hasClass("show")) {
                $(".bbctouch-nav-layout").removeClass("show")
            } else {
                $(".bbctouch-nav-layout").addClass("show")
            }
        });
    $("#header_user").on("click", ".bbctouch-nav-layout",
        function () {
            $(".bbctouch-nav-layout").removeClass("show")
        });
    $(document).scroll(function () {
        $(".bbctouch-nav-layout").removeClass("show")
    });

    $(".input-del").click(function () {
        $(this).parent().removeClass("write").find("input").val("");
        btnCheck($(this).parents("form"))
    });
    $("body").on("click", "label",
        function () {
            if ($(this).has('input[type="radio"]').length > 0) {
                $(this).addClass("checked").siblings().removeClass('checked').find('input[type="radio"]').removeAttr("checked")
            } else if ($(this).has('[type="checkbox"]')) {
                if ($(this).find('input[type="checkbox"]').prop("checked")) {
                    $(this).addClass("checked")
                    $("#wrapperPaymentPassword").show();
                } else {
                    $(this).removeClass("checked");
                    $("#wrapperPaymentPassword").hide();
                }
            }
        });
    if ($("body").hasClass("scroller-body")) {
        new IScroll(".scroller-body", {
            mouseWheel: true,
            click: true
        })
    }
    $(document).scroll(function () {
        e()
    });
    $(".fix-block-r,footer").on("click", ".gotop",
        function () {
            btn = $(this)[0];
            this.timer = setInterval(function () {
                    $(window).scrollTop(Math.floor($(window).scrollTop() * .8));
                    if ($(window).scrollTop() == 0) clearInterval(btn.timer, e)
                },
                10)
        });

    function e() {
        $(window).scrollTop() == 0 ? $("#goTopBtn").addClass("hide") : $("#goTopBtn").removeClass("hide")
    }
});

function writeClear(e) {
    if (e.val().length > 0) {
        e.parent().addClass("write")
    } else {
        e.parent().removeClass("write")
    }
    btnCheck(e.parents("form"))
}

function btnCheck(e) {
    var t = true;
    e.find("input").each(function () {
        if ($(this).hasClass("no-follow")) {
            return
        }
        if ($(this).val().length == 0) {
            t = false
        }
    });
    if (t) {
        e.find(".btn").parent().addClass("ok")
    } else {
        e.find(".btn").parent().removeClass("ok")
    }
}

function getCartCount(e, t) {
    var a = 0;
    delCookie("cart_count")
    if (getcookie("key") !== null && getcookie("key") != "" && (getcookie("cart_count") == '' || getcookie("cart_count") == null)) {
        var e = getcookie("key");
        $.ajax({
            type: "post",
            url: ApiUrl + "/index.php?app=cart&mod=cart_list",
            data: {
                key: e
            },
            dataType: "json",
            async: false,
            success: function (e) {

                addcookie("cart_count", e.datas.cart_list.length, t);
                a = e.datas.cart_list.length;
            }
        })
    } else {
        a = getcookie("cart_count")
    }
    if (a > 0 && $(".bbctouch-nav-menu").has(".cart").length > 0) {
        $(".bbctouch-nav-menu").has(".cart").find(".cart").parents("li").find("sup").show();
        $("#header-nav").find("sup").show()
    }
}

function getChatCount() {
    if ($("#header").find(".message").length > 0) {
        var e = getcookie("key");
        if (e !== null) {
            $.getJSON(ApiUrl + "/index.php?app=chat&mod=get_msg_count", {
                    key: e
                },
                function (e) {
                    if (e.datas > 0) {
                        $("#header").find(".message").parent().find("sup").show();
                        $("#header-nav").find("sup").show()
                    }
                })
        }
        $("#header").find(".message").parent().click(function () {
            window.location.href = WapSiteUrl + "/cwap_chat_list.html"
        })
    }
}

(function ($) {
    var key = getcookie('key');
    $.extend($, {
        scrollTransparent: function (e) {
            var t = {
                valve: "#header",
                scrollHeight: 50
            };
            var e = $.extend({},
                t, e);

            function a() {
                $(window).scroll(function () {
                    if ($(window).scrollTop() <= e.scrollHeight) {
                        // $(e.valve).addClass("transparent").removeClass("posf")
                    } else {
                        // $(e.valve).addClass("posf").removeClass("transparent")
                    }
                })
            }

            return this.each(function () {
                a()
            })()
        },
        areaSelected: function (options) {
            var defaults = {
                success: function (e) {
                }
            };
            var options = $.extend({},
                defaults, options);
            var ASID = 0;
            var ASID_1 = 0;
            var ASID_2 = 0;
            var ASID_3 = 0;
            var ASNAME = "";
            var ASINFO = "";
            var ASDEEP = 1;
            var ASINIT = true;

            function _init() {
                if ($("#areaSelected").length > 0) {
                    $("#areaSelected").remove()
                }
                var e = '<div id="areaSelected">' + '<div class="bbctouch-full-mask left">' + '<div class="bbctouch-full-mask-bg"></div>' + '<div class="bbctouch-full-mask-block">' + '<div class="header">' + '<div class="header-wrap">' + '<div class="header-l"><a href="javascript:void(0);"><i class="back"></i></a></div>' + '<div class="header-title">' + "<h1>选择地区</h1>" + "</div>" + '<div class="header-r"><a href="javascript:void(0);"><i class="close"></i></a></div>' + "</div>" + "</div>" + '<div class="bbctouch-main-layout">' + '<div class="bbctouch-single-nav">' + '<ul id="filtrate_ul" class="area">' + '<li class="selected"><a href="javascript:void(0);">一级地区</a></li>' + '<li><a href="javascript:void(0);" >二级地区</a></li>' + '<li><a href="javascript:void(0);" >三级地区</a></li>' + "</ul>" + "</div>" + '<div class="bbctouch-main-layout-a"><ul class="bbctouch-default-list"></ul></div>' + "</div>" + "</div>" + "</div>" + "</div>";
                $("body").append(e);
                _getAreaList();
                _bindEvent();
                _close()
            }

            function _getAreaList() {
                $.ajax({
                    type: "post",
                    url: ApiUrl + "/index.php?app=address&mod=area_list",
                    data: {
                        area_id: ASID,
                        key: key
                    },
                    dataType: "json",
                    async: false,
                    success: function (e) {
                        if (e.datas.area_list.length == 0) {
                            _finish();
                            return false
                        }
                        if (ASINIT) {
                            ASINIT = false
                        } else {
                            ASDEEP++
                        }
                        $("#areaSelected").find("#filtrate_ul").find("li").eq(ASDEEP - 1).addClass("selected").siblings().removeClass("selected");
                        checklogin(e.login);
                        var t = e.datas;
                        var a = "";
                        for (var n = 0; n < t.area_list.length; n++) {
                            a += '<li><a href="javascript:void(0);" data-id="' + t.area_list[n].area_id + '" data-name="' + t.area_list[n].area_name + '"><h4>' + t.area_list[n].area_name + '</h4><span class="arrow-r"></span> </a></li>'
                        }
                        $("#areaSelected").find(".bbctouch-default-list").html(a);
                        if (typeof myScrollArea == "undefined") {
                            if (typeof IScroll == "undefined") {
                                $.ajax({
                                    url: WapSiteUrl + "/js/iscroll.js",
                                    dataType: "script",
                                    async: false
                                })
                            }
                            myScrollArea = new IScroll("#areaSelected .bbctouch-main-layout-a", {
                                mouseWheel: true,
                                click: true
                            })
                        } else {
                            myScrollArea.refresh()
                        }
                    }
                });
                return false
            }

            function _bindEvent() {
                $("#areaSelected").find(".bbctouch-default-list").off("click", "li > a");
                $("#areaSelected").find(".bbctouch-default-list").on("click", "li > a",
                    function () {
                        ASID = $(this).attr("data-id");
                        eval("ASID_" + ASDEEP + "=$(this).attr('data-id')");
                        ASNAME = $(this).attr("data-name");
                        ASINFO += ASNAME + " ";
                        var _li = $("#areaSelected").find("#filtrate_ul").find("li").eq(ASDEEP);
                        _li.prev().find("a").attr({
                            "data-id": ASID,
                            "data-name": ASNAME
                        }).html(ASNAME);
                        if (ASDEEP == 3) {
                            _finish();
                            return false
                        }
                        _getAreaList()
                    });
                $("#areaSelected").find("#filtrate_ul").off("click", "li > a");
                $("#areaSelected").find("#filtrate_ul").on("click", "li > a",
                    function () {
                        if ($(this).parent().index() >= $("#areaSelected").find("#filtrate_ul").find(".selected").index()) {
                            return false
                        }
                        ASID = $(this).parent().prev().find("a").attr("data-id");
                        ASNAME = $(this).parent().prev().find("a").attr("data-name");
                        ASDEEP = $(this).parent().index();
                        ASINFO = "";
                        for (var e = 0; e < $("#areaSelected").find("#filtrate_ul").find("a").length; e++) {
                            if (e < ASDEEP) {
                                ASINFO += $("#areaSelected").find("#filtrate_ul").find("a").eq(e).attr("data-name") + " "
                            } else {
                                var t = "";
                                switch (e) {
                                    case 0:
                                        t = "一级地区";
                                        break;
                                    case 1:
                                        t = "二级地区";
                                        break;
                                    case 2:
                                        t = "三级地区";
                                        break
                                }
                                $("#areaSelected").find("#filtrate_ul").find("a").eq(e).html(t)
                            }
                        }
                        _getAreaList()
                    })
            }

            function _finish() {
                var e = {
                    area_id: ASID,
                    area_id_1: ASID_1,
                    area_id_2: ASID_2,
                    area_id_3: ASID_3,
                    area_name: ASNAME,
                    area_info: ASINFO
                };
                options.success.call("success", e);
                if (!ASINIT) {
                    $("#areaSelected").find(".bbctouch-full-mask").addClass("right").removeClass("left")
                }
                return false
            }

            function _close() {
                $("#areaSelected").find(".header-l").off("click", "a");
                $("#areaSelected").find(".header-l").on("click", "a",
                    function () {
                        $("#areaSelected").find(".bbctouch-full-mask").addClass("right").removeClass("left")
                    });
                return false
            }

            return this.each(function () {
                return _init()
            })()
        },
        animationLeft: function (e) {
            var t = {
                valve: ".animation-left",
                wrapper: ".bbctouch-full-mask",
                scroll: ""
            };
            var e = $.extend({},
                t, e);

            function a() {
                $(e.valve).click(function () {
                    $(e.wrapper).removeClass("hide").removeClass("right").addClass("left");
                    if (e.scroll != "") {
                        if (typeof myScrollAnimationLeft == "undefined") {
                            if (typeof IScroll == "undefined") {
                                $.ajax({
                                    url: WapSiteUrl + "/js/iscroll.js",
                                    dataType: "script",
                                    async: false
                                })
                            }
                            myScrollAnimationLeft = new IScroll(e.scroll, {
                                mouseWheel: true,
                                click: true
                            })
                        } else {
                            myScrollAnimationLeft.refresh()
                        }
                    }
                });
                $(e.wrapper).on("click", ".header-l > a",
                    function () {
                        $(e.wrapper).addClass("right").removeClass("left")
                    })
            }

            return this.each(function () {
                a()
            })()
        },
        animationUp: function (e) {
            var t = {
                valve: ".animation-up",
                wrapper: ".bbctouch-bottom-mask",
                scroll: ".bbctouch-bottom-mask-rolling",
                start: function (ele) {
                },
                close: function () {
                }
            };
            var e = $.extend({},
                t, e);

            function a(ele) {
                e.start.call("start", ele);
                $(e.wrapper).removeClass("down").addClass("up");
                if (e.scroll != "") {
                    if (typeof myScrollAnimationUp == "undefined") {
                        if (typeof IScroll == "undefined") {
                            $.ajax({
                                url: WapSiteUrl + "/js/iscroll.js",
                                dataType: "script",
                                async: false
                            })
                        }
                        myScrollAnimationUp = new IScroll(e.scroll, {
                            mouseWheel: true,
                            click: true
                        })
                    } else {
                        myScrollAnimationUp.refresh()
                    }
                }
            }

            return this.each(function () {
                if (e.valve != "") {
                    $(e.valve).on("click",
                        function () {
                            a(this)
                        })
                } else {
                    a(this)
                }
                $(e.wrapper).on("click", ".bbctouch-bottom-mask-tip,.bbctouch-bottom-mask-bg,.bbctouch-bottom-mask-close",
                    function () {
                        $(e.wrapper).addClass("down").removeClass("up");
                        e.close.call("close")
                    })
            })()
        }
    })
})(Zepto);

function errorTipsShow(e) {
    $(".error-tips").html(e).show();
    setTimeout(function () {
            errorTipsHide()
        },
        3e3)
}

function errorTipsHide() {
    $(".error-tips").html("").hide()
}

function loadCss(e) {
    var t = document.createElement("link");
    t.setAttribute("type", "text/css");
    t.setAttribute("href", e);
    t.setAttribute("href", e);
    t.setAttribute("rel", "stylesheet");
    css_id = document.getElementById("auto_css_id");
    if (css_id) {
        document.getElementsByTagName("head")[0].removeChild(css_id)
    }
    document.getElementsByTagName("head")[0].appendChild(t)
}

function loadJs(e) {
    var t = document.createElement("script");
    t.setAttribute("type", "text/javascript");
    t.setAttribute("src", e);
    t.setAttribute("id", "auto_script_id");
    script_id = document.getElementById("auto_script_id");
    if (script_id) {
        document.getElementsByTagName("head")[0].removeChild(script_id)
    }
    document.getElementsByTagName("head")[0].appendChild(t)
}

function favoriteGoods(e) {
    var t = getcookie("key");
    if (!t) {
        checklogin(0);
        return
    }
    if (e <= 0) {
        $.sDialog({
            skin: "green",
            content: "参数错误",
            okBtn: false,
            cancelBtn: false
        });
        return false
    }
    var a = false;
    $.ajax({
        type: "post",
        url: ApiUrl + "/index.php?app=userfollow&mod=favorites_add",
        data: {
            key: t,
            gid: e
        },
        dataType: "json",
        async: false,
        success: function (e) {
            if (e.datas == '1') {
                a = true
            } else {
                $.sDialog({
                    skin: "red",
                    content: e.datas.error,
                    okBtn: false,
                    cancelBtn: false
                })
            }
        }
    });
    return a
}

function dropFavoriteGoods(e) {
    var t = getcookie("key");
    if (!t) {
        checklogin(0);
        return
    }
    if (e <= 0) {
        $.sDialog({
            skin: "green",
            content: "参数错误",
            okBtn: false,
            cancelBtn: false
        });
        return false
    }
    var a = false;
    $.ajax({
        type: "post",
        url: ApiUrl + "/index.php?app=userfollow&mod=favorites_del",
        data: {
            key: t,
            fav_id: e
        },
        dataType: "json",
        async: false,
        success: function (e) {
            if (e.code == 200) {
                a = true
            } else {
                $.sDialog({
                    skin: "red",
                    content: e.datas.error,
                    okBtn: false,
                    cancelBtn: false
                })
            }
        }
    });
    return a
}

function favoriteStore(e) {
    var t = getcookie("key");
    if (!t) {
        checklogin(0);
        return
    }
    if (e <= 0) {
        $.sDialog({
            skin: "green",
            content: "参数错误",
            okBtn: false,
            cancelBtn: false
        });
        return false
    }
    var a = false;
    $.ajax({
        type: "post",
        url: ApiUrl + "/index.php?app=vendorfollow&mod=fadd",
        data: {
            key: t,
            vid: e
        },
        dataType: "json",
        async: false,
        success: function (e) {
            if (e.code == 200) {
                a = true
            } else {
                $.sDialog({
                    skin: "red",
                    content: e.datas.error,
                    okBtn: false,
                    cancelBtn: false
                })
            }
        }
    });
    return a
}

function dropFavoriteStore(e) {
    var t = getcookie("key");
    if (!t) {
        checklogin(0);
        return
    }
    if (e <= 0) {
        $.sDialog({
            skin: "green",
            content: "参数错误",
            okBtn: false,
            cancelBtn: false
        });
        return false
    }
    var a = false;
    $.ajax({
        type: "post",
        url: ApiUrl + "/index.php?app=vendorfollow&mod=fdel",
        data: {
            key: t,
            vid: e
        },
        dataType: "json",
        async: false,
        success: function (e) {
            if (e.code == 200) {
                a = true
            } else {
                $.sDialog({
                    skin: "red",
                    content: e.datas.error,
                    okBtn: false,
                    cancelBtn: false
                })
            }
        }
    });
    return a
}

$.fn.ajaxUploadImage = function (e) {
    var t = {
        url: "",
        data: {},
        start: function () {
        },
        success: function () {
        }
    };
    var e = $.extend({},
        t, e);
    var a;

    function n() {
        if (a === null || a === undefined) {
            alert("请选择您要上传的文件！");
            return false
        }
        return true
    }

    return this.each(function () {
        $(this).on("change",
            function () {
                var t = $(this);
                e.start.call("start", t);
                a = t.prop("files")[0];
                if (!n) return false;
                try {
                    var r = new XMLHttpRequest;
                    r.open("post", e.url, true);
                    r.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                    r.onreadystatechange = function () {
                        if (r.readyState == 4) {
                            returnDate = $.parseJSON(r.responseText);
                            e.success.call("success", t, returnDate)
                        }
                    };
                    var i = new FormData;
                    for (k in e.data) {
                        i.append(k, e.data[k])
                    }
                    i.append(t.attr("name"), a);
                    result = r.send(i)
                } catch (o) {
                    alert(o)
                }
            })
    })
};

// 获取 用户 当前 未读消息条数
function getNoReadMsgCount() {
    var t = getcookie("key");
    $.ajax({
        type: 'get',
        url: ApiUrl + "/index.php?app=usercenter&mod=receivedSystemNewNum",
        dataType: 'json',
        data: {
            key: t
        },
        async: false,
        success: function (e) {
            if (e.code == 200) {
                var data = e.datas
                if (data.status == 1) {
                    var msg_num = data.countnum * 1;
                    if (msg_num > 9) {
                        msg_num = '9+';
                    }

                    $(".right-top-msg").find('em').show();
                    $(".right-top-msg").find('em').text(msg_num);
                } else {
                    $(".right-top-msg").find('em').hide();
                    // $(".right-top-msg").find('em').remove();
                }
            }
        }
    });
}

function count(obj) {
    var t = typeof obj;
    if (t == 'string') {
        return obj.length;
    } else if (t == 'object') {
        var n = 0;
        for (var i in obj) {
            n++;
        }
        return n;
    }
    return false;
}

// ----------推手分享--------------------

// 分享商品统计
function share_add_up(gid) {
    var key = getcookie('ssys_key');
    if (key && gid) {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=index&mod=add_up_goods_num&sld_addons=spreader',
            data: {ssys_key: key, gid: gid},
            success: function (result) {

            }
        });
    }
}

function share_data(share_title='', share_desc='', share_link='', share_img='', gid='') {

    // 重构 当前 分享链接 加上分享标示
    var share_flag_str = share_link ? '&shareId=' + getcookie('ssys_share_code') : '';

    // 默认数据
    share_title = share_title ? share_title : document.title;
    share_desc = share_desc ? share_desc : document.title;
    share_link = share_link ? share_link + share_flag_str : location.href;
    share_img = share_img ? share_img : '';

    share_data_set(share_title, share_desc, share_link, share_img, gid);
}

// 分享内容设置
function share_data_set(share_title, share_desc, share_link, share_img, gid) {
    wx_ready = function () {
        var share_array = {
            title: share_title,
            desc: share_desc,
            link: share_link,
            imgUrl: share_img,
            success: function () {
                // 分享成功 后进行统计
                if (gid) {
                    share_add_up(gid);
                }
            }
        };
        wx.onMenuShareTimeline(share_array); //分享朋友圈
        wx.onMenuShareAppMessage(share_array); //发送给朋友
        wx.onMenuShareQQ(share_array); //发送给QQ
        wx.onMenuShareWeibo(share_array); //发送给微博
    }

    var wqurl = encodeURIComponent(window.location.href.split('#')[0]);
    wqurl = encodeURIComponent(wqurl);
    //微信相关
    if (window.navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == 'micromessenger') {
        $.ajax({
            url: ApiUrl + "/index.php?app=jssdk&mod=getJssdkConfig&sld_addons=spreader&url=" + wqurl,
            type: 'get',
            dataType: 'json',
            success: function (result) {
                if (result.code == '200') {
                    jQuery.getScript("http://res.wx.qq.com/open/js/jweixin-1.2.0.js", function () {
                        /*
                         做一些加载完成后需要执行的事情
                         */
                        wx.config({
                            debug: false,
                            appId: result.datas.config.appId,
                            timestamp: result.datas.config.timestamp,
                            nonceStr: result.datas.config.nonceStr,
                            signature: result.datas.config.signature,
                            jsApiList: [
                                'checkJsApi',
                                'onMenuShareTimeline',
                                'onMenuShareAppMessage',
                                'onMenuShareQQ',
                                'onMenuShareWeibo',
                                'hideMenuItems',
                                'showMenuItems',
                                'hideAllNonBaseMenuItem',
                                'showAllNonBaseMenuItem',
                                'translateVoice',
                                'startRecord',
                                'stopRecord',
                                'onRecordEnd',
                                'playVoice',
                                'pauseVoice',
                                'stopVoice',
                                'uploadVoice',
                                'downloadVoice',
                                'chooseImage',
                                'previewImage',
                                'uploadImage',
                                'downloadImage',
                                'getNetworkType',
                                'openLocation',
                                'getLocation',
                                'hideOptionMenu',
                                'showOptionMenu',
                                'closeWindow',
                                'scanQRCode',
                                'chooseWXPay',
                                'openProductSpecificView',
                                'addCard',
                                'chooseCard',
                                'openCard'
                            ]
                        });
                        wx.ready(function () {
                            wx_ready();
                        });
                    });
                }
            }
        });
    }
}

function share_tips_show(e) {
    var share_title = e.data('title');
    var share_desc = e.data('desc');
    var share_link = e.data('url');
    var share_img = e.data('img');
    var gid = e.data('gid');

    var share_tips_html = '';
    share_tips_html += '<div class="alert" style="display:block">';
    share_tips_html += '<div class="alert_box">';
    share_tips_html += '<img src="images/sld_bj@2x(1).png" alt="">';
    share_tips_html += '<div class="cancel"><img src="images/sld_back@2x.png" alt=""></div>';
    share_tips_html += '<div class="cance2"><img src="images/sld_jiantou@2x(1).png" alt=""></div>';
    share_tips_html += '</div>';
    share_tips_html += '</div>';
    share_data(share_title, share_desc, share_link, share_img, gid);
    $("body").append(share_tips_html);
}

function share_tips_close() {
    share_data();
    $("body .alert").remove();
}

// 获取当前位置
var geocoder;
function get_location_position(fn) {

    var map = new AMap.Map('container');

    AMap.service('AMap.Geocoder', function () {//回调函数
        //实例化Geocoder
        geocoder = new AMap.Geocoder({
            city: "010"//城市，默认：“全国”
        });
    });

    map.plugin('AMap.Geolocation', function () {
        geolocation = new AMap.Geolocation({
            enableHighAccuracy: true,//是否使用高精度定位，默认:true
            timeout: 10000,          //超过10秒后停止定位，默认：无穷大
            maximumAge: 0,           //定位结果缓存0毫秒，默认：0
            convert: true,           //自动偏移坐标，偏移后的坐标为高德坐标，默认：true
            showButton: true,        //显示定位按钮，默认：true
            buttonPosition: 'LB',    //定位按钮停靠位置，默认：'LB'，左下角
            buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
            showMarker: true,        //定位成功后在定位到的位置显示点标记，默认：true
            showCircle: true,        //定位成功后用圆圈表示定位精度范围，默认：true
            panToLocation: true,     //定位成功后将定位到的位置作为地图中心点，默认：true
            zoomToAccuracy: true      //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
        });
        geolocation.getCurrentPosition();
        AMap.event.addListener(geolocation, 'complete', onComplete);//返回定位信息
        AMap.event.addListener(geolocation, 'error', onError);      //返回定位出错信息
    });

    //解析定位结果
    function onComplete(data) {
        if (data.position) {
            var lng = data.position.lng;
            var lat = data.position.lat;
            location[0] = lng;
            location[1] = lat;
            fn && fn(lng, lat,data.addressComponent.street);
        }
    }

    //解析定位错误信息
    function onError(data) {
        layer.open({
            content: '定位失败',
            skin: 'msg',
            time: 2
        })
        fn && fn();
    }

}

// 经纬度--地址
function getAddr(lnglat) {
    geocoder.getAddress(lnglat, function(status, result) {
        if (status === 'complete' && result.info === 'OK') {
            // result为对应的地理位置详细信息
            console.log(result)
        }
    })
}


function isWeixin() {
    var ua = window.navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == 'micromessenger') {
        return 1;
    } else {
        return 0;
    }
}


// 再来一单
$('body').on('click', '.come_again', function () {
    var key = getcookie('key');
    var order_id = $(this).data('id');
    $.ajax({
        type: 'get',
        url: ApiUrl + '/index.php?app=order&mod=buy_again&sld_addons=ldj',
        data: {
            key: key,
            order_id: order_id
        },
        dataType: 'json',
        success: function (res) {
            console.log(res);
            if (res.status == 200) {
                window.location.href = WapSiteUrl + '/cart.html';
            } else {
                layer.open({
                    content: res.msg,
                    skin: 'msg',
                    time: 2
                })
            }
        }
    })
})

// 取消订单
$('body').on('click', '.cancel_order', function () {
    var key = getcookie('key');
    var order_id = $(this).data('id');
    layer.open({
        content: '确定取消订单',
        btn: ['确定','取消'],
        yes: function () {
            $.ajax({
                type: 'get',
                url: ApiUrl + '/index.php?app=order&mod=order_cancel&sld_addons=ldj',
                data: {
                    key: key,
                    order_id: order_id
                },
                dataType: 'json',
                success: function (res) {
                    console.log(res);
                    if (res.status == 200) {
                        window.location.reload()
                    } else {
                        layer.open({
                            content: res.msg,
                            skin: 'msg',
                            time: 2
                        })
                    }
                }
            })
        }
    })
})

function order_map_init(dianLocation,center){
    var loca_addr = getcookie('location');
    var location = [];
    if (loca_addr) {
        loca_addr = JSON.parse(loca_addr);
        location[0] = loca_addr[0];
        location[1] = loca_addr[1];
        mapInit(location[0], location[1]);
    } else {
        get_location_position(function (lng, lat, addr) {
            if (lng && lat) {
                location[0] = lng;
                location[1] = lat;
                addcookie('location', JSON.stringify(location));
                addcookie('address', addr);
            }
            mapInit(location[0], location[1]);
        });
    }

    function mapInit(lng, lat) {
        var map = new AMap.Map(center, {
            resizeEnable: true,
            center: dianLocation,//地图中心点
            zoom: 16 //地图显示的缩放级别
        });

        var marker = new AMap.Marker({
            position: dianLocation,
            icon: './images/marker.png'
        });
        map.add(marker);
        map.plugin('AMap.Geocoder', function () {
            var geocoder = new AMap.Geocoder({
                city: '010'
            });
            // 获取店铺地址信息
            geocoder.getAddress(dianLocation, function (status, result) {
                if (status === 'complete' && result.info === 'OK') {
                    var dian_addr = result.regeocode.formattedAddress;
                    $('.The_map h1').text(dian_addr);
                }
            })
        })
        //步行导航
        var driving = new AMap.Driving({
            map: map
        });
        //根据起终点坐标规划步行路线
        driving.search([lng, lat], dianLocation, function (status, result) {
            var distance = result.routes[0].distance;
            distance = (distance/1000>0)? ((distance/1000).toFixed(2)+'km'): distance+'m';
            marker.setLabel({
                offset: new AMap.Pixel(-46, -36),
                content: "<div class='addr_info'>距您"+ distance +"</div>"
            });
        });

    }
}


// 购物车计算总价格
function calcPrice(delivery,iserr) {
    var sum = 0;
    $('#wrapper .goods_list_item').forEach(function (el) {
        if ($(el).hasClass('actived')) {
            var count = parseInt($(el).find('.count ins').text());
            var price = parseFloat($(el).find('.cart_goods_title p').text().replace('¥', ''));
            if (count > 0) {
                sum = sum + count * price;
            }
        }
    })
    sum = sum.toFixed(2);
    $('.bottom .num').text('¥' + sum);
    $('.bottomCArt p').text('¥' + sum);
    var checkLen = $('.hasData li.actived').length;
    $('#allSelect span').text('(已选'+ checkLen +'件)');
    if(iserr==1){
        return;
    }else{
        if(sum>delivery){
            $('.bottomCArt a').removeClass('disable').addClass('go_pay');
            $('.bottomCArt a').text('去结算');
            $('.bottom>a').removeClass('disable').addClass('go_pay');
            $('.bottom>a').text('去结算');
        }else{
            if(sum==0){
                $('.bottomCArt a').removeClass('go_pay').addClass('disable');
                $('.bottomCArt a').text('￥'+ delivery +'起送');
                $('.bottom>a').removeClass('go_pay').addClass('disable');
                $('.bottom>a').text('￥'+ delivery +'起送');
            }else{
                $('.bottomCArt a').removeClass('go_pay').addClass('disable');
                $('.bottomCArt a').text('差'+ (delivery-sum) +'元起送');
                $('.bottom>a').removeClass('go_pay').addClass('disable');
                $('.bottom>a').text('差'+ (delivery-sum) +'元起送');
            }

        }
    }
}


// 删除失效商品
$('body').on('click','.shixiao_gods h1 button',function () {
    var key = getcookie('key');
    var cart_ids = [];
    var del = $('.shixiao_gods li');
    for(var j = 0;j<del.length;j++){
        cart_ids.push($(del[j]).data('cartid'));
    }
    var that = $(this);
    if(cart_ids.length){
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=cart&mod=deletecart&sld_addons=ldj',
            data:{
                key: key,
                type: 1,
                cart_ids: cart_ids
            },
            dataType: 'json',
            success: function (res) {
                if(res.status==200){
                    that.parents('.shixiao_gods').find('ul').html('');
                }else{
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    })
                }
            }
        })
    }
})

// 登录后返回处理
!function no_login_back() {
   var key = getcookie('key');
   var from_url = document.referrer;
   if(key&&from_url==(SiteUrl+'/cwap/cwap_login.html')){
       location.href = WapSiteUrl+'/index.html'
   }
}()

// 滚动穿透
var scroll = (function (className) {
    var scrollTop;
    return {
        afterOpen: function () {
            scrollTop = document.scrollingElement.scrollTop || document.body.scrollTop;
            document.body.classList.add(className);
            document.documentElement.classList.add(className);
            document.body.style.top = -scrollTop + 'px';
        },
        beforeClose: function () {
            document.body.classList.remove(className);
            document.documentElement.classList.remove(className);
            document.scrollingElement.scrollTop = scrollTop;
            document.body.scrollTop = scrollTop;
        }
    };
})('cancel_scroll');


// 未登录提示页
function no_login_tip() {
    var key = getcookie('key');
    if(key) return '';
    var html = '<div class="no_login_tip">\n' +
        '    <div class="img">\n' +
        '        <img src="./images/sousuo_null@2x.png" alt="">\n' +
        '    </div>\n' +
        '    <p>您还未登录</p>\n' +
        '    <a href="'+SiteUrl+'/cwap/cwap_login.html">去登录</a>\n' +
        '</div>'
    return html
}