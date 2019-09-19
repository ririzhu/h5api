<?php
namespace app\v1\controller;

use app\v1\model\UploadFile;
use app\v1\model\VendorJoinIn;
use think\db;
class Capply extends Base
{
    public function apply(){
        if (!empty($_POST)) {
            $param = array();
            $param['member_name'] = input('member_name');//会员名称
            $param['company_name'] = $_POST['company_name'];//店铺名称
            $param['company_intro'] = input("company_intro");
            $param['company_address'] = $_POST['company_address_detail'];//详细地址
            $param['company_country_id'] = $_POST['country_id'];//国家
            $param['company_province_id'] = $_POST['province_id'];//省份
            $param['company_city_id'] = $_POST['city_id'];//城市
            $param['company_area_id'] = $_POST['area_id'];//区
            $param['company_address_detail'] = $_POST['company_address_detail'];
            $param['contacts_name'] = $_POST['contacts_name'];//联系人
            $param['contacts_phone'] = $_POST['contacts_phone'];//手机号
            $param['contacts_email'] = $_POST['contacts_email'];
            $param['business_licence_number'] = $_POST['business_licence_number'];//身份证号码
            $param['business_sphere'] = $param['contacts_name'];//$_POST['business_sphere'];//真实姓名
            $param['legal_licence_zheng_electronic'] = $this->upload_image('business_licence_number_electronic');//身份证正面
            $param['legal_licence_fan_electronic'] = $this->upload_image('business_licence_number_electronic_back');//身份证反面
           // $param['general_taxpayer'] = $this->upload_image('general_taxpayer');
            $param['store_label'] = $this->upload_image("store_label");//logo
            $code = input("code");
            $inviteCode = input("invite_code");
            //$param['settlement_bank_account_name'] = $_POST['settlement_bank_account_name'];
            //$param['settlement_bank_account_number'] = $_POST['settlement_bank_account_number'];
            if (!empty($_POST['store_class_ids'])) {
                foreach ($_POST['store_class_ids'] as $value) {
                    $store_class_ids[] = $value;
                }
            }
            if (!empty($_POST['store_class_names'])) {
                foreach ($_POST['store_class_names'] as $value) {
                    $store_class_names[] = $value;
                }
            }
            //$param = array();
            $param['seller_name'] = $_POST['member_name'];
            $param['store_name'] = $_POST['company_name'];//店铺名称
            $param['store_class_ids'] = serialize($store_class_ids);//经营类目ids
            $param['store_class_names'] = serialize($store_class_names);//经营类目名称列表
            $param['joinin_year'] = 9999;//intval($_POST['joinin_year']);
            $param['joinin_state'] = STORE_JOIN_STATE_NEW;
            $param['paying_amount'] = floatval(0) * 1;

            $model_store_joinin = new VendorJoinIn();
            $joinin_info = $model_store_joinin->getOne(array('member_id' => input("member_id")));
            if (empty($joinin_info)) {
                $param['member_id'] = input("member_id");
                db::name("vendor_joinin")->insert($param);
            } else {
                $model_store_joinin->modify($param, array('member_id' => input("member_id")));
            }
            $data['error_code'] = 200;
            $data['message'] = lang("入驻申请已经提交，请等待管理员审核");
            return json_encode($data,true);
        }
    }
    private function upload_image($file)
    {
        $pic_name = '';
        $upload = new UploadFile();
        $uploaddir = ATTACH_PATH . DS . 'store_joinin' . DS;
        $upload->set('default_dir', $uploaddir);
        $upload->set('allow_type', array('jpg', 'jpeg', 'gif', 'png'));
        if (!empty($_FILES[$file]['name'])) {
            $result = $upload->upfile($file);
            if ($result) {
                $pic_name = $upload->file_name;
                $upload->file_name = '';
            }
        }
        return $pic_name;
    }
}