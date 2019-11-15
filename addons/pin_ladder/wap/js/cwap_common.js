var wx_ready = function () {

}
var _sld_stats = _sld_stats || [];

function GetQueryString(name){
	var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
	var r = window.location.search.substr(1).match(reg);
	if (r!=null) return decodeURIComponent(r[2]); return "";
}
//cookie修改为localstorage
function addcookie(name,value,expireHours){
	window.localStorage.setItem( name, value );
}

function getcookie(name){
	var value = window.localStorage.getItem(name);
	if(value){
		return value;
	}else{
		return ""
	}
}


function delCookie(name){//删除cookie
	window.localStorage.removeItem(name);
}

function delHisCookie(name){//删除历史记录cookie
	var exp = new Date();
	exp.setTime(exp.getTime() - 1);
	name = cookie_pre + name;
	var cval=getcookie(name);
	if(cval!=null) document.cookie= name + "="+cval+"; path=/;expires="+exp.toGMTString();
}


function checklogin(state){
	if(state == 0){
		location.href = WapSiteUrl+'/cwap_login.html';
		return false;
	}else {
		return true;
	}
}

function contains(arr, str) {
    var i = arr.length;
    while (i--) {
           if (arr[i] === str) {
           return true;
           }
    }
    return false;
}
/*头部的返回箭头事件
 *url：需要替换的页面路径，如果need_navigate的缓存为1，需要替换，为0，返回上级页面，如果clear为1，需要情况need_navigate的缓存
 */
function is_back_or_navigate(url,clear) {
	if(localStorage.getItem('need_navigate')==1){
		if(clear == 1){
			localStorage.setItem('need_navigate',0);
		}
        window.location.replace(url);
	}else{
		history.go(-1);
	}
}


//返回logo图片全路径
function getSldLogoUrl() {
	$.ajax({
		type:'post',
		url:ApiUrl+"/index.php?app=login&mod=getSldWapLogo",
		data:{},
		dataType:'json',
		success:function(result){
			var text = '<img  src="'+result.sldwaplogo+'"/>';
			$('.login_logo').html(text);
		}
	});
}
function buildUrl(type, data) {
    switch (type) {
        case 'keyword':
            return WapSiteUrl + '/cwap_product_list.html?keyword=' + encodeURIComponent(data);
        case 'special':
            return WapSiteUrl + '/cwap_subject.html?topic_id=' + data;
        case 'goods':
            return WapSiteUrl + '/cwap_product_detail.html?gid=' + data;
        case 'url':
            return data;
    }
    return WapSiteUrl;
}

$(function(){
	setTimeout(function(){
		if($("#content .container").height()<$(window).height())
		{
			$("#content .container").css("min-height",$(window).height());
		}
	},300);
	$("#bottom .nav .get_down").click(function(){
		$("#bottom .nav").animate({"bottom":"-50px"});
		$("#nav-tab").animate({"bottom":"0px"});
	});
	$("#nav-tab-btn").click(function(){
		$("#bottom .nav").animate({"bottom":"0px"});
		$("#nav-tab").animate({"bottom":"-40px"});
		
	});
	setTimeout(function(){$("#bottom .nav .get_down").click();},500);
	$("#scrollUp").click(function(t) {
		$("html, body").scrollTop(300);
		$("html, body").animate( {
			scrollTop : 0
		}, 300);
		t.preventDefault()
	});
	$("#header_user").on("click", "#header-nav",
		function() {
			if ($(".bbctouch-nav-layout").hasClass("show")) {
				$(".bbctouch-nav-layout").removeClass("show")
			} else {
				$(".bbctouch-nav-layout").addClass("show")
			}
		});
	$("#header_user").on("click", ".bbctouch-nav-layout",
		function() {
			$(".bbctouch-nav-layout").removeClass("show")
		});
	$(document).scroll(function() {
		$(".bbctouch-nav-layout").removeClass("show")
	});

	$(".input-del").click(function() {
		$(this).parent().removeClass("write").find("input").val("");
		btnCheck($(this).parents("form"))
	});
	$("body").on("click", "label",
		function() {
			if ($(this).has('input[type="radio"]').length > 0) {
				$(this).addClass("checked").siblings().removeClass('checked').find('input[type="radio"]').removeAttr("checked")
			} else if ($(this).has('[type="checkbox"]')) {
				if ($(this).find('input[type="checkbox"]').prop("checked")) {
					$(this).addClass("checked")
					$("#wrapperPaymentPassword").show();
				} else {
					$(this).removeClass("checked");
					$("#wrapperPaymentPassword").hide();
				}
			}
		});
	if ($("body").hasClass("scroller-body")) {
		new IScroll(".scroller-body", {
			mouseWheel: true,
			click: true
		})
	}
	$(document).scroll(function() {
		e()
	});
	$(".fix-block-r,footer").on("click", ".gotop",
		function() {
			btn = $(this)[0];
			this.timer = setInterval(function() {
					$(window).scrollTop(Math.floor($(window).scrollTop() * .8));
					if ($(window).scrollTop() == 0) clearInterval(btn.timer, e)
				},
				10)
		});
	function e() {
		$(window).scrollTop() == 0 ? $("#goTopBtn").addClass("hide") : $("#goTopBtn").removeClass("hide")
	}
});
function writeClear(e) {
	if (e.val().length > 0) {
		e.parent().addClass("write")
	} else {
		e.parent().removeClass("write")
	}
	btnCheck(e.parents("form"))
}
function btnCheck(e) {
	var t = true;
	e.find("input").each(function() {
		if ($(this).hasClass("no-follow")) {
			return
		}
		if ($(this).val().length == 0) {
			t = false
		}
	});
	if (t) {
		e.find(".btn").parent().addClass("ok")
	} else {
		e.find(".btn").parent().removeClass("ok")
	}
}
function getCartCount(e, t) {
	var a = 0;
	delCookie("cart_count")
	if (getcookie("key") !== null&&getcookie("key")!="" &&(getcookie("cart_count") == ''||getcookie("cart_count") == null)) {
		var e = getcookie("key");
		$.ajax({
			type: "post",
			url: ApiUrl + "/index.php?app=cart&mod=cart_list",
			data: {
				key: e
			},
			dataType: "json",
			async: false,
			success: function(e) {

					addcookie("cart_count", e.datas.cart_list.length, t);
					a = e.datas.cart_list.length;
			}
		})
	} else {
		a = getcookie("cart_count")
	}
	if (a > 0 && $(".bbctouch-nav-menu").has(".cart").length > 0) {
		$(".bbctouch-nav-menu").has(".cart").find(".cart").parents("li").find("sup").show();
		$("#header-nav").find("sup").show()
	}
}
function getChatCount() {
	if ($("#header").find(".message").length > 0) {
		var e = getcookie("key");
		if (e !== null) {
			$.getJSON(ApiUrl + "/index.php?app=chat&mod=get_msg_count", {
					key: e
				},
				function(e) {
					if (e.datas > 0) {
						$("#header").find(".message").parent().find("sup").show();
						$("#header-nav").find("sup").show()
					}
				})
		}
		$("#header").find(".message").parent().click(function() {
			window.location.href = WapSiteUrl + "/cwap_chat_list.html"
		})
	}
}
function errorTipsShow(e) {
	$(".error-tips").html(e).show();
	setTimeout(function() {
			errorTipsHide()
		},
		3e3)
}
function errorTipsHide() {
	$(".error-tips").html("").hide()
}
function loadCss(e) {
	var t = document.createElement("link");
	t.setAttribute("type", "text/css");
	t.setAttribute("href", e);
	t.setAttribute("href", e);
	t.setAttribute("rel", "stylesheet");
	css_id = document.getElementById("auto_css_id");
	if (css_id) {
		document.getElementsByTagName("head")[0].removeChild(css_id)
	}
	document.getElementsByTagName("head")[0].appendChild(t)
}
function loadJs(e) {
	var t = document.createElement("script");
	t.setAttribute("type", "text/javascript");
	t.setAttribute("src", e);
	t.setAttribute("id", "auto_script_id");
	script_id = document.getElementById("auto_script_id");
	if (script_id) {
		document.getElementsByTagName("head")[0].removeChild(script_id)
	}
	document.getElementsByTagName("head")[0].appendChild(t)
}
function favoriteGoods(e) {
	var t = getcookie("key");
	if (!t) {
		checklogin(0);
		return
	}
	if (e <= 0) {
		$.sDialog({
			skin: "green",
			content: "参数错误",
			okBtn: false,
			cancelBtn: false
		});
		return false
	}
	var a = false;
	$.ajax({
		type: "post",
		url: ApiUrl + "/index.php?app=userfollow&mod=favorites_add",
		data: {
			key: t,
			gid: e
		},
		dataType: "json",
		async: false,
		success: function(e) {
			if (e.datas == '1') {
				a = true
			} else {
				$.sDialog({
					skin: "red",
					content: e.datas.error,
					okBtn: false,
					cancelBtn: false
				})
			}
		}
	});
	return a
}
function dropFavoriteGoods(e) {
	var t = getcookie("key");
	if (!t) {
		checklogin(0);
		return
	}
	if (e <= 0) {
		$.sDialog({
			skin: "green",
			content: "参数错误",
			okBtn: false,
			cancelBtn: false
		});
		return false
	}
	var a = false;
	$.ajax({
		type: "post",
		url: ApiUrl + "/index.php?app=userfollow&mod=favorites_del",
		data: {
			key: t,
			fav_id: e
		},
		dataType: "json",
		async: false,
		success: function(e) {
			if (e.code == 200) {
				a = true
			} else {
				$.sDialog({
					skin: "red",
					content: e.datas.error,
					okBtn: false,
					cancelBtn: false
				})
			}
		}
	});
	return a
}
function favoriteStore(e) {
	var t = getcookie("key");
	if (!t) {
		checklogin(0);
		return
	}
	if (e <= 0) {
		$.sDialog({
			skin: "green",
			content: "参数错误",
			okBtn: false,
			cancelBtn: false
		});
		return false
	}
	var a = false;
	$.ajax({
		type: "post",
		url: ApiUrl + "/index.php?app=vendorfollow&mod=fadd",
		data: {
			key: t,
			vid: e
		},
		dataType: "json",
		async: false,
		success: function(e) {
			if (e.code == 200) {
				a = true
			} else {
				$.sDialog({
					skin: "red",
					content: e.datas.error,
					okBtn: false,
					cancelBtn: false
				})
			}
		}
	});
	return a
}
function dropFavoriteStore(e) {
	var t = getcookie("key");
	if (!t) {
		checklogin(0);
		return
	}
	if (e <= 0) {
		$.sDialog({
			skin: "green",
			content: "参数错误",
			okBtn: false,
			cancelBtn: false
		});
		return false
	}
	var a = false;
	$.ajax({
		type: "post",
		url: ApiUrl + "/index.php?app=vendorfollow&mod=fdel",
		data: {
			key: t,
			vid: e
		},
		dataType: "json",
		async: false,
		success: function(e) {
			if (e.code == 200) {
				a = true
			} else {
				$.sDialog({
					skin: "red",
					content: e.datas.error,
					okBtn: false,
					cancelBtn: false
				})
			}
		}
	});
	return a
}
$.fn.ajaxUploadImage = function(e) {
	var t = {
		url: "",
		data: {},
		start: function() {},
		success: function() {}
	};
	var e = $.extend({},
		t, e);
	var a;
	function n() {
		if (a === null || a === undefined) {
			alert("请选择您要上传的文件！");
			return false
		}
		return true
	}
	return this.each(function() {
		$(this).on("change",
			function() {
				var t = $(this);
				e.start.call("start", t);
				a = t.prop("files")[0];
				if (!n) return false;
				try {
					var r = new XMLHttpRequest;
					r.open("post", e.url, true);
					r.setRequestHeader("X-Requested-With", "XMLHttpRequest");
					r.onreadystatechange = function() {
						if (r.readyState == 4) {
							returnDate = $.parseJSON(r.responseText);
							e.success.call("success", t, returnDate)
						}
					};
					var i = new FormData;
					for (k in e.data) {
						i.append(k, e.data[k])
					}
					i.append(t.attr("name"), a);
					result = r.send(i)
				} catch(o) {
					alert(o)
				}
			})
	})
};

// 获取 用户 当前 未读消息条数
function getNoReadMsgCount() {
    var t = getcookie("key");
    $.ajax({
        type: 'get',
        url: ApiUrl + "/index.php?app=usercenter&mod=receivedSystemNewNum",
        dataType: 'json',
        data: {
            key: t
        },
        async: false,
        success: function (e) {
            if (e.code == 200) {
                var data = e.datas
                if (data.status == 1) {
                    var msg_num = data.countnum * 1;
                    if (msg_num > 9) {
                        msg_num = '9+';
                    }

                    $(".right-top-msg").find('em').show();
                    $(".right-top-msg").find('em').text(msg_num);
                } else {
                    $(".right-top-msg").find('em').hide();
                    // $(".right-top-msg").find('em').remove();
                }
            }
        }
    });
}
function count(obj) {  
    var t = typeof obj;  
    if (t == 'string') {  
        return obj.length;  
    } else if (t == 'object') {  
        var n = 0;  
        for (var i in obj) {  
            n++;  
        }  
        return n;  
    }  
    return false;  
}  


// 获取当前位置
function get_location_position(){

	map = new AMap.Map('iCenter');

	AMap.service('AMap.Geocoder',function(){//回调函数
	    //实例化Geocoder
	    geocoder = new AMap.Geocoder({
	        city: "010"//城市，默认：“全国”
	    });
	});

    var lnglatXY = [];

    if (window.navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == 'micromessenger' && typeof jQuery !='undefined')
    {  
    	//微信
	    wx_ready=function () {
	        wx.getLocation({
	            complete:function (res) {
	                if(res.errMsg) {
	                    var gps = [];
	                    lng = res.longitude;
	                    lat = res.latitude;
	                    gps.push(lng);
	                    gps.push(lat);

	                    // 转为 高德坐标
	                    AMap.convertFrom(gps, 'gps', function (status, result) {
	                        // console.log(result);
	                      if (result.info === 'ok') {
	                        lnglatXY.push(result.locations[0].lng);
	                        lnglatXY.push(result.locations[0].lat);
	                        get_address();
	                      }
	                    });
	                }
	            }
	        });
	    }

	}else{

	    map.plugin('AMap.Geolocation', function () {
		    geolocation = new AMap.Geolocation({
		        enableHighAccuracy: true,//是否使用高精度定位，默认:true
		        timeout: 10000,          //超过10秒后停止定位，默认：无穷大
		        maximumAge: 0,           //定位结果缓存0毫秒，默认：0
		        convert: true,           //自动偏移坐标，偏移后的坐标为高德坐标，默认：true
		        showButton: true,        //显示定位按钮，默认：true
		        buttonPosition: 'LB',    //定位按钮停靠位置，默认：'LB'，左下角
		        buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
		        showMarker: true,        //定位成功后在定位到的位置显示点标记，默认：true
		        showCircle: true,        //定位成功后用圆圈表示定位精度范围，默认：true
		        panToLocation: true,     //定位成功后将定位到的位置作为地图中心点，默认：true
		        zoomToAccuracy:true      //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
		    });
		    geolocation.getCurrentPosition();
		    AMap.event.addListener(geolocation, 'complete', onComplete);//返回定位信息
		    AMap.event.addListener(geolocation, 'error', onError);      //返回定位出错信息
		});

	    //解析定位结果
	    function onComplete(data) {
	        lnglatXY.push(data.position.getLng());
	        lnglatXY.push(data.position.getLat());
	        get_address();
	    }

	    //解析定位错误信息
	    function onError(data) {
	    	// 测试定位数据
	    }
	}

	function get_address(){
	    //TODO: 使用geocoder 对象完成相关功能
	    geocoder.getAddress(lnglatXY, function(status, result) {
		    if (status === 'complete' && result.info === 'OK') {
		       //获得了有效的地址信息:
		       //即，result.regeocode.formattedAddress
		       var city_info = {};
		       city_info.province = result.regeocode.addressComponent.province;
		       city_info.district = result.regeocode.addressComponent.district;

		       save_city_site_bind_id(city_info);
		    }else{
		       //获取地址失败
		    }
		});  
	}

    // 根据省 市 区 定位信息 验证 是否有 当前分站
    function save_city_site_bind_id(city_info){
    	if (!getcookie('city_site_area_name')) {
	    	$.ajax({
		        type: 'get',
		        url: ApiUrl + "/index.php?app=index&mod=check_city_site",
		        dataType: 'json',
		        data: {
		        	city_info:city_info
		        },
		        async: false,
		        success: function (e) {
		        	var bid = 0;
		        	if (e.code==200) {
		        		var change_data = {};
		            	change_data.bid = bid = e.datas.bid;
		            	change_data.area_name = area_name = e.datas.area_name;
		            	change_data.site_id = site_id = e.datas.site_id;

		            	change_current_city_site_data(change_data);
		            	change_index_current_text(change_data);
		        	}

		        }
		    });
    	}
    }

}

// 获取当前 选择的城市
function get_current_city_site_data(){
	var current_data = {};
	current_data.bid = getcookie('city_site_bind_id') ? getcookie('city_site_bind_id') : 0;
	current_data.area_name = getcookie('city_site_area_name') ? getcookie('city_site_area_name') : '全国';
	current_data.site_id = getcookie('city_site_site_id') ? getcookie('city_site_site_id') : 0;
	return current_data;
}

// 切换 城市
function change_current_city_site_data(change_data){
	addcookie('city_site_bind_id',change_data.bid);	
	addcookie('city_site_area_name',change_data.area_name);
	addcookie('city_site_site_id',change_data.site_id);
}

function change_index_current_text(current_data){
	// 首页 分站信息 展示
	if ($(".city-site-btn").size()) {
		if (current_data.bid > 0) {
			// 非全国站
			$(".city-site-btn").find("span").text(current_data.area_name);
		}else{
			$(".city-site-btn").find("span").text('{:w:语言}');
		}
	}
}

var run_city_check = false;

function check_city_site_open(){
	var result = true;
    // $.ajax({
    //     type: 'get',
    //     url: ApiUrl + "/index.php?app=index&mod=get_city_site",
    //     dataType: 'json',
    //     data: {},
    //     async: false,
    //     success: function (e) {
    //     	run_city_check = true;
    //     	var bid = 0;
    //     	if (e.code==200) {
    //     		if (e.datas.sld_city_site > 0) {
		// 			result = true;
    //     		}
    //     	}
    //
    //     }
    // });

    return result;
}

// 检查 是否开启 城市分站
if (check_city_site_open()) {
	var current_data = get_current_city_site_data();
}else{
	var current_data = {};
	current_data.bid = 0;
	current_data.area_name = '全国';
	current_data.site_id = 0;
	$(".city-site-btn").hide();
	$(".htsearch-wrap").addClass('clear-city-site');
}
change_index_current_text(current_data);

// 统计 装修页的商品ID 集合
function getGidsFromTemplateData(gids,item_data){
	if (!gids) {
		gids = [];
	}
    if(item_data.type == 'gonggao'){
        if (item_data.lianjie_type == 'goods') {
            // 将链接中的商品ID 加入gids
            if (item_data.lianjie_url*1 > 0) {
                gids.push(item_data.lianjie_url*1);
            }
        }
    }
    if(item_data.type == 'lunbo'){
        $.each(item_data.data, function(key, value) {
            if (value.url_type == 'goods') {
                // 将链接中的商品ID 加入gids
                if (value.url*1 > 0) {
                    gids.push(value.url*1);
                }
            }
        });
    }
    if(item_data.type == 'nav'){
        $.each(item_data.data, function(key, value) {
            if (value.url_type == 'goods') {
                // 将链接中的商品ID 加入gids
                if (value.url*1 > 0) {
                    gids.push(value.url*1);
                }
            }
        });
    }
    if (item_data.type == 'huodong') {
        if(item_data.data.top.top[0].url_type){
            if (item_data.data.top.top[0].url_type == 'goods') {
                // 将链接中的商品ID 加入gids
                if (item_data.data.top.top[0].url*1 > 0) {
                    gids.push(item_data.data.top.top[0].url*1);
                }
            }
        }

        if (item_data.sele_style == 0) {
	        if (item_data.data.left.top[0]) {
	        	$.each(item_data.data.left.top[0],function(k,item){
	                if (item && item.gid && item.gid*1 > 0) {
	                    gids.push(item.gid*1);
	                }
	        	});
	        }
	        if (item_data.data.right.top[0]) {
	        	if (item_data.data.right.top[0].gid) {
		        	$.each(item_data.data.right.top[0].gid,function(k,item){
		                if (item*1 > 0) {
		                    gids.push(item*1);
		                }
		        	});
		        }
	        }
	        if (item_data.data.right.bottom) {
	        	$.each(item_data.data.right.bottom,function(p_k,p_item){
	        		if (p_item.gid) {
	        			$.each(p_item.gid,function(k,item){
	        				if (item*1 > 0) {
			                    gids.push(item*1);
			                }
	        			});
	        		}
	        	});
	        }
	    }else if(item_data.sele_style == 1){
	    	if (item_data.data.bottom) {
	    		$.each(item_data.data.bottom,function(p_k,p_item){
	    			$.each(p_item,function(i_k,i_item){
	    				if (i_item && i_item.gid) {
		        			$.each(i_item.gid,function(k,item){
		        				if (item*1 > 0) {
				                    gids.push(item*1);
				                }
		        			});
	    				}
	    			});
	    		});
	    	}
        }else{
	    	if (item_data.data.bottom) {
	    		$.each(item_data.data.bottom,function(p_k,p_item){
	    			$.each(p_item,function(i_k,i_item){
	    				if (i_item.gid) {
		        			$.each(i_item.gid,function(k,item){
		        				if (item*1 > 0) {
				                    gids.push(item*1);
				                }
		        			});
	    				}
	    			});
	    		});
	    	}
        }
    }
    if(item_data.type == 'tupianzuhe'){
        $.each(item_data.data, function(key, value) {
            if (value.url_type == 'goods') {
                // 将链接中的商品ID 加入gids
                if (value.url*1 > 0) {
                    gids.push(value.url*1);
                }
            }
        });
    }else{
        if(item_data.type == 'tuijianshangpin'){
        	if (item_data.data.gid.length) {
        		$.each(item_data.data.gid , function(k,item){
        			if (item*1 > 0) {
        				gids.push(item*1);
        			}
        		});
        	}
            // console.log(item_data.gid);
        }
    }

	gids = _distinct(gids);
	
    return gids;
}

// 数组去重
_distinct = function (arr){
 var result = [],
  len = arr.length;
 arr.forEach(function(v, i ,arr){  //这里利用map，filter方法也可以实现
  var bool = arr.indexOf(v,i+1);  //从传入参数的下一个索引值开始寻找是否存在重复
  if(bool === -1){
   result.push(v);
  }
 })
 return result;
};

//合并两个数组，去重
function concat_(arr1,arr2) {
    //不要直接使用var arr = arr1，这样arr只是arr1的一个引用，两者的修改会互相影响
	if(typeof arr1 == 'undefined'){
		arr1 = [];
	}
    var arr = arr1.concat();
    //或者使用slice()复制，var arr = arr1.slice(0)
    for (var i = 0; i < arr2.length; i++) {
        arr.indexOf(arr2[i]) === -1 ? arr.push(arr2[i]) : 0;
    }
    return arr;
}

// 分享成功 增加会员积分
function sharedAction(key,share_type,extend_id,extend_title){
	$.ajax({
        type:'get',
        url:ApiUrl+"/index.php?app=usercenter&mod=shareAction",
        data:{
          'key':key,
          'type':share_type,
          'extend_id':extend_id,
          'extend_title':extend_title
        },
        dataType:'json',
        success:function(result){

        }
    });
}

$('.header-share').on('click',function (e) {
    e.stopPropagation();
    $(this).find('ul').show();
})

$('body').on('click',function (e) {
    if(e.target.className!='header-share'){
        $('.header-share ul').hide();
    }
})