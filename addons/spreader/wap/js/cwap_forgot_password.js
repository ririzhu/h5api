$('.send_success_tips').hide();
var ALLOW_SEND = true;
$(function(){

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
        console.log(123123);
        errorTipsShow("手机号码不能为空！");
        $(this).parent().prev().find("input").focus();
        return false;
    }else if(!reg.test(phoneNum)){
        errorTipsHide();
        errorTipsShow("手机号码格式不正确，重新输入！");
        $(this).parent().prev().find("input").focus();
        return false;
    }else{
        $.ajax({
            type: "post",
            url: ApiUrl + '/index.php?app=login_mobile&mod=send_sms_mobile&sld_addons=spreader',
            data:{'mobile':phoneNum,'type':'3'},
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

    $(".yanzheng").html("("+delayTime+"S)后再试");
    $(".yanzheng").css({"background":"#FC3357","color":"#fff"});
    if (delayTime == 0) {
        delayTime = 60;
        $(".yanzheng").bind("click",getcode);
        $(".yanzheng").html("获取验证码");
        $(".yanzheng").css({"background":"#E6E9EE","color":"#5C5C5C"});
        clearTimeout(t);
    }
    else
    {
        t=setTimeout(countDown,1000);
    }
}
$(".loginlei").click(function(){
    // 手机号
    var phone = $("#tel_phone").val();
    // 验证码
    var code = $("#tel_yanzheng").val();
    // 密码
    var password = $("#password").val();
    // 进行信息验证
    if(code == "" || password == "" || phone == ""){
        errorTipsHide();
        errorTipsShow("请补全信息");
    }else{
        // 更新密码
        $.ajax({
            type: "post",
            url: ApiUrl + '/index.php?app=login_mobile&mod=editpass&sld_addons=spreader',
            data:{'password':password,'mobile':phone,'code':code},
            dataType: "JSON",
            success: function(data){
                var statua = JSON.parse(data);
                if(statua.datas.state == 'failuer'){
                    errorTipsHide();
                    errorTipsShow(statua.datas.msg);
                }else{
                    errorTipsHide();
                    errorTipsShow("重置密码成功");
                    window.location.href="./cwap_the_login.html";
                }
            }
        });
    }
});