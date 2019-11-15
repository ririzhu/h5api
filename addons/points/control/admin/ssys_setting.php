<?php
/**
 * 网站设置
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_settingCtl extends SystemCtl{
    private $links = array(
        array('url'=>'app=setting&mod=base','lang'=>'web_set'),
        array('url'=>'app=setting&mod=dump','lang'=>'dis_dump'),
    );
    public function __construct(){
        parent::__construct();
        Language::read('setting','spreader');
    }
    /**
     * slodon_基本信息(获取、设置)
     */
    public function basicInfo(){
//        定时任务测试
//        M('ssys_order','spreader')->ts_member_change_state();
//        die;
//        dd(M('ssys_order','spreader')->StatisticsMemberOrderMoney(243));
        $model_setting = M('ssys_setting');
        if($_POST['type'] == 'save'){
            //保存信息
            $update_array = array();
            if (isset($_POST['spreader_isuse'])) {
                $update_array['spreader_isuse'] = $_POST['spreader_isuse'];
            }
            if (isset($_POST['ssys_site_name'])) {
                $update_array['ssys_site_name'] = $_POST['ssys_site_name'];
            }
            if (isset($_POST['ssys_site_phone'])) {
                $update_array['ssys_site_phone'] = $_POST['ssys_site_phone'];
            }
            if (isset($_POST['ssys_sms_login'])) {
                $update_array['ssys_sms_login'] = $_POST['ssys_sms_login'];
            }
            if (isset($_POST['ssys_sms_password'])) {
                $update_array['ssys_sms_password'] = $_POST['ssys_sms_password'];
            }
            if (isset($_POST['ssys_sms_register'])) {
                $update_array['ssys_sms_register'] = $_POST['ssys_sms_register'];
            }
            if (isset($_POST['ssys_share_valid_time'])) {
                $update_array['ssys_share_valid_time'] = $_POST['ssys_share_valid_time'];
            }
            if (isset($_POST['ssys_yj_type'])) {
                $update_array['ssys_yj_type'] = $_POST['ssys_yj_type'];
            }
            if (isset($_POST['ssys_yj_percent'])) {
                $ssys_yj_percent = explode(',', $_POST['ssys_yj_percent']);
                $update_array['ssys_yj_percent'] = serialize($ssys_yj_percent);
            }
            // if (isset($_POST['ssys_allow_self_get_yj'])) {
            //     $update_array['ssys_allow_self_get_yj'] = $_POST['ssys_allow_self_get_yj'];
            // }
            if (isset($_POST['ssys_freeze_to_av_days'])) {
                $update_array['ssys_freeze_to_av_days'] = $_POST['ssys_freeze_to_av_days']; // 佣金冻结转可用的结算周期（天）
            }
            if (isset($_POST['ssys_min_cash_amount_once'])) {
                $update_array['ssys_min_cash_amount_once'] = $_POST['ssys_min_cash_amount_once']; // 单次提现限制金额（最低金额）
            }
            if (isset($_POST['time_zone'])) {
                $update_array['time_zone'] = $this->setTimeZone($_POST['time_zone']);
            }
            // 规则页面 内容
            if(isset($_POST['ssys_rulepage_set'])){
                $update_array['ssys_rulepage_set'] = html_entity_decode(trim($_POST['ssys_rulepage_set']));
            }
            
            $result = $model_setting->updateSetting($update_array);
            if ($result === true){
                $this->log(L('bbc_edit,web_set'),1);
                echo json_encode(array('state'=>200,'msg'=>'保存成功'));die;
            }else {
                $this->log(L('bbc_edit,web_set'),0);
                echo json_encode(array('state'=>255,'msg'=>'保存失败'));die;
            }
        }else{
            $list_setting = $model_setting->getListSetting();
            foreach ($this->getTimeZone() as $k=>$v) {
                if ($v == $list_setting['time_zone']){
                    $list_setting['time_zone'] = (string)$k;break;
                }
            }

            //只返回需要的信息
            $list_setting_new = array();
            $list_setting_new['spreader_isuse'] = $list_setting['spreader_isuse'] ? $list_setting['spreader_isuse'] : 0;
            $list_setting_new['ssys_site_name'] = $list_setting['ssys_site_name'];
            $list_setting_new['ssys_site_phone'] = $list_setting['ssys_site_phone'];
            $list_setting_new['ssys_sms_login'] = $list_setting['ssys_sms_login'];
            $list_setting_new['ssys_sms_password'] = $list_setting['ssys_sms_password'];
            $list_setting_new['ssys_sms_register'] = $list_setting['ssys_sms_register'];
            $list_setting_new['ssys_share_valid_time'] = $list_setting['ssys_share_valid_time']; // 分享有效时间
            $list_setting_new['ssys_yj_type'] = $list_setting['ssys_yj_type'];  // 几级分佣金（下拉选择）--1/2/3   
            $list_setting_new['ssys_yj_percent'] = unserialize($list_setting['ssys_yj_percent']); // 设置分佣比例 (序列化 0 一级比例 1 二级比例 2 三级比例)
            // $list_setting_new['ssys_allow_self_get_yj'] = $list_setting['ssys_allow_self_get_yj']; // 选择自己购买是否分佣金
            $list_setting_new['ssys_freeze_to_av_days'] = $list_setting['ssys_freeze_to_av_days']; // 佣金冻结转可用的结算周期（天）
            $list_setting_new['ssys_min_cash_amount_once'] = $list_setting['ssys_min_cash_amount_once']; // 单次提现限制金额（最低金额）
            $list_setting_new['ssys_min_cash_amount_once'] = $list_setting['ssys_min_cash_amount_once']; // 单次提现限制金额（最低金额）
            $list_setting_new['ssys_rulepage_set'] = $list_setting['ssys_rulepage_set']; // 规则页面 内容

            echo json_encode(array('basicinfo'=>$list_setting_new));die;
        }

    }
    /*
     * 成为推手条件设置
     * [@param string type]可选,当type=save时修改信息,以下参数是修改值,否则是取数据信息
     * @param int ssys_become_ts_open 成为推手条件开关
     * @param float ssys_ts_condition1_money 成为推手条件1:累计多少钱条件
     * @param float ssys_ts_condition2_goodsmoney 成为推手条件条件2:指定商品消费条件
     * return 取列表=>json['becomecondition'=>数据],修改条件=>json[state=>200(成功)/255(失败),msg=>说明信息]
     */
    public function become_ts_condition()
    {
        $model_setting = M('ssys_setting');
        if($_POST['type'] == 'save'){
            $update_data = [];
            if(isset($_POST['ssys_become_ts_open'])){
                $update_data['ssys_become_ts_open'] = intval($_POST['ssys_become_ts_open']);
            }
            if(isset($_POST['ssys_ts_condition1_money'])){
                $update_data['ssys_ts_condition1_money'] = floatval($_POST['ssys_ts_condition1_money']);
            }
            if(isset($_POST['ssys_ts_condition2_goodsmoney'])){
                $update_data['ssys_ts_condition2_goodsmoney'] = floatval($_POST['ssys_ts_condition2_goodsmoney'])==0?0:floatval($_POST['ssys_ts_condition2_goodsmoney']);
            }
            $result = $model_setting->updateSetting($update_data);
            if ($result === true){
                $this->log(L('bbc_edit,web_set'),1);
                echo json_encode(array('state'=>200,'msg'=>'保存成功'));die;
            }else {
                $this->log(L('bbc_edit,web_set'),0);
                echo json_encode(array('state'=>255,'msg'=>'保存失败'));die;
            }
        }

        $list_setting = $model_setting->getListSetting();
        //取出返回的数据
        $res_list = [];
        $res_list['ssys_become_ts_open'] = $list_setting['ssys_become_ts_open'];
        $res_list['ssys_ts_condition1_money'] = $list_setting['ssys_ts_condition1_money'];
        $res_list['ssys_ts_condition2_goodsmoney'] = $list_setting['ssys_ts_condition2_goodsmoney'];
        echo json_encode(array('becomecondition'=>$res_list));die;
    }
    /**
     * 设置时区
     *
     * @param int $time_zone 时区键值
     */
    private function setTimeZone($time_zone){
        $zonelist = $this->getTimeZone();
        return empty($zonelist[$time_zone]) ? 'Asia/Shanghai' : $zonelist[$time_zone];
    }

    //通过获取的value值获取相应的数据存库
    private function getTimeZone(){
        return array(
        '-12' => 'Pacific/Kwajalein',
        '-11' => 'Pacific/Samoa',
        '-10' => 'US/Hawaii',
        '-9' => 'US/Alaska',
        '-8' => 'America/Tijuana',
        '-7' => 'US/Arizona',
        '-6' => 'America/Mexico_City',
        '-5' => 'America/Bogota',
        '-4' => 'America/Caracas',
        '-3.5' => 'Canada/Newfoundland',
        '-3' => 'America/Buenos_Aires',
        '-2' => 'Atlantic/St_Helena',
        '-1' => 'Atlantic/Azores',
        '0' => 'Europe/Dublin',
        '1' => 'Europe/Amsterdam',
        '2' => 'Africa/Cairo',
        '3' => 'Asia/Baghdad',
        '3.5' => 'Asia/Tehran',
        '4' => 'Asia/Baku',
        '4.5' => 'Asia/Kabul',
        '5' => 'Asia/Karachi',
        '5.5' => 'Asia/Calcutta',
        '5.75' => 'Asia/Katmandu',
        '6' => 'Asia/Almaty',
        '6.5' => 'Asia/Rangoon',
        '7' => 'Asia/Bangkok',
        '8' => 'Asia/Shanghai',
        '9' => 'Asia/Tokyo',
        '9.5' => 'Australia/Adelaide',
        '10' => 'Australia/Canberra',
        '11' => 'Asia/Magadan',
        '12' => 'Pacific/Auckland'
        );      
    }
    //根据数据库里的值获取前台要初始化的数据
    private function getTimeZone_show($key){
        $timezone =  array(
            'Pacific/Kwajalein' => '(GMT -12:00) Eniwetok, Kwajalein',
            'Pacific/Samoa' => '(GMT -11:00) Midway Island, Samoa',
            'US/Hawaii' => '(GMT -10:00) Hawaii',
            'US/Alaska' => '(GMT -09:00) Alaska',
            'America/Tijuana' => '(GMT -08:00) Pacific Time (US &amp; Canada), Tijuana',
            'US/Arizona' => '(GMT -07:00) Mountain Time (US &amp; Canada), Arizona',
            'America/Mexico_City' => '(GMT -06:00) Central Time (US &amp; Canada), Mexico City',
            'America/Bogota' => '(GMT -05:00) Eastern Time (US &amp; Canada), Bogota, Lima, Quito',
            'America/Caracas' => '(GMT -04:00) Atlantic Time (Canada), Caracas, La Paz',
            'Canada/Newfoundland' => '(GMT -03:30) Newfoundland',
            'America/Buenos_Aires' => '(GMT -03:00) Brassila, Buenos Aires, Georgetown, Falkland Is',
            'Atlantic/St_Helena' => '(GMT -02:00) Mid-Atlantic, Ascension Is., St. Helena',
            'Atlantic/Azores' => '(GMT -01:00) Azores, Cape Verde Islands',
            'Europe/Dublin' => '(GMT) Casablanca, Dublin, Edinburgh, London, Lisbon, Monrovia',
            'Europe/Amsterdam' => '(GMT +01:00) Amsterdam, Berlin, Brussels, Madrid, Paris, Rome',
            'Africa/Cairo' => '(GMT +02:00) Cairo, Helsinki, Kaliningrad, South Africa',
            'Asia/Baghdad' => '(GMT +03:00) Baghdad, Riyadh, Moscow, Nairobi',
            'Asia/Tehran' => '(GMT +03:30) Tehran',
            'Asia/Baku' => '(GMT +04:00) Abu Dhabi, Baku, Muscat, Tbilisi',
            'Asia/Kabul' => '(GMT +04:30) Kabul',
            'Asia/Karachi' => '(GMT +05:00) Ekaterinburg, Islamabad, Karachi, Tashkent',
            'Asia/Calcutta' => '(GMT +05:30) Bombay, Calcutta, Madras, New Delhi',
            'Asia/Katmandu' => '(GMT +05:45) Katmandu',
            'Asia/Almaty' => '(GMT +06:00) Almaty, Colombo, Dhaka, Novosibirsk',
            'Asia/Rangoon' => '(GMT +06:30) Rangoon',
            'Asia/Bangkok' => '(GMT +07:00) Bangkok, Hanoi, Jakarta',
            'Asia/Shanghai' => '(GMT +08:00) Beijing, Hong Kong, Perth, Singapore, Taipei',
            'Asia/Tokyo' => '(GMT +09:00) Osaka, Sapporo, Seoul, Tokyo, Yakutsk',
            'Australia/Adelaide' => '(GMT +09:30) Adelaide, Darwin',
            'Australia/Canberra' => '(GMT +10:00) Canberra, Guam, Melbourne, Sydney, Vladivostok',
            'Asia/Magadan' => '(GMT +11:00) Magadan, New Caledonia, Solomon Islands',
            'Pacific/Auckland' => '(GMT +12:00) Auckland, Wellington, Fiji, Marshall Island'
        );
        return $timezone[$key];
    }

}