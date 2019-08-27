window.onpageshow = function (e) {
    if (e.persisted) {
        window.location.reload()
    }
};
$(function () {
    var key = getcookie('key');
   /* if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }*/

    var pn = 1;
    var hasmore = true;
    var flag = true;

    if(!key){
        var str = no_login_tip();
        $('#orderList').html(str);
    }else {
        getOrderList();
    }

    function getOrderList() {
        if (!flag) return;
        flag = false;
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=order&mod=order_list&sld_addons=ldj',
            data: {
                key: key,
                page: 10,
                pn: pn
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    res.data.map(time);
                    var order_html = template.render('my_order_list', res);
                    $('#orderList').append(order_html);
                    if (res.ismore.hasmore) {
                        pn++;
                    } else {
                        hasmore = false;
                    }
                    res.data.map(time2);
                } else {
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    })
                }
                flag = true;
            }
        })
    }

    // 计算时间
    function time(el) {
        var now = new Date(),
            year = now.getFullYear(),
            month = now.getMonth() + 1,
            data = now.getDate(),
            addtime = new Date(el.add_time * 1000),
            addy = addtime.getFullYear(),
            addm = addtime.getMonth() + 1,
            addd = addtime.getDate(),
            h = addtime.getHours(),
            m = addtime.getMinutes(),
            result = {};
        addm = addm > 9 ? addm : '0' + addm;
        addd = addd > 9 ? addd : '0' + addd;
        h = h > 9 ? h : '0' + h;
        m = m > 9 ? m : '0' + m;
        if (year == addy && month == addm && data == addd) {
            result = '今天' + h + ':' + m;
        } else {
            result = addm + '-' + addd + ' ' + h + ':' + m;
        }
        $.extend(el, {
            add_time_str: result
        })
    }

    // 倒计时
    function time2(el) {
        if (el.order_state == 10) {
            window['t' + el.order_id] = parseInt(el.surplus_time);
            djs();
        }

        function djs() {
            if (window['timer' + el.order_id]) {
                clearTimeout(window['timer' + el.order_id]);
            }

            var h = parseInt(window['t' + el.order_id] / 60 / 60);
            var m = parseInt(window['t' + el.order_id] / 60 % 60);
            var s = parseInt(window['t' + el.order_id] % 60);
            if (window['t' + el.order_id] > 0) {
                h = h > 9 ? h : '0' + h;
                m = m > 9 ? m : '0' + m;
                s = s > 9 ? s : '0' + s;
                $('.v' + el.order_id + ' .pay_t').text('去支付（还剩' + h + ':' + m + ':' + s + '）');
                window['t' + el.order_id]--;
                window['timer' + el.order_id] = setTimeout(djs, 1000)
            } else {
                $('.v' + el.order_id + ' .pay_t').text('已取消');
                $('.v' + el.order_id + ' a').hide();
            }
        }
    }

    // 加载更多
    $(window).on('scroll', function () {
        if (hasmore) {
            var top = document.body.scrollTop || document.documentElement.scrollTop;
            var hei = document.body.scrollHeight || document.documentElement.scrollHeight;
            var scrollHei = document.documentElement.clientHeight || document.body.clientHeight;
            if (top + scrollHei >= hei - 100) {
                getOrderList();
            }
        }
    })

    // 查看订单详情
    $('body').on('click', '.order_Goods', function () {
        var order_id = $(this).data('oid');
        window.location.href = WapSiteUrl + '/cwap_order_detail.html?order_id=' + order_id;
    })
})