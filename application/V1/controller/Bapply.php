<?php
namespace app\v1\controller;

use app\v1\model\Document;
use app\v1\model\GoodsClass;
use app\v1\model\UploadFile;
use app\v1\model\VendorCategory;
use app\v1\model\VendorJoinIn;
use think\Db;
use function Composer\Autoload\includeFile;

class Bapply extends Base
{
    private $joinin_detail = NULL;

    public function __construct() {
        parent::__construct();


    }

    /**
     * @return false|string
     * 审核状态
     */
    public function check_apply_state() {
        $model_store_joinin = new VendorJoinIn();
        $joinin_detail = $model_store_joinin->getOne(array('member_id'=>input("member_id")));
        $data['error_code'] = 200;
        if(!empty($joinin_detail)) {
            $this->joinin_detail = $joinin_detail;
            switch (intval($joinin_detail['joinin_state'])) {
                case STORE_JOIN_STATE_NEW:
                    $data['status']=0;
                    $data['message']=lang('入驻申请已经提交，请等待管理员审核');
                    break;
                case STORE_JOIN_STATE_PAY:
                    $data['status']=2;
                    $data['message']=lang('已经提交，请等待管理员核对后为您开通店铺');
                    break;
                case STORE_JOIN_STATE_VERIFY_SUCCESS:
                    //if(!in_array($_GET['mod'], array('pay', 'pay_save'))) {
                    $data['status']=3;
                    $data['message']=lang('审核成功，请完成付款，付款后点击下一步提交付款凭证');
                    //}
                    break;
                case STORE_JOIN_STATE_VERIFY_FAIL:
                    //if(!in_array($_GET['mod'], array('step1', 'step2', 'step3', 'step4s'))) {
                    $data['status']=4;
                    $data['message']=lang('审核失败:').$joinin_detail['joinin_message'];
                    //}
                    break;
                case STORE_JOIN_STATE_PAY_FAIL:
                    //if(!in_array($_GET['mod'], array('pay', 'pay_save'))) {
                    $data['status']=5;
                    $data['message']=lang('付款审核失败:').$joinin_detail['joinin_message'];
                    //}
                    break;
                case STORE_JOIN_STATE_FINAL:
                    //@header('location: index.php?app=seller_login');
                    $data['status']=1;
                    $data['message']=lang('审核通过');
                    break;
            }
        }
        return json_encode($data,true);
    }



    public function step0() {
        $model_document = new Document();
        $document_info = $model_document->getOneByCode('open_store');
        $data['html'] = $document_info['doc_content'];
        return json_encode($data,true);
    }



    public function apply() {
        if(!empty($_POST)) {
            $param = array();
            $param['member_name'] = input('member_name');
            $param['company_name'] = $_POST['company_name'];
            if(isset($_POST['province_id'])) {
                $param['company_province_id'] = intval($_POST['province_id']);
                $param['company_city_id'] = $_POST['city_id'];
                $param['company_area_id'] = $_POST['area_id'];
            }
            $param['company_address'] = $_POST['company_address_detail'];
            $param['company_address_detail'] = $_POST['company_address_detail'];
            //$param['company_phone'] = $_POST['company_phone'];
            //$param['company_employee_count'] = intval($_POST['company_employee_count']);
            //$param['company_registered_capital'] = intval($_POST['company_registered_capital']);
            $param['contacts_name'] = $_POST['contacts_name'];
            $param['contacts_phone'] = $_POST['contacts_phone'];
            $param['contacts_email'] = $_POST['contacts_email'];
            $param['business_licence_number'] = $_POST['business_licence_number'];//营业执照号
            //$param['business_licence_address'] = $_POST['business_licence_address'];
            //$param['business_licence_start'] = $_POST['business_licence_start'];
            //$param['business_licence_end'] = $_POST['changqi'] ? $_POST['business_licence_start'] : $_POST['business_licence_end'];
            $param['business_sphere'] = $_POST['contacts_name'];
            //法人信息-start
            //$param['legal_person_name'] = $_POST['legal_person_name'];
            $param['legal_licence_number'] = $_POST['legal_licence_number'];
            //$param['legal_licence_start'] = $_POST['legal_licence_start'];
            //$param['legal_licence_end'] = $_POST['legal_licence_end'];
            $base = new Base();
            $save_path = rtrim($base->setPath(),DS);
            $param['legal_licence_zheng_electronic'] = $this->upload_base64(input('legal_licence_zheng_electronic'),BASE_UPLOAD_PATH.DS.$save_path.DS);
            $param['legal_licence_fan_electronic'] = $this->upload_base64(input('legal_licence_fan_electronic'),BASE_UPLOAD_PATH.DS.$save_path.DS);
            //法人信息-end

            $param['business_licence_number_electronic'] = $this->upload_base64(input('business_licence_number_electronic'),BASE_UPLOAD_PATH.DS.$save_path.DS);//营业执照电子版
            //$param['vendor_add_img1'] = $this->upload_image('vendor_add_img1');
            //$param['vendor_add_img2'] = $this->upload_image('vendor_add_img2');
            //$param['vendor_add_img3'] = $this->upload_image('vendor_add_img3');
            //$this->step2_save_valid($param);

            $model_store_joinin = new VendorJoinIn();
            $joinin_info = $model_store_joinin->getOne(array('member_id' => input("member_id")));
            if(empty($joinin_info)) {
                $param['member_id'] = input("member_id");
                db::name("vendor_joinin")->insert($param);
            } else {
                $model_store_joinin->modify($param, array('member_id'=>input("member_id")));
            }
            //$param['bank_account_name'] = $_POST['bank_account_name'];
            //$param['bank_account_number'] = $_POST['bank_account_number'];
            //$param['bank_name'] = $_POST['bank_name'];
            //$param['bank_code'] = $_POST['bank_code'];
            //$param['bank_address'] = $_POST['bank_address'];
            //$param['bank_licence_electronic'] = $this->upload_image('bank_licence_electronic');
            if(!empty($_POST['is_settlement_account'])) {
                $param['is_settlement_account'] = 1;
                $param['settlement_bank_account_name'] = $_POST['bank_account_name'];
                $param['settlement_bank_account_number'] = $_POST['bank_account_number'];
                $param['settlement_bank_name'] = $_POST['bank_name'];
                $param['settlement_bank_code'] = $_POST['bank_code'];
                $param['settlement_bank_address'] = $_POST['bank_address'];
            } else {
                $param['is_settlement_account'] = 2;
                $param['settlement_bank_account_name'] = $_POST['settlement_bank_account_name'];
                $param['settlement_bank_account_number'] = $_POST['settlement_bank_account_number'];
                $param['settlement_bank_name'] = $_POST['settlement_bank_name'];
                $param['settlement_bank_code'] = $_POST['settlement_bank_code'];
                $param['settlement_bank_address'] = $_POST['settlement_bank_address'];

            }
            $store_class_ids = array();
            $store_class_names = array();
            /*if(!empty($_POST['store_class_ids'])) {
                foreach ($_POST['store_class_ids'] as $value) {
                    $store_class_ids[] = $value;
                }
            }
            if(!empty($_POST['store_class_names'])) {
                foreach ($_POST['store_class_names'] as $value) {
                    $store_class_names[] = $value;
                }
            }*/
            //取最小级分类最新分佣比例
            $sc_ids = array();
            foreach ($store_class_ids as $v) {
                $v = explode(',',trim($v,','));
                if (!empty($v) && is_array($v)) {
                    $sc_ids[] = end($v);
                }
            }
            if (!empty($sc_ids)) {
                $store_class_commis_rates = array();
                $gc = new GoodsClass();
                $goods_class_list = $gc->getGoodsClassListByIds($sc_ids);
                if (!empty($goods_class_list) && is_array($goods_class_list)) {
                    $sc_ids = array();
                    foreach ($goods_class_list as $v) {
                        $store_class_commis_rates[] = $v['commis_rate'];
                    }
                }
            }

//        新增根据店铺分类id获取店铺分类名称
            //店铺分类
            $model_store = new VendorCategory();

            $store_class_list = $model_store->getStoreClassList(array('sc_id'=>array('in',$_POST['sc_id'])));
//        dd($model_store->getLastSql());die;
//        dd($store_class_list);die;
            $sc_id = '';
            $sc_name = '';
            foreach($store_class_list as $k=>$v){
                $sc_id .= $v['sc_id'].',';
                $sc_name .= $v['sc_name'].',';
            }
            $sc_id = rtrim($sc_id, ',');
            $sc_name = rtrim($sc_name, ',');

            $param = array();
            $param['seller_name'] = $_POST['seller_name'];
            $param['store_name'] = $_POST['store_name'];
            $param['store_class_ids'] = serialize($store_class_ids);
            $param['store_class_names'] = serialize($store_class_names);
            $param['sg_name'] = $_POST['sg_name'];
            $param['joinin_year'] = 9999;//intval($_POST['joinin_year']);
            $param['joinin_state'] = STORE_JOIN_STATE_NEW;
            $param['store_class_commis_rates'] = implode(',', $store_class_commis_rates);
            $param['sg_id'] = 1;//$_POST['sg_id'];
            $param['sg_id'] = 1;//$sc_id;
//        $param['sc_name'] = $_POST['sc_name'];
            $param['sc_name'] = $sc_name;
            $param['sc_id'] = $_POST['sc_id'];
            $param['joinin_state'] = STORE_JOIN_STATE_NEW;
            //取店铺等级信息
            $grade_list = db::name("store_grade")->select();
            if (!empty($grade_list[1])) {
                $param['sg_id'] = 1;
                $param['sg_name'] = $grade_list[1]['sg_name'];
                $param['sg_info'] = serialize(array('sg_price' => $grade_list[1]['sg_price']));
            }


            $model_store_joinin = new VendorJoinIn();
            db::name("vendor_join")->insert($param);
            $data['error_code']=200;
            $data['message']=lang("申请成功，请等待平台审核");
            return json_encode($data,true);
            //$model_store_joinin->modify($param, array('member_id'=>input("member_id")));
        }
        exit;
    }

    private function step2_save_valid($param) {
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$param['company_name'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("公司名称不能为空且必须小于50个字")),
            array("input"=>$param['company_address'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("公司地址不能为空且必须小于50个字")),
            array("input"=>$param['company_address_detail'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("公司详细地址不能为空且必须小于50个字")),
            array("input"=>$param['company_phone'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"20","message"=>Language::get("公司电话不能为空")),
            array("input"=>$param['company_employee_count'], "require"=>"true","validator"=>"Number",Language::get("员工总数不能为空且必须是数字")),
            array("input"=>$param['company_registered_capital'], "require"=>"true","validator"=>"Number",Language::get("注册资金不能为空且必须是数字")),
            array("input"=>$param['contacts_name'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"20","message"=>Language::get("联系人姓名不能为空且必须小于20个字")),
            array("input"=>$param['contacts_phone'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"20","message"=>Language::get("联系人电话不能为空")),
            array("input"=>$param['contacts_email'], "require"=>"true","validator"=>"email","message"=>Language::get("电子邮箱不能为空")),
            array("input"=>$param['business_licence_number'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"20","message"=>Language::get("营业执照号不能为空且必须小于20个字")),
            array("input"=>$param['business_licence_address'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("营业执照所在地不能为空且必须小于50个字")),
            array("input"=>$param['business_licence_start'], "require"=>"true","message"=>Language::get("营业执照有效期不能为空")),
            array("input"=>$param['business_licence_end'], "require"=>"true","message"=>Language::get("营业执照有效期不能为空")),
            array("input"=>$param['business_sphere'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"500","message"=>Language::get("法定经营范围不能为空且必须小于50个字")),
            array("input"=>$param['business_licence_number_electronic'], "require"=>"true","message"=>Language::get("营业执照电子版不能为空")),

            array("input"=>$param['legal_person_name'], "require"=>"true","message"=>Language::get("法人姓名不能为空")),
            array("input"=>$param['legal_licence_number'], "require"=>"true","message"=>Language::get("法人身份证号不能为空")),
            array("input"=>$param['legal_licence_start'], "require"=>"true","message"=>Language::get("证件有效期开始日志不能为空")),
            array("input"=>$param['legal_licence_end'], "require"=>"true","message"=>Language::get("证件有效期截止日期不能为空")),
            array("input"=>$param['legal_licence_zheng_electronic'], "require"=>"true","message"=>Language::get("法人证件正面电子版不能为空")),
            array("input"=>$param['legal_licence_fan_electronic'], "require"=>"true","message"=>Language::get("法人证件反面电子版不能为空")),



        );
        $error = $obj_validate->validate();
        if ($error != ''){
            showMsg($error);
        }
    }

    public function step3() {
        if(!empty($_POST)) {
            $param = array();
            $param['bank_account_name'] = $_POST['bank_account_name'];
            $param['bank_account_number'] = $_POST['bank_account_number'];
            $param['bank_name'] = $_POST['bank_name'];
            $param['bank_code'] = $_POST['bank_code'];
            $param['bank_address'] = $_POST['bank_address'];
            $param['bank_licence_electronic'] = $this->upload_image('bank_licence_electronic');
            if(!empty($_POST['is_settlement_account'])) {
                $param['is_settlement_account'] = 1;
                $param['settlement_bank_account_name'] = $_POST['bank_account_name'];
                $param['settlement_bank_account_number'] = $_POST['bank_account_number'];
                $param['settlement_bank_name'] = $_POST['bank_name'];
                $param['settlement_bank_code'] = $_POST['bank_code'];
                $param['settlement_bank_address'] = $_POST['bank_address'];
            } else {
                $param['is_settlement_account'] = 2;
                $param['settlement_bank_account_name'] = $_POST['settlement_bank_account_name'];
                $param['settlement_bank_account_number'] = $_POST['settlement_bank_account_number'];
                $param['settlement_bank_name'] = $_POST['settlement_bank_name'];
                $param['settlement_bank_code'] = $_POST['settlement_bank_code'];
                $param['settlement_bank_address'] = $_POST['settlement_bank_address'];

            }

            $this->step3_save_valid($param);

            $model_store_joinin = Model('vendor_joinin');
            $model_store_joinin->modify($param, array('member_id'=>$_SESSION['member_id']));
        }

        //商品分类
        $gc	= Model('goods_class');
        $gc_list	= $gc->getClassList(array('gc_parent_id'=>'0'),null,LANG_TYPE);
        Template::output('gc_list',$gc_list);

        //店铺等级
        $grade_list = ($setting = H('store_grade')) ? $setting : H('store_grade',true);
        //附加功能
        if(!empty($grade_list) && is_array($grade_list)){
            foreach($grade_list as $key=>$grade){
                $sg_function = explode('|',$grade['sg_function']);
                if (!empty($sg_function[0]) && is_array($sg_function)){
                    foreach ($sg_function as $key1=>$value){
                        if ($value == 'editor_multimedia'){
                            $grade_list[$key]['function_str'] .= Language::get('富文本编辑器');
                        }
                    }
                }else {
                    $grade_list[$key]['function_str'] = Language::get('无');
                }
            }
        }
        Template::output('grade_list', $grade_list);

        //店铺分类
        $model_store = Model('vendor_category');
        $store_class = $model_store->getTreeClassList(2);
        if (!empty($store_class) && is_array($store_class)){
            foreach ($store_class as $k => $v){
                $store_class[$k]['sc_name'] = str_repeat("&nbsp;",$v['deep']*2).$v['sc_name'];
            }
        }
        Template::output('vendor_category', $store_class);

        Template::output('step', '3');
        Template::output('sub_step', 'step3');
        Template::showpage('bapply');
        exit;
    }

    private function step3_save_valid($param) {
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
//            array("input"=>$param['bank_account_name'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("银行开户名不能为空且必须小于50个字")),
//            array("input"=>$param['bank_account_number'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"23","message"=>Language::get("银行账号不能为空且必须小于23个字")),
//            array("input"=>$param['bank_name'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("开户银行支行名称不能为空且必须小于50个字")),
//            array("input"=>$param['bank_code'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"23","message"=>Language::get("支行联行号不能为空且必须小于23个字")),
//            array("input"=>$param['bank_address'], "require"=>"true",Language::get("开户行所在地不能为空")),
//            array("input"=>$param['bank_licence_electronic'], "require"=>"true",Language::get("开户银行许可证电子版不能为空")),
//            array("input"=>$param['settlement_bank_account_name'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("银行开户名不能为空且必须小于50个字")),
//            array("input"=>$param['settlement_bank_account_number'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"23","message"=>Language::get("银行账号不能为空且必须小于23个字")),
//            array("input"=>$param['settlement_bank_name'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("开户银行支行名称不能为空且必须小于50个字")),
//            array("input"=>$param['settlement_bank_code'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"23","message"=>Language::get("支行联行号不能为空且必须小于23个字")),
//            array("input"=>$param['settlement_bank_address'], "require"=>"true",Language::get("开户行所在地不能为空")),
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            showMsg($error);
        }
    }

    private function check_seller_name_exist() {
        $condition = array();
        $condition['seller_name'] = $_GET['seller_name'];

        $model_seller = Model('seller');
        $result = $model_seller->isSellerExist($condition);

        if($result) {
            echo 'true';
        } else {
            echo 'false';
        }
    }


    public function step4s() {
//        echo "<pre>";
//        var_dump($_POST);die;
        $store_class_ids = array();
        $store_class_names = array();
        if(!empty($_POST['store_class_ids'])) {
            foreach ($_POST['store_class_ids'] as $value) {
                $store_class_ids[] = $value;
            }
        }
        if(!empty($_POST['store_class_names'])) {
            foreach ($_POST['store_class_names'] as $value) {
                $store_class_names[] = $value;
            }
        }
        //取最小级分类最新分佣比例
        $sc_ids = array();
        foreach ($store_class_ids as $v) {
            $v = explode(',',trim($v,','));
            if (!empty($v) && is_array($v)) {
                $sc_ids[] = end($v);
            }
        }
        if (!empty($sc_ids)) {
            $store_class_commis_rates = array();
            $goods_class_list = Model('goods_class')->getGoodsClassListByIds($sc_ids);
            if (!empty($goods_class_list) && is_array($goods_class_list)) {
                $sc_ids = array();
                foreach ($goods_class_list as $v) {
                    $store_class_commis_rates[] = $v['commis_rate'];
                }
            }
        }

//        新增根据店铺分类id获取店铺分类名称
        //店铺分类
        $model_store = Model('vendor_category');

        $store_class_list = $model_store->getStoreClassList(array('sc_id'=>array('in',$_POST['sc_id'])));
//        dd($model_store->getLastSql());die;
//        dd($store_class_list);die;
        $sc_id = '';
        $sc_name = '';
        foreach($store_class_list as $k=>$v){
            $sc_id .= $v['sc_id'].',';
            $sc_name .= $v['sc_name'].',';
        }
        $sc_id = rtrim($sc_id, ',');
        $sc_name = rtrim($sc_name, ',');

        $param = array();
        $param['seller_name'] = $_POST['seller_name'];
        $param['store_name'] = $_POST['store_name'];
        $param['store_class_ids'] = serialize($store_class_ids);
        $param['store_class_names'] = serialize($store_class_names);
        $param['sg_name'] = $_POST['sg_name'];
        $param['joinin_year'] = intval($_POST['joinin_year']);
        $param['joinin_state'] = STORE_JOIN_STATE_NEW;
        $param['store_class_commis_rates'] = implode(',', $store_class_commis_rates);
//        $param['sg_id'] = $_POST['sg_id'];
        $param['sg_id'] = $sc_id;
//        $param['sc_name'] = $_POST['sc_name'];
        $param['sc_name'] = $sc_name;
        $param['sc_id'] = $_POST['sc_id'];
        $param['joinin_state'] = STORE_JOIN_STATE_NEW;
        //取店铺等级信息
        $grade_list = rkcache('store_grade',true);
        if (!empty($grade_list[$_POST['sg_id']])) {
            $param['sg_id'] = $_POST['sg_id'];
            $param['sg_name'] = $grade_list[$_POST['sg_id']]['sg_name'];
            $param['sg_info'] = serialize(array('sg_price' => $grade_list[$_POST['sg_id']]['sg_price']));
        }
        //店铺应付款
        $param['paying_amount'] = floatval($grade_list[$_POST['sg_id']]['sg_price'])*$param['joinin_year'];
        $this->step4_save_valid($param);

        $model_store_joinin = Model('vendor_joinin');
        $model_store_joinin->modify($param, array('member_id'=>$_SESSION['member_id']));

        @header('location: index.php?app=bapply');

    }

    private function step4_save_valid($param) {
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$param['store_name'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"50","message"=>Language::get("店铺名称不能为空且必须小于50个字")),
            array("input"=>$param['sg_id'], "require"=>"true","message"=>Language::get("店铺等级不能为空")),
            array("input"=>$param['sc_id'], "require"=>"true","message"=>Language::get("店铺分类不能为空")),
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            showMsg($error);
        }
    }

    public function pay() {
        Template::output('joinin_detail', $this->joinin_detail);
        Template::output('step', 'step3');
        Template::showpage('applyjoinin_pay');
    }

    public function pay_save() {
        $param = array();
        $param['paying_money_certificate'] = $this->upload_image('paying_money_certificate');
        $param['paying_money_certificate_explain'] = $_POST['paying_money_certificate_explain'];
        $param['joinin_state'] = STORE_JOIN_STATE_PAY;

        if(empty($param['paying_money_certificate'])) {
            showMsg(Language::get('请上传付款凭证'),'','','error');
        }

        $model_store_joinin = Model('vendor_joinin');
        $model_store_joinin->modify($param, array('member_id'=>$_SESSION['member_id']));

        @header('location: index.php?app=bapply');
    }

    private function step4() {
        $model_store_joinin = Model('vendor_joinin');
        $joinin_detail = $model_store_joinin->getOne(array('member_id'=>$_SESSION['member_id']));
        $joinin_detail['store_class_ids'] = unserialize($joinin_detail['store_class_ids']);
        $joinin_detail['store_class_names'] = unserialize($joinin_detail['store_class_names']);
        $joinin_detail['store_class_commis_rates'] = explode(',', $joinin_detail['store_class_commis_rates']);
        $joinin_detail['sg_info'] = unserialize($joinin_detail['sg_info']);
        Template::output('joinin_detail',$joinin_detail);
    }



    private function show_join_message($message, $btn_next = FALSE, $step = 'step2') {
        Template::output('joinin_message', $message);
        Template::output('btn_next', $btn_next);
        Template::output('step', $step);
        Template::output('sub_step', 'step4');
        Template::showpage('bapply');
    }

    private function upload_image($file) {
        $pic_name = '';
        $upload = new UploadFile();
        $uploaddir = ATTACH_PATH.DS.'store_joinin'.DS;
        $upload->set('default_dir',$uploaddir);
        $upload->set('allow_type',array('jpg','jpeg','gif','png'));
        if (!empty($_FILES[$file]['name'])){
            $result = $upload->upfile($file);
            if ($result){
                $pic_name = $upload->file_name;
                $upload->file_name = '';
            }
        }
        return $pic_name;
    }
    private function upload_base64($base64_image_content,$path){
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $type = $result[2];
            $new_file = $path."/".date('Ymd',time())."/";
            if(!file_exists($new_file)){
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0700);
            }
            $new_file = $new_file.time().".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
                return $path.'/'.$new_file;
            }else{
                return false;
            }
        }else{
            return false;
        }

        //$re=@move_uploaded_file($this->upload_file['tmp_name'],BASE_UPLOAD_PATH.DS.$this->save_path.DS.$this->file_name);
    }

    /**
     * 检查店铺名称是否存在
     *
     * @param
     * @return
     */
    private function checkname() {
        if(!$this->checknameinner()) {
            echo 'false';
        } else {
            echo 'true';
        }
    }
    /**
     * 检查店铺名称是否存在
     *
     * @param
     * @return
     */
    private function checknameinner() {
        /**
         * 实例化卖家模型
         */
        $model_store	= Model('vendor');

        $store_name	= trim($_GET['store_name']);
        $store_info	= $model_store->getStoreInfo(array('store_name'=>$store_name));
        if($store_info['store_name'] != ''&&$store_info['member_id'] != $_SESSION['member_id']) {
            return false;
        } else {
            return true;
        }
    }
}