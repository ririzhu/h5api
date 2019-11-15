$(function(){
    var app = GetQueryString("app");
    var tmpl = '<div class="footer">'
        +'<div class="footer-top">'
            +'<div class="footer-tleft">'+'</div>'
            +'<a href="javascript:void(0);"class="gotop">'
                +'<span class="gotop-icon"></span>'
                +'<p>回顶部</p>'
            +'</a>'
        +'</div>'
        +'<div class="footer-content">'
            +'<p class="link">'
		+'<a href="'+AndroidSiteUrl+'" class="standard">下载Android客户端</a>'
		+'<a href="https://itunes.apple.com/cn/app/wu-tong-hui/id1051351520?l=en&mt=8">下载IOS客户端</a>'
            +'</p>'
        +'</div>'
    +'</div>';
	var render = template.compile(tmpl);
	var html = render();
	$("#footer").html(html);
    //回到顶部
    $(".gotop").click(function (){
        $(window).scrollTop(0);
    });
	//登录背景铺满
	var full_height = $(window).height();
	$('.login_full_bg').css('height','full_height');
	
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
	$('#loginbtn').click(function(){//绑定账号
		var username = $('#username').val();
		var pwd = $('#userpwd').val();
		var client = 'wap';
		if($.sValid()){
	          $.ajax({
				type:'post',
				url:ApiUrl+"/index.php?app=login&mod=gzh_bind_account&sld_addons=spreader",
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
							location.href = './cwap_user.html';
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