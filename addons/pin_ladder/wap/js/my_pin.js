$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html'
    }
    var type = '';  // 订单状态
    var pn = 1;
    var hasmore = true;
    if (key) {
        getPinList()
    }

    function getPinList() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=buy_ladder&mod=pin_order_list&sld_addons=pin_ladder',
            data: {
                key: key,
                type: type,
                page: 10,
                pn: pn
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    var list_html = template.render('pin_list', res.data);
                    if (pn == 1) {
                        $('.main .goods_list ul').html(list_html);
                    } else {
                        $('.main .goods_list ul').append(list_html);
                    }

                    if (res.data.ismore.hasmore) {
                        pn++;
                    } else {
                        hasmore = false;
                    }
                } else {
                    hasmore = false;
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    })
                }
            }
        })
    }


    // 下拉加载更多
    $(window).on('scroll', function () {
        if (hasmore) {
            var top = document.body.scrollTop || document.documentElement.scrollTop;
            var hei = document.body.scrollHeight || document.documentElement.scrollHeight;
            var scrollHei = document.documentElement.clientHeight || document.body.clientHeight;
            if (top + scrollHei >= hei - 100) {
                getPinList()
            }
        }
    })

    // 切换分类
    $('.nav_container .nav li a').on('click', function () {
        var id = $(this).data('id');
        if (type == id) return;
        $('.nav_container .nav li').removeClass('active');
        $(this).parent('li').addClass('active');
        type = id;
        pn = 1;
        hasmore = true;
        getPinList();
    })

})