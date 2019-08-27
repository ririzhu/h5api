$(function () {
    var type = GetQueryString('type');
    var vid = GetQueryString('vid')
    var sh = [];

    // 获取搜索记录
    function getSH() {
        var search_history = window.localStorage.getItem('search_history');
        var result = search_history ? search_history.split('-') : [];
        return result;
    }

    // 设置搜索记录
    function setSH(val) {
        var search_history = window.localStorage.getItem('search_history');
        var arr = search_history ? search_history.split('-') : [];
        arr.unshift(val);
        arr = arr.filter(function (el, i, a) {
            return a.indexOf(el) == i;
        })
        while (arr.length >= 30) {
            arr.pop();
        }
        window.localStorage.setItem('search_history', arr.join('-'));
    }

    // 清除历史记录
    function clearSh() {
        window.localStorage.removeItem('search_history');
    }

    // 搜索记录
    sh = getSH();
    if (sh.length) {
        var s_html = '';
        sh.forEach(function (el) {
            s_html += '<dd data-val="' + el + '">' + el + '</dd>';
        })
        $('#store-wrapper .ls_jr').html(s_html);
    }
    /*else {
        $('#store-wrapper').hide();
    }*/
    $('body').on('click', '#store-wrapper .ls_jr dd', function () {
        var k = $(this).data('val');
        $('.search-input').val(k);
    });

    // 清除
    $('body').on('click', '#delete', function () {
        layer.open({
            content: '清除历史记录？',
            btn: ['确定', '取消'],
            yes: function () {
                clearSh();
                // $('#store-wrapper').hide();
                $('#store-wrapper .ls_jr').remove();
                layer.closeAll();
            }
        })
    })

    // 搜索
    $('body').on('click', '.search-btn', function () {
        var k = $('.search-input').val().trim();
        if (k.length > 0) {
            setSH(k);
            if (type == 1) {
                window.location.href = WapSiteUrl + '/cwap_search.html?k=' + k;
            } else {
                window.location.href = WapSiteUrl + '/cwap_shop_goodslist.html?vid=' + vid + '&type=2&k='+k;
            }
        } else {
            layer.open({
                content: '请输入店铺或商品进行搜索',
                skin: 'msg',
                time: 2
            })
        }
    })
})