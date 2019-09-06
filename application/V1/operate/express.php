<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/12/10
 * Time: 15:15
 */
class express
{
    private $bird_model;
    private $model;
    private $order_model;
    private $EBusinessID;
    private $AppKey;
    private $ReqURL;
    private $API_URL;
    private $IP_SERVICE_URL;
    private $QUERY_ReqURL;
    public function __construct()
    {
        $this->bird_model = Model('express_bird');
        $this->order_model = Model('order');
        $this->model = Model();

        //生成电子面单接口地址
        //测试环境
//        $this->ReqURL = 'http://testapi.kdniao.com:8081/api/Eorderservice';
        //正式环境
        $this->ReqURL = 'http://api.kdniao.com/api/Eorderservice';

        //打印电子面单接口地址
        $this->API_URL = 'http://www.kdniao.com/External/PrintOrder.aspx';
        $this->IP_SERVICE_URL = 'http://www.kdniao.com/External/GetIp.aspx';

        //物流信息查询接口
        //线上接口
        $this->QUERY_ReqURL = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
        //测试环境接口
//        $this->QUERY_ReqURL = 'http://sandboxapi.kdniao.com:8080/kdniaosandbox/gateway/exterfaceInvoke.json';

    }
    /*
     * 物流信息查询
     * $param = [
     *  'order_sn'=>'',      //订单号
     * ]
     */
    public function query_order_traces($param)
    {
//        $requestData = "{'OrderCode':'','ShipperCode':'YTO','LogisticCode':'12345678'}";
        $order_model = Model('order');
        $condition = [];
        $condition['order_sn'] = $param['order_sn'];
        $order_info = $order_model->getOrderInfo($condition,array('order_common'));
        if(!$order_info){
            return ['status'=>255,'msg'=>'暂未查到相关订单'];
        }
        //设置用户账号参数
        $param_res = $this->setparam($order_info['vid']);
        if($param_res['status'] == 255){
            return ['status'=>255,'msg'=>$param_res['msg']];
        }
        //获取物流代码
        if($order_info['extend_order_common']['is_dzmd']){
            $express_info = $this->model->table('express_extra')->where(['e_id'=>$order_info['extend_order_common']['shipping_express_id']])->find();
            $e_code = $express_info['e_code'];
        }else{
            $express_info = $this->model->table('express')->where(['id'=>$order_info['extend_order_common']['shipping_express_id']])->find();
            $e_code = $express_info['e_code'];
        }
        $requestData = json_encode([
            'OrderCode'=>$param['order_sn'],
            'ShipperCode'=>$e_code,
            'LogisticCode'=>$order_info['shipping_code'],
        ]);
        $datas = array(
            'EBusinessID' => $this->EBusinessID,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->bird_encrypt($requestData, $this->AppKey);
        $result = $this->sendPost($this->QUERY_ReqURL, $datas);
        $result = json_decode($result,1);
        if(!$result['Success']){
            return ['status'=>255,'msg'=>$result['Reason']];
        }
        return ['status'=>200,'data'=>$result ];
    }
    /*
     * 生成电子面单,注意是传递的参数是关联数组
     * @param array param:[
     * vid=>店铺id,
     * order_id=>订单id,
     * express_id=>快递id,
     * Remark=>发货备注(选填),
     * IsNotice=>是否通知快递员上门取货,       //不写就通知
     *daddress_id=>店铺发货地址id     //不写取默认地址
     * 待续扩展...]
     * return array
     */
    public function make_express($param)
    {
        $vid = $param['vid'];
        $order_id = $param['order_id'];
        $express_id = $param['express_id'];
        //设置用户账号参数
        $param_res = $this->setparam($vid);
        if($param_res['status'] == 255){
            return ['status'=>255,'msg'=>$param_res['msg']];
        }
        //快递公司信息
        $express_info = $this->bird_model->getone(['vid'=>$vid,'express_id'=>$express_id]);
        if(!$express_info){
            return ['status'=>255,'msg'=>'快递信息错误'];
        }
        //订单信息
        $order_info = $this->order_model->getOrderInfo(['vid'=>$vid,'order_id'=>$order_id], array('order_common', 'order_goods'));
        if(!$order_info){
            return ['status'=>255,'msg'=>'订单不存在'];
        }
        if($order_info['order_state'] != 20){
            return ['status'=>255,'msg'=>'订单不能发货'];
        }
        //是否是申请客户号
        $paytype = $this->ismonthpay($express_info);
        if($paytype['status'] == 255){
            return ['status'=>255,'msg'=>$paytype['msg']];
        }
        //构造电子面单提交信息
        $eorder = [
            'MemberID'=>$vid,
            'ShipperCode'=>$express_info['express_code'],
            'OrderCode'=>$order_info['order_sn'],
            'PayType'=>$paytype['data'],            //运费支付方式
            'ExpType'=>1,
            'Cost'=>$order_info['shipping_fee'],
            'IsNotice'=>isset($param['IsNotice'])?($param['IsNotice']?1:0):0,            //是否通知快递员揽件
            'Quantity'=>1,                  //就写1吧
            'Remark'=>$param['Remark']?:'',            //备注
            'IsReturnPrintTemplate'=>1,       //是否返回电子面单模板
        ];
        //申请客户号需要传递的参数
        if(isset($express_info['customer_name']) && !empty($express_info['customer_name'])){
            $eorder['CustomerName	'] = trim($express_info['customer_name']);
        }
        if(isset($express_info['customer_pwd']) && !empty($express_info['customer_pwd'])){
            $eorder['CustomerPwd'] = trim($express_info['customer_pwd']);
        }
        if(isset($express_info['send_site']) && !empty($express_info['send_site'])){
            $eorder['SendSite'] = trim($express_info['send_site']);
        }
        if(isset($express_info['send_staff']) && !empty($express_info['send_staff'])){
            $eorder['SendStaff'] = trim($express_info['send_staff']);
        }
        if(isset($express_info['mouth_code']) && !empty($express_info['mouth_code'])){
            $eorder['MonthCode'] = trim($express_info['mouth_code']);
        }

        //发货人信息
        if(isset($param['daddress_id']) && !empty($param['daddress_id'])){
            $daddress = $this->model->table('daddress')->where(['address_id'=>intval($param['daddress_id'])])->find();
            if(!$daddress){
                return ['status'=>255,'msg'=>'发货地址不存在'];
            }
        }else{
            $daddress = $this->model->table('daddress')->where(['vid'=>$vid,'is_default'=>1])->find();
            if(!$daddress){
                return ['status'=>255,'msg'=>'店铺没有设置默认发货地址'];
            }
        }
        $vendor_area = preg_split('/(\s|\&nbsp\;|　|\xc2\xa0)/',$daddress['area_info']);
        $sender = [
                'Company'=>$daddress['company'],
                'Name'=>$daddress['seller_name'],
                'Mobile'=>$daddress['telphone'],
                'ProvinceName'=>$vendor_area[0],
                'CityName'=>$vendor_area[1],
                'ExpAreaName'=>$vendor_area[2],
                'Address'=>$daddress['address'],
        ];
        //收货人信息
        $common_info = $order_info['extend_order_common'];
        $address =preg_split('/(\s|\&nbsp\;|　|\xc2\xa0)/',$common_info['reciver_info']['address']);
        $ProvinceName = $address[0];
        array_shift($address);
        $CityName = $address[1];
        array_shift($address);
        $ExpAreaName = $address[2];
        array_shift($address);
        $receiver = [
            'Name'=>$common_info['reciver_name'],
            'Mobile'=>explode(',',$common_info['reciver_info']['phone'])[0],
            'Tel'=>explode(',',$common_info['reciver_info']['phone'])[1]?:'',
            'ProvinceName'=>$ProvinceName,
            'CityName'=>$CityName,
            'ExpAreaName'=>$ExpAreaName,
            'Address'=>implode(' ',$address),
        ];
        //商品信息
        $goods_list = $order_info['extend_order_goods'];
        $commodity = [];
        foreach($goods_list as $k=>$v){
            $goods = [
                'GoodsName'=>$v['goods_name'],
                'GoodsCode'=>$v['gid'],
                'Goodsquantity'=>$v['goods_num'],
                'GoodsPrice'=>$v['goods_pay_price'],
            ];
            $commodity[] = $goods;
        }

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;

        //调用电子面单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);

        //$jsonParam = $this->JSON($eorder);//兼容php5.2（含）以下

        $jsonResult = $this->submitEOrder($jsonParam);

        //解析电子面单返回结果
        $result = json_decode($jsonResult, true);

        if($result["ResultCode"] != 100 && $result['Success'] != 1){
            return ['status'=>255,'msg'=>$result["Reason"]];
        }
        return $result['Order'];
    }
    /*
     * 打印电子面单接口
     */
    public function printdzmd($param,$vid)
    {
        //设置用户账号参数
        $param_res = $this->setparam($vid);
        if($param_res['status'] == 255){
            return ['status'=>255,'msg'=>$param_res['msg']];
        }
        //OrderCode:需要打印的订单号，和调用快递鸟电子面单的订单号一致，PortName：本地打印机名称，请参考使用手册设置打印机名称。支持多打印机同时打印。
        $order = $param;
        $request_data = json_encode($order);
        // $request_data = '["OrderCode":"1012345678911","PortName":"Gprinter GP-3120TU"}]';
        $request_data_encode = urlencode($request_data);
        // var_dump(get_ip(),$request_data, APIKey);
        $ip = $this->get_ip();
//        $ip = '210.12.69.66';
        $data_sign = $this->bird_encrypt($ip.$request_data, $this->AppKey);
        //是否预览，0-不预览 1-预览
        $is_priview = '0';

        //组装表单
        $form = '<form id="form1" method="POST" action="'.$this->API_URL.'"><input type="text" name="RequestData" value=\''.$request_data.'\'/><input type="text" name="EBusinessID" value="'.$this->EBusinessID.'"/><input type="text" name="DataSign" value="'.$data_sign.'"/><input type="text" name="IsPriview" value="'.$is_priview.'"/></form><script>document.getElementById("form1").submit();</script>';
        print_r($form);
    }
    /**
     * 判断是否为内网IP
     * @param ip IP
     * @return 是否内网IP
     */
    function is_private_ip($ip) {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    /**
     * 获取客户端IP(非用户服务器IP)
     * @return 客户端IP
     */
    public function get_ip() {
        //获取客户端IP
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip = explode(', ','210.12.69.66, 112.17.13.99, 115.238.101.11')[0];
        if(!$ip || $this->is_private_ip($ip)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->IP_SERVICE_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            return $output;
        }
        else{
            return $ip;
        }
    }
    /*
     * 检测订单状态能否生成电子面单
     * @param int order_id 订单id
     */
    public function testdianzimd($order_id)
    {
        $order_info = $this->model->table('order')->where(['order_id'=>$order_id])->find();
        if(!$order_info){
            return ['status'=>255,'msg'=>'订单不存在'];
        }
        $if_allow_send = intval($order_info['lock_state']) || !in_array($order_info['order_state'],array(ORDER_STATE_PAY,ORDER_STATE_SEND));

        if ($if_allow_send) {
            return ['status'=>255,'msg'=>'订单状态不支持生成电子面单'];
        }
        if($order_info['order_state'] == ORDER_STATE_PAY && empty($order_info['shipping_code'])){
            return ['status'=>200];
        }
        return ['status'=>255,'msg'=>'订单状态不支持生成电子面单'];
    }
    /*
     * 设置商户的快递鸟账号
     */
    public function setparam($vid)
    {
        $vendor_info = $this->model->table('vendor_extend')->where(['vid'=>$vid])->field('ebusinessid,appkey')->find();
        if(empty($vendor_info['ebusinessid']) || empty($vendor_info['appkey'])){
                return ['status'=>255,'msg'=>'账号信息错误'];
        }
        $this->EBusinessID = $vendor_info['ebusinessid'];
        $this->AppKey = $vendor_info['appkey'];
    }
    /*
     * 店铺运费是否月结
     */
    private function ismonthpay($data)
    {
        $express_info = $this->model->table('express_extra')->where(['id'=>$data['express_id']])->find();
        if(!$express_info){
            return ['status'=>255,'msg'=>'快递公司未配置'];
        }
        if($express_info['is_apply']){
            return ['status'=>200,'data'=>3];
        }else{
            //顺丰情况比较特殊,可申请可不申请
            if($express_info['e_code'] == 'SF'){
                if(!empty($data['mouth_code'])){
                    return ['status'=>200,'data'=>3];
                }
            }
            return ['status'=>200,'data'=>1];
        }

    }
    /**
     * Json方式 调用电子面单接口
     */
    public function submitEOrder($requestData){
        $datas = array(
            'EBusinessID' => $this->EBusinessID,
            'RequestType' => '1007',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->bird_encrypt($requestData, $this->AppKey);
        $result = $this->sendPost($this->ReqURL, $datas);
        return $result;
    }
    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    public function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Types:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    public function bird_encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
    /**************************************************************
     *
     *  使用特定function对数组中所有元素做处理
     *  @param  string  &$array     要处理的字符串
     *  @param  string  $function   要执行的函数
     *  @return boolean $apply_to_keys_also     是否也应用到key上
     *  @access public
     *
     *************************************************************/
    public function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
    {
        static $recursive_counter = 0;
        if (++$recursive_counter > 1000) {
            die('possible deep recursion attack');
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->arrayRecursive($array[$key], $function, $apply_to_keys_also);
            } else {
                $array[$key] = $function($value);
            }

            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
        $recursive_counter--;
    }


    /**************************************************************
     *
     *  将数组转换为JSON字符串（兼容中文）
     *  @param  array   $array      要转换的数组
     *  @return string      转换得到的json字符串
     *  @access public
     *
     *************************************************************/
    public function JSON($array) {
        $this->arrayRecursive($array, 'urlencode', true);
        $json = json_encode($array);
        return urldecode($json);
    }

}