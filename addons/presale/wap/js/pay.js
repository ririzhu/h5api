$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
    }
    var order_sn = GetQueryString('order_sn');
    var price = GetQueryString('p');  // 价格
    var payment;   // 支付方式
    getPayMode();   // 获取支付列表

    function isWeixin() {
        var ua = window.navigator.userAgent.toLowerCase();
        if (ua.match(/MicroMessenger/i) == 'micromessenger') {
            return 1;
        } else {
            return 0;
        }
    }

    function getPayMode() {
        var client_type = isWeixin() ? 'h5_weixin' : 'h5_brower';
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=buy&mod=payment&sld_addons=presale',
            data: {
                key: key,
                order_sn: order_sn,
                client_type: client_type
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    var pay_html = '';
                    res.data.payment.forEach(function (el, index) {
                        switch (el.payment_code) {
                            case 'alipay':
                                el.img = './images/alipay@2x.png';
                                break;
                            case 'predeposit':
                                el.img = './images/yuer@2x.png';
                                break;
                            case 'wxpay_jsapi':
                                el.img = './images/weixin@2x.png';
                        }
                        pay_html += '<li class="pay_item" data-payment="' + el.payment_code + '">\n' +
                            '        <div class="p_left">\n' +
                            '            <img src="' + el.img + '" alt="">\n' +
                            '            <div class="info">\n' +
                            '                <p>商品总额：¥' + price + '</p>\n' +
                            '                <p>' + el.payment_name + '</p>\n' +
                            '            </div>\n' +
                            '        </div>\n' +
                            '        <div class="p_right">\n' +
                            '            <i class="checkbox ' + (index == 0 ? 'active' : '') + '"></i>\n' +
                            '        </div>\n' +
                            '    </li>'
                    });
                    $('.pay_mode ul').html(pay_html);
                    payment = res.data.payment[0].payment_code;
                }else{
                    layer.open({
                        content: res.msg,
                        btn: ['确定','取消'],
                        yes: function () {
                            history.back();
                        },
                        no: function () {
                            history.back();
                        }
                    })
                }
            }
        })
    }

    // 切换支付方式
    $('body').on('click', '.pay_item', function () {
        payment = $(this).data('payment');
        $('.pay_item i').removeClass('active');
        $(this).find('i').addClass('active');
    })

    // 支付
    var flag = true;
    $('.go_pay').on('click', function () {
        if (!flag) return;
        flag = false;
        if (!payment) {
            layer.open({
                content: '请选择支付方式',
                skin: 'msg',
                time: 2
            });
            flag = true;
            return;
        }
        if (payment == 'predeposit') {
            layer.open({
                content: '确认使用余额支付',
                btn: ['确定', '取消'],
                yes: function () {
                    pay()
                },
                no: function () {
                    flag = true;
                }
            })
        } else {
            pay();
        }

        function pay() {
            $.ajax({
                type: 'post',
                url: ApiUrl + '/index.php?app=buy&mod=topay&sld_addons=presale',
                data: {
                    key: key,
                    order_sn: order_sn,
                    payment: payment
                },
                dataType: 'json',
                success: function (res) {
                    flag = true;
                    if (res.status == 200) {
                        layer.open({
                            content: res.msg,
                            skin: 'msg',
                            time: 2
                        })
                        window.location.href = WapSiteUrl+'/order.html';
                    } else if (res.status == 300) {
                        var url_params = res.url_param;
                        var url = ApiUrl + '/index.php?';
                        for (var i in url_params) {
                            if (i == 'app') {
                                url += i + '=' + url_params[i];
                            } else {
                                url += '&' + i + '=' + url_params[i];
                            }
                        }
                        window.location.href = url;
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
})