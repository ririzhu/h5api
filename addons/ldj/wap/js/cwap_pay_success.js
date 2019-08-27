$(function () {
    var pay_sn = GetQueryString('pay_sn');
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }
    if(!pay_sn){
        window.location.replace(WapSiteUrl+'/index.html');
    }

    $.ajax({
        type: 'get',
        url: ApiUrl+'/index.php?app=order&mod=pay_ok&sld_addons=ldj',
        data:{
            key: key,
            pay_sn: pay_sn
        },
        dataType: 'json',
        success: function (res) {
            if(res.status==200){

                var v_html = '<p>支付成功</p>\n' +
                    '    <h1> <span>&yen;</span> '+ res.data.order_amount +'</h1>';
                $('.view').html(v_html);

                var s_html = '<ul>\n' +
                    '        <li>\n' +
                    '            <span>收款方</span>'+ res.data.dian_id +'\n' +
                    '        </li>\n' +
                    '        <li>\n' +
                    '            <span>下单时间</span>'+ res.data.add_time +'\n' +
                    '        </li>\n' +
                    '        <li>\n' +
                    '            <span>支付方式</span>'+ res.data.payment_name +'\n' +
                    '        </li>\n' +
                    '        <li>\n' +
                    '            <span>订单编号</span>'+ res.data.order_sn +'\n' +
                    '        </li>\n' +
                    '    </ul>';
                $('.pay_detail').html(s_html);
            }else{
                layer.open({
                    content: res.msg,
                    skin: 'msg',
                    time: 2
                })
            }
        }
    })

    $('.finish').on('click',function () {
        window.location.href = WapSiteUrl+'/index.html';
    })

})