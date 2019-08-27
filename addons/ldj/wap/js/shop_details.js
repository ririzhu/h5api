$(function () {
    var key = getcookie('key');
    /*if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
    }*/
    var vid = GetQueryString('vid');

    getStoreDetail();
    // 获取店铺详情
    function getStoreDetail() {
        $.ajax({
            type: 'post',
            url: ApiUrl + '/index.php?app=dian&mod=dian_info_function&sld_addons=ldj',
            data: {
                dian_id: vid
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    var detail_html = template.render('shop_detail', res.data);
                    $('.shop_detail_wrap').html(detail_html);
                }
            }
        })
    }
    // 搜索
    $('.search-input').on('click', function () {
        window.location.href = WapSiteUrl + '/cwap_pro_search.html?vid=' + vid + '&type=2';
    })
})