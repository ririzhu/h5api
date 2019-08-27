$(function () {
    var key = getcookie('key');
    var type = 1;
    var cartData;
    if(!key){
        var str = no_login_tip();
        $('.cart').html(str);
    }

    if(key){
        var loca_addr = getcookie('location');
        var location = [];
        if(loca_addr){
            loca_addr = JSON.parse(loca_addr);
            location[0]=loca_addr[0];
            location[1]=loca_addr[1];
            getCart(location[0],location[1]);
        }else{
            get_location_position(function (lng, lat,addr) {
                if (lng && lat) {
                    location[0] = lng;
                    location[1] = lat;
                    addcookie('location',JSON.stringify(location));
                    addcookie('address',addr);
                }
                getCart(location[0],location[1]);
            });
        }
    }

    // 获取购物车详情
    function getCart(lng, lat) {
        var data = {
            key: key
        };
        if (lng && lat) {
            data.latitude = lng;
            data.longitude = lat;
        } else {

        }
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=cart&mod=getAllCartList&sld_addons=ldj',
            data: data,
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    cartData = res;
                    if (res.data.length) {
                        var in_data = res.data.filter(function (el) {
                            return el.error==0
                        });
                        var out_data = res.data.filter(function (el) {
                            return el.error==1
                        });

                        $.extend(res,{
                            errlen: out_data.length,
                            noerr: in_data.length
                        });

                        var in_html = template.render('in_cart', res);
                        $('.in_h').html(in_html);
                        var out_html = template.render('out_cart', res);
                        $('.out_h').html(out_html);
                    }
                } else {
                    var n_html = '<div class="cart_not">\n' +
                        '        <div class="img">\n' +
                        '            <img src="./images/cart_w.png" alt="">\n' +
                        '        </div>\n' +
                        '        <p>您的购物车还是空的</p>\n' +
                        '    </div>';
                    $('.cart').html(n_html);
                    /*layer.open({
                        skin: 'msg',
                        content: res.msg,
                        time: 2
                    })*/
                }
            }
        })
    }

    // cart_id
    function cart_id(vid) {
        var result = [];
        cartData.data.filter(function (item) {
            return item.vid == vid;
        })[0].cart_list.list.forEach(function (el) {
            result.push(el.cart_id);
        });
        return result;
    }

    // 删除购物车
    $('body').on('click', '.item .imgdel', function () {
        var ids = [];
        var vid = $(this).data('vid');
        ids = cart_id(vid);
        layer.open({
            content: '确认删除该购物车吗',
            btn: ['确认', '取消'],
            yes: function () {
                layer.closeAll();
                $.ajax({
                    type: 'post',
                    url: ApiUrl + '/index.php?app=cart&mod=deletecart&sld_addons=ldj',
                    data: {
                        key: key,
                        type: type,
                        cart_ids: ids
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.status == 200) {
                            layer.open({
                                skin: 'msg',
                                content: res.msg,
                                time: 2
                            })
                            window.location.reload();
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

    // 结算
    $('body').on('click','.pay button',function () {
        var vid = $(this).data('vid');
        var delivery_type = [];
        var type1 = $(this).data('type1');
        var type2 = $(this).data('type2');
        if(type1){
            delivery_type.push(type1)
        }
        if(type2){
            delivery_type.push(type2)
        }
        var ids = cart_id(vid).join(',');
        var from_url = '';
        if (delivery_type.length == 2) {
            from_url = WapSiteUrl + '/cwap_confirm_an_order.html?type=2&order_type=1&vid=' + vid + '&cart_id=' + ids;
        } else if (delivery_type.indexOf('上门自提') > -1) {
            from_url = WapSiteUrl + '/cwap_order_ziqu.html?type=2&order_type=2&vid=' + vid + '&cart_id=' + ids;
        } else if (delivery_type.indexOf('门店配送') > -1) {
            from_url = WapSiteUrl + '/cwap_confirm_an_order.html?type=2&order_type=1&vid=' + vid + '&cart_id=' + ids;
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