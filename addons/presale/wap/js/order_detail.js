$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
    }
    var order_id = GetQueryString('orderid');
    getOrderDetail();

    function getOrderDetail() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=order&mod=order_desc&sld_addons=presale',
            data: {
                key: key,
                order_id: order_id
            },
            dataType: 'json',
            success: function (res) {
                console.log(res)
                if (res.status == 200) {
                    var addr_html = template.render('j_address', res.data.address);
                    $('.address').html(addr_html);
                    var goods_html = template.render('j_goods', res.data);
                    $('.goods_list').html(goods_html);

                    // 订单号
                    $('.order p:nth-child(1) span:nth-child(2)').text(res.data.order_sn);
                    $('.order p:nth-child(2) span:nth-child(2)').text(res.data.add_time);

                    // 阶段
                    var jd_html = template.render('price_detail', res.data);
                    $('.ys_jd_wrap').html(jd_html);

                    // 付尾款
                    if (res.data.finish) {
                        $('.go_pay_wrap').html('<div class="go_pay">\n' +
                            '        <p class="g_left">应付尾款：￥' + res.data.wei_price + '</p>\n' +
                            '        <div class="g_right">去支付尾款</div>\n' +
                            '    </div>')
                        $('.main').css('paddingBottom', '2.39130435rem');
                        $('body').on('click', '.go_pay .g_right', function () {
                            pay_price(res.data.order_sn, res.data.wei_price)
                        })
                    }
                    // 付定金
                    if (res.data.ding) {
                        $('.go_pay_wrap').html('<div class="go_pay">\n' +
                            '        <p class="g_left">应付定金：￥' + res.data.ding_price + '</p>\n' +
                            '        <div class="g_right">去支付定金</div>\n' +
                            '    </div>')
                        $('.main').css('paddingBottom', '2.39130435rem');
                        $('body').on('click', '.go_pay .g_right', function () {
                            pay_price(res.data.order_sn, res.data.ding_price)
                        })
                    }

                    if (res.data.order_state == 20) {
                        var o_html = '<div class="pay_success_img"><img src="./images/pay_success@2x.png" alt=""></div>'
                        $('.main').prepend(o_html);
                    }
                    if (res.data.order_state == 0) {
                        var o_html = '<div class="pay_fail_txt"><p>支付超时，订单已失效</p></div>';
                        $('.main').prepend(o_html);
                    }
                }
            }
        })
    }

    // 付定金
    function pay_price(order_sn, p) {
        window.location.href = WapSiteUrl + '/pay.html?order_sn=' + order_sn + '&p=' + p;
    }
})