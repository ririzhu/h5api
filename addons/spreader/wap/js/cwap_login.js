$(function() {
    // if (!GetQueryString('out')) {
        //如果是微信浏览器，则运用授权（显式授权），获取code
        if (window.navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == 'micromessenger') {
            var uricode = encodeURIComponent(ApiUrl + '/index.php?app=login&mod=gzh_login&sld_addons=spreader&sld_back_url=' + location.href);
            window.location.href = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' + gzh_appid + '&redirect_uri=' + uricode + '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        }
    // }
	//获取logo
	getSldLogoUrl();

    var key = getcookie('ssys_key');
	$('#logoutbtn').click(function(){
		var username = getcookie('ssys_username');
		var key = getcookie('ssys_key');
		var client = 'wap';
		$.ajax({
			type:'post',
			url:ApiUrl+'/index.php?app=logout&sld_addons=spreader',
			data:{username:username,ssys_key:key,client:client},
			success:function(result){
				if(result){
					delCookie('ssys_username');
					delCookie('ssys_key');
					delCookie('ssys_username');
					location.href = WapSiteUrl+'/cwap_the_login.html?out=1';
				}
			}
		});
	});
	
	var referurl = document.referrer;//上级网址
	$("input[name=referurl]").val(referurl);
	Zepto.sValid.init({
        rules:{
            username:"required",
            userpwd:"required"
        },
        messages:{
            username:"用户名必须填写！",
            userpwd:"密码必填!"
        },
        callback:function (eId,eMsg,eRules){
            if(eId.length >0){
                var errorHtml = "";
                $.map(eMsg,function (idx,item){
                    errorHtml += "<p>"+idx+"</p>";
                });

				errorTipsShow(errorHtml);
            }else{
				errorTipsHide();
            }
        }  
    });
    //上级网址
    var referurl = document.referrer;
	$('#loginbtn').click(function(){//会员登陆
		var username = $('#username').val();
		var pwd = $('#userpwd').val();
		var client = 'wap';
		if($.sValid()){
	          $.ajax({
				type:'post',
				url:ApiUrl+"/index.php?app=login&mod=index&sld_addons=spreader",
				data:{username:username,password:pwd,client:'wap'},
				dataType:'json',
				success:function(result){
					if(!result.datas.error){
						if(typeof(result.datas.key)=='undefined'){
							return false;
						}else{
							addcookie('ssys_username',result.datas.username);
							addcookie('ssys_key',result.datas.key);
							addcookie('ssys_share_code',result.datas.share_code);
                            if(referurl=WapSiteUrl+'/cwap_forgot_password.html'){
                                location.href = './cwap_user.html';
							}else{
                                location.href = referurl;
                            }

						}
						errorTipsHide();
					}else{
						errorTipsShow(result.datas.error);
					}
				}
			 });  
        }
	});
});