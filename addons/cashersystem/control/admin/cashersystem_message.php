<?php
/**
 * 消息通知
 *
 */
defined('DYMall') or exit('Access Invalid!');
class cashersystem_messageCtl extends SystemCtl
{
    private $links = array(
        array('url' => 'app=message&mod=email', 'lang' => 'email_set'),
        array('url' => 'app=message&mod=mobile', 'lang' => 'mobile_set'),
        array('url' => 'app=message&mod=seller_tpl', 'lang' => 'seller_tpl'),
        array('url' => 'app=message&mod=dian_tpl', 'lang' => 'dian_tpl'),
        array('url' => 'app=message&mod=member_tpl', 'lang' => 'member_tpl'),
        array('url' => 'app=message&mod=email_tpl', 'lang' => 'email_tpl')
    );

    public function __construct()
    {
        parent::__construct();
        Language::read('setting,message','spreader');
    }

    /**
     * 获取/保存基本设置的信息（OSS对象存储、小程序、微信公众号、高德地图api）
     */
    public function getSldBaseInfo(){
        $model_setting = M('cashsys_setting');
        if($_POST['type'] == 'save'){
            //保存编辑信息
            $update_array = array();
            $update_array['cashsys_gzh_appid'] 	= $_POST['cashsys_gzh_appid'];
            $update_array['cashsys_gzh_secret'] 	= $_POST['cashsys_gzh_secret'];
            $result = $model_setting->updateSetting($update_array);
            if ($result === true){
                $this->log(L('bbc_edit,email_set'),1);
                echo json_encode(array('state'=>200,'msg'=>L('保存成功')));die;
            }else {
                $this->log(L('bbc_edit,email_set'),0);
                echo json_encode(array('state'=>255,'msg'=>L('保存失败')));die;
            }
        }else{
            $list_setting = $model_setting->getListSetting();
            $data = array();
            $data['cashsys_gzh_appid'] = $list_setting['cashsys_gzh_appid'];
            $data['cashsys_gzh_secret'] = $list_setting['cashsys_gzh_secret'];
            echo json_encode(array('list'=>$data));die;
        }
    }
    /**
     * 获取短信平台的设置信息
     */
    public function smsInfo(){
        $model_setting = M('cashsys_setting');
        if ($_POST[type] == 'save'){
            $update_array = array();
            $update_array['cashsys_mobile_tplid'] 		= $_POST['cashsys_mobile_tplid'];//云片短信模板id
            $update_array['cashsys_mobile_memo']   = $_POST['cashsys_mobile_memo'];//云片短信模板内容
            $update_array['cashsys_mobile_signature'] 	= $_POST['cashsys_mobile_signature'];//云片短信模板内容
            $result = $model_setting->updateSetting($update_array);
            if ($result === true){
                $this->log(L('bbc_edit,mobile_set'),1);
                echo json_encode(array('state'=>200,'msg'=>L('保存成功')));die;
            }else {
                $this->log(L('bbc_edit,mobile_set'),0);
                echo json_encode(array('state'=>255,'msg'=>L('保存失败')));die;
            }
        }else{
            $list_setting = $model_setting->getListSetting();
            $data = array();
            $data['cashsys_mobile_tplid'] = $list_setting['cashsys_mobile_tplid'];
            $data['cashsys_mobile_memo'] = $list_setting['cashsys_mobile_memo'];
            $data['cashsys_mobile_signature'] = $list_setting['cashsys_mobile_signature'];
            echo json_encode(array('list'=>$data));
        }
    }

}
