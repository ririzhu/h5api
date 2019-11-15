$(function() {
    wap_now = 'sld_mine';
    var e = getcookie("key");
    if (!e) {
        window.location.href = WapSiteUrl + "/cwap_login.html";
        return
    }

    $.sValid.init({
		rules: {
			feedback_content: "required",
		},
		messages: {
			feedback_content: "反馈内容必填！",
		},
		callback: function(a, e, r) {
			if (a.length > 0) {
				var d = "";
				$.map(e,
					function(a, e) {
						d += "<p>" + a + "</p>"
					});
				errorTipsShow(d)
			} else {
				errorTipsHide()
			}
		}
	});

    // 意见反馈 提交
    $("#feedback_submit").click(function(n){
    	if ($.sValid()) {
			var r = $("#feedback_content").val();
			$.ajax({
				type: "post",
				url: ApiUrl + "/index.php?app=usercenter&mod=user_feedback",
				data: {
					key: e,
					content: r,
				},
				dataType: "json",
				before:function(){
					layer.open({
				        type: 2
				        ,content: '提交中'
				    });
				},
				success: function(a) {
					layer.closeAll();
					if (a.code == 200) {
						layer.open({
	                        content: a.datas.msg
	                        , skin: 'msg'
	                        , time: 2 //2秒后自动关闭
	                    });
						if (a.datas.status >= 0) {
	                        setInterval(function () {
	                            location.reload();
	                        }, 2000);
						}
					} else {
						layer.open({
	                        content: '接口异常'
	                        , skin: 'msg'
	                        , time: 2 //2秒后自动关闭
	                    });
					}
				}
			})
		}
    });
    // 清除缓存
    $("#clear_local_cookie").click(function(e){

        layer.open({
            type: 2
            ,content: '清除中'
            , time: 2 //2秒后自动关闭
        });

        var a = setTimeout(function () {
            var keys = document.cookie.match(/[^ =;]+(?=\=)/g);
            if(keys) {
                for(var i = keys.length; i--;)
                    document.cookie = keys[i] + '=0;expires=' + new Date(0).toUTCString()
            }

            console.log(123123);
            layer.closeAll();

            layer.open({

	            content: "清除缓存成功"
	            , skin: 'msg'
	            , time: 2 //2秒后自动关闭
	        });
        }, 2000);
    });
});