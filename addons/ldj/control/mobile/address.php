<?php

/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/26
 * Time: 16:22
 */
class addressCtl extends mobileHomeCtl {
    private $member_info;
    public function __construct(){
        parent::__construct();
        if(!C('sld_ldjsystem') || !C('ldj_isuse') || !C('dian') || !C('dian_isuse')){
            echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
        }
        $model_mb_user_token = Model('mb_user_token');
        if($_POST['key']){
            $key = trim($_POST['key']);
        }else{
            $key = trim($_GET['key']);
        }
        if(!$key){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        if (empty($mb_user_token_info)) {
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }

        $model_member = Model('member');
        $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        if(empty($member_info)) {
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        } else {
            unset($member_info['member_passwd']);
            //读取卖家信息
            $this->member_info = $member_info;
        }
    }
    /* get
     * 获取会员列表
     * key
     */
    public function getmemberaddresslist()
    {
        $address_model = M('ldj_address','ldj');
        try{
            $where = [
                'member_id'=>$this->member_info['member_id']
            ];
            $list = $address_model->memberaddresslist($where);
            echo json_encode(['status'=>200,'data'=>$list]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /* get
     * 获取全国省列表
     * key
     */
    public function getprovinceinfo()
    {
        $address_model = M('ldj_address', 'ldj');
        try {
            $area_list = $address_model->querylist(['area_parent_id'=>0]);
            if(!$area_list){
                throw new Exception('暂无相关记录');
            }
        } catch (Exception $e) {
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
        echo json_encode(['status'=>200,'msg'=>$area_list]);die;
    }
    /* get
     * 获取地址下级列表
     * key
     * area_id
     */
    public function getareainfo()
    {
        $address_model = M('ldj_address', 'ldj');
        try {
            $area_list = $address_model->querylist(['area_parent_id'=>intval($_GET['area_id'])]);
            if(!$area_list){
                throw new Exception('暂无相关记录');
            }
        } catch (Exception $e) {
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
        echo json_encode(['status'=>200,'msg'=>$area_list]);die;
    }

    /*
     * 获取会员单条地址
     * key
     * address_id
     */
    public function getmemberaddressinfo()
    {
        $address_model = M('ldj_address','ldj');
        try{
            $where = [
                'address_id'=>intval($_GET['address_id']),
                'member_id'=>$this->member_info['member_id'],
            ];
            $address_info = $address_model->findmemberaddress($where);
            if(!$address_info){
                throw new Exception('暂无相关数据');
            }
            echo json_encode(['status'=>200,'data'=>$address_info]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /* get
     * 会员添加地址
     * key
     * true_name 真实姓名
     * province_id 省id
     * city_id 城市id
     * area_id 区id
     * area_info 地区内容
     * address 地区详细信息
     * address_precose 地区精准信息
     * mob_phone 手机
     * lng 经度
     * lat 纬度
     */
    public function insertaddress()
    {
        $address = M('ldj_address', 'ldj');
        $address->begintransaction();
        try {
            $province_id = intval($_GET['province_id']);
            $city = intval($_GET['city_id']);
            $area = intval($_GET['area_id']);
            $insert = [
                'member_id'=>$this->member_info['member_id'],
                'true_name'=>trim($_GET['true_name']),
                'province_id'=>$province_id,
                'city_id'=>$city,
                'area_id'=>$area,
                'area_info'=>trim($_GET['area_info']),
                'address'=>trim($_GET['address']),
                'address_precose'=>trim($_GET['address_precose']),
                'mob_phone'=>trim($_GET['mob_phone']),
                'lng'=>trim($_GET['lng']),
                'lat'=>trim($_GET['lat']),
                'is_default'=>1
            ];
            //开始之前先把默认地址去掉
            $address->editaddress(['member_id'=>$this->member_info['member_id'],'is_default'=>1],['is_default'=>0]);
            $address_id = $address->add($insert);
            if(!$address_id){
                throw new Exception('地址添加失败');
            }
            $address->commit();
            echo json_encode(['status'=>200,'msg'=>'地址添加成功']);die;
        } catch (Exception $e) {
            $address->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /*
     * 会员编辑地址
     * key
     * address_id
     * true_name 真实姓名
     * province_id 省id
     * city_id 城市id
     * area_id 区id
     * area_info 地区内容
     * address 地区详细信息
     * address_precose 地区精准信息
     * tel_phone 电话
     * mob_phone 手机
     * lng 经度
     * lat 纬度
     * is_default 是否默认
     */
    public function editaddress()
    {
        $address = M('ldj_address', 'ldj');
        $address->begintransaction();
        try {
            $province_id = intval($_GET['province_id']);
            $city = intval($_GET['city_id']);
            $area = intval($_GET['area_id']);
            $where = [
                'address_id'=>$_GET['address_id'],
                'member_id'=>$this->member_info['member_id'],
            ];
            $update = [
                'true_name'=>trim($_GET['true_name']),
                'province_id'=>$province_id,
                'area_id'=>$area,
                'city_id'=>$city,
                'area_info'=>trim($_GET['area_info']),
                'address'=>trim($_GET['address']),
                'address_precose'=>trim($_GET['address_precose']),
                'mob_phone'=>trim($_GET['mob_phone']),
                'tel_phone'=>trim($_GET['tel_phone']),
                'lng'=>trim($_GET['lng']),
                'lat'=>trim($_GET['lat']),
                'is_default'=>intval($_GET['is_default']),
            ];
            //开始之前先把默认地址去掉
            if($update['is_default'] == 1){
                $address->editaddress(['member_id'=>$this->member_info['member_id'],'is_default'=>1],['is_default'=>0]);
            }
            $address_id = $address->editaddress($where,$update);
            if(!$address_id){
                throw new Exception('地址修改失败');
            }
            $address->commit();
            echo json_encode(['status'=>200,'msg'=>'地址修改成功']);die;
        } catch (Exception $e) {
            $address->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /* get
     * 会员删除地址信息
     * key
     * address_id
     */
    public function deladdress()
    {
        $address_model = M('ldj_address','ldj');
        try{
            $where = [
                'member_id'=>$this->member_info['member_id'],
                'address_id'=>intval($_GET['address_id']),
                'is_default'=>1,
            ];
            $is_has = $address_model->findmemberaddress($where);
            if($is_has){
                throw new Exception('默认地址不能删除');
            }
            unset($where['is_default']);
            $res = $address_model->dropaddress($where);
            if(!$res){
                throw new Exception('删除失败');
            }
            echo json_encode(['status'=>200,'msg'=>'删除成功']);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
        /**
     * @api {get} index.php?app=address&mod=addarea&sld_addons=ldj 会员通过地图添加地址
     * @apiVersion 0.1.0
     * @apiName addarea
     * @apiGroup Address
     * @apiDescription 会员通过地图添加地址
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=address&mod=addarea&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} true_name 真实姓名
     * @apiParam {String} province 省
     * @apiParam {String} city  市
     * @apiParam {String} area  区
     * @apiParam {String} address  地址信息说明
     * @apiParam {String} mob_phone 手机号
     * @apiParam {String} lng  经度
     * @apiParam {String} lat  纬度
     * @apiSuccess {Number} status 状态
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *         status:200
     *         'msg':'操作成功'
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "地址添加失败"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function addarea()
    {
        $address = M('ldj_address', 'ldj');
        $address->begintransaction();
        try {
            $province = trim($_GET['province']);
            $city = trim($_GET['city']);
            $area = trim($_GET['area']);
            if ($province == $city) {
                $province = mb_substr($province, 0, -1, 'utf-8');
            }
            $where = [
                'area_name' => ['exp', 'area_name="' . $province . '" or area_name="' . $city . '" or area_name="' . $area . '"']
            ];
            $address_list = $address->key('area_id')->querylist($where);
            if (count($address_list) != 3) {
                throw new Exception('地址选择失败');
            }
            //按省市区排序
            ksort($address_list,1);
            $address_list = array_values($address_list);
            //取出省市区
            $area_info = low_array_column($address_list,'area_name');
            $insert = [
                'member_id'=>$this->member_info['member_id'],
                'true_name'=>trim($_GET['true_name']),
                'area_id'=>$address_list[2]['area_id'],
                'city_id'=>$address_list[1]['area_id'],
                'area_info'=>implode(' ',$area_info),
                'address'=>trim($_GET['address']),
                'mob_phone'=>trim($_GET['mob_phone']),
                'lng'=>trim($_GET['lng']),
                'lat'=>trim($_GET['lat']),
                'is_default'=>1,
            ];
            //开始之前先把默认地址去掉
            $address->editaddress(['member_id'=>$this->member_info['member_id'],'is_default'=>1],['is_default'=>0]);
            $address_id = $address->add($insert);
            if(!$address_id){
                throw new Exception('地址添加失败');
            }
            $address->commit();
            echo json_encode(['status'=>200,'msg'=>'地址添加成功']);die;
        } catch (Exception $e) {
            $address->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }

}