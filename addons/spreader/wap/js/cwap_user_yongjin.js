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


    var cash_page = pagesize;
    var cash_pn = 1;
    var cash_hasMore = true;
    var cash_reset = true;

    get_user_info();
    get_order_list();
    $(window).scroll(function() {
        if ($(window).scrollTop()!=0&&$(window).scrollTop() + $(window).height() > $(document).height() - 1) {
            get_order_list()
        }
    })

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
            url: ApiUrl + "/index.php?app=userorder&mod=order_list&sld_addons=spreader&page=" + page + "&pn=" + pn,
            data: {
                ssys_key: key,
                yj_state: yj_state,
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
        });
        // 没有订单数据了
        if (yj_state == 0 && !hasMore) {
            // 获取 提现申请 正在审核中 的记录列表

            get_cash_list()
        }
    }
    function get_cash_list(){

        if (cash_reset) {
            cash_pn = 1;
            cash_hasMore = true
        }
        $(".loading").remove();
        if (!cash_hasMore) {
            return false
        }
        cash_hasMore = false;

        var cash_state = 'i';
        $.ajax({
            type: "post",
            url: ApiUrl + "/index.php?app=ssys_cash&mod=pdcashlist&sld_addons=spreader&page=" + cash_page + "&pn=" + cash_pn,
            data: {
                ssys_key: key,
                cash_state: cash_state,
            },
            dataType: "json",
            success: function(e) {
                checklogin(e.login);
                cash_pn++;
                cash_hasMore = e.hasmore;
                var t = e;
                var r = template.render("order-cash-list-tmpl", t);
                if (cash_reset) {
                    cash_reset = false;
                    $("#order-cash-list").html(r);
                    $(".loading").remove();
                    $("#order-list .no-order").remove();
                } else {
                    $("#order-cash-list").append(r);
                    $(".loading").remove();
                    $("#order-list .no-order").remove();
                }
            }
        });
    }

});