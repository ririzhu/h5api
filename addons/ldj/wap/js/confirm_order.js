window.onpageshow = function (e) {
    if (e.persisted) {
        window.location.reload()
    }
};
$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }
    var vid = GetQueryString('vid');
    var cart_id = GetQueryString('cart_id');
    var type = GetQueryString('type');
    var order_type = GetQueryString('order_type');
    var address_id = getcookie('address_id'); //地址id
    var time_type = 1, time_section, member_phone, order_message;

    if (!type) {
        layer.open({
            content: '订单错误!',
            skin: 'msg',
            time: 2
        })
        setTimeout(function () {
            window.location.href = WapSiteUrl + '/index.html'
        }, 1000)
    } else {
        getOrderInfo();
    }

    // 获取订单信息
    function getOrderInfo() {
        var data = {
            key: key
        };
        if (type && type == 1) {
            data.type = 1;
            data.cart_id = cart_id;
        } else if (type && type == 2) {
            data.cart_id = cart_id;
            data.type = type;
            data.dian_id = vid;
        }
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=order&mod=confirm&sld_addons=ldj',
            data: data,
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    var iskd = res.data.dian_info.kuaidi;
                    var issm = res.data.dian_info.shangmen;
                    if (iskd && issm) {
                        $('.kaiguan').removeClass('hide');
                    }
                    if (!iskd) {
                        $('.peisongBox').height(0);
                    }
                    if (order_type == 1) {
                        sj_render(res.data);  // 商家配送
                    } else if (order_type == 2) {
                        zq_render(res.data);   // 自取
                    }
                } else {
                    layer.open({
                        content: res.msg,
                        btn: ['确定'],
                        yes: function () {
                            window.history.go(-1)
                        }
                    })
                }
            }
        })
    }

    // 商家自送
    var now = new Date();
    var year = now.getFullYear(),
        month = now.getMonth() + 1,
        date = now.getDate();
    var first = year + '-' + month + '-' + date;
    var next = year + '-' + month + '-' + (date + 1);

    function sj_render(data) {
        if (!address_id) {
            if (data.error_area_msg == '请添加收货地址') {
                layer.open({
                    content: '请添加收货地址',
                    btn: ['确定', '取消'],
                    yes: function () {
                        window.location.href = WapSiteUrl + '/cwap_address_list.html?type=2'
                    },
                    no: function () {
                        window.history.go(-1)
                    }
                })
                return;
            } else if (data.error_area_state == 1) {
                layer.open({
                    content: data.error_area_msg,
                    skin: 'msg',
                    time: 2
                })
                var addr_html = template.render('order_addr', {address_id: ''});
                $('.peisongBox .map').html(addr_html);
            } else {
                address_id = data.address.address_id;
                var addr_html = template.render('order_addr', data.address);
                $('.peisongBox .map').html(addr_html);
            }
        } else {
            // 更换收货地址
            $.ajax({
                type: 'get',
                url: ApiUrl + '/index.php?app=address&mod=getmemberaddressinfo&sld_addons=ldj',
                data: {
                    key: key,
                    address_id: address_id
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status == 200) {
                        var addr_html = template.render('order_addr', res.data);
                        $('.peisongBox .map').html(addr_html);
                        $('.banner').text('配送至：' + res.data.address);
                        window.localStorage.removeItem('address_id');
                        editAreaFreight(address_id);
                    }
                }
            })
        }
        time_section = data.estimatedTime.first_day[0] ? data.estimatedTime.first_day[0] : data.estimatedTime.sencond_day[0];

        var list_html = template.render('order_list', data);
        $('.orderList').html(list_html);

        var price_html = template.render('order_price', data);
        $('.jiesuan').html(price_html);

        var time = JSON.parse(JSON.stringify(data.estimatedTime));
        $.extend(time, {
            next: next
        })
        var time_html = template.render('order_time', time);
        $('.rightTab').html(time_html);

        $('.banner').text('配送至：' + data.address.address);
        var send_time = data.estimatedTime.first_day[0] ? data.estimatedTime.first_day[0] : next + '' + data.estimatedTime.sencond_day[0];
        $('.state em').text('预计送达时间: ' + send_time);
        time_type = data.estimatedTime.first_day[0] ? 1 : 2;
        $('#heji span b').text('¥' + (parseFloat(data.goods_all_price) + parseFloat(data.freight_money)));
    }

    // 到店自取
    function zq_render(data) {
        if (data.error_area_state == 1) {
            member_phone = '';
        } else {
            member_phone = data.address.mob_phone
        }
        time_section = data.estimatedTime.first_day[0] ? data.estimatedTime.first_day[0] : data.estimatedTime.sencond_day[0];
        var list_html = template.render('order_list', data);
        $('.orderList').html(list_html);

        var price_html = template.render('order_price', data);
        $('.jiesuan').html(price_html);

        var time = JSON.parse(JSON.stringify(data.estimatedTime));

        $.extend(time, {
            now: first,
            next: next,
            phone: member_phone
        });
        var info_html = template.render('order_info', time);
        $('.mapList').html(info_html);

        var time_html = template.render('order_time', time);
        $('.rightTab').html(time_html);

        $('#heji span b').text('¥' + data.goods_all_price);

        // 初始化地图
        var dianLocation = [data.dian_info.dian_lng, data.dian_info.dian_lat]; // 店铺位置
        order_map_init(dianLocation, 'iCenter')
    }

    // 计算运费
    function editAreaFreight(id) {
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=order&mod=editAreaFreight&sld_addons=ldj',
            data: {
                key: key,
                dian_id: vid,
                address_id: id
            },
            async: false,
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    $('.jiesuan p:nth-child(2) span').eq(1).text('￥' + res.freight_money);
                    var oldp = parseFloat($('.jiesuan p:nth-child(1) span').eq(1).text().split('¥')[1]);
                    $('#heji span b').text('¥' + ((parseFloat(oldp) + parseFloat(res.freight_money))).toFixed(2));
                } else {
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    })
                    $('.peisong .map').html('<div class="adddizhi">\n' +
                        '        <a href="cwap_address_list.html?type=2"><em>+</em>添加收货地址</a>\n' +
                        '    </div>')
                    address_id = '';
                }
            }
        })
    }

    // 商家自送 选择送达时间
    var se_time;
    $('body').on('click', '.state em', timeModelShow)
    $('body').on('click', '.choose_time', timeModelShow)
    function timeModelShow() {
        $(".time_alert").show();
        if (order_type == 1) {
            se_time = $('.state em').text();
        } else {
            se_time = $('.choose_time em').text();
        }
        se_type = time_type;
        se_setion = time_section;
        time_type = $('.leftTab .choose').index()+1;
    }

    $(".leftTab span").click(function () {
        $(".leftTab span").removeClass('choose');
        $(this).addClass("choose");
        $(".rightTab ul").css("display", "none");
        $(".rightTab ul").eq($(this).index()).css("display", "block");
        time_type = ($(this).index() == 0) ? 1 : 2;
    });
    $('body').on('click', '.rightTab ul li', function () {
        $(".rightTab ul li").removeClass("sel");
        $(this).addClass("sel");
        time_section = $(this).data('time');
        if (order_type == 1) {
            $('.state em').text('预计送达时间: ' + (time_type == 1 ? '' : next) + ' ' + $(this).data('time'));
        } else {
            $('.choose_time em').text((time_type == 1 ? first : next) + '  ' + $(this).data('time'))
        }
    })

    $(".timeAlert h1 span").click(function () {
        $(".time_alert").hide()
    });
    $(".timeAlert h1 b").click(function () {
        $(".time_alert").hide();
        if (order_type == 1) {
            $('.state em').text(se_time);
        }else{
            $('.choose_time em').text(se_time);
        }
        time_type = se_type;
        time_section = se_setion;
    });

    // 备注
    $('body').on('blur', '.beizhutext textarea', function (e) {
        order_message = $(this).val();
    })

    // 输入预留电话
    $('body').on('click', '.edit_phone', function () {
        $('.edit_phone_input').css('display', 'flex');
        member_phone = '';
    })
    $('body').on('blur', '.edit_phone_input input', function () {
        member_phone = $(this).val();
    });
    $("body").on('click', '.edit_phone_input h1 em', function () {
        $(".edit_phone_input").css("display", "none");
        member_phone = $('.edit_phone em').text();
    });
    $('body').on('click', '.edit_phone_input h1 span', function () {
        if (!(/^1[345678]\d{9}$/.test(member_phone))) {
            layer.open({
                content: '请输入正确的手机号码',
                skin: 'msg',
                time: 2
            })
            member_phone = '';
            return;
        } else {
            $(".edit_phone_input").css("display", "none");
            $('.edit_phone em').text(member_phone);
        }
    })


    // 切换订单类型
    $('body').on('click', '.zq', function () {
        var url = WapSiteUrl + '/cwap_order_ziqu.html?type=' + type + '&order_type=2&vid=' + vid + '&cart_id=' + cart_id;
        window.location.replace(url);
    })

    $('body').on('click', '.sj', function () {
        var url = WapSiteUrl + '/cwap_confirm_an_order.html?type=' + type + '&order_type=1&vid=' + vid + '&cart_id=' + cart_id;
        window.location.replace(url);
    })

    // 提交订单
    var sumbitFlag = true;
    $('body').on('click', '.sumbit_order', function () {
        if (!sumbitFlag) return;
        sumbitFlag = false;
        layer.closeAll();
        var data = {
            key: key,
            dian_id: vid,
            express_type: order_type
        }
        if (time_section) {
            data.time_type = time_type||1;
            data.time_section = time_section;
        }
        if (order_type == 1) {
            if (!address_id) {
                layer.open({
                    content: '请选择送货地址',
                    skin: 'msg',
                    time: 2
                })
                return;
            }
            data.address_id = address_id;
        }

        if (order_type == 2) {
            if (!member_phone) {
                layer.open({
                    content: '请输入预留电话',
                    skin: 'msg',
                    time: 2
                })
                return;
            }
            data.member_phone = member_phone;
        }

        if (order_message) {
            if (order_message.length > 30) {
                layer.open({
                    content: '订单备注在30字以内',
                    skin: 'msg',
                    time: 2
                })
                return;
            }
            data.order_message = order_message;
        }

        if (cart_id) {
            data.cart_id = cart_id;
        }
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=order&mod=createorder&sld_addons=ldj',
            data: data,
            dataType: 'json',
            success: function (res) {
                sumbitFlag = true;
                if (res.status == 200) {
                    var pay_sn = res.pay_sn;
                    window.location.href = WapSiteUrl + '/cwap_pay.html?pay_sn=' + pay_sn;
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
})



