$(function(){
	wap_now = 'sld_mine';
	//从浏览器里获取key或者从缓存里获取key
	if (GetQueryString("ssys_key") || GetQueryString("ssys_share_code")) {
		var key = GetQueryString("ssys_key");
		var share_code = GetQueryString("ssys_share_code");
		//并把key存缓存
		addcookie('ssys_share_code',share_code);
	} else {
		var key = getcookie("ssys_key");
		var share_code = getcookie("ssys_share_code");
	}
//检测权限接口,是否可以进入推手系统
	check_Jurisdiction(key);
	if(key){
		// 获取 用户下级 列表
		$.ajax({
			type:'post',
			url:ApiUrl+"/index.php?app=usercenter&mod=memberSubordinates&sld_addons=spreader",
			data:{ssys_key:key},
			dataType:'json',
			success:function(result){
				checklogin(result.login);

                var t = result;
                var r = template.render("member-list-tmpl", t);
                $("#member-list").html(r);
			}
		});
	}else{
		window.location.href = WapSiteUrl + "/cwap_the_login.html";
		return false;
	}
})