window.onpageshow = function (e) {
    if (e.persisted) {
        window.location.reload()
    }
};

$(function () {
    var keyword = GetQueryString('k');
    var key = getcookie('key');
    var type = 1;

    var page = 10;
    var pn = 1;
    var hasmore = true;
    var location = [];
    var flag = true;

    var loca_addr = getcookie('location');
    if (loca_addr) {
        loca_addr = JSON.parse(loca_addr);
        location[0] = loca_addr[0];
        location[1] = loca_addr[1];
        search(keyword);
    } else {
        get_location_position(function (lng, lat, addr) {
            if (lng && lat) {
                location[0] = lng;
                location[1] = lat;
                addcookie('location', JSON.stringify(location));
                addcookie('address', addr);
            }
            search(keyword);
        });
    }

    if (keyword) {
        $('.search-input').val(keyword)
    }

    function search(val) {
        if (!flag) return;
        flag = false;
        var data = {
            type: type,
            keyworld: val,
            page: page,
            pn: pn
        }
        if (key) {
            data.key = key;
        }
        if (location.length) {
            data.longitude = location[1];
            data.latitude = location[0];
        } else {
            data.lng = 116.46;
            data.lat = 39.54;
        }
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=goods&mod=goods_list&sld_addons=ldj',
            data: data,
            dataType: 'json',
            success: function (res) {
                flag = true;
                if (res.status == 200) {
                    var list_html = template.render('search_l', res);
                    $('.s_list').append(list_html);

                    if (res.ismore.hasmore) {
                        pn++;
                    } else {
                        hasmore = false;
                    }
                } else {
                    if (pn == 1) {
                        var err = '<div class="map_sb">\n' +
                            '\t<img src="./images/sousuo_null@2x.png" alt="">\n' +
                            '\t<p>没有找到该商品</p>\n' +
                            '</div>'
                        $('.s_list').html(err)
                    }
                    hasmore = false;
                    if (pn > 1) {
                        var n_html = '<div class="null">\n' +
                            '        <span></span>\n' +
                            '        没有更多了\n' +
                            '        <span></span>\n' +
                            '    </div>';
                        $('.s_list').append(n_html);
                    }
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
                search(keyword)
            }
        }
    })

    // 搜索

    $('body').on('click', '.header-inp', function () {
        window.location.href = WapSiteUrl + '/cwap_pro_search.html?type=1';
    })
    $('body').on('click', '.search-btn', function () {
        window.location.href = WapSiteUrl + '/cwap_pro_search.html?type=1';
    })

})