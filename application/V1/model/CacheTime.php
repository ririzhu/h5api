<?php
namespace app\V1\model;

use think\Model;
use think\db;
class CacheTime extends Model
{
// 允许存储 更新的类型(tuan,xianshi,p_mbuy,pin_tuan,man_song,zero_area)
    private $allow_types = array();

    public function __construct() {
        parent::__construct('cache_time');

        $this->allow_types = array('tuan','xianshi','p_mbuy','pin_tuan','pin_ladder_tuan','man_song','zero_area','sld_presale');
    }

    /**
     * 获取 缓存最新时间
     * @param array $type (tuan,xianshi,p_mbuy,pin_tuan,man_song,zero_area)
     *
     */
    public function getNewCacheTime($type){
        if ($type && in_array($type, $this->allow_types)) {
            $condition['cache_type'] = $type;
            //$last_new_time = $this->table("bbc_cache_time")->where($condition)->field('cache_time')->find();
            $last_new_time = DB::table("bbc_cache_time")->where($condition)->field('cache_time')->find();
            return $last_new_time['cache_time'] ;//? $last_new_time['cache_time'] : false;
        }
    }

    /**
     * 更新 缓存最新时间
     * @param string $type
     */
    public function saveNewCacheTime($type){
        $result = false;

        if ($type && in_array($type,$this->allow_types)) {

            $has_data = $this->getNewCacheTime($type);
            $data['cache_time'] = time();
            if ($has_data) {
                $condition['cache_type'] = $type;
                $result = $this->table('bbc_cache_time')->where($condition)->update($data);
            }else{
                $data['cache_type'] = $type;
                $result = $this->table('bbc_cache_time')->insert($data);
            }
        }

        if($result) {
            return $result;
        } else {
            return false;
        }

    }
}