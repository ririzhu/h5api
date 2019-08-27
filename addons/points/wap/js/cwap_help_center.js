$(function() {
    var t = GetQueryString("i");
    var c_n = GetQueryString("n");

    function load_article_list(t){
	    $.ajax({
	        type:'get',
	        url:ApiUrl+"/index.php?app=index&mod=article_list&acid="+t,
	        dataType:'json',
	        success:function(e){

	        	$(".loading").remove();

	        	if (e.code = 200) {
	                data = e.datas;

		            var r = template.render("article_list", data);
		            $(".bbctouch-main-layout .article-list").html(r);
	
	        	}

	        }
	    });
    }

    function load_article_info(t){
    	if (t) {
		    $.ajax({
		        type:'get',
		        url:ApiUrl+"/index.php?app=index&mod=article_detail&id="+t,
		        dataType:'json',
		        success:function(e){

		        	$(".loading").remove();

		        	if (e.code = 200) {
		                data = e.datas;
		                if (data.status == 1) {
			                $(".header-title h1").text(data.article_detail.article_title);
				            $(".bbctouch-main-layout .article-info").html(data.article_detail.article_content);
		                }else{
				    		window.location.href='/cwap_help_center.html';
				    	}
		
		        	}

		        }
		    });
    	}else{
    		window.location.href='/';
    	}
    }

    function load_article_cate(){
	    $.ajax({
	        type:'get',
	        url:ApiUrl+"/index.php?app=index&mod=article_help",
	        dataType:'json',
	        success:function(e){

	        	$(".loading").remove();

	        	if (e.code = 200) {
	                data = e.datas;

		            var r = template.render("article_cate", data);
		            $(".bbctouch-main-layout .article-cate").html(r);
	
	        	}

	        }
	    });
    }

    if ($(".article-info").size()) {
		load_article_info(t);
	}else if($(".article-cate").size()){
		load_article_cate();
    }else{
    	$(".header-wrap").find(".header-title h1").text(c_n);
	    // 请求列表
	    load_article_list(t);
    	
    }

});