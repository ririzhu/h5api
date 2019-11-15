window.onpageshow = function (e) {
    if (e.persisted) {
        window.location.reload()
    }
};

$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
    }

    var gid = GetQueryString('gid');
    var number = GetQueryString('num');
    var pin_id = GetQueryString('id');
    var address_id,
        member_message = sessionStorage.getItem('member_message') || '', p, is_tui;

    $.ajax({
        type: 'get',
        url: ApiUrl + '/index.php?app=buy_ladder&mod=iwantpinladder&sld_addons=pin_ladder',
        data: {
            key: key,
            gid: gid,
            number: number,
            pin_id: pin_id,
            t:new Date().getTime()
        },
        dataType: 'json',
        success: function (res) {
            if(res.code == 200){
                if(res.datas.error == Language::get('请登录')){
                    delCookie('key');
                    window.location.href = SiteUrl + '/cwap/cwap_login.html';
                    return;
                }
            }
            if (res.status == 200) {
                console.log(res.data.address);
                if (Object.keys(res.data.address).length > 0) {
                    address_id = res.data.address.address_id;
                }else{
                    layer.open({
                        content: '请添加地址',
                        btn: ['确定','取消'],
                        yes: function () {
                            layer.closeAll();
                            window.location.href = SiteUrl + '/cwap/cwap_address.html?from_url=' + location.href;
                        },
                        no: function () {
                            history.back();
                        }
                    })
                }
                var goods_html = template('goods_info', res.data.goods_info);
                $('.goods_list ul').html(goods_html);
                var addr_html = template('address_info', res.data.address);
                $('.address').html(addr_html);
                p = res.data.goods_info.deposit_money_all
                $('.money .m_right span:nth-child(2)').text('￥' + p);
                $('.go_pay .g_left').text('应付定金：￥' + p);
                is_tui = res.data.goods_info.is_tui;
                if (is_tui == '0') {
                    $('.agreement').html('<p>我已同意定金不退预售协议 <i class="checkbox"></i></p>')
                }
            }else if(res.status == 255){
                layer.open({
                    content: res.msg,
                    btn: ['确定','取消'],
                    shadeClose: false,
                    yes: function () {
                        layer.closeAll();
                        history.back();
                    },
                    no: function () {
                        history.back();
                    }
                })
            } else {
                layer.open({
                    content: res.msg,
                    skin: 'msg',
                    time: 2
                })
            }
        }
    });

    // 选择地址
    $('body').on('click', '.addr_right', function () {
        $('.select_address').removeClass('hide');
        getAddrList(address_id);

    });
    $('.select_address .header-l a').on('click', function () {
        $('.select_address').addClass('hide')
    });

    $('body').on('click', '.select_address .addr_item', function () {
        var true_name = $(this).data('name');
        address_id = $(this).data('id');
        var area_info = $(this).data('areainfo');
        var mob_phone = $(this).data('phone');
        var address = $(this).data('addr');
        var addr_obj = {
            true_name: true_name,
            area_info: area_info,
            mob_phone: mob_phone,
            address: address
        }
        var addr_html = template('address_info', addr_obj);
        $('.address').html(addr_html);
        $('.select_address').addClass('hide');
    });

    // 新增地址
    $('.add_address').on('click', function () {
        window.sessionStorage.setItem('member_message', member_message);
        window.location.href = SiteUrl + '/cwap/cwap_address.html?from_url=' + location.href;
    })


    // 同意协议
    $('body').on('click', '.agreement i', function () {
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
        } else {
            $(this).addClass('active');
        }
    });

    // 留言
    if (member_message) {
        $('.msg textarea').val(member_message);
        sessionStorage.removeItem('member_message');
    }
    $('.msg textarea').on('blur', function () {
        member_message = $(this).val();
    });

    function getAddrList(id) {
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=address&mod=address_list',
            data: {
                key: key
            },
            dataType: 'json',
            success: function (res) {
                if (res.code == 200) {
                    var addr_html = '';
                    var addr_list = res.datas.address_list;
                    if (addr_list && addr_list.length) {
                        addr_list.forEach(function (el) {
                            addr_html += '<li class="addr_item" ' +
                                'data-name="' + el.true_name + '" ' +
                                'data-id="' + (el.address_id) + '"' +
                                'data-areainfo="' + el.area_info + '"' +
                                'data-phone="' + el.mob_phone + '"' +
                                'data-addr="' + el.address + '">' +
                                '        <div class="address_left">';
                            if (id && el.address_id == id) {
                                addr_html += '<img src="./images/ok.png" alt="">';
                            }
                            addr_html += '</div>\n' +
                                '        <div class="address_right">\n' +
                                '            <p>' + (el.true_name) + ' ' + (el.mob_phone) + (el.is_default == 1 ? '<span class="isDefault">默认</span>' : '') + '</p>\n' +
                                '            <p>' + el.area_info + el.address + '</p>\n' +
                                '        </div>\n' +
                                '    </li>'
                        })
                    }
                    $('.addr_main ul').html(addr_html);
                }
            }
        })
    }

    var flag = true;
    $('.go_pay .g_right').on('click', function () {
        if (!flag) return;
        flag = false;
        if (is_tui == '0' && !$('.agreement i').hasClass('active')) {
            layer.open({
                content: '请同意销售协议',
                skin: 'msg',
                time: 2
            });
            flag = true;
            return;
        }
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=buy_ladder&mod=submitorder&sld_addons=pin_ladder',
            data: {
                key: key,
                gid: gid,
                number: number,
                pin_id: pin_id,
                address_id: address_id,
                member_message: member_message
            },
            dataType: 'json',
            success: function (res) {
                flag = true;
                if (res.status == 200) {
                    var order_sn = res.data.order_sn;
                    window.location.href = WapSiteUrl + '/pay.html?order_sn=' + order_sn + '&p=' + p;
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