<?php
/**
 * 网站设置
 *
 */
defined('DYMall') or exit('Access Invalid!');
class cashersystem_settingCtl extends SystemCtl{
    public function __construct(){
        parent::__construct();
        Language::read('cashsys_setting','cashersystem');
    }

    /**
     * slodon_基本信息(获取、设置)
     */
    public function basicInfo(){

        $model_setting = M('cashsys_setting');
        if($_POST['type'] == 'save'){
            //保存信息
            $update_array = array();
            if (isset($_POST['cashersystem_isuse'])) {
                $update_array['cashersystem_isuse'] = $_POST['cashersystem_isuse'];
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

            //只返回需要的信息
            $list_setting_new = array();
            $list_setting_new['cashersystem_isuse'] = $list_setting['cashersystem_isuse'] ? $list_setting['cashersystem_isuse'] : 0;

            echo json_encode(array('basicinfo'=>$list_setting_new));die;
        }

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