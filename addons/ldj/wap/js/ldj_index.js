window.onpageshow = function (e) {
    if (e.persisted) {
        window.location.reload()
    }
};
$(function () {
    var key = getcookie('key');
    // if (key == '') {
    //     window.location.href = WapSiteUrl + '/cwap_login.html';
    // }

    var location = [];
    var address;
    var pn = 1;
    var page = 10;
    var hasmore = true;
    var flag = true;

    var loca_addr = getcookie('location');
    if(loca_addr){
        loca_addr = JSON.parse(loca_addr);
        location[0]=loca_addr[0];
        location[1]=loca_addr[1];
        address = getcookie('address');
        $('.name').text(address);
        getInfo(location[0],location[1]);
    }else{
        get_location_position(function (lng,lat,addr) {
            if(lng&&lat){
                location[0]=lng;
                location[1]=lat;
                addcookie('location',JSON.stringify(location));
                addcookie('address',addr);
            }
            address = addr?addr:'未知';
            $('.name').text(address);
            getInfo(lng,lat);
        });
    }

    // 获取首页数据
    function getInfo(lng, lat) {
        if(!flag) return;
        flag = false;
        layer.open({
            type: 2
            ,content: '加载中'
        });
        var data = {
            page: page,
            pn: pn
        };
        if (key) data.key = key;
        if (lng && lat) {
            data.latitude = lng;
            data.longitude = lat;
        }
        $.ajax({
            type: "get",
            url: ApiUrl + "/index.php?app=index&mod=index&sld_addons=ldj",
            data: data,
            dataType: "json",
            success: function (res) {
                flag = true;
                layer.closeAll();
                if (res.status == 200) {
                    if(pn==1){
                        $('.shop_box').html('');
                    }
                    var html = template.render('shop-list', res);
                    $('.shop_box').append(html);
                    if (res.ismore.hasmore) {
                        pn++;
                    } else {
                        hasmore = false;
                    }
                } else {
                    hasmore = false;
                    layer.open({
                        skin: 'msg',
                        content: res.msg,
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
                if (location.length) {
                    getInfo(location[0], location[1])
                } else {
                    getInfo()
                }
            }
        }
    })

    // 搜索
    $('body').on('click','.header_search',function(){
        window.location.href = WapSiteUrl + '/cwap_pro_search.html?type=1';
    })

})