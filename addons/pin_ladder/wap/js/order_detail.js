$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html'
    }
    var order_id = GetQueryString('orderid');
    getOrderDetail();

    function getOrderDetail() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=buy_ladder&mod=order_desc&sld_addons=pin_ladder',
            data: {
                key: key,
                order_id: order_id
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    if (parseInt(res.data.guding.order_state) < 30 && parseInt(res.data.guding.order_state) > 0) {
                        $('.go_pay_wrap').html('<div class="go_pay">\n' +
                            '        <p class="g_left"></p>\n' +
                            '        <div class="g_right disable">去支付尾款</div>\n' +
                            '    </div>')
                        $('.main').css('paddingBottom', '2.39130435rem');
                    }
                    var addr_html = template('j_address', res.data.guding);
                    $('.address').html(addr_html);

                    $('.order p:nth-child(1) span:nth-child(2)').text(res.data.guding.order_sn);
                    $('.order p:nth-child(2) span:nth-child(2)').text(res.data.guding.add_time);
                    if (res.data.change.jieduan_2_price) {
                        $('.go_pay .g_left').text('应付尾款：￥' + res.data.change.jieduan_2_price);
                    }
                    $('.msg textarea').val(res.data.guding.member_message);

                    var goods_html = template('j_goods', res.data);
                    $('.goods_list').html(goods_html);

                    var price_html = template('price_detail', res.data.change);
                    $('.money').html(price_html);

                    // 阶梯团 进度
                    window.time = res.data.change.dao_ji_shi;
                    var jt = 0;   // 当前进行到的阶梯
                    var people_sum = res.data.change.yijing_pin_num;  // 已参与的人数
                    for (var i = 0; i < res.data.ladder_price.length; i++) {
                        var el = res.data.ladder_price[i];
                        if (people_sum >= el.people_num) {
                            jt = i + 1;
                        } else {
                            break;
                        }
                    }
                    jtt_pro_render(res.data.ladder_price, jt, people_sum)
                    // 交尾款
                    if (res.data.change.shi_fou_ke_yi_fu_wei_kuan) {
                        $('.g_right').removeClass('disable');
                        var order_sn = res.data.guding.order_sn;
                        var p = res.data.change.jieduan_2_price;
                        $('body').on('click', '.g_right', function () {
                            pay_wei_kuan(order_sn, p);
                        })
                    }
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

    function jtt_pro_render(data, jt, sum) {
        var str = '';
        if (window.time > 0) {
            str += ' <p class="time_out">\n' +
                '            <span>距结束 </span>\n' +
                '            <span><i></i>:<i></i>:<i></i></span>\n' +
                '        </p>'
        }
        str += '<div class="wrap">\n' +
            '            <div class="prev"><img src="./images/rtl.png" alt=""></div>\n' +
            '            <div class="next"><img src="./images/ltr.png" alt=""></div>\n' +
            '            <div class="pro_list">\n' +
            '                <ul>\n';
        for (var i = 0; i < data.length; i++) {
            var item = data[i];
            var now = parseInt(item.people_num);
            var left_pro = jt >= i + 1 ? (sum >= now ? 100 : (sum / item.people_num) * 100) : 0;
            var right_pro = jt >= i + 1 ? (sum > now ? 100 : 0) : 0;
            str += '<li class="pro_item ' + (jt == i + 1 ? "on" : "") + '">\n' +
                '    <div class="top">\n' +
                '        <p>￥' + item.pay_money + '</p>\n' +
                '        <p>满' + item.people_num + '人参团</p>\n' +
                '    </div>\n' +
                '    <div class="bottom ' + (jt >= i + 1 ? "on" : "") + '">\n' +
                '        <div>\n' +
                '            <span>' + (i + 1) + '</span>\n' +
                '            <div class="jd_pro_left">\n' +
                '                <div class="jd_pro_on" style="width: ' + left_pro + '%"></div>\n' +
                '            </div>\n' +
                '            <div class="jd_pro_right">\n' +
                '                <div class="jd_pro_on" style="width: ' + right_pro + '%"></div>\n' +
                '            </div>\n' +
                '        </div>\n' +
                '        <p>阶梯' + (i + 1) + '</p>\n' +
                '    </div>\n' +
                '</li>'
        }
        str += '            </ul>\n' +
            '        </div>\n' +
            '    </div>';
        $('.program').html(str);
        var $width = $('.pro_list').width();
        var child = $('.pro_list li');
        var childw = child.width() + parseInt(child.css('marginLeft')) * 2;
        var childWid = childw * child.length;
        if (jt * childw > $width) {
            var spend = jt * childw - $width + $width / 2;
            $('.pro_list').animate({
                scrollLeft: spend
            })
        }
        if (childWid < $width) {
            $('.program .prev').remove();
            $('.program .next').remove();
        }
        time_out();
    }

    function time_out() {
        if (window.timer) {
            clearTimeout(window.timer);
        }
        var h = parseInt(window.time / 60 / 60);
        var m = parseInt(window.time / 60 % 60);
        var s = parseInt(window.time % 60);
        if (window.time > 0) {
            h = h > 9 ? h : '0' + h;
            m = m > 9 ? m : '0' + m;
            s = s > 9 ? s : '0' + s;
            $('.time_out span:nth-child(2)').html('<i>' + h + '</i>:<i>' + m + '</i>:<i>' + s + '</i>');
            window.time--;
            window.timer = setTimeout(time_out, 1000);
        } else {
            $('.time_out span:nth-child(2)').text('活动已结束');
        }
    }


    // 交尾款
    var flag = true;

    function pay_wei_kuan(order_sn, p) {
        if (!flag) return;
        flag = false;
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=buy_ladder&mod=buy_finish&sld_addons=pin_ladder',
            data: {
                key: key,
                order_sn: order_sn
            },
            dataType: 'json',
            success: function (res) {
                flag = true;
                if (res.status == 200) {
                    var order_sn = res.data.order_sn;
                    window.location.href = WapSiteUrl + '/pay.html?order_sn=' + order_sn + '&p=' + p;
                } else {
                    if (res.status == 255) {
                        layer.open({
                            content: res.msg,
                            skin: 'msg',
                            time: 2
                        })
                    } else {
                        layer.open({
                            content: res.error,
                            skin: 'msg',
                            time: 2
                        })
                    }
                }
            }
        })
    }
})