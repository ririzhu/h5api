<?php
namespace app\v1\model;
use think\Db;
use think\Model;

/**
 * 系统设置内容
 *
 * 
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class Setting extends Model
{
	/**
	 * 读取系统设置信息
	 *
	 * @param string $name 系统设置信息名称
	 * @return array 数组格式的返回结果
	 */
	public function getRowSetting($name){
		$param	= array();
		$param['table']	= 'setting';
		$param['where']	= "name='".$name."'";
		$result	= Db::select($param);
		if(is_array($result) and is_array($result[0])){
			return $result[0];
		}
		return false;
	}
	
	/**
	 * 读取系统设置列表
	 *
	 * @param 
	 * @return array 数组格式的返回结果
	 */
	public function getListSetting($key = ''){
	    $condition = [];
		if($key) {
            $condition['name']	= $key;
        }
		$result = Db::name('setting')->where($condition)->select();
		/**
		 * 整理
		 */
		$list_setting = [];
		if ($result){
			$list_setting = array();
			foreach ($result as $k => $v){
				$list_setting[$v['name']] = $v['value'];
			}
		}
		if($key){
		    return $list_setting[$key];
        }
		return $list_setting;
	}
	
	/**
	 * 更新信息
	 *
	 * @param array $param 更新数据
	 * @return bool 布尔类型的返回结果
	 */
	public function updateSetting($param){
		if (empty($param)){
			return false;
		}

		if (is_array($param)){
			foreach ($param as $k => $v){
				$tmp = array();
				$specialkeys_arr = array('statistics_code');
				$tmp['value'] = (in_array($k,$specialkeys_arr) ? htmlentities($v,ENT_QUOTES) : $v);
				$where = " name = '". $k ."'";
				$result = Db::update('setting',$tmp,$where);
				if ($result !== true){
					return $result;
				}
			}
			H('setting',true);
			delete_file(BASE_DATA_PATH.DS.'cache'.DS.'setting.php');
			return true;
		}else {
			return false;
		}
	}

    //添加或更新设置
    public function addSetOrUpdate($up){
        if(empty($up) || !is_array($up)) return false;
        foreach ($up as $name => $value) {
            //查询 没主键不能insert on duplicate
            $param = [];
            $param['table'] = 'setting';
            $param['field'] = 'name';
            $param['value'] = $name;
            $result = Db::getRow($param);
            if(empty($result)){
                //添加
                $add = [];
                $add['name'] = $name;
                $add['value'] = $value;
                $result = Db::insert('setting',$add);
                if($result !== true) return $result;
                continue;
            }
            //更新
            $up = [];
            $up['value'] = $value;
            $where = 'name = "'.$name.'"';
            $result = Db::update('setting',$up,$where);
            if($result !== true) return $result;
            continue;
        }
        delete_file(BASE_DATA_PATH.DS.'cache'.DS.'setting.php');
        H('setting',true);
        return true;
    }

    //通过获取的value值获取相应的数据存库
    public function getTimeZone(){
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

    public function setTimeZone($time_zone){
        $zonelist = $this->getTimeZone();
        return empty($zonelist[$time_zone]) ? 'Asia/Shanghai' : $zonelist[$time_zone];
    }

    public function getTimeZone2(){
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
        return $timezone;
    }
	
}
