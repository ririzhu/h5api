$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }
    var pay_sn = GetQueryString('pay_sn');
    var client = isWeixin();
    var pay_type;  // 支付方式
    var surplus_time; // 订单是否过期

    getOrderInfo();

    // 获取订单信息
    function getOrderInfo() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=order&mod=pay_confirm&sld_addons=ldj',
            data: {
                key: key,
                pay_sn: pay_sn,
                is_weixin: client           // 0不是微信  1是微信
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    pay_type = res.paymentlist.data[0].payment_code;
                    isInvalid = parseInt(res.data.surplus_time);
                    var order_html = template.render('pay_info', res.data);
                    $('.nav-top').html(order_html);

                    res.data.order_amount = parseFloat(res.data.order_amount);
                    res.member_info.available_predeposit = parseFloat(res.member_info.available_predeposit);
                    var mode_html = template.render('pay_mode', res);
                    $('.select').html(mode_html);

                    $('.submit span').text(' ¥' + res.data.order_amount);
                    time(res.data)
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

    // 倒计时
    function time(data) {
        djs();

        function djs() {
            if (window.timer) {
                clearTimeout(window.timer);
            }
            var h = parseInt(isInvalid / 60 / 60);
            var m = parseInt(isInvalid / 60 % 60);
            var s = parseInt(isInvalid % 60);
            if (isInvalid > 0) {
                h = h > 9 ? h : '0' + h;
                m = m > 9 ? m : '0' + m;
                s = s > 9 ? s : '0' + s;
                $('.nav-top span').text('剩余支付时间为:' + h + ':' + m + ':' + s);
                isInvalid--;
                window.timer = setTimeout(djs, 1000);
            } else {
                $('.nav-top span').text('超时已取消');
            }
        }
    }

    // 选择支付方式
    $('body').on('click', '.select ul li', function () {
        $(this).siblings().children('em').removeClass("actived");
        $(this).children('em').addClass("actived");
        pay_type = $(this).data('mode');
    })

    // 确认支付
    $('body').on('click', '.submit', function () {
        if(pay_type=='predeposit'){
            layer.open({
                content: '确认使用余额支付',
                btn: ['确定','取消'],
                yes: function () {
                    submit()
                }
            })
        }else{
            submit()
        }
        function submit() {
            if (isInvalid <= 0) {
                layer.open({
                    content: '订单已失效',
                    skin: 'msg',
                    time: 2
                })
                return;
            }
            if (!pay_type) {
                layer.open({
                    content: '请选择支付方式',
                    skin: 'msg',
                    time: 2
                })
                return;
            }
            $.ajax({
                type: 'get',
                url: ApiUrl + '/index.php?app=order&mod=pay&sld_addons=ldj',
                data: {
                    key: key,
                    pay_sn: pay_sn,
                    pay_type: pay_type
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status == 100) {
                        layer.open({
                            content: res.msg,
                            skin: 'msg',
                            time: 2
                        })
                    } else if (res.status == 200) {
                        layer.open({
                            content: '支付成功',
                            skin: 'msg',
                            time: 1.5
                        })
                        window.location.href=WapSiteUrl+'/cwap_pay_successful.html?pay_sn='+res.pay_sn;
                    } else if (res.status == 300) {
                        window.location.href = res.url;
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