// 本地开发 验证
var local_flag = window.localStorage.getItem('local');
if (local_flag) {
	document.write("<script type='text/javascript' src='js/cwap_config_env.js'></script>");  
}else{
var SiteUrl = "http://site7.55jimu.com";
var ApiUrl = "http://site7.55jimu.com/cmobile";//注意这儿一定不可以加“/”
var pagesize = 10;
var WapSiteUrl = "http://site7.55jimu.com/ladder";
var AndroidSiteUrl = "http://123.56.255.63";
var cookie_pre = '0D59_';
var distribution = true;//true为开启分销，false为关闭分销
var webimurl = 'http://site2.slodon.cn/webim';
var WebImPort = "ws://123.56.203.196:9502";
var gzh_appid = 'wxd4e85b4526eaac01';//微信公众号的appid
// var Aurl = location.pathname.split('/').pop().split('_');
}

