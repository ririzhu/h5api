$(function () {
    var key = getcookie('key');
    var delivery;
    var iserr;    // 是否休息
    var error;
    /*if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }*/
    var gid = GetQueryString('gid');
    var vid = GetQueryString('vid');
    var delivery_type = [];   //结算方式
    if (!gid) {

    }
    getStoreDetail(getGoodsDetail)

    // 获取店铺详情
    function getStoreDetail(fn) {
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=dian&mod=dian_info_function&sld_addons=ldj',
            data: {
                // key: key,
                dian_id: vid
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    delivery = parseFloat(res.data.ldj_delivery_order_Price);
                    error = res.data.error;
                    $('#alert a').attr('href', 'tel:' + res.data.dian_phone);
                    $('#alert a').text(res.data.dian_phone);
                    fn && fn();
                }
            }
        })
    }

    function getGoodsDetail() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=goods&mod=goods_detail&sld_addons=ldj',
            data: {
                key: key,
                gid: gid,
                vid: vid
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    iserr = res.cart_info.error;
                    var swiper_imgs = res.goods_info.goods_images;
                    // 轮播图
                    var swiper_html = '';
                    for (var i = 0; i < swiper_imgs.length; i++) {
                        swiper_html += '<div class="swiper-slide"><img src="' + swiper_imgs[i] + '" alt=""></div>';
                    }
                    $('#pic .swiper-wrapper').html(swiper_html);
                    var mySwiper = new Swiper('.swiper-container', {
                        // pagination: '.swiper-pagination',
                    })

                    // 商品详情
                    var cart = res.cart_info.list;
                    if (res.cart_info.list.length) {
                        delivery_type = res.cart_info.list[0].delivery_type;
                    }
                    var goods_info = res.goods_info;
                    var list = cart.filter(function (el) {
                        return el.gid == gid;
                    })

                    if (list.length) {
                        $.extend(goods_info, {
                            goods_num: parseInt(list[0].goods_num),
                            gclass: 'gid' + gid,
                        })
                    } else {
                        $.extend(goods_info, {
                            goods_num: 0,
                            gclass: 'gid' + gid,
                        })
                    }

                    var detail_html = '    <div class="number">\n' +
                        '        <h1>' + goods_info.goods_name + '</h1>\n' +
                        '        <div class="jisuan">\n' +
                        '            <span>&yen;' + goods_info.goods_price + '</span>\n' +
                        '            <p class="yunsuan ' + (goods_info.goods_num > 0 ? '' : 'hide') + '">\n' +
                        '                <b class="reduce" data-id="' + goods_info.goods_id + '" data-vid="' + goods_info.dian_id + '">\n' +
                        '                    <time></time>\n' +
                        '                </b>\n' +
                        '                <ins class="' + goods_info.gclass + '">' + goods_info.goods_num + '</ins>\n' +
                        '                <em class="add"  data-id="' + goods_info.goods_id + '" data-vid="' + goods_info.dian_id + '"><img src="./images/add@2x.png" alt=""></em>\n' +
                        '            </p>\n' +
                        '            <p class="addCart ' + (goods_info.goods_num > 0 ? 'hide' : '') + '" data-id="' + goods_info.goods_id + '" data-vid="' + goods_info.dian_id + '">加入购物车</p>\n' +
                        '        </div>\n' +
                        '    </div>\n' +
                        '    <div class="evaluation">\n' +
                        '        <div class="shop"><p><a href="cwap_goods_list.html?vid=' + goods_info.dian_id + '"><img src="./images/shop04.png" alt="">' + goods_info.dian_name + '</a></p>\n' +
                        '            <p class="tel"><img src="./images/terl.png" alt=""><a href="javascript:;">联系商家</a></p></div>\n' +
                     /*   '        <div class="goodspj">\n' +
                        '            <p>商品评价<span>(共0评价)</span></p>\n' +
                        '            <p>好评率100%</p>\n' +
                        '        </div>\n' +*/
                        '    </div>\n' +
                        '    <div class="goodsDetails">\n' +
                        '        <h1><span></span>商品详情<span></span></h1>\n' +
                        '        <div>' + goods_info.body + '</div>' +
                        '    </div>';
                    $('.goods_detail_info').html(detail_html);

                    // 购物车
                    var dat = JSON.parse(JSON.stringify(res))
                    $.extend(dat, {
                        dian_info: {
                            ldj_delivery_order_Price: delivery,
                        }
                    })
                    var cart_html = template.render('store_cart', dat);
                    $('.bottom').html(cart_html);

                    var store_cart_html = template.render('store_cart_list', dat);
                    $('#wrapper').html(store_cart_html);
                }
            }
        })
    }

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
                        $('.gid' + gid).parent('.yunsuan').removeClass('hide');
                        $('.addCart').hide();
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
                            $('.cartAlert .hasData').append(newhtml);
                        }
                    }
                    if (num == 0) {
                        $('.gid' + gid).parent('.yunsuan').addClass('hide');
                        $('.addCart').show();
                        $('.addCart').removeClass('hide');
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
                        },
                        cart_info: JSON.parse(JSON.stringify(res.cart_list))
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

    $('body').on('click', '.addCart', function () {
        var gid = $(this).data('id');
        var vid = $(this).data('vid');
        var num = 1;
        cartNum('add', gid, vid, num);
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
        var checkLen = $('.hasData li.actived').length;
        $('#allSelect span').text('(已选' + checkLen + '件)');
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
            gids.push(parseInt($(sel[j]).data('gid')));
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
                    if(gids.indexOf(parseInt(gid))>-1){
                        getGoodsDetail();
                    }
                    if (delAll) {
                        $('#wrapper').hide();
                    } else {
                        sel.remove();
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
})