window.onpageshow = function (e) {
    var u = navigator.userAgent;
    var isIOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
    if (e.persisted) {
        window.location.reload()
        // isStorage()
    }
};

$(function () {
    var location = [];  // 经纬度
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }
    var address_id = GetQueryString('id');
    if (address_id) {
        $('#header .header-title h1').text('编辑地址');
        document.title = '编辑地址'
        // 编辑地址
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=address&mod=getmemberaddressinfo&sld_addons=ldj',
            data: {
                key: key,
                address_id: address_id
            },
            dataType: 'json',
            success: function (res) {
                console.log(res)
                if (res.status == 200) {
                    var $city = $('.choose_city .inp')
                    $city.val(res.data.area_info);
                    $city.attr({
                        'data-pid': res.data.province_id,
                        'data-cid': res.data.city_id,
                        'data-aid': res.data.area_id
                    })
                    $('.choose_addr .inp').val(res.data.address);
                    $('.mp .inp').val(res.data.address_precose);
                    $('.user .inp').val(res.data.true_name);
                    $('.phone .inp').val(res.data.mob_phone);
                    if (res.data.is_default == 1) {
                        $('.isde label').addClass('checked');
                    } else {
                        $('.isde label').removeClass('checked');
                    }
                    location = [res.data.lng, res.data.lat];
                    isStorage()
                }
            }
        })
    } else {
        $('#header .header-title h1').text('新增地址');
        $('.isde').remove()
        isStorage()
    }

    // 判断存储中是否有数据
    function isStorage() {
        var lng = getcookie('session_lng');   // 地图返回
        var lat = getcookie('session_lat');
        var addr = getcookie('session_addr');  // 详细地址
        var area_info = getcookie('choose_city');
        var mp = getcookie('mp');
        var user = getcookie('user');
        var phone = getcookie('phone');
        var isdefault = getcookie('isdefault');
        if (lng) {
            location[0] = lng;
            location[1] = lat;
            $('.choose_addr input').val(addr);
        }
        if (area_info) {
            $('.choose_city .inp').val(area_info);
            var id = getcookie('choose_city_id').split('-');
            $('.choose_city .inp').attr({
                'data-pid': id[0],
                'data-cid': id[1],
                'data-aid': id[2]
            })
        }
        if (mp) {
            $('.mp .inp').val(mp);
        }
        if (user) {
            $('.user .inp').val(user);
        }
        if (phone) {
            $('.phone .inp').val(phone);
        }
        if (isdefault == 'true') {
            $('.isde label').addClass('checked');
        } else if(isdefault == 'false') {
            $('.isde label').removeClass('checked');
        }
    }

    // 选择详细位置
    $('.choose_addr').on('click', function () {
        // 选择的城市
        var $city = $('.choose_city .inp');
        if ($city.val()) {
            addcookie('choose_city', $city.val());
            addcookie('choose_city_id', $city.data('pid') + '-' + $city.data('cid') + '-' + $city.data('aid'));
        }
        //门牌号
        var mp = ($('.mp .inp').val()).trim();
        if (mp) {
            addcookie('mp', mp)
        }
        // 收货人
        var user = ($('.user .inp').val()).trim();
        if (user) {
            addcookie('user', user)
        }
        // 联系电话
        var phone = ($('.phone .inp').val()).trim();
        if (phone) {
            addcookie('phone', phone)
        }
        // 是否默认
        var sid = $('.isde label').hasClass('checked');
        addcookie('isdefault', sid)

        var area_info = $('.choose_city .inp').val();
        window.location.href = WapSiteUrl + '/cwap_map_list.html?area=' + area_info;
    })

    // 选择城市
    var province = function (callback) {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=address&mod=getprovinceinfo&sld_addons=ldj',
            data: {
                key: key
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    res.msg.map(function (el) {
                        $.extend(el, {
                            id: el.area_id,
                            value: el.area_name,
                            parentId: el.area_parent_id
                        })
                    });
                    callback(res.msg);
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

    var city = function (pr, callback) {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=address&mod=getareainfo&sld_addons=ldj',
            data: {
                key: key,
                area_id: pr
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    res.msg.map(function (el) {
                        $.extend(el, {
                            id: el.area_id,
                            value: el.area_name,
                            parentId: el.area_parent_id
                        })
                    });
                    callback(res.msg)
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

    var area = function (p, ci, callback) {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=address&mod=getareainfo&sld_addons=ldj',
            data: {
                key: key,
                area_id: ci
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    res.msg.map(function (el) {
                        $.extend(el, {
                            id: el.area_id,
                            value: el.area_name,
                            parentId: el.area_parent_id
                        })
                    });
                    callback(res.msg);
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
    $('.choose_city').on('click', function () {
        var iosSelect = new IosSelect(3,
            [province, city, area],
            {
                title: '',
                itemHeight: 35,
                relation: [1, 1, 0, 0],
                showLoading: true,
                callback: function (selectOneObj, selectTwoObj, selectThreeObj) {
                    var str = selectOneObj.value + ' ' + selectTwoObj.value + ' ' + selectThreeObj.value;
                    var dom = $('.choose_city .inp');
                    dom.val(str);
                    dom.attr({
                        'data-pid': selectOneObj.area_id,
                        'data-cid': selectTwoObj.area_id,
                        'data-aid': selectThreeObj.area_id
                    })
                }
            });
    })

    // 清除缓存
    function remove() {
        window.localStorage.removeItem('session_lng');
        window.localStorage.removeItem('session_lat');
        window.localStorage.removeItem('session_addr');
        window.localStorage.removeItem('choose_city');
        window.localStorage.removeItem('choose_city_id');
        window.localStorage.removeItem('mp');
        window.localStorage.removeItem('user');
        window.localStorage.removeItem('phone');
        window.localStorage.removeItem('isdefault');
    }

    // 保存地址
    $('.form-btn').on('click', function () {
        console.log(location)
        var mp = $('.mp .inp').val(),
            name = $('.user .inp').val(),
            phone = $('.phone .inp').val(),
            is_default = $('.isde label').hasClass('checked'),
            area_info = $('.choose_city .inp').val(),
            pid = $('.choose_city .inp').data('pid'),
            cid = $('.choose_city .inp').data('cid'),
            aid = $('.choose_city .inp').data('aid'),
            addr = $('.choose_addr .inp').val();
        if (!area_info) {
            layer.open({
                content: '请选择地址',
                skin: 'msg',
                time: 2
            })
            return;
        }
        if (!location.length) {
            layer.open({
                content: '请选择详细地址',
                skin: 'msg',
                time: 2
            })
            return;
        }
        if (!name) {
            layer.open({
                content: '请输入收货人',
                skin: 'msg',
                time: 2
            })
            return;
        }

        if (!phone) {
            layer.open({
                content: '请输入联系电话',
                skin: 'msg',
                time: 2
            })
            return;
        }
        is_default = is_default ? '1' : '0';

        if (name.length > 6) {
            layer.open({
                content: '收货人名称过长',
                skin: 'msg',
                time: 2
            })
            return;
        }

        if (address_id) {  // 编辑修改
            $.ajax({
                type: 'get',
                url: ApiUrl + '/index.php?app=address&mod=editaddress&sld_addons=ldj',
                data: {
                    key: key,
                    address_id: address_id,
                    true_name: name,
                    province_id: pid,
                    city_id: cid,
                    area_id: aid,
                    area_info: area_info,
                    address: addr,
                    address_precose: mp,
                    mob_phone: phone,
                    lng: location[0],
                    lat: location[1],
                    is_default: is_default
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status == 200) {
                        remove()
                        var from_url = document.referrer;
                        if (from_url) {
                            window.history.go(-1);
                        } else {
                            window.location.replace(WapSiteUrl + '/cwap_address_list.html');
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
        } else {    // 新增
            $.ajax({
                type: 'get',
                url: ApiUrl + '/index.php?app=address&mod=insertaddress&sld_addons=ldj',
                data: {
                    key: key,
                    true_name: name,
                    province_id: pid,
                    city_id: cid,
                    area_id: aid,
                    area_info: area_info,
                    address: addr,
                    address_precose: mp,
                    mob_phone: phone,
                    lng: location[0],
                    lat: location[1]
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status == 200) {
                        remove()
                        var from_url = document.referrer;
                        if (from_url) {
                            window.history.go(-1);
                        } else {
                            window.location.replace(WapSiteUrl + '/cwap_address_list.html');
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
    })

    $('.user .inp').on('blur', function () {
        var val = $(this).val();
        if (val.length > 6) {
            layer.open({
                content: '收货人名称过长',
                skin: 'msg',
                time: 2
            })
        }
    })

})