<?php
/**
 * 城市分站
 *
 */

use app\V1\model\Citysite;

defined('DYMall') or exit('Access Invalid!');
class city_site {

    //判断城市分站功能是否可用
    public function checkCityOpen(){
        return (Config('sld_city_site')&&Config('sld_city_site_isopen')?true:false);
    }

    //根据绑定的城市分站id获取对应的域名
    public function getDomainByBindId($bind_id){
        $city_model = Model('citysite');
        $model = Model('citysite');
        $condition = " province_id = '". $bind_id ."' or city_id = '". $bind_id ."' or area_id = '". $bind_id."'";
        $sldCurCity = $model -> getCitySite($condition);
        return $sldCurCity[0]['sld_city_domain'];
    }


    //根据域名获取分站的信息
    public function getDomainInfoByDomain($domain){
        $city_info = array();
        $model_city_site = new Citysite();
        $city_info = $model_city_site->getCitySite(array('sld_city_domain' => $domain));
        return $city_info;
    }

    //验证该域名是否允许访问
    public function checkDomain($domain){
        if($this->checkCityOpen()){
            //开启的情况下，但是不在当前分站范围内，要跳商城总站
            $city_info = $this->getDomainInfoByDomain($domain);
            if(empty($city_info)&&$domain!=C('main_url_main')){
                if(APP_ID == 'mall'){
                    @header("location: ".C('main_url'));
                }else{
                    return false;
                }
            }
        }else{
            //未开启的情况下  域名与商城总站域名不一致，要跳商城总站
            if($domain!=C('main_url_main')){
                if(APP_ID == 'mall'){
                    @header("location: ".C('main_url'));
                }else{
                    return false;
                }
            }
        }
        return true;
    }


    //根据域名获取对应的城市分站的信息(mall)
    public function getUrlCity($domain)
    {
        $city_info = $this->getDomainInfoByDomain($domain);
        if(empty($city_info)){
            return array();
        }else{
            return $city_info[0];
        }
    }

    //根据域名获取对应的城市分站绑定的分站id(绑定的最后一级id)
    public function getUrlCityBindId($domain){
        $bindid = $this -> getUrlCity($domain);
        if(empty($bindid)){
            return 0;
        }else{
            $bindid = $bindid['area_id']?$bindid['area_id']:($bindid['city_id']?$bindid['city_id']:$bindid['province_id']);
            return $bindid?$bindid:0;
        }
    }

    // 根据 定位获取的 信息 验证是否有分站
    public function checkCitySiteByPositionInfo($cityInfo){
        $condition = " province_id = '". $bind_id ."' or city_id = '". $bind_id ."' or area_id = '". $bind_id."'";
        $sldCurCity = $model -> getCitySite($condition);
    }

    //获取城市分站列表+热门推荐分站列表(开启的话获取，未开启的话为空)
    // $filter 筛选条件
    public function getCityAndHotList($filter=array()){
        $dataList = array();
        if($this->checkCityOpen()){

            $normal_condition['sld_city_isshow'] = 1;
            // 筛选条件
            if (isset($filter['keyword']) && $filter['keyword']) {
                $normal_condition['sld_city_site_name'] = array('LIKE','%'.$filter['keyword'].'%');
            }

            $city_model = Model('citysite');
            $sldCitySiteList = $city_model -> getCitySiteList($normal_condition);
            //获取热门推荐城市列表
            $sldHotCityList = $city_model -> getCitySiteList(array('sld_city_isshow'=>1,'sld_recommnd_hot'=>1));

            // 允许展示 全国站
            if (Config('sld_is_allow_back_mall') == 1) {
                $dataList['is_allow_show_default'] = true;
            }else{
                $dataList['is_allow_show_default'] = false;
            }

            //以首字母为键组装数据
            $sldCityList = array();
            if(!empty($sldCitySiteList)){
                foreach ($sldCitySiteList as $key => $val){
                    $sldCityList[$val['sld_bind_city_name_first_char']][] = $val;
                }
                ksort($sldCityList);//数组按键从低到高排序
            }
            $dataList['citylist'] = $sldCityList;
            $dataList['hotlist'] = $sldHotCityList ? $sldHotCityList : array();
        }
        return $dataList;
    }

}