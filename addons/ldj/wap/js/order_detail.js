$(function () {
    var key = getcookie('key');
    if (!key) {
        window.location.href = SiteUrl + '/cwap/cwap_login.html';
        return;
    }
    var order_id = GetQueryString('order_id');
    var isInvalid;
    var map;
    getOrderDetail();

    function getOrderDetail() {
        $.ajax({
            type: 'get',
            url: ApiUrl + '/index.php?app=order&mod=order_desc&sld_addons=ldj',
            data: {
                key: key,
                order_id: order_id
            },
            dataType: 'json',
            success: function (res) {
                if (res.status == 200) {
                    isInvalid = parseInt(res.data.surplus_time);
                    $.extend(res.data,{
                        sd_time: initTime(res.data.start_time,res.data.end_time)
                    })
                    var state_html = template.render('order_state', res.data);
                    $('.taitou').html(state_html);

                    var express_html = template.render('order_express',res.data);
                    $('.express_type').html(express_html);

                    var list_html = template.render('order_detail_list', res.data);
                    $('.orderList').html(list_html);

                    var total_html = template.render('order_total', res.data);
                    $('.heji').html(total_html);

                    var order_html = template.render('order_detail_info', res.data);
                    $('.order_detail_ls').html(order_html);

                    var call_html = template.render('order_call', res.data);
                    $('#alert').html(call_html);

                    if(res.data.express_type==1&&(res.data.order_state==30 || res.data.order_state==20 || res.data.order_state==40)){
                        $('body').prepend('<div id="order_container"></div>');
                        $('.taitou').addClass('hasmap');
                        var dianlocation = [res.data.dian_lng,res.data.dian_lat];
                        order_map_init(dianlocation,'order_container');
                    }

                    if(res.data.order_state==40){
                        $('.panel').css('height','1.54rem');
                    }

                    time(res.data);
                } else {
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    })
                }
                var pd = true;
                var height  = $('body .orderList li').height();
                var linum = $('body .orderList li').length;
                if(linum > 1){
                    $(".orderList ul").css("height",  height*2+'px');
                }else{
                    $(".orderList ul").css("height",  height+'px');
                }
                $('body').on('click', '.Clickshow', function () {
                    if (pd == true) {
                        $(".orderList ul").css("height", 'auto');
                        pd = false;
                    } else {
                        if(linum > 1){
                            $(".orderList ul").css("height",  height*2+'px');
                        }else{
                            $(".orderList ul").css("height",  height+'px');
                        }

                        pd = true;
                    }

                })
            }
        })
    }

    function time(data) {
        if (data.order_state == 10) {
            djs();
        }

        function djs() {
            if (window.timer) {
                clearTimeout(window.timer);
            }

            var h = parseInt(isInvalid / 60 / 60);
            var m = parseInt(isInvalid / 60 % 60);
            var s = parseInt(isInvalid % 60);
            if (isInvalid > 0) {
                h = h > 9 ? h : '0' + h;
                m = m > 9 ? m : '0' + m;
                s = s > 9 ? s : '0' + s;
                $('.red_btn').text('去支付（还剩'+ h + '时' + m + '分' + s + '秒）');;
                isInvalid--;
                window.timer = setTimeout(djs, 1000);
            } else {
                $('.red_btn').text('超时已取消');
                $('.red_btn').attr('href', 'javascript:;')
            }
        }
    }
    
    function initTime(t1,t2) {
        return t1.split(' ')[1]+'-'+t2.split(' ')[1]
    }

})