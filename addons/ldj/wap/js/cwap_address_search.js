window.onpageshow = function (e) {
    if (e.persisted) {
        window.location.reload()
    }
};

$(function () {
    var type = GetQueryString('type');
    var key = getcookie('key');
    if (!key && type!=1) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    } else if(key) {
        getAddrList();
    }

    if(!key){
        var str = no_login_tip();
        $('.c_my_address').html(str);
        $('.add_address').remove();
    }
    if(type){
        $('.header-title h1').text('选择地址');
        document.title = '选择地址';
    }

    // 获取我的地址
    function getAddrList() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=address&mod=getmemberaddresslist&sld_addons=ldj',
            data: {
                key: key
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    if(res.data.length>0){
                        var li_html = template('addr_list', res);
                        $('.c_addr_list ul').html(li_html);
                    }else{
                        var l_html = ' <div class="noadd">\n' +
                            '            <div class="img">\n' +
                            '                <img src="./images/gprs_sb1.png" alt="">\n' +
                            '            </div>\n' +
                            '            <p>您还未添加收货地址</p>\n' +
                            '        </div>'
                        $('.c_addr_list').html(l_html);
                    }
                } else {
                    var l_html = ' <div class="noadd">\n' +
                        '            <div class="img">\n' +
                        '                <img src="./images/gprs_sb1.png" alt="">\n' +
                        '            </div>\n' +
                        '            <p>您还未添加收货地址</p>\n' +
                        '        </div>'
                    $('.c_addr_list').html(l_html);
                }
            }
        })
    }

    // 删除地址
    $('body').on('click', '.addr_item .del', function () {
        var id = $(this).data('id');
        var that = $(this);
        layer.open({
            content: '确认删除当前地址',
            btn: ['确定','取消'],
            yes: function () {
                $.ajax({
                    type: 'get',
                    url: ApiUrl + '/index.php?app=address&mod=deladdress&sld_addons=ldj',
                    data: {
                        key: key,
                        address_id: id
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.status == 200) {
                            layer.open({
                                content: res.msg,
                                skin: 'msg',
                                time: 2
                            });
                            that.parents('.addr_item').remove()
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
    })

    // 编辑
    $('body').on('click', '.addr_item .edit', function () {
        remove()
        var id = $(this).data('id');
        if (id) {
            window.location.href = WapSiteUrl + '/cwap_new_address.html?id=' + id;
        }
    })

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

    // 添加
    $('.add_address').on('click', function () {
        remove()
        window.location.href = WapSiteUrl + '/cwap_new_address.html';
    })

    // 详细地址
    $('.c_search').on('click',function () {
        window.location.href = WapSiteUrl + '/cwap_map_list.html?from_url=index.html';
    })
    $('.c_dw').on('click',function () {
        window.location.href = WapSiteUrl + '/cwap_map_list.html?from_url=index.html';
    })

    // 选择地址
    $('body').on('click','.addr_item .info',function () {
        var lng = $(this).data('lng');
        var lat = $(this).data('lat');
        var address = $(this).data('addr');
        var location = [lng,lat];
        if(type==1){
            addcookie('location',JSON.stringify(location));
            addcookie('address',address);
        }else if(type==2){
            addcookie('address_id', $(this).data('id'));
        }
        if(type){
            if(document.referrer){
                window.history.go(-1);
            }else{
                window.location.replace(WapSiteUrl+'/index.html');
            }
        }
    })
})