<?php
namespace app\v1\api\payment\qpay;

class Pay
{
    /**
     * 支付申请
     * @param $params
     */
    public function payapply($params)
    {
        $params["cusid"] = AppConfig::CUSID;
        $params["appid"] = AppConfig::APPID;
        $params["version"] = AppConfig::APIVERSION;
        $params["randomstr"] = "HORIZOU".time();//随机字符串
        $params['reqip'] = $_SERVER['SERVER_ADDR'];
        $params["currency"] = "CNY";//
//        $params["validtime"] = "";//
        $params["notifyurl"] = "http://miniprogram.com/index.php/v1/notify/allinpayNotify";//
        $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名
        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL . "/payapplyagree";
        $rsp = $this->request($url, $paramsStr);
        /*echo "请求返回:".$rsp;
        echo "<br/>";*/
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray)){
//            echo "验签正确,进行业务处理";
            return $rspArray;
        }
    }

    /**
     * 支付确认
     * @param $params
     * @return mixed
     */
    public function payconfirm($params)
    {
        $params["cusid"] = AppConfig::CUSID;
        $params["appid"] = AppConfig::APPID;
        $params["version"] = AppConfig::APIVERSION;
        $params["randomstr"] = "HORIZOU".time();//随机字符串
        $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名
        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL . "/payagreeconfirm";
        $rsp = $this->request($url, $paramsStr);

        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray)){
            return $rspArray;
        }
    }

    /**
     * 重新获取支付短信
     * @param $params
     * @return mixed
     */
    public function paysms($params)
    {
        $params["cusid"] = AppConfig::CUSID;
        $params["appid"] = AppConfig::APPID;
        $params["version"] = AppConfig::APIVERSION;
        $params["randomstr"] = "HORIZOU".time();//随机字符串
        $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名
        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL . "/paysmsagree";
        $rsp = $this->request($url, $paramsStr);

        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray)){
            return $rspArray;
        }
    }

    /**
     * 签约申请
     * @param $params
     * @return mixed
     */
    public function agreeapply($params)
    {
        $params["cusid"] = AppConfig::CUSID;
        $params["appid"] = AppConfig::APPID;
        $params["version"] = AppConfig::APIVERSION;
        $params["randomstr"] = "HORIZOU".time();//随机字符串
        $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名
        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL . "/agreeapply";
        $rsp = $this->request($url, $paramsStr);

        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray)){
            return $rspArray;
        }
    }

    /**
     * 签约申请确认
     * @param $params
     * @return mixed
     */
    public function agreeconfirm($params)
    {
        $params["cusid"] = AppConfig::CUSID;
        $params["appid"] = AppConfig::APPID;
        $params["version"] = AppConfig::APIVERSION;
        $params["randomstr"] = "HORIZOU".time();//随机字符串
        $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名
        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL . "/agreeconfirm";
        $rsp = $this->request($url, $paramsStr);

        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray)){
            return $rspArray;
        }
    }

    public function request($url,$params)
    {
        $ch = curl_init();
        $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
        curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//如果不加验证,就设false,商户自行处理
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $output = curl_exec($ch);
        curl_close($ch);
        return  $output;
    }

    function validSign($array)
    {
        if("SUCCESS"==$array["retcode"]){
            $signRsp = strtolower($array["sign"]);
            $array["sign"] = "";
            $sign =  strtolower(AppUtil::SignArray($array, AppConfig::APPKEY));
            if($sign==$signRsp){
                return TRUE;
            }
            else {
                echo "验签失败:".$signRsp."--".$sign;
            }
        }
        else{
            echo $array["retmsg"];
        }

        return FALSE;
    }
}
?>