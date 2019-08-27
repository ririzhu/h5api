window.onpageshow = function (e) {
    if (e.persisted) {
        window.location.reload()
    }
};

$(function () {
    var dian_id = GetQueryString('vid');
    var key = getcookie('key');
    var pn = 1;
    var hasmore = true;
    var flag = true;  // 控制滚动时只执行一次
    var id;     // 分类id
    var name;    // 分类名
    var delivery;  //起送价
    var iserr;    // 是否休息
    var delivery_type = [];   //结算方式
    /*if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }*/

    getStoreInfo();
    getGoodsList();

    // 店铺信息出错
    function storeErr() {
        $('.shop_alert').show();
        $(".shop_alert .guanbi").click(function () {
            $('.shop_alert').hide();
        })
    }

    // 获取首页详情
    function getStoreInfo() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=dian&mod=index&sld_addons=ldj',
            data: {
                key: key,
                dian_id: dian_id
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    if (res.dian_info.error == 1) {
                        storeErr();
                    }
                    var title = res.dian_info.dian_name + ' -店铺首页';
                    document.title = title;
                    iserr = res.cart_list.error;
                    if (res.cart_list.list.length) {
                        delivery_type = res.cart_list.list[0].delivery_type;
                    }
                    delivery = parseFloat(res.dian_info.ldj_delivery_order_Price);
                    res.dian_info.ldj_delivery_order_Price = delivery;
                    var body_html = template.render('store_info', res.dian_info);
                    $('.top_nav').html(body_html);
                    var cart_html = template.render('store_cart', res);
                    $('.bottom').html(cart_html);
                    var nav_html = template.render('store_nav', res);
                    $('.left_nav').html(nav_html);
                    var cart_list_html = template.render('store_cart_list', res);
                    $('#wrapper').html(cart_list_html);
                } else {

                }
            }
        })
    }

    // 获取商品列表
    function getGoodsList(id, name, order, ordertype) {
        if (flag) {
            flag = false;
            var data = {
                key: key,
                dian_id: dian_id,
                pn: pn,
                page: 10
            };
            data.stcid = id ? id : 'all';
            if (order && ordertype) {
                data.order = order;
                data.ordertype = ordertype;
            }
            $.ajax({
                type: 'get',
                url: ApiUrl + '/index.php?app=dian&mod=search_goods&sld_addons=ldj',
                data: data,
                dataType: 'json',
                success: function (res) {
                    flag = true
                    if (res.status == 200) {
                        var rel = name ? name : '全部';
                        $('.right_goods>h1>span').text(rel + '(' + res.goods_list.count_list + ')')
                        var goods_list_html = template.render('store_goods_list', res.goods_list);
                        $('.right_goods ul').append(goods_list_html);
                        if (res.goods_list.ismore.hasmore) {
                            pn++;
                        } else {
                            hasmore = false;
                        }
                    } else {
                        hasmore = false;
                        var nolist = '<div class="nogoods"> <div class="img"><img src="./images/zanwushangpin@2x.png" alt=""></div> <p>该分类暂无商品</p> </div>';
                        $('.right_goods ul').append(nolist);
                    }
                }
            })
        }
    }

    // 下拉加载更多
    $('.right_goods ul').on('scroll', function (e) {
        if (hasmore) {
            var top = e.currentTarget.scrollTop;
            var hei = $('.right_goods ul').height();
            var allHei = e.currentTarget.scrollHeight;
            if (top + hei >= allHei - 50) {
                getGoodsList(id, name);
            }
        }
    })

    // 切换商品分类
    $('body').on('click', '.left_nav li', function () {
        $(this).siblings().removeClass("default");
        $(this).addClass("default");
        $('.svnum').removeClass('on');
        id = $(this).data('id');
        name = $(this).data('name');
        pn = 1;
        hasmore = true;
        $('.right_goods ul').html('');
        getGoodsList(id, name);
    })

    // 按销量
    $('.svnum').on('click', function () {
        var order = 's';
        var ordertype;
        pn = 1;
        hasmore = true;
        $('.right_goods ul').html('');
        if ($(this).hasClass('on')) {
            ordertype = 'asc';
            getGoodsList(id, name, order, ordertype);
            $(this).removeClass('on');
        } else {
            ordertype = 'desc';
            getGoodsList(id, name, order, ordertype);
            $(this).addClass('on');
        }
    })

    // 按价格
    $('.pricesort').on('click', function () {
        var order = 'p';
        var ordertype;
        pn = 1;
        hasmore = true;
        $('.svnum').removeClass('on');
        $('.right_goods ul').html('');
        if ($(this).hasClass('desc')) {
            ordertype = 'desc';
            getGoodsList(id, name, order, ordertype);
            $(this).addClass('asc').removeClass('desc');
        } else {
            ordertype = 'asc';
            getGoodsList(id, name, order, ordertype);
            $(this).addClass('desc').removeClass('asc');
        }
    })

    // 购物车数量变化
    function cartNum(type, gid, vid, num) {
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=cart&mod=editcart&sld_addons=ldj',
            data: {
                gid: gid,
                dian_id: vid,
                quantity: num,
                key: key
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    var list = res.cart_list.list;
                    var newNum;
                    var newList;
                    if (res.cart_list.list.length) {
                        delivery_type = res.cart_list.list[0].delivery_type;
                    }
                    for (var i = 0; i < list.length; i++) {
                        if (list[i].gid == gid) {
                            newNum = list[i].goods_num;
                            newList = list[i];
                            $('.gid' + gid).text(newNum);
                            break;
                        }
                    }
                    if (num == 1) {
                        $('.gid' + gid).removeClass('hide').prev('em').removeClass('hide');
                        if (type == 'add') {
                            var newhtml = '<li class="goods_list_item actived" data-cartid="' + newList.cart_id + '" data-gid="' + newList.gid + '">\n' +
                                '                <em class="check"></em>\n' +
                                '                <div class="gods">\n' +
                                '                    <div class="shop_img">\n' +
                                '                        <img src="' + newList.goods_image + '" alt="">\n' +
                                '                    </div>\n' +
                                '                    <div class="cart_goods_title">\n' +
                                '                        <h1>' + newList.goods_name + '</h1>\n' +
                                '                        <p>&yen;' + newList.goods_price + '</p>\n' +
                                '                    </div>\n' +
                                '                </div>\n' +
                                '                <div class="count">\n' +
                                '                    <em class="reduce" data-id="' + newList.gid + '" data-vid="' + newList.vid + '"><b></b></em>\n' +
                                '                    <ins class="gid' + newList.gid + '">' + newList.goods_num + '</ins>\n' +
                                '                    <span class="add" data-id="' + newList.gid + '" data-vid="' + newList.vid + '"><img src="./images/add@2x.png" alt=""></span>\n' +
                                '                </div>\n' +
                                '            </li>'
                            $('.cartAlert .hasData').append(newhtml)
                        }
                    }
                    if (num == 0) {
                        $('.gid' + gid).addClass('hide').prev('em').addClass('hide');
                        $('.gid' + gid).text('0');
                        if (type == 'reduce') {
                            $('.gid' + gid).parents('.goods_list_item').remove();
                        }
                    }
                    $('.cart_num').text(list.length);
                    var dat = JSON.parse(JSON.stringify(res))
                    $.extend(dat, {
                        dian_info: {
                            ldj_delivery_order_Price: delivery
                        }
                    })
                    var cart_html = template.render('store_cart', dat);
                    $('.bottom').html(cart_html);
                    $('.cart_img p span').text(list.length);
                    calcPrice(delivery, iserr)
                } else if (res.status == 266) {
                    layer.open({
                        content: res.msg,
                        btn: ['确定', '取消'],
                        yes: function () {
                            window.location.href = SiteUrl + '/cwap/cwap_login.html';
                        }
                    })
                } else {
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    })
                    return;
                }
            }
        })
    }

    // 购物车加
    $('body').on('click', '.add', function () {
        var gid = $(this).data('id');
        var vid = $(this).data('vid');
        var num = parseInt($(this).prev('ins').text()) + 1 || 1;
        cartNum('add', gid, vid, num);
    })

    // 购物车减
    $('body').on('click', '.reduce', function () {
        var gid = $(this).data('id');
        var vid = $(this).data('vid');
        var num = parseInt($(this).next('ins').text()) - 1;
        cartNum('reduce', gid, vid, num);
    })

    // 购物车全选
    $('body').on('click', '#allSelect', function () {
        if ($(this).hasClass('actived')) {
            $(this).removeClass('actived');
            $('.goods_list_item').removeClass('actived');
        } else {
            $(this).addClass('actived');
            $('.goods_list_item').addClass('actived');
        }
        calcPrice(delivery, iserr)
    })

    $('body').on('click', '.goods_list_item .check', function () {
        if ($(this).parent('.goods_list_item').hasClass('actived')) {
            $(this).parent('.goods_list_item').removeClass('actived');
        } else {
            $(this).parent('.goods_list_item').addClass('actived');
        }
        calcPrice(delivery, iserr)
    })

    // 清空购物车
    $('body').on('click', '.clear_cart', function () {
        var sel = $('.hasData .actived');
        var delAll = ($('.goods_list_item').length == sel.length) ? 1 : 0;
        var card_ids = [],
            gids = [];
        for (var j = 0; j < sel.length; j++) {
            card_ids.push($(sel[j]).data('cartid'));
            gids.push($(sel[j]).data('gid'));
        }
        var data = {key: key, vid: dian_id}
        if (delAll) {
            data.type = 2
        } else {
            data.type = 1;
            data.cart_ids = card_ids;
        }
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=cart&mod=deletecart&sld_addons=ldj',
            data: data,
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    var list = $('.right_goods ul li');
                    for (var i = 0; i < list.length; i++) {
                        var el = $(list[i]);
                        var gid = el.data('gid');
                        if (gids.indexOf(gid) > -1) {
                            el.find('.ToCalculate em').addClass('hide');
                            el.find('.ToCalculate ins').addClass('hide');
                            el.find('.ToCalculate ins').text(0);
                        }
                    }
                    if (delAll) {
                        getStoreInfo()
                        $('#wrapper').hide();
                    } else {
                        sel.remove()
                        var old = $('.cartAlert .cart_img span').text();
                        $('.cartAlert .cart_img span').text(old - sel.length);
                        $('.money a span').text(old - sel.length);
                    }
                }
            }
        })
    })

    // 查看店铺详情
    $('body').on('click', '.top_nav img', function () {
        window.location.href = WapSiteUrl + '/cwap_shop_details.html?vid=' + dian_id;
    })
    $('body').on('click', '.top_nav h1', function () {
        window.location.href = WapSiteUrl + '/cwap_shop_details.html?vid=' + dian_id;
    })

    // 搜索
    $('.sousuo a').on('click', function () {
        window.location.href = WapSiteUrl + '/cwap_pro_search.html?vid=' + dian_id + '&type=2';
    })

    // 去结算
    $('body').on('click', '.go_pay', function () {
        var ids = [];
        $('.hasData .actived').forEach(function (el) {
            ids.push($(el).data('cartid'));
        })
        var from_url = '';
        if (delivery_type.length == 2) {
            from_url = WapSiteUrl + '/cwap_confirm_an_order.html?type=1&order_type=1&vid=' + dian_id + '&cart_id=' + ids.join(',');
        } else if (delivery_type.indexOf('上门自提') > -1) {
            from_url = WapSiteUrl + '/cwap_order_ziqu.html?type=1&order_type=2&vid=' + dian_id + '&cart_id=' + ids.join(',');
        } else if (delivery_type.indexOf('门店配送') > -1) {
            from_url = WapSiteUrl + '/cwap_confirm_an_order.html?type=1&order_type=1&vid=' + dian_id + '&cart_id=' + ids.join(',');
        } else {
            layer.open({
                content: '该门店暂无配送方式',
                skin: 'msg',
                time: 2
            })
            return;
        }
        window.location.href = from_url;
    })
})