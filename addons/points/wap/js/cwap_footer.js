$(function (){
	if(typeof(wap_now) == 'undefined'){wap_now = "sld_home";}
	var tmpl2 = '<div id="bottom">'
		+'<div style=""><div id="nav-tab"><div class="clearfix tab-line nav"><div class="tab-line-item" style="width:25%;" >';
		if(wap_now == "sld_home"){
			tmpl2+='<a class="sle" href="index.html"><span class="wap_footer_img"><i class="iconfontali icon-home"></i></span><br>首页</a>';
		}else{
			tmpl2+='<a href="index.html"><span class="wap_footer_img"><i class="iconfontali icon-home"></i></span><br>首页</a>';
		}
	tmpl2+='</div>'
		+'<div class="tab-line-item" style="width:25%;position: relative;" >';
	if(wap_now == "sld_cart"){
		tmpl2+='<a class="sle" href="cwap_shopping_cart.html"><span class="wap_footer_img"><i class="iconfontali icon-icon-"></i></span><br>购物车</a>';
	}else{
		tmpl2+='<a href="cwap_shopping_cart.html"><span class="wap_footer_img"><i class="iconfontali icon-icon-"></i></span><br>购物车</a>';
	}
		tmpl2+='</div>'
		+'<div class="tab-line-item" style="width:25%;" >';
		if(wap_now == "sld_faxian"){
			tmpl2+='<a class="sle" href="cwap_my_order.html"><span class="wap_footer_img"><i class="iconfontali icon-icon-2"></i></span><br><span class="mid-title">我的订单<span></a>';
		}else{
			tmpl2+='<a href="cwap_my_order.html"><span class="wap_footer_img"><i class="iconfontali icon-icon-2"></i></span><br><span class="mid-title">我的订单</span></a>';
		}
		tmpl2+='</div>'
		+'<div class="tab-line-item" style="width:25%;" >';
		if(wap_now == "sld_mine"){
			tmpl2+='<a class="sle" href="cwap_integral_center.html"><span class="wap_footer_img"><i class="iconfontali icon-icon-1"></i></span><br>我的</a>';
		}else{
			tmpl2+='<a href="cwap_integral_center.html"><span class="wap_footer_img"><i class="iconfontali icon-icon-1"></i></span><br>我的</a>';
		}
		tmpl2+='</div></div></div></div><div style="z-index: 10000; border-radius: 3px; position: fixed; background: none repeat scroll 0% 0% rgb(255, 255, 255); display: none;" id="myAlert" class="modal hide fade"><div style="text-align: center;padding: 15px 0 0;" class="title"></div><div style="min-height: 40px;padding: 15px;" class="modal-body"></div><div style="padding:3px;height: 35px;line-height: 35px;" class="alert-footer"><a style="padding-top: 4px;border-top: 1px solid #ddd;display: block;float: left;width: 50%;text-align: center;border-right: 1px solid #ddd;margin-right: -1px;" class="confirm" href="javascript:;">Save changes</a><a aria-hidden="true" data-dismiss="modal" class="cancel" style="padding-top: 4px;border-top: 1px solid #ddd;display: block;float: left;width: 50%;text-align: center;" href="javascript:;">关闭</a></div></div><div style="display:none;" class="tips"><i class="fa fa-info-circle fa-lg"></i><span style="margin-left:5px" class="tips_text"></span></div><div class="bgbg" id="bgbg" style="display: none;"></div></div></div>';
	$("#footer").html(tmpl2);
	$.ajax({
		url: ApiUrl + "/index.php?app=index&mod=botnav_color",
		type: 'get',
		dataType: 'json',
		async:false,
		success: function(result) {
			if(result.code == '200'){
				$('#bottom .nav a.sle i').css('color','#'+result.datas.botnav_color);
				//如果门店关闭 发现换成文章页
                if(result.datas.dian_open==0){
                	$("#nav-tab .tab-line.nav").children('div').eq(2).find('a').attr('href','cwap_found_list.html');
                	$("#nav-tab .tab-line.nav").children('div').eq(2).find('a .mid-title').text('发现');
				}
			}
		}
	});
	//当前页面
	if(wap_now == "1"){
		$(".footnav .icon-shop2").parent().addClass("cur");
	}else if(wap_now == "2"){
		$(".footnav .icon-cate").parent().addClass("cur");
	}else if(wap_now == "3"){
		$(".footnav .icon-sousuo1").parent().addClass("cur");
	}else if(wap_now == "4"){
		$(".footnav .icon-shoppingcart2line").parent().addClass("cur");
	}else if(wap_now == "5"){
		$(".footnav .icon-huiyuan").parent().addClass("cur");
	}

    //回到顶部
    $(".gotop").click(function (){
        $(window).scrollTop(0);
    });
    var key = getcookie('key');
	$('#logoutbtn').click(function(){
		var username = getcookie('username');
		var key = getcookie('key');
		var client = 'wap';
		$.ajax({
			type:'get',
			url:ApiUrl+'/index.php?app=logout',
			data:{username:username,key:key,client:client},
			success:function(result){
				if(result){
					delCookie('username');
					delCookie('key');
					location.href = WapSiteUrl+'/cwap_login.html?out=1';
				}
			}
		});
	});
});

$(function() {
	setTimeout(function(){
		if($("#content .container").height()<$(window).height())
		{
			$("#content .container").css("min-height",$(window).height());
		}
	},300);
	setTimeout(function(){$("#bottom .nav .get_down").click();},500);
	$("#scrollUp").click(function(t) {
		$("html, body").scrollTop(300);
		$("html, body").animate( {
			scrollTop : 0
		}, 300);
		t.preventDefault()
	});


	var wqurl= encodeURIComponent(window.location.href.split('#')[0]);
	wqurl= encodeURIComponent(wqurl);
	//微信相关
    if (window.navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == 'micromessenger')
    {
        $.ajax({
            url: ApiUrl + "/index.php?app=jssdk&mod=getJssdkConfig&url=" + wqurl,
            type: 'get',
            dataType: 'json',
            success: function (result) {
                if (result.code == '200') {
                    jQuery.getScript("http://res.wx.qq.com/open/js/jweixin-1.2.0.js", function () {
                        /*
                         做一些加载完成后需要执行的事情
                        */
                        wx.config({
                            debug: false,
                            appId: result.datas.config.appId,
                            timestamp: result.datas.config.timestamp,
                            nonceStr: result.datas.config.nonceStr,
                            signature: result.datas.config.signature,
                            jsApiList: [
                                'checkJsApi',
                                'onMenuShareTimeline',
                                'onMenuShareAppMessage',
                                'onMenuShareQQ',
                                'onMenuShareWeibo',
                                'hideMenuItems',
                                'showMenuItems',
                                'hideAllNonBaseMenuItem',
                                'showAllNonBaseMenuItem',
                                'translateVoice',
                                'startRecord',
                                'stopRecord',
                                'onRecordEnd',
                                'playVoice',
                                'pauseVoice',
                                'stopVoice',
                                'uploadVoice',
                                'downloadVoice',
                                'chooseImage',
                                'previewImage',
                                'uploadImage',
                                'downloadImage',
                                'getNetworkType',
                                'openLocation',
                                'getLocation',
                                'hideOptionMenu',
                                'showOptionMenu',
                                'closeWindow',
                                'scanQRCode',
                                'chooseWXPay',
                                'openProductSpecificView',
                                'addCard',
                                'chooseCard',
                                'openCard'
                            ]
                        });
                        wx.ready(function () {
                            wx_ready();
                        });
                    });
                }
            }
        });
    }

});

_sld_stats.uid = localStorage.getItem('key')?localStorage.getItem('key'):0;
_sld_stats.gid = GetQueryString('gid')?GetQueryString('gid'):0;
(function() {
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.defer=true; g.src=SiteUrl+'/static/js/stats.js'; s.parentNode.insertBefore(g,s);
})();

