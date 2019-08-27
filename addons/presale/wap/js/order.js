$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
    }
    var type = '', pn = 1, hasmore = true, flag = true;
    getOrderList();

    function getOrderList() {
        if (!flag) return;
        flag = false;
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=order&mod=pre_order_list&sld_addons=presale',
            data: {
                key: key,
                type: type,
                page: 10,
                pn: pn
            },
            dataType: 'json',
            success: function (res) {
                flag = true;
                if (res.status = 200) {
                    var order_html = template.render('ys_order_list', res.data);
                    if (pn == 1) {
                        $('.goods_list ul').html(order_html);
                    } else {
                        $('.goods_list ul').append(order_html);
                    }
                    if (res.data.ismore.hasmore) {
                        pn++;
                    } else {
                        hasmore = false;
                    }
                }else{
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    });
                    hasmore = false;
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
                getOrderList()
            }
        }
    })

    // 切换分类
    $('.nav_container li a').on('click', function () {
        var id = $(this).data('id');
        console.log(id);
        if(id==type){
            return;
        }
        type = id;
        pn=1;
        hasmore=true;
        getOrderList();
        $('.nav_container li').removeClass('on');
        $(this).parent('li').addClass('on');
    })
})