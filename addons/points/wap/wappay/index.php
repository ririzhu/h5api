<?php
/* *
 * 功能：支付宝手机网站支付接口接口调试入口页面
 * 版本：3.4
 * 修改日期：2016-03-08
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

?>
<!DOCTYPE html>
<html>
	<head>
	<title>支付宝手机网站支付接口接口</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<style>
    *{
        margin:0;
        padding:0;
    }
    ul,ol{
        list-style:none;
    }
    body{
        font-family: "Microsoft YaHei",tahoma,arial,宋体;
        overflow-x:hidden;
    }
    .hidden{
        display:none;
    }
    .new-btn-login-sp{
        padding: 1px;
        display: inline-block;
        width: 90%;
    }
    .new-btn-login {
        background-color: #1abb9b;
        color: #FFFFFF;
        font-weight: bold;
        border: none;
        width: 100%;
        height: 40px;
        border-radius: 5px;
        font-size: 20px;
    }
    #main{
        width:100%;
        margin:0 auto;
        font-size:14px;
    }
    .red-star{
        color:#f00;
        width:10px;
        display:inline-block;
    }
    .null-star{
        color:#fff;
    }
    .content{
        margin-top:20px;
    }
    .content dt{
        width:100px;
        display:inline-block;
        float: left;
        margin-left: 20px;
        color: #666;
        font-size: 15px;
        margin-top: 4px;
    }
    .content dd{
        margin-left:120px;
        margin-bottom:5px;
    }
    .content dd input {
        width: 85%;
        height: 28px;
        border: 0;
        font-size: 15px;
        -webkit-border-radius: 0;
        -webkit-appearance: none;
    }
    #foot{
        margin-top:10px;
        position: absolute;
        bottom: 15px;
        width: 100%;
    }
    .foot-ul{
        width: 100%;
    }
    .foot-ul li {
        width: 100%;
        text-align:center;
        color: #666;
    }
    .note-help {
        color: #999999;
        font-size: 12px;
        line-height: 130%;
        margin-top: 5px;
        width: 100%;
        display: block;
    }
    #btn-dd{
        margin: 60px 0;
        text-align: center;
    }
    .foot-ul{
        width: 100%;
    }
    .one_line{
        display: block;
        height: 1px;
        border: 0;
        border-top: 1px solid #eeeeee;
        width: 100%;
        margin-left: 15px;
        margin-top: 5px;
        margin-bottom: 5px;
    }
    .am-header {
        display: -webkit-box;
        display: -ms-flexbox;
        display: box;
        width: 100%;
        position: relative;
        padding: 7px 0;
        -webkit-box-sizing: border-box;
        -ms-box-sizing: border-box;
        box-sizing: border-box;
        background: #1abb9b;
        height: 47px;
        text-align: center;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        box-pack: center;
        -webkit-box-align: center;
        -ms-flex-align: center;
        box-align: center;
    }
    .am-header h1 {
        -webkit-box-flex: 1;
        -ms-flex: 1;
        box-flex: 1;
        line-height: 18px;
        text-align: center;
        font-size: 18px;
        font-weight: 300;
        color: #fff;
        font-weight: normal;
    }
    .h_prev {
        margin: 7px 0 13px;
        padding: 0 8px 0 12px;
        position: absolute;
        left: 0;
    }
    .h_prev a{
        padding-left: 20px;
        font-size: 15px;
        color:#fff;
        display: block;
        min-height: 23px;
    }
</style>
</head>
<body text=#000000 bgColor="#ffffff" leftMargin=0 topMargin=4>
<header class="am-header">
<div class="h_prev">
     <a style="background:url(../img/image/ico_011.png) no-repeat;background-size: 12px auto;" href="javascript:history.back();"></a>
    </div> 
        <h1>支付宝支付</h1>
</header>
<div id="main">
        <form name=alipayment action=alipayapi.php method=post target="_blank">
            <div id="body" style="clear:left">
                <dl class="content">
                    <dt>商户订单号
：</dt>
                    <dd>
                    <?php  
                       echo  "<input id="."WIDout_trade_no"." name="."WIDout_trade_no"." value=$_GET[uorder] readonly/>"; ?>
                    </dd>
                    <hr class="one_line" style="display: none;">
                    <dt style="display: none;">订单名称
：</dt>
                    <dd>
                        <input id="WIDsubject" type="hidden" name="WIDsubject" />
                    </dd>
                    <hr class="one_line">
                    <dt style="text-indent: 16px;">付款金额
：</dt>
                    <dd>
                        <!-- <input id="WIDtotal_fee" name="WIDtotal_fee" /> -->
                         <?php  
                       echo  "<input id="."WIDtotal_fee"." name="."WIDtotal_fee"." value=$_GET[uprice] readonly/>"; ?>
                    </dd>
                    <dd id="btn-dd">
                        <span class="new-btn-login-sp">
                            <button class="new-btn-login" type="submit" style="text-align:center;">确 认</button>
                        </span>
                        <span class="note-help" style="display: none;">如果您点击“确认”按钮，即表示您同意该次的执行操作。</span>
                    </dd>
                </dl>
            </div>
		</form>
        <div id="foot">
			<ul class="foot-ul">
				<li style="display: none;">
					支付宝版权所有 2015-2018 ALIPAY.COM 
				</li>
			</ul>
		</div>
	</div>
</body>
<script language="javascript">
	function GetDateNow() {
		document.getElementById("WIDsubject").value = "等待付款";
	}
	GetDateNow();
</script>
</html>