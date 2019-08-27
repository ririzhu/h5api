$('.send_success_tips').hide();
var ALLOW_SEND = true;
$(function(){
    //获取logo
    getSldLogoUrl();
    loadSeccode();
    $("#refreshcode").bind("click",
        function() {
            loadSeccode()
        });
    if(!GetQueryString('u') == 'null' || !GetQueryString('u')=='' || !GetQueryString('u') == 'undefined'){
        addcookie('inviteid',(GetQueryString('u')));
        var inviteid=(GetQueryString('u'));
    }else{
        if(getcookie('inviteid') == 'null' || getcookie('inviteid')=='' || getcookie('inviteid') == 'undefined'){

        }else{
            var inviteid=decodeURIComponent(getcookie('inviteid'));
        }
    }
});
//wap获取图形验证码
function loadSeccode() {
    $("#codeimage").attr("src", ApiUrl + "/index.php?app=randcode" + "&rand=" + Math.random())
}

// 密码显示与隐藏切换
$(".showhide").click(function(){
    if($(this).prev().attr("type") == "password"){
        $(this).prev().attr("type","text");
        $(this).find("img").eq(0).attr("style","display:none");
        $(this).find("img").eq(1).attr("style","display:block");
    }else if($(this).prev().attr("type") == "text"){
        $(this).prev().attr("type","password");
        $(this).find("img").eq(0).attr("style","display:block");
        $(this).find("img").eq(1).attr("style","display:none");
    }
});
//获取验证码事件
$(".yanzheng").bind('click',function () {
    getcode();
});
// 获取验证码
function getcode(){
    var phoneNum = $("#tel_phone").val();
    var reg = /(1[3-9]\d{9}$)/;
    if(phoneNum == ""){
        errorTipsShow("手机号码不能为空！");
        $(this).parent().prev().find("input").focus();
        return false;
    }else if(!reg.test(phoneNum)){
        errorTipsHide();
        errorTipsShow("手机号码格式不正确，重新输入！");
        $(this).parent().prev().find("input").focus();
        return false;
    }else{
        var picyanzheng = $("#picyanzheng").val();
        if(picyanzheng == ""){
            errorTipsHide();
            errorTipsShow("请输入正确的验证码");
            return false;
        }
        var codekey = $("#codekey").val();
        $.ajax({
            type: "post",
            url: ApiUrl + '/index.php?app=login_mobile&mod=send_sms_mobile',
            data:{'mobile':phoneNum,'type':'1','picyanzheng':picyanzheng,'sldcode':codekey},
            dataType: "json",
            success: function(data){
                if (data.code == 200) {
                    if (data.datas.state == 'failuer') {
                        errorTipsHide();
                        errorTipsShow(data.datas.msg);
                    }else{
                        // 获取验证码倒计时
                        $(".yanzheng").unbind("click");
                        countDown();
                    }
                } else {
                    errorTipsHide();
                    errorTipsShow(data.datas.msg);
                }
                if(data.status == 250){
                    errorTipsHide();
                    errorTipsShow(data.msg);
                }else{
                }

            }
        });
    }
}
var delayTime = 60;
function countDown()
{
    delayTime--;
    $(".yanzheng").html(delayTime + '秒');
    if (delayTime == 0) {
        delayTime = 60;
        $(".yanzheng").bind("click",getcode);
        $(".yanzheng").html("获取验证码");
        clearTimeout(t);
    }
    else
    {
        t=setTimeout(countDown,1000);
    }
}
var spreader = '';
if(typeof get_main_share_id != 'undefined' && get_main_share_id instanceof Function){
    spreader = get_main_share_id();
}
$(".loginlei").click(function(){
    // 手机号
    var phone = $("#tel_phone").val();
    // 验证码
    var code = $("#tel_yanzheng").val();
    // 密码
    var password = $("#password").val();
    // 进行信息验证
    if(code == "" || password == "" || $(".shop").val() == ""){
        errorTipsHide();
        errorTipsShow("请补全信息");
    }else{
        if(!$(".xieyicheck").attr("checked")){
            errorTipsHide();
            errorTipsShow("同意协议之后才可以注册！");
            return false;
        }
        // 进行注册
        $.ajax({
            type: "post",
            url: ApiUrl + '/index.php?app=login_mobile&mod=mobileregister',
            data:{'mobile':phone,'password':password,'client':'wap','vcode':code,'inviteid':decodeURIComponent(getcookie('inviteid')),spreader:spreader},
            dataType: "JSON",
            success: function(data){
                var data = $.parseJSON(data);
                if(data.datas.state == 'true'){
                    if(typeof(data.datas.key)=='undefined'){
                        errorTipsHide();
                        errorTipsShow('注册出错');
                        return false;
                    }else{
                        addcookie('username',data.datas.username);
                        addcookie('key',data.datas.key);
                        location.href = WapSiteUrl+'/cwap_user.html';
                    }
                }else{
                    errorTipsHide();
                    errorTipsShow(data.datas.msg);
                }
            }
        });
    }
});