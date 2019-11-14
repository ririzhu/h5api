<?php
namespace app\v1\api\payment\qpay;

/**
 * 常量配置
 */
class AppConfig{
    //测试环境
	const APPID = '00000156';//测试环境
	const CUSID = '990581053996001';//测试环境
    const APPKEY = '43df939f1e7f5c6909b3f4b63f893994';//测试环境
    const APIURL = "http://test.allinpaygd.com/apiweb/qpay";//测试环境

    //生产环境
    /*const APPID = '00178859';
    const CUSID = '56058104816HDSQ';
    const APPKEY = '15202156609';
    const APIURL = "https://vsp.allinpay.com/apiweb/qpay";*/
    const APIVERSION = '11';
}
?>