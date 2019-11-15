$(function () {
    var type = GetQueryString('type');
    var key = getcookie('key');
    /*if(!key){
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }*/
    var vid = GetQueryString('vid');
    var k = GetQueryString('k');
    var pn = 1;
    var hasmore = true;
    var delivery;
    var iserr;    // 是否休息
    var error;
    var flag = true;
    var delivery_type = [];   //结算方式
    $('.search-input').val(k);

    getStoreInfo();

    // 获取首页详情
    function getStoreInfo() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=dian&mod=index&sld_addons=ldj',
            data: {
                key: key,
                dian_id: vid
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    iserr = res.cart_list.error;
                    if (res.cart_list.list.length) {
                        delivery_type = res.cart_list.list[0].delivery_type;
                    }
                    delivery = parseInt(res.dian_info.ldj_delivery_order_Price);
                    error = res.dian_info.error;
                    res.dian_info.ldj_delivery_order_Price = delivery;
                    var cart_html = template.render('store_cart', res);
                    $('.bottom').html(cart_html);
                    var store_cart_html = template.render('store_cart_list', res);
                    $('#wrapper').html(store_cart_html);
                }
            }
        })
    }

    search();

    // 搜索
    function search() {
        if (!flag) return;
        flag = false;
        var data = {
            type: type,
            keyworld: k,
            page: 10,
            pn: pn
        }
        if (type == 2) {
            data.vid = vid;
            data.key = key;
        }
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=goods&mod=goods_list&sld_addons=ldj',
            data: data,
            dataType: 'json',
            success: function (res) {
                flag = true;
                if (res.status == 200) {
                    render(res.goods_list.list);
                    if (res.goods_list.ismore.hasmore) {
                        pn++
                    } else {
                        hasmore = false;
                    }
                } else if (res.status == 255) {
                    if (pn == 1) {
                        renderErr();
                    }
                    layer.open({
                        content: res.msg
                        , skin: 'msg'
                        , time: 2
                    });
                }
            }
        })
    }

    // 渲染商品列表
    function render(data) {
        var list_html = '';
        data.forEach(function (el) {
            list_html += '<li data-gid="'+ el.gid +'">\n' +
                '                <a href="cwap_goods_details.html?gid=' + el.gid + '&vid=' + vid + '">\n' +
                '                    <dl>\n' +
                '                        <img src="' + el.goods_image + '" alt="">\n' +
                '                    </dl>\n' +
                '                    <div class="minTitle">\n' +
                '                        <h1>' + el.goods_name + '</h1>\n' +
                '                        <p><em>月销' + el.month_sales + '件</em></p>\n' +
                '                        <span>&yen;' + el.goods_price + '</span>\n' +
                '                    </div>\n' +
                '                </a>\n' +
                '                <div class="ToCalculate">\n' +
                '                    <em class="reduce ' + (el.cart_num > 0 ? "" : "hide") + '" data-id="' + el.gid + '" data-vid="' + vid + '"><b></b></em>' +
                '                    <ins class="' + "gid" + el.gid + ' ' + (el.cart_num > 0 ? "" : "hide") + '">' + el.cart_num + '</ins>' +
                '                    <span class="add" data-id="' + el.gid + '" data-vid="' + vid + '"><img src="./images/add@2x.png" alt=""></span>\n' +
                '                </div>\n' +
                '            </li>'
        });
        $('#shopList ul').append(list_html);
    }

    function renderErr() {
        var err = '<div class="map_sb">\n' +
            '\t<img src="./images/sousuo_null@2x.png" alt="">\n' +
            '\t<p>没有找到该商品</p>\n' +
            '</div>'
        $('#shopList ul').html(err);
    }

    // 下拉加载更多
    $(window).on('scroll', function () {
        if (hasmore) {
            var top = document.body.scrollTop || document.documentElement.scrollTop;
            var hei = document.body.scrollHeight || document.documentElement.scrollHeight;
            var scrollHei = document.documentElement.clientHeight || document.body.clientHeight;
            if (top + scrollHei >= hei - 100) {
                search();
            }
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
                            var newhtml = '<li class="goods_list_item actived" data-cartid="' + newList.cart_id + '" data-gid="'+ newList.gid +'">\n' +
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
                    layer.closeAll();
                    layer.open({skin: 'msg', content: res.msg, time: 2});
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
        var delAll = ($('.goods_list_item').length==sel.length) ? 1 : 0;
        var card_ids = [],
            gids = [];
        for (var j = 0; j < sel.length; j++) {
            card_ids.push($(sel[j]).data('cartid'));
            gids.push($(sel[j]).data('gid'));
        }
        var data = {key: key, vid: vid}
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
                        $('.cartAlert .cart_img span').text(old-sel.length);
                        $('.money a span').text(old-sel.length);
                    }
                }
            }
        })
    })

    // 去结算
    $('body').on('click', '.go_pay', function () {
        var ids = [];
        $('.hasData .actived').forEach(function (el) {
            ids.push($(el).data('cartid'));
        })
        var from_url = '';
        if (delivery_type.length == 2) {
            from_url = WapSiteUrl + '/cwap_confirm_an_order.html?type=1&order_type=1&vid=' + vid + '&cart_id=' + ids.join(',');
        } else if (delivery_type.indexOf('上门自提') > -1) {
            from_url = WapSiteUrl + '/cwap_order_ziqu.html?type=1&order_type=2&vid=' + vid + '&cart_id=' + ids.join(',');
        } else if (delivery_type.indexOf('门店配送') > -1) {
            from_url = WapSiteUrl + '/cwap_confirm_an_order.html?type=1&order_type=1&vid=' + vid + '&cart_id=' + ids.join(',');
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

    $('body').on('click', '.header-inp', function () {
        window.location.href = WapSiteUrl + '/cwap_pro_search.html?vid=' + vid + '&type=2';
    })
})