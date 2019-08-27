$(function(){
		wap_now = 'sld_mine';
		//从浏览器里获取key或者从缓存里获取key
		if (GetQueryString("ssys_key") || GetQueryString("ssys_share_code")) {
			var key = GetQueryString("ssys_key");
			var share_code = GetQueryString("ssys_share_code");
			//并把key存缓存
			addcookie('ssys_key',key);
			addcookie('ssys_share_code',share_code);
		} else {
			var key = getcookie("ssys_key");
			var share_code = getcookie("ssys_share_code");
		}
		if(key){
			$.ajax({
				type:'post',
				url:ApiUrl+"/index.php?app=usercenter&mod=memberInfo&sld_addons=spreader",
				data:{ssys_key:key},
				dataType:'json',
				success:function(result){
					checklogin(result.login);

					var avator = '';
					if(result.datas.member_info.avator){
						avator = result.datas.member_info.avator;
					}else{
						avator = "./images/usercenter/def_user_avatar.png";
					}

					var card_class = 'normal-member';

					var member_info = '';
					member_info += '<a href="cwap_user_account.html">';
					member_info += '<dt>';
					member_info += '<img src="'+avator+'" alt="">';
					member_info += '</dt>';
					member_info += '<dd>'+result.datas.member_info.user_name+' <em>&gt;</em></dd>';
					member_info += '</a>';

					$(".touxiang").html(member_info);
					// 用户余额
					var member_money = '';
					member_money += '<ul id="user_amount">';
					member_money += '<li><span data-t="withdrawal">可提现金额</span>|<span data-t="frozen">冻结金额</span>|<span  data-t="failure">失效金额</span></li>';
					member_money += '<li><span data-t="withdrawal">'+result.datas.member_info.available_yongjin+'</span><span data-t="frozen">'+result.datas.member_info.freeze_yongjin+'</span><span data-t="failure">'+result.datas.member_info.disable_yongjin+'</span></li>';
					member_money += '</ul>';
					$(".tixian").html(member_money);

					var msu = '';

					msu += '<ul>';
					msu += '<li><a href="cwap_my_invitation.html">我的邀请<em>&gt;</em></a></li>';
					msu += '<li><a href="cwap_the_order_details.html">订单明细<em>&gt;</em></a></li>';
					msu += '<li><a href="cwap_help_center.html">常见问题<em>&gt;</em></a></li>';
					msu += '<li class="share"><a class="complain" href="javascript:;">联系客服<em>&gt;</em></a></li>';
					msu += '</ul>';
					$(".list").html(msu);

					$("#user_amount li span").on("click",$("#user_amount"),function(e){
						var l_href="";
						var l_t = $(this).data('t');
						switch(l_t){
							case 'withdrawal':
									l_href="cwap_withdrawal.html";
								break;
							case 'frozen':
									l_href="cwap_failure_amount.html";
								break;
							case 'failure':
									l_href="cwap_frozen_details.html";
								break;
						}
						if (l_href) {
							window.location.href= l_href;
						}
					});
					return false;
				}
			});

			// 获取客服电话
			$.ajax({
				type:'post',
				url:ApiUrl+"/index.php?app=index&mod=get_site_phone&sld_addons=spreader",
				data:{key:key},
				dataType:'json',
				success:function(result){
					if (result.datas.site_phone) {
						// 400 电话拨打
						$("body").on("click",".complain",function(e){
		                    Zepto.sDialog({
		                        skin: "block",
		                        content: "确认要联系客服么？",
		                        okFn: function() {
									var tel = result.datas.site_phone;
									window.location.href="tel://"+tel;
		                        },
		                        cancelFn: function() {
		                        }
		                    });
						});
					}
				}
			});
		}else{
			window.location.href = WapSiteUrl + "/cwap_the_login.html";
			return false;
		}
});