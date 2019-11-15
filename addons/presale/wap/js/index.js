$(function () {
    var key = getcookie('key');
    var hasmore = true;
    var pn = 1;
    var class_id = '';
    var flag = true;
    getMode();

    function getMode() {
        $.ajax({
            url: ApiUrl + '/index.php?app=goods&mod=getclasslist&sld_addons=presale',
            type: 'get',
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    var nav_html = ''
                    res.list.forEach(function (el, index) {
                        if (index == 0) {
                            nav_html += ' <li class="on" data-id="' + el.id + '">\n' +
                                '            <a href="javascript:;">' + el.class_name + '</a>\n' +
                                '        </li>'
                        } else {
                            nav_html += ' <li data-id="' + el.id + '">\n' +
                                '            <a href="javascript:;">' + el.class_name + '</a>\n' +
                                '        </li>'
                        }
                    })
                    $('.nav_container ul').html(nav_html);
                    navInit();
                    class_id = res.list[0].id;
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
        if(!flag) return;
        flag = false;
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=goods&mod=index&sld_addons=presale',
            data: {
                class_id: class_id,
                page: 10,
                pn: pn
            },
            dataType: 'json',
            success: function (res) {
                flag = true;
                if (res.status == 200) {
                    var goods_html = template.render('goods_item', res.data);
                    if (pn == 1) {
                        $('.goods_list ul').html(goods_html);
                    } else {
                        $('.goods_list ul').append(goods_html);
                    }
                    if (res.data.ismore.hasmore) {
                        pn++;
                    } else {
                        hasmore = false;
                    }
                } else {
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
                getIndexDate()
            }
        }
    })

    // 切换分类
    $('body').on('click', '.nav_container li', function () {
        var id = $(this).data('id');
        if (id == class_id) return;
        class_id = id;
        pn = 1;
        hasmore = true;
        getIndexDate();
    })
})

// navInit
function navInit() {
    var nav = $('.nav_container ul');
    var child = nav.find('li');
    var child_len = child.length;
    if (child_len < 6) {
        child.css('width', 100 / child_len + 'vw');
    }
    var $width = child.width();
    nav.css('width', $width * child_len + 'px');
    $('body').on('click', '.nav_container li', function () {
        $(this).addClass('on').siblings('li').removeClass('on');
    })
}

