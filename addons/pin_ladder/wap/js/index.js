$(function () {
    var key = getcookie('key');
    var hasmore = true;
    var pn = 1;
    var tid = '';
    getMode();

    function getMode() {
        $.ajax({
            url: ApiUrl + '/index.php?app=index&mod=index&sld_addons=pin_ladder',
            type: 'get',

            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    var nav_html = ''
                    res.data.forEach(function (el, index) {
                        if (index == 0) {
                            nav_html += ' <li class="nav_item active" data-id="' + el.id + '">\n' +
                                '            <a href="javascript:;">' + el.class_name + '</a>\n' +
                                '        </li>'
                        } else {
                            nav_html += ' <li class="nav_item" data-id="' + el.id + '">\n' +
                                '            <a href="javascript:;">' + el.class_name + '</a>\n' +
                                '        </li>'
                        }
                    })
                    $('.nav_container2 .nav').html(nav_html);
                    navInit();
                    tid = res.data[0].id;
                    getIndexDate();
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


    function getIndexDate() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=index&mod=data&sld_addons=pin_ladder',
            data: {
                tid: tid,
                page: 10,
                pn: pn
            },
            dataType: 'json',
            success: function (res) {
                if (res.code == 200) {
                    var goods_html = template.render('goods_item', res.datas);
                    if (pn == 1) {
                        $('.content').html(goods_html);
                    } else {
                        $('.content').append(goods_html);
                    }
                    if (res.hasmore) {
                        pn++;
                    } else {
                        hasmore = false;
                    }
                } else {
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    })
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
                getIndexDate()
            }
        }
    })

    // 切换分类
    $('body').on('click', '.nav_container2 .nav .nav_item', function () {
        var id = $(this).data('id');
        if (id == tid) return;
        tid = id;
        pn = 1;
        hasmore = true;
        getIndexDate();
    })
})

// navInit
function navInit() {
    var nav = $('.nav_container2 .nav');
    var child = nav.find('li');
    var child_len = child.length;
    if (child_len < 6) {
        child.css('width', 100 / child_len + 'vw');
    }
    var $width = child.width();
    nav.css('width', $width * child_len + 'px');
    $('body').on('click', '.nav_container2 .nav li', function () {
        $(this).addClass('active').siblings('li').removeClass('active');
    })
}

