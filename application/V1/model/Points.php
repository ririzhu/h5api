<?php
namespace app\V1\model;

use think\Model;
use think\db;
class Points extends Model
{
    /**
     * 操作积分
     * @param  string $stage 操作阶段 regist(注册),login(登录),comments(评论),order(下单),system(系统),other(其他),pointorder(积分礼品兑换),app(同步积分兑换),purpose(积分抵扣),checkin(签到)
     * @param  array $insertarr 该数组可能包含信息 array('pl_memberid'=>'会员编号','pl_membername'=>'会员名称','pl_adminid'=>'管理员编号','pl_adminname'=>'管理员名称','pl_points'=>'积分','pl_desc'=>'描述','orderprice'=>'订单金额','order_sn'=>'订单编号','order_id'=>'订单序号','point_ordersn'=>'积分兑换订单编号');
     * @param  bool $if_repeat 是否可以重复记录的信息,true可以重复记录，false不可以重复记录，默认为true
     * @return bool
     */
    function savePointsLog($stage,$insertarr,$if_repeat = true){
        if (!$insertarr['pl_memberid']){
            echo 2;

            return false;
        }
        $data_msg['time'] = date('Y-m-d H:i:s');
        $data_msg['points_url'] = urlShop('points');
        //记录原因文字
        switch ($stage){
            case 'regist':
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '注册会员';
                }
                $insertarr['pl_points'] = intval($GLOBALS['setting_config']['points_reg']);
                break;
            case 'login':
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '会员登录';
                }
                $insertarr['pl_points'] = intval($GLOBALS['setting_config']['points_login']);
                break;
            case 'comments':
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '评论商品';
                }
                $insertarr['pl_points'] = intval($GLOBALS['setting_config']['points_comments']);
                break;
            case 'checkin':
                if (!isset($insertarr['pl_desc'])){
                    $insertarr['pl_desc'] = '会员签到';
                }
                //$insertarr['pl_points'] = intval($GLOBALS['setting_config']['sign_points']);
                break;
            case 'order':
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '订单'.$insertarr['order_sn'].'购物消费';
                }
                $insertarr['pl_points'] = 0;
                if ($insertarr['orderprice']){
                    $insertarr['pl_points'] = @intval($insertarr['orderprice']/$GLOBALS['setting_config']['points_orderrate']);
                    if ($insertarr['pl_points'] > intval($GLOBALS['setting_config']['points_ordermax'])){
                        $insertarr['pl_points'] = intval($GLOBALS['setting_config']['points_ordermax']);
                    }
                }
                //订单添加赠送积分列
                $obj_order = Model('order');
                $data = array();
                $data['order_pointscount'] = array('exp','order_pointscount+'.$insertarr['pl_points']);
                $obj_order->editOrderCommon($data,array('order_id'=>$insertarr['order_id']));
                break;
            case 'system':
                break;
            case 'pointorder':
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '兑换礼品信息'.$insertarr['point_ordersn'].'消耗积分';
                }
                break;
            case 'purpose':
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '支付订单抵扣';
                }
                break;
            case 'returnpurpose':
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '抵扣积分退回';
                }
                break;
            case 'app':
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = 'UC应用的积分兑入';
                }
                break;
            case 'inviter': //
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '邀请新会员['.$insertarr['invited'].']注册';
                }
                $insertarr['pl_points'] = intval($GLOBALS['setting_config']['points_invite']);
                break;
            case 'rebate0': //购买人自身返利
                if (!$insertarr['pl_desc']){
                    //$insertarr['pl_desc'] = '自己购买返利';
                    $insertarr['pl_desc'] = '订单'.$insertarr['order_sn'].'购物消费';
                }
                $insertarr['pl_points'] = $insertarr['rebate_amount'];
                break;
            case 'rebate1': //购买人一级上线返利
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '被邀请人['.$_SESSION['member_name'].']消费';
                }
                $insertarr['pl_points'] = $insertarr['rebate_amount'];
                break;
            case 'rebate2': //购买人二级上线（上上级）返利
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '被邀请人下级['.$_SESSION['member_name'].']消费';
                }
                $insertarr['pl_points'] = $insertarr['rebate_amount'];
                break;
            case 'rebate3': //购买人三级上线（上上上级）返利
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '被邀请人下下级['.$_SESSION['member_name'].']消费';
                }
                $insertarr['pl_points'] = $insertarr['rebate_amount'];
                break;

            case 'share_article': // 分享发现文章
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '分享发现文章获得的积分';
                }
                $insertarr['pl_points'] = intval($GLOBALS['setting_config']['share_article_points']);
                break;
            case 'share_goods': // 分享发现文章
                if (!$insertarr['pl_desc']){
                    $insertarr['pl_desc'] = '分享商品获得的积分';
                }
                $insertarr['pl_points'] = intval($GLOBALS['setting_config']['share_goods_points']);
                break;
            case 'other':
                break;
        }
        $save_sign = true;
        if ($if_repeat == false){
            //检测是否有相关信息存在，如果没有，入库
            $condition['pl_memberid'] = $insertarr['pl_memberid'];
            $condition['pl_stage'] = $stage;
            $log_array = self::getPointsInfo($condition,$page);
            if (!empty($log_array)){
                $save_sign = false;
            }
        }
        if ($save_sign == false){
            return true;
        }
        //新增日志
        $value_array = array();
        $value_array['pl_memberid'] = $insertarr['pl_memberid'];
        $value_array['pl_membername'] = $insertarr['pl_membername'];
        if (isset($insertarr['pl_adminid'])){
            $value_array['pl_adminid'] = $insertarr['pl_adminid'];
        }
        if (isset($insertarr['pl_adminname'])){
            $value_array['pl_adminname'] = $insertarr['pl_adminname'];
        }
        if (isset($insertarr['pl_points']))
        $value_array['pl_points'] = $insertarr['pl_points'];
        $value_array['pl_addtime'] = time();
        if (isset($insertarr['pl_desc']))
        $value_array['pl_desc'] = $insertarr['pl_desc'];
        $value_array['pl_stage'] = $stage;
        if (isset($insertarr['pl_extend_data'])) {
            $value_array['pl_extend_data'] = $insertarr['pl_extend_data'];
        }
        $result = false;
        if(isset($value_array['pl_points']) && $value_array['pl_points'] != '0'){
            $result = self::addPointsLog($value_array);
        }
        if ($result){
            //更新member内容
            $obj_member = new User();
            $upmember_array = array();
//			$upmember_array['member_points'] = array('sign'=>'increase','value'=>$insertarr['pl_points']);
//			$obj_member->updateMember($upmember_array,$insertarr['pl_memberid']);
            $upmember_array['member_points'] = array('exp','member_points+'.$insertarr['pl_points']);
            $obj_member->editMember(array('member_id'=>$insertarr['pl_memberid']),$upmember_array);

            $now_member_info = $obj_member->name('member')->where(array('member_id'=>$value_array['pl_memberid']))->field('member_points')->find();

            if ($value_array['pl_points'] > 0) {
                $data_msg['desc'] = $value_array['pl_desc'];
                $data_msg['points_amount'] = $value_array['pl_points'];
                // 发送买家消息
                $param = array();
                $param['code'] = 'points_change_notice';
                $param['member_id'] = $value_array['pl_memberid'];

                $data_msg['first'] =  '您好，您的积分账户有新的变化，具体内容如下：';
                $data_msg['keyword1'] =  date('Y年m月d日 H时i分',time());
                $data_msg['keyword2'] =  $data_msg['points_amount'];
                $data_msg['keyword3'] =  $data_msg['desc'];
                $data_msg['keyword4'] =  $now_member_info['member_points'];
                $data_msg['remark'] =  '感谢您的使用';
                $data_msg['url'] =  WAP_SITE_URL.'/cwap_pointslog_list.html';

                $param['param'] = $data_msg;
                $param['link']=$data_msg['points_url'];
                $param['system_type']=5;
                QueueClient::push('sendMemberMsg', $param);
            }

            return true;
        }else {
            return false;
        }

    }
    /**
     * 添加积分日志信息
     *
     * @param array $param 添加信息数组
     */
    public function addPointsLog($param) {
        if(empty($param)) {
            return false;
        }
        $result	= Db::name("points_log")->insert($param);
        echo Db::name("points_log")->getLastSql();
        return $result;
    }
    /**
     * 积分日志列表
     *
     * @param array $condition 条件数组
     * @param array $page   分页
     * @param array $field   查询字段
     * @param array $page   分页
     */
    public function getPointsLogList($condition,$page='',$field='*'){
        $condition_str	= $this->getCondition($condition);
        $param	= array();
        $param['table']	= 'points_log';
        $param['where']	= $condition_str;
        $param['field'] = $field;
        $param['order'] = $condition['order'] ? $condition['order'] : 'points_log.pl_id desc';
        if(isset($condition['limit']))
        $param['limit'] = $condition['limit'];
        if(isset($condition['group']))
        $param['group'] = $condition['group'];
        return Db::name("points_log")->field($field)->where($condition_str)->select();
    }
    /**
     * 积分日志详细信息
     *
     * @param array $condition 条件数组
     * @param array $field   查询字段
     */
    public function getPointsInfo($condition,$field='*'){
        //得到条件语句
        $condition_str	= $this->getCondition($condition);
        $array			= array();
        $list		= Db::name("points_log")->where($condition_str)->field($field)->find();
        return $list;
    }
    /**
     * 将条件数组组合为SQL语句的条件部分
     *
     * @param	array $condition_array
     * @return	string
     */
    private function getCondition($condition_array){
        $condition_sql = '1=1';
        //积分日志会员编号
        if (isset($condition_array['pl_memberid'])) {
            $condition_sql	.= " and `bbc_points_log`.pl_memberid = '{$condition_array['pl_memberid']}'";
        }
        //操作阶段
        if ($condition_array['pl_stage']) {
            if(is_array($condition_array['pl_stage'])){
                $condition_sql .= " and `bbc_points_log`.pl_stage in ( {$condition_array['pl_stage'][1]})";
            }else {
                $condition_sql .= " and `bbc_points_log`.pl_stage = '{$condition_array['pl_stage']}'";
            }
        }
        //会员名称
        if (isset($condition_array['pl_membername_like'])) {
            $condition_sql	.= " and `bbc_points_log`.pl_membername like '%{$condition_array['pl_membername_like']}%'";
        }
        //管理员名称
        if (isset($condition_array['pl_adminname_like'])) {
            $condition_sql	.= " and `bbc_points_log`.pl_adminname like '%{$condition_array['pl_adminname_like']}%'";
        }
        //添加时间
        if (isset($condition_array['saddtime'])){
            $condition_sql	.= " and `bbc_points_log`.pl_addtime >= '{$condition_array['saddtime']}'";
        }
        if (isset($condition_array['eaddtime'])){
            $condition_sql	.= " and `bbc_points_log`.pl_addtime <= '{$condition_array['eaddtime']}'";
        }
        //描述
        if (isset($condition_array['pl_desc_like'])){
            $condition_sql	.= " and `bbc_points_log`.pl_desc like '%{$condition_array['pl_desc_like']}%'";
        }
        // 预留数据
        if (isset($condition_array['pl_extend_data'])){
            $condition_sql	.= " and `bbc_points_log`.pl_extend_data = '{$condition_array['pl_extend_data']}'";
        }
        return $condition_sql;
    }
}