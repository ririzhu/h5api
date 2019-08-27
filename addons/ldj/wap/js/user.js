$(function () {
    var key = getcookie('key');
    if (!key) {
        nologin()
    } else {
        getUserInfo();
    }

    function nologin() {
        var user_html = '<div class="userName">\n' +
            '        <img src="./images/user_img@2x.png" alt="">\n' +
            '        <a href="'+SiteUrl+'/cwap/cwap_login.html">登录</a><span>|</span><a href="'+SiteUrl+'/cwap/cwap_register_tel.html">注册</a>\n' +
            '    </div>';
        $('.user_box').html(user_html);

        var list_html = '<div class="userList">\n' +
            '    <a href="'+SiteUrl+'/cwap/cwap_login.html">\n' +
            '        <div>\n' +
            '        <div class="img"><img src="./images/qianbao@2x.png" alt=""></div>\n' +
            '        <span>钱包</span>\n' +
            '        </div>\n' +
            '        <span>\n' +
            '            ---\n' +
            '        </span>\n' +
            '    </a>\n' +
            '</div>\n' +
            '\n' +
            '<div class="userList">\n' +
            '    <a href="'+SiteUrl+'/cwap/cwap_login.html">\n' +
            '        <div>\n' +
            '            <div class="img"><img src="./images/dizhi6@2x.png" alt=""></div>\n' +
            '            <span>我的地址</span>\n' +
            '        </div>\n' +
            '    </a>\n' +
            '</div>';
        $('.user_wrap').html(list_html);
    }

    function getUserInfo() {
        if (!key) return;
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=login&mod=usercenter&sld_addons=ldj',
            data: {
                key: key
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 255) {
                    var phone = res.data.member_mobile;
                    phone = phone ? phone : '暂无手机号';
                    var user_html = ' <div class="userName">\n' +
                        '<a href="'+ (SiteUrl+'/cwap/cwap_profile.html') +'"><img src="' + res.data.member_avatar + '" alt=""></a>' +
                        '    <dl>\n' +
                        '    <dd>' + res.data.member_name + '</dd>\n' +
                        '    <dd>' + phone + '</dd>\n' +
                        '    </dl>\n' +
                        '    </div>';
                    $('.user_box').html(user_html);

                    var list_html = '<div class="userList">\n' +
                        '    <a href="'+ (SiteUrl+'/cwap/cwap_useryue.html?type=ldj') +'">\n' +
                        '        <div>\n' +
                        '        <div class="img"><img src="./images/qianbao@2x.png" alt=""></div>\n' +
                        '        <span>钱包</span>\n' +
                        '        </div>\n' +
                        '        <span>\n' +
                        '            ' + res.data.available_predeposit + '\n' +
                        '        </span>\n' +
                        '    </a>\n' +
                        '</div>\n' +
                        '\n' +
                        '<div class="userList">\n' +
                        '    <a href="cwap_address_list.html">\n' +
                        '        <div>\n' +
                        '            <div class="img"><img src="./images/dizhi6@2x.png" alt=""></div>\n' +
                        '            <span>我的地址</span>\n' +
                        '        </div>\n' +
                        '    </a>\n' +
                        '</div>';
                    $('.user_wrap').html(list_html);
                }else if(res.status=266){
                    localStorage.removeItem('key');
                    nologin();
                }
                else {
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

