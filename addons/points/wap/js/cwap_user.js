$(function(){
		wap_now = 'sld_mine';
		//从浏览器里获取key或者从缓存里获取key
		if (GetQueryString("key")) {
			var key = GetQueryString("key")
			//并把key存缓存
			addcookie('key',key);
		} else {
			var key = getcookie("key")
		}
		if(key){
			// 未读消息条数
			getNoReadMsgCount();
			
			//判断分销是否开启
            $.ajax({
                type:'post',
                url:ApiUrl+"/index.php?app=index&mod=moudleStatus",
                data:{key:key},
                dataType:'json',
                success:function(result){
					console.info(result);
					if(result.state == 200){
						if(result.data.distribution){
                            $('.fenxiaopart').show();
						}
					}
				}
            })
			$.ajax({
				type:'post',
				url:ApiUrl+"/index.php?app=usercenter&mod=memberInfo",
				data:{key:key},
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
					member_info += '<div class="member-card '+card_class+'">';
					member_info += '<div class="member-card-info">';
					member_info += '<div class="member-base-info">';
					member_info += '<div class="user-avatar">';
					member_info += '<img src="'+avator+'">';
					member_info += '</div>';

					member_info += '<div class="user-name">';
					member_info += result.datas.member_info.user_name;
					member_info += '</div>';
					member_info += '<div class="user-level">';
					member_info += '<img src="./images/lv_'+result.datas.member_info.grade_id+'.png">';
					member_info += '</div>';

					member_info += '</div>';

					member_info += '<div class="member-action">';
					member_info += '<a href="cwap_favorites.html">';
					member_info += '我的收藏';
					member_info += '<span>';
					member_info += result.datas.member_info.goodsNum*1 + result.datas.member_info.storeNum*1;
					member_info += '</span>';
					member_info += '</a>';
					member_info += '<span class="middle-flag">|</span>';
					member_info += '<a href="cwap_views_list.html">';
					member_info += '我的足迹';
					member_info += '<span>';
					member_info += result.datas.member_info.BrowseHistoryNum;
					member_info += '</span>';
					member_info += '</a>';
					member_info += '</div>';
					member_info += '</div>';

					$(".member-top").html(member_info);
					// 用户余额
					var member_money = '';
					member_money += '<div class="my_money-item">';
					member_money += '<div class="total-money">';
					member_money += '<span class="money-num">'+result.datas.member_info.total_predeposit+'</span>';
					member_money += '<span>元</span>';
					member_money += '</div>';
					member_money += '<div class="bottom-field">';
					member_money += '总余额';
					member_money += '</div>';
					member_money += '</div>';
					member_money += '<div class="my_money-item">';
					member_money += '<div class="normal-money">';
					member_money += '<span class="money-num">'+result.datas.member_info.available_predeposit+'</span>';
					member_money += '<span>元</span>';
					member_money += '</div>';
					member_money += '<div class="bottom-field">';
					member_money += '可用余额';
					member_money += '</div>';
					member_money += '</div>';
					member_money += '<div class="my_money-item">';
					member_money += '<div class="normal-money">';
					member_money += '<span class="money-num">'+result.datas.member_info.freeze_predeposit+'</span>';
					member_money += '<span>元</span>';
					member_money += '</div>';
					member_money += '<div class="bottom-field">';
					member_money += '冻结金额';
					member_money += '</div>';
					member_money += '</div>';
					$("#my_money_ul").html(member_money);


					var e = '<li><a href="cwap_order_list.html?data-state=1">' + (result.datas.member_info.order_nopay_count > 0 ? "<em></em>": "") + '<i class="cc-01"></i><p>待付款</p><em style="display: inline;">'+(result.datas.member_info.dai_fu)+'</em></a></li>' + '<li><a href="cwap_order_list.html?data-state=256"><i class="cc-03">' + (result.datas.member_info.order_nopay_count > 0 ? "<em></em>": "") + '</i><p>待发货</p><em style="display: inline;">'+(result.datas.member_info.dai_fahuo)+'</em></a></li>' + '<li>' +  '<a href="cwap_order_list.html?data-state=1024">' + (result.datas.member_info.order_noreceipt_count > 0 ? "<em></em>": "") + '<i class="cc-02"></i><p>待收货</p><em style="display: inline;">'+(result.datas.member_info.dai_send)+'</em></a></li>'  + '<li><a href="cwap_order_list.html?data-state=nocomment">' + (result.datas.member_info.order_noeval_count > 0 ? "<em></em>": "") + '<i class="cc-04"></i><p>待评价</p><em style="display: inline;">'+(result.datas.member_info.dai_ping)+'</em></a></li>' + '<li><a href="cwap_user_refund.html">' + (result.datas.member_info.
							return > 0 ? "<em></em>": "") + '<i class="cc-05"></i><p>退款/退货</p><em style="display: inline;">'+(result.datas.member_info.refund_count)+'</em></a></li>';
					$("#order_ul").html(e);
					var e = '<li><a href="cwap_tuiguang.html"><i class="cc-tg001"></i><p>我要推广</p></a></li><li>'+ '<a href="cwap_dis_income.html"><i class="cc-tg002"></i><p>我的团队</p></a></li>'+ '<li><a href="cwap_disincome_detail.html"><i class="cc-tg003"></i><p>分销收入</p></a></li>';
					$("#asset_ul").html(e);
					var msu = '';

					msu += '<li><a href="cwap_useryue.html"><i class="my-s-01"></i><p>预存款</p><p class="asset_ul_li_sub_t"></p></a></li>';
					msu += '<li><a href="red_list.html"><i class="my-s-05"></i><p>优惠券</p><p class="asset_ul_li_sub_t"></p></a></li>';
					if(result.datas.member_info.if_pin){
						msu += '<li><a href="pin_list.html"><i class="my-s-03"></i><p>拼团</p><p class="asset_ul_li_sub_t"></p></a></li>';
					}
					msu += '<li><a href="cwap_pointslog_list.html"><i class="my-s-04"></i><p>积分</p><p class="asset_ul_li_sub_t"></p></a></li>';
					msu += '<li><a href="cwap_address_list.html"><i class="my-s-06"></i><p>地址</p><p class="asset_ul_li_sub_t"></p></a></li>';
					msu += '<li><a href="cwap_user_points.html"><i class="my-s-02"></i><p>签到</p><p class="asset_ul_li_sub_t"></p></a></li>';
					msu += '<li><a href="./cwap_help_center.html"><i class="my-s-08"></i><p>帮助</p><p class="asset_ul_li_sub_t"></p></a></li>';
					msu += '<li class="complain"><a href="javascript:void(0);"><i class="my-s-07"></i><p>投诉</p><p class="asset_ul_li_sub_t"></p></a></li>';
					$("#my_service_ul").html(msu);

					extend_member_build(result,key);
					return false;
				}
			});
			// 获取平台客服电话
			$.ajax({
				type:'post',
				url:ApiUrl+"/index.php?app=index&mod=get_site_phone",
				data:{key:key},
				dataType:'json',
				success:function(result){
					if (result.datas.site_phone) {
						// 400 电话拨打
						$("body").on("click",".complain",function(e){
		                    Zepto.sDialog({
		                        skin: "block",
		                        content: "确认要投诉么？",
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
			window.location.href = WapSiteUrl + "/cwap_login.html";
			return false;
		}
	// $.scrollTransparent()
});