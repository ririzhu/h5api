$(function(){
	wap_now = 'sld_cart';
	var key = getcookie('ssys_key');
	if(key==''){
		window.location.href = WapSiteUrl+'/cwap_the_login.html';
	}
//检测权限接口,是否可以进入推手系统
    check_Jurisdiction(key);
	var page = pagesize;
	var pn = 1;
	var hasMore = true;
    var footer = false;
    var readytopay = false;
    var reset = true;

    if (cash_action) {
        var ssys_min_cash_amount_once = 10;

        // 获取 提现相关配置
        get_cash_setting();

        $(".max_allow_cash_amount").text(ssys_min_cash_amount_once);

        $(".apply_cash_btn").click(function(e){
            // 提现金额
            var cash_amount = $(".cash_amount").val()*1;

            if (cash_amount) {
                // 提交金额申请
                $.ajax({
                    type:'post',
                    url:ApiUrl+"/index.php?app=ssys_cash&mod=cash_apply&sld_addons=spreader",
                    data:{ssys_key:key,cash_amount:cash_amount},
                    dataType:'json',
                    success:function(result){
                        checklogin(result.login);
                        if (result.datas.error) {
                            layer.open({
                                content: result.datas.error
                                , skin: 'msg'
                                , time: 2 //2秒后自动关闭
                            });
                        }else{
                            layer.open({
                                content: result.datas.msg
                                , skin: 'msg'
                                , time: 2 //2秒后自动关闭
                                ,success: function(layero, index){
                                    setTimeout(function(){
                                        window.location.href = 'cwap_withdrawal.html';
                                    },2000);
                                }
                            });
                        }

                    }
                });
            }else{
                layer.open({
                    content: '请填写申请提现的金额'
                    , skin: 'msg'
                    , time: 2 //2秒后自动关闭
                });
            }
        });
    }else{
        var cash_state = '';
        get_user_info();
        get_order_list();
        $(".cash_state ul li").click(function(e){
            reset = true;
            cash_state = $(this).data('v');
            $(".cash_state p span").text($(this).text());
            $(".cash_state ul").hide();
            get_order_list();
        });
        $(window).scroll(function() {
            if ($(window).scrollTop()!=0&&$(window).scrollTop() + $(window).height() > $(document).height() - 1) {
                get_order_list()
            }
        })
    }

    function get_cash_setting(){
        $.ajax({
            type:'post',
            url:ApiUrl+"/index.php?app=api&mod=get_cash_setting&sld_addons=spreader",
            data:{ssys_key:key},
            dataType:'json',
            async:false,
            success:function(result){
                ssys_min_cash_amount_once = result.ssys_min_cash_amount_once;
            }
        });
    }

    function get_user_info(){
        $.ajax({
            type:'post',
            url:ApiUrl+"/index.php?app=usercenter&mod=memberInfo&sld_addons=spreader",
            data:{ssys_key:key},
            dataType:'json',
            success:function(result){
                checklogin(result.login);

                $(".top_user_amount_num.disable_yongjin").text(result.datas.member_info.disable_yongjin);
                $(".top_user_amount_num.freeze_yongjin").text(result.datas.member_info.freeze_yongjin);
                $(".top_user_amount_num.available_yongjin").text(result.datas.member_info.available_yongjin);
                return false;
            }
        });
    }

    function get_order_list() {
        if (reset) {
            pn = 1;
            hasMore = true
        }
        $(".loading").remove();
        if (!hasMore) {
            return false
        }
        hasMore = false;
        $.ajax({
            type: "post",
            url: ApiUrl + "/index.php?app=ssys_cash&mod=pdcashlist&sld_addons=spreader&page=" + page + "&pn=" + pn,
            data: {
                ssys_key: key,
                cash_state: cash_state,
            },
            dataType: "json",
            success: function(e) {
                checklogin(e.login);
                pn++;
                hasMore = e.hasmore;
                var t = e;
                var r = template.render("order-list-tmpl", t);
                if (reset) {
                    reset = false;
                    $("#order-list").html(r);
                    $(".loading").remove();
                } else {
                    $("#order-list").append(r);
                    $(".loading").remove();
                }
            }
        })
    }

});