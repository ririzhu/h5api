window.onpageshow = function (e) {
    if (e.persisted) {
        window.location.reload()
    }
};
$(function () {
    var key = getcookie('key');
  /*  if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }*/
    var area = GetQueryString('area');
    var from = GetQueryString('from_url');
    var pn = 1;
    var hasmore = true;
    var from_url = document.referrer;
    var location = [];
    var city;
    var marker;
    var flag = true;
    var keyword; // 搜索关键词
    var map = new AMap.Map('ldj_container', {
        resizeEnable: true,
        zoom: 16
    })
    if (area) {
        getLnglat()
    } else {
        dw();
    }

    if (from_url) {
        document.title = '选择地址'
    }

    // 获取城市经纬度
    function getLnglat() {
        var area_info = area.split(' ');
        var p = area_info[0];
        var c = area_info[1];
        var a = area_info[2];
        AMap.plugin('AMap.Geocoder', function () {
            var geocoder = new AMap.Geocoder({
                city: p
            })
            geocoder.getLocation(a, function (status, result) {
                if (status === 'complete' && result.info === 'OK') {
                    city = result.geocodes[0].adcode;
                    location = [result.geocodes[0].location.lng, result.geocodes[0].location.lat];
                    setCenter(location);
                    searchAddr();
                }
            })
        })
    }

    // 定位
    function dw() {
        var loc = getcookie('location')
        if(loc){
            location = JSON.parse(loc);
            setCenter(location);
            searchAddr()
        }else{
            AMap.plugin('AMap.Geolocation', function () {
                var geolocation = new AMap.Geolocation({
                    enableHighAccuracy: true,//是否使用高精度定位，默认:true
                    timeout: 10000,          //超过10秒后停止定位，默认：5s
                    buttonPosition: 'RB',    //定位按钮的停靠位置
                    buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
                    zoomToAccuracy: true,   //定位成功后是否自动调整地图视野到定位点

                });
                map.addControl(geolocation);
                geolocation.getCurrentPosition(function (status, result) {
                    if (status == 'complete') {
                        city = result.addressComponent.city ? result.addressComponent.city : result.addressComponent.province;
                        location = [result.position.lng, result.position.lat];
                        searchAddr()
                    } else {
                        onError()
                    }
                });
            });
        }
    }

    // 设置地图中心点
    function setCenter(location) {
        if(marker){
            map.remove(marker);
        }
        map.setCenter(location);
        marker = new AMap.Marker({
            position: location,
            // icon: './images/dw2.png'
        });
        map.add(marker);
    }

    // 搜索附近地标  isaddr=false 通过经纬度搜索 isaddr=true 通过地址搜索
    function searchAddr(keyworld,isaddr) {
        if(!flag) return;
        flag = false;
        layer.open({
            type: 2
        })
        AMap.plugin(["AMap.PlaceSearch"], function () {
            var placeSearch = new AMap.PlaceSearch({
                pageSize: 10,
                pageIndex: pn,
                city: city
            });
            if(isaddr){
                placeSearch.search(keyworld,function (status,result) {
                    layer.closeAll();
                    flag = true;
                    if(status=='complete'){
                        var locationList = result.poiList;
                        var pois_html = template.render('pois', locationList);
                        if (pn == 1) {
                            var f = locationList.pois[0].location;
                            location = [f.lng,f.lat];
                            setCenter(location);
                            $('.map_list ul').html(pois_html);
                        } else {
                            $('.map_list ul').append(pois_html);
                        }
                        if (Math.ceil(locationList.count / 10) > pn) {
                            pn++
                        } else {
                            hasmore = false;
                        }
                    }
                })
            }else{
                placeSearch.searchNearBy(keyworld, location, 3000, function (status, result) {
                    layer.closeAll();
                    flag = true;
                    if (result.info === 'OK') {
                        var locationList = result.poiList;
                        var pois_html = template.render('pois', locationList);
                        if (pn == 1) {
                            $('.map_list ul').html(pois_html);
                        } else {
                            $('.map_list ul').append(pois_html);
                        }
                        if (Math.ceil(locationList.count / 10) > pn) {
                            pn++
                        } else {
                            hasmore = false;
                        }
                    } else {
                        onError()
                    }
                });
            }
        });
    }
    // 发送错误
    function onError() {
        var err_html = '<div class="gprs_sibai">\n' +
            '    <img src="./images/gprs_sb.png" alt="">\n' +
            '    <p>自动定位失败，请尝试搜索或拖动地图</p>\n' +
            '</div>';
        $('.map_list').hide();
        $('.noadd').html(err_html);
    }

    // 点击地图事件
    map.on('click', function (ev) {
        location = [ev.lnglat.lng,ev.lnglat.lat];
        setCenter(location);
        pn=1;
        hasmore = true;
        keyword = '';
        searchAddr('',false);
    })

    // 滚动加载更多

    $('.map_list').on('scroll', function () {
        if (hasmore) {
            var $hei = $(this).find('ul').height();
            var whei = $(this).height();
            var sctop = $(this).scrollTop();
            if (whei + sctop > $hei - 50) {
                if(keyword){
                    searchAddr(keyword,true);
                }else{
                    searchAddr('',false)
                }
            }
        }
    })

    // 搜索
    $('.header-title img').on('click',function () {
        pn = 1;
        hasmore = true;
        keyword = $('.search').val();
        searchAddr(keyword,true);
    });
    $('.search').on('keydown', function (e) {
        if (e.keyCode == 13) {
            keyword = $(this).val();
            $(this).blur();
            if (!(keyword.trim())) {
                layer.open({
                    content: '请输入地址',
                    skin: 'msg',
                    time: 2
                })
                return;
            }
            pn = 1;
            hasmore = true;
            searchAddr(keyword,true);
        }
    })


    // 选择地点
    $('body').on('click', '.map_list li', function () {
        var lng = $(this).data('lng');
        var lat = $(this).data('lat');
        var addr = $(this).data('addr');
        if (from) {
            addcookie('address', addr);
            var lo = [lng, lat];
            addcookie('location', JSON.stringify(lo));
            window.location.replace(WapSiteUrl + '/index.html');
        } else {
            addcookie('session_lng', lng);
            addcookie('session_lat', lat);
            addcookie('session_addr', addr);
            window.history.go(-1);
        }
    })

})
