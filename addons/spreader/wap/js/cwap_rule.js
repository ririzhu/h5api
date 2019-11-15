$(function(){
	wap_now = 'sld_mine';
	//从浏览器里获取key或者从缓存里获取key
	if (GetQueryString("ssys_key")) {
		var key = GetQueryString("ssys_key");
		//并把key存缓存
		addcookie('ssys_key',key);
	} else {
		var key = getcookie("ssys_key");
	}

    function load_rule(){
	    $.ajax({
	        type:'get',
	        url:ApiUrl+"/index.php?app=index&mod=rule_page&sld_addons=spreader",
	        dataType:'json',
	        success:function(e){

	        	$(".loading").remove();

	        	if (e.code = 200) {
	                data = e.datas;
	                if (data.status == 1) {
			            $(".rule_content").html(data.rule_content);
	                }else{
			    		window.location.href='/cwap_my_invitation.html';
			    	}
	
	        	}

	        }
	    });
    }

    if (key) {
		load_rule();
    }else{
		window.location.href = WapSiteUrl + "/cwap_the_login.html";
		return false;
    }

});