$(function(){
	wap_now = 'sld_cart';
	var key = getcookie('ssys_key');
	if(key==''){
		window.location.href = WapSiteUrl+'/cwap_the_login.html';
	}

	var page = pagesize;
	var pn = 1;
	var hasMore = true;
    var footer = false;
    var readytopay = false;
    var reset = true;

    get_order_list();
    $(window).scroll(function() {
        if ($(window).scrollTop()!=0&&$(window).scrollTop() + $(window).height() > $(document).height() - 1) {
            get_order_list()
        }
    })

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