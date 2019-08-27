
    $(".head ul li a").click(function(){
        $(".head ul li a").css("border-color","transparent");
        console.log($(this));
        $(this).css("border-color","#fc3357");
        console.log($(this).siblings());
    });


    var u_k = getcookie("ssys_key");
    var tid = GetQueryString('t')?GetQueryString('t'):0;
    var page = pagesize;
    var pn = 1;
    var hasmore = true;
    $("doucment").ready(function () {
    	get_cates_list();
        change_page(tid);
        $(window).scroll(function () {
            if ($(window).scrollTop() + $(window).height() > $(document).height() - 1) {
                get_list(tid)
            }
        });
    });

    // ------------------------------------------------

	// 获取 商品分类列表
    function get_cates_list(){
        $.ajax({
            url: ApiUrl + "/index.php?app=ssys_goods&mod=cates_list&sld_addons=spreader",
            type: 'get',
            dataType: 'json',
            success: function(result) {
                if (result.code != '200') {
                    return;
                }
                var data = result.datas;
                var h_types = template.render("menu", data);

                $(".top_cate_menu ul").html(h_types);

                //  菜单选中状态
                if (tid) {
                	var all_cate_dom = $(".top_cate_menu ul").find('.cate_menu');
                	var current_cate_dom = $(".top_cate_menu ul").find('.cate_menu.menu_'+tid);
                	all_cate_dom.css("border-color","transparent");
                	current_cate_dom.css("border-color","#fc3357");
                }
            }
        });
    }

    // 分类商品列表
	function get_list() {
        $(".loading").remove();
        if (!hasmore) {
            return false
        }
        hasmore = false;
        ajaxing = true;
        $.ajax({
            url: ApiUrl+"/index.php?app=ssys_goods&mod=more_goods_list&sld_addons=spreader&t="+tid+"&page="+page+"&pn="+pn+"&ssys_key="+u_k,
            type: 'get',
            dataType: 'json',
            success: function(result) {
                if(result.code!='200'){
                    return;
                }
                var data = result.datas;
                hasmore = result.hasmore;
                data.hasmore = hasmore;

                // if (data.goods.length > 0) {

                    var goods_list = template.render("home_body", data);

                    $(".box").append(goods_list);
                //商品详情
                $(document).find('.border_2').find('dt,dd').not('.shareinfo').on('click',function(){
                    var $this = $(this).parent('.border_2').find('.share');
                    var id = $this.data('gid');
                    var amount = $this.data('sharemoney');
                    location.href = 'cwap_product_detail.html?gid='+id+'&amount='+amount;
                });
                    // 分享 弹窗
                    $(".share").on("click",function(e){
                        var the_obj = $(this);
                        share_tips_show(the_obj);
                        $(".cancel").on("click",function(){
                            share_tips_close();
                        })
                    });

                // }

                if(hasmore){
                    pn++;
                }
            }
        });
    }

	function change_page(tid) {
        pn = 1;
        hasmore =true;
        $(".box").html('');
        get_list(tid);
    }
