<?php

namespace app\V1\model;

use think\Db;
use think\Exception;
use think\Model;

class User extends Model
{
    protected $table = 'bbc_member';

    public function login($token, $ip)
    {
        $user = model("user");
        $userinfo = $user->where('wx_openid', $token)->find()->toArray();
        return $userinfo;
    }

    /**
    * 查询当前昵称有没有被注册
     * @param $username
     * @return float|string
     */
    public function checkMember($username)
    {
        $user = model("user");
        $count = $user->where('member_name', $username)->count();
        return $count;
    }

    /**查询手机号有没有被注册
     * @param $mobile
     * @return float|string
     */

    public function checkMobile($mobile)
    {
        $user = model("user");
        $count = $user->where('member_mobile', $mobile)->count();
        return $count;
    }
    /**
     * 用户注册，不用手机号
     * $param Array $userData
     */
    public function insertMemberWithOutMobile($userData)
    {
        if($userData['inviteCode']!=""){
            $inviteIds = $this->getInviteIds($userData["inviteCode"]);
            $userData['inviter2_id'] = $inviteIds["inviter_id"];
            $userData['inviter3_id'] = $inviteIds["inviter2_id"];
        }
        Db::startTrans();
        try {
            $res = DB::table("bbc_member")->insert(['member_name'=>$userData["username"],"member_passwd"=>$userData["password"],'inviter_id'=>$userData["inviter_id"],'inviter3_id'=>$userData["inviter3_id"],'inviter3_id'=>$userData["inviter3_id"]]);
            return DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    /**
     * 获取推荐人的上级和上上级的id
     * $param int inviteid;
     **/
    public function getInviteIds($inviteId)
    {
       return $this->model("bbc_member")->where(["id"=>$inviteId])->field(["inviter_id","inviter2_id","inviter3_id"])->find()->toArray();
    }
    /**
     * 手机号注册
     * $param Array $userData
     */
    public function insertMemberWithMobile($userData)
    {
        if($userData['inviteCode']!=""){
            $inviteIds = $this->getInviteIds($userData["inviteCode"]);
            $userData['inviter2_id'] = $inviteIds["inviter_id"];
            $userData['inviter3_id'] = $inviteIds["inviter2_id"];
        }
        Db::startTrans();
        try {
            $res = DB::table("bbc_member")->insert(["member_mobile"=>$userData["member_mobile"],'inviter_id'=>$userData["inviter_id"],'inviter3_id'=>$userData["inviter3_id"],'inviter3_id'=>$userData["inviter3_id"]]);
            return DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    /**
     * 更改密码
     * $param int inviteid;
     */
    public function updatePwdWithMobile($userData)
    {
        Db::startTrans();
        try {
            $res = DB::table("bbc_member")->where(["member_mobile"=>$userData['member_mobile']])->update($userData);
            return DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * 注册商城会员
     *
     * @param	array $param 会员信息
     * @return	array 数组格式的返回结果
     */
    public function addMember($param) {
        if(empty($param)) {
            return false;
        }
        $member_info	= array();
        $member_info['member_id']			= $param['member_id'];
        $member_info['member_name']			= $param['member_name'];
        $member_info['member_passwd']		= md5(trim($param['member_passwd']));
        $member_info['member_time']			= time();
        $member_info['member_login_time'] 	= $member_info['member_time'];
        $member_info['member_old_login_time'] = $member_info['member_time'];
        $member_info['member_login_ip']		= getIp();
        $member_info['member_old_login_ip']	= $member_info['member_login_ip'];

        if(!empty($param['member_mobile'])){
            $member_info['member_mobile']		= $param['member_mobile'];
        }

        if(!empty($param['member_mobile_bind'])){
            $member_info['member_mobile_bind']		= $param['member_mobile_bind'];
        }

        if(!empty($param['member_email'])){
            $member_info['member_email']		= $param['member_email'];
            $member_info['member_email_bind']		= 1;
        }

        $member_info['member_truename']		= $param['member_truename'];
        $member_info['member_qq']			= $param['member_qq'];
        $member_info['member_sex']			= $param['member_sex'];
        $member_info['member_avatar']		= $param['member_avatar'];
        $member_info['member_qqopenid']		= $param['member_qqopenid'];
        $member_info['member_qqinfo']		= $param['member_qqinfo'];
        $member_info['member_sinaopenid']	= $param['member_sinaopenid'];
        $member_info['member_sinainfo']		= $param['member_sinainfo'];
        $member_info['inviter_id']	        = $param['inviter_id'];
        $member_info['inviter2_id']	        = $param['inviter2_id'];
        $member_info['inviter3_id']	        = $param['inviter3_id'];
        $member_info['parent_member_id']= $param['inviter_id'];//暂无
        if($param['wx_openid']){
            $member_info['wx_openid']	        = $param['wx_openid'];
            $member_info['wx_nickname']         = $param['wx_nickname'];
            $member_info['weixin_unionid']         = $param['wx_unionid'];
            $member_info['wx_area']             = $param['wx_area'];
        }
        if ($param['weixin_unionid']) {
            $member_info['weixin_unionid'] = $param['weixin_unionid'];
            $member_info['weixin_info'] = $param['weixin_info'];
            $member_info['wx_openid'] = $param['wx_openid'];
            $member_info['wx_nickname'] = $param['wx_nickname'];
            $member_info['wx_area']             = $param['wx_area'];
        }

        //czz
        $member_info['member_provinceid']	    = $param['member_provinceid'];
        $member_info['member_cityid']	        = $param['member_cityid'];
        $member_info['member_areaid']	        = $param['member_areaid'];
        $member_info['member_areainfo']	        = $param['member_areainfo'];
        $member_info['member_role']	        = $param['member_role'];

        $result	= Db::insert('member',$member_info);
        if($result) {
            $inderid = Db::getLastId();
            // 添加默认相册
//            $insert['ac_name']      = '买家秀';
//            $insert['member_id']    = $inderid;
//            $insert['ac_des']       = '买家秀默认相册';
//            $insert['ac_sort']      = 1;
//            $insert['is_default']   = 1;
//            $insert['upload_time']  = TIMESTAMP;
//            Model()->table('sns_albumclass')->insert($insert);
            return $inderid;
        } else {
            return false;
        }
    }
    /**
     * 获取会员信息
     *
     * @param	array $param 会员条件
     * @param	string $field 显示字段
     * @return	array 数组格式的返回结果
     */
    public function infoMember($param, $field='*') {
        if (empty($param)) return false;

        //得到条件语句
        $condition_str	= $this->getCondition($param);
        $param	= array();
        $param['table']	= 'member';
        $param['where']	= $condition_str;
        $param['field']	= $field;
        $param['limit'] = 1;
        $member_list	= Db::table("bbc_member")->select($param);
        $member_info	= $member_list[0];
        //if (intval($member_info['vid']) > 0){
            $param	= array();
            $param['table']	= 'store';
            $param['field']	= 'vid';
            //$param['value']	= $member_info['vid'];
            $field	= 'vid,store_name,grade_id';
            //$store_info	= Db::getRow($param,$field);
            //if (!empty($store_info) && is_array($store_info)){
                //$member_info['store_name']	= $store_info['store_name'];
                //$member_info['grade_id']	= $store_info['grade_id'];
            //}
        //}
        return $member_info;
    }

    /**
     * 更新会员信息
     *
     * @param	array $param 更改信息
     * @param	int $member_id 会员条件 id
     * @return	array 数组格式的返回结果
     */
    public function updateMember($param,$member_id) {
        if(empty($param)) {
            return false;
        }
        $update		= false;
        //得到条件语句
        $condition_str	= " member_id='{$member_id}' ";
        $update		= Db::update('member',$param,$condition_str);
        return $update;
    }
    /**
     * 编辑会员
     * @param array $condition
     * @param array $data
     */
    public function editMember($condition, $data) {
        $update = $this->table('member')->where($condition)->update($data);
        if ($update && $condition['member_id']) {
            dcache($condition['member_id'], 'member');
        }
        return $update;
    }
    /**
     * 插入扩展表信息
     * @param unknown $data
     * @return Ambigous <mixed, boolean, number, unknown, resource>
     */
    public function addMemberCommon($data) {
        return $this->table('member_common')->insert($data);
    }
    /**
     * 编辑会员扩展表
     * @param unknown $data
     * @param unknown $condition
     * @return Ambigous <mixed, boolean, number, unknown, resource>
     */
    public function editMemberCommon($data,$condition) {
        return $this->table('member_common')->where($condition)->update($data);
    }
    /**
     * 会员登录检查
     *
     */
    public function checkloginMember() {
        if($_SESSION['is_login'] == '1') {
            @header("Location: index.php");
            exit();
        }
    }

    /**
     * 检查会员是否允许举报商品
     *
     */
    public function isMemberAllowInform($member_id) {

        $condition = array();
        $condition['member_id'] = $member_id;
        $member_info = $this->infoMember($condition,'inform_allow');
        if(intval($member_info['inform_allow']) === 1) {
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * 将条件数组组合为SQL语句的条件部分
     *
     * @param	array $conditon_array
     * @return	string
     */
    private function getCondition($conditon_array){
        $condition_sql = '';
        if($conditon_array['member_id'] != '') {
            $condition_sql	.= " and member_id= '" .intval($conditon_array['member_id']). "'";
        }
        if(isset($conditon_array['member_name']) && $conditon_array['member_name'] != '') {
            $condition_sql	.= " and member_name='".$conditon_array['member_name']."'";
        }
        if(isset($conditon_array['member_passwd']) &&$conditon_array['member_passwd'] != '') {
            $condition_sql	.= " and member_passwd='".$conditon_array['member_passwd']."'";
        }
        //是否允许举报
        if(isset($conditon_array['inform_allow']) &&$conditon_array['inform_allow'] != '') {
            $condition_sql	.= " and inform_allow='{$conditon_array['inform_allow']}'";
        }
        //是否允许购买
        if(isset($conditon_array['is_buy']) &&$conditon_array['is_buy'] != '') {
            $condition_sql	.= " and is_buy='{$conditon_array['is_buy']}'";
        }
        //是否允许发言
        if(isset($conditon_array['is_allowtalk']) &&$conditon_array['is_allowtalk'] != '') {
            $condition_sql	.= " and is_allowtalk='{$conditon_array['is_allowtalk']}'";
        }
        //是否允许登录
        if(isset($conditon_array['member_state']) &&$conditon_array['member_state'] != '') {
            $condition_sql	.= " and member_state='{$conditon_array['member_state']}'";
        }
        if(isset($conditon_array['friend_list']) &&$conditon_array['friend_list'] != '') {
            $condition_sql	.= " and member_name IN (".$conditon_array['friend_list'].")";
        }
        if(isset($conditon_array['member_email']) &&$conditon_array['member_email'] != '') {
            $condition_sql	.= " and member_email='".$conditon_array['member_email']."'";
        }
        if(isset($conditon_array['no_member_id']) &&$conditon_array['no_member_id'] != '') {
            $condition_sql	.= " and member_id != '".$conditon_array['no_member_id']."'";
        }
        if(isset($conditon_array['like_member_name']) &&$conditon_array['like_member_name'] != '') {
            $condition_sql	.= " and member_name like '%".$conditon_array['like_member_name']."%'";
        }
        if(isset($conditon_array['like_member_email']) &&$conditon_array['like_member_email'] != '') {
            $condition_sql	.= " and member_email like '%".$conditon_array['like_member_email']."%'";
        }
        if(isset($conditon_array['like_member_truename']) &&$conditon_array['like_member_truename'] != '') {
            $condition_sql	.= " and member_truename like '%".$conditon_array['like_member_truename']."%'";
        }
        if(isset($conditon_array['in_member_id']) &&$conditon_array['in_member_id'] != '') {
            $condition_sql	.= " and member_id IN (".$conditon_array['in_member_id'].")";
        }
        if(isset($conditon_array['in_member_name']) &&$conditon_array['in_member_name'] != '') {
            $condition_sql	.= " and member_name IN (".$conditon_array['in_member_name'].")";
        }
        if(isset($conditon_array['member_qqopenid']) &&$conditon_array['member_qqopenid'] != '') {
            $condition_sql	.= " and member_qqopenid = '{$conditon_array['member_qqopenid']}'";
        }
        if(isset($conditon_array['member_sinaopenid']) &&$conditon_array['member_sinaopenid'] != '') {
            $condition_sql	.= " and member_sinaopenid = '{$conditon_array['member_sinaopenid']}'";
        }

        if(isset($conditon_array['inviter_id']) &&$conditon_array['inviter_id'] != '') {
            $condition_sql	.= " and inviter_id = '{$conditon_array['inviter_id']}'";
        }

        if(isset($conditon_array['inviter2_id']) &&$conditon_array['inviter2_id'] != '') {
            $condition_sql	.= " and inviter2_id = '{$conditon_array['inviter2_id']}'";
        }

        if(isset($conditon_array['inviter3_id']) &&$conditon_array['inviter3_id'] != '') {
            $condition_sql	.= " and inviter3_id = '{$conditon_array['inviter3_id']}'";
        }


        return $condition_sql;
    }

// 	/**
// 	 * 会员列表
// 	 *
// 	 * @param array $condition 检索条件
// 	 * @param obj $obj_page 分页对象
// 	 * @return array 数组类型的返回结果
// 	 */
// 	public function getMemberList($condition,$obj_page='',$field='*'){
// 		$condition_str = $this->getCondition($condition);
// 		$param = array();
// 		$param['table'] = 'member';
// 		$param['where'] = $condition_str;
// 		$param['order'] = $condition['order'] ? $condition['order'] : 'member_id desc';
// 		$param['field'] = $field;
// 		$param['limit'] = $condition['limit'];
// 		$member_list = Db::select($param,$obj_page);
// 		return $member_list;
// 	}

    /**
     * 删除会员
     *
     * @param int $id 记录ID
     * @return array $rs_row 返回数组形式的查询结果
     */
    public function del($id){
        if (intval($id) > 0){
            $where = " member_id = '". intval($id) ."'";
            $result = Db::delete('member',$where);
            return $result;
        }else {
            return false;
        }
    }
    /**
     * 查询会员总数
     */
    public function countMember($condition){
        //得到条件语句
        $condition_str	= $this->getCondition($condition);
        $count = Db::getCount('member',$condition_str);
        return $count;
    }

    /**
     * 获得会员等级
     * @param bool $show_progress 是否计算其当前等级进度
     * @param int $exppoints  会员经验值
     * @param array $cur_level 会员当前等级
     */
    public function getMemberGradeArr($show_progress = false,$exppoints = 0,$cur_level = ''){
        $member_grade = C('member_grade')?unserialize(C('member_grade')):array();
        //处理会员等级进度
        if ($member_grade && $show_progress){
            $is_max = false;
            if ($cur_level === ''){
                $cur_gradearr = $this->getOneMemberGrade($exppoints, false, $member_grade);
                $cur_level = $cur_gradearr['level'];
            }
            foreach ($member_grade as $k=>$v){
                if ($cur_level == $v['level']){
                    $v['is_cur'] = true;
                }
                $member_grade[$k] = $v;
            }
        }
        return $member_grade;
    }


    /**
     * 获得某一会员等级
     * @param int $growthvalue
     * @param bool $show_progress 是否计算其当前等级进度
     * @param array $member_grade 会员等级
     */
    public function getOneMemberGrade($growthvalue,$show_progress = false,$member_grade = array()){
        if (!$member_grade){
            $member_grade = C('member_grade')?unserialize(C('member_grade')):array();
        }
        if (empty($member_grade)){//如果会员等级设置为空
            $grade_arr['level'] = -1;
            $grade_arr['level_name'] = '暂无等级';
            return $grade_arr;
        }

        $growthvalue = intval($growthvalue);

        $grade_arr = array();
        if ($member_grade){
            foreach ($member_grade as $k=>$v){
                if($growthvalue >= $v['growthvalue']){
                    $grade_arr = $v;
                }
            }
        }
        //计算提升进度
        if ($show_progress == true){
            if (intval($grade_arr['level']) >= (count($member_grade) - 1)){//如果已达到顶级会员
                $grade_arr['downgrade'] = $grade_arr['level'] - 1;//下一级会员等级
                $grade_arr['downgrade_name'] = $member_grade[$grade_arr['downgrade']]['level_name'];
                $grade_arr['downgrade_exppoints'] = $member_grade[$grade_arr['downgrade']]['exppoints'];
                $grade_arr['upgrade'] = $grade_arr['level'];//上一级会员等级
                $grade_arr['upgrade_name'] = $member_grade[$grade_arr['upgrade']]['level_name'];
                $grade_arr['upgrade_exppoints'] = $member_grade[$grade_arr['upgrade']]['exppoints'];
                $grade_arr['less_exppoints'] = 0;
                $grade_arr['exppoints_rate'] = 100;
            } else {
                $grade_arr['downgrade'] = $grade_arr['level'];//下一级会员等级
                $grade_arr['downgrade_name'] = $member_grade[$grade_arr['downgrade']]['level_name'];
                $grade_arr['downgrade_exppoints'] = $member_grade[$grade_arr['downgrade']]['growthvalue'];
                $grade_arr['upgrade'] = $member_grade[$grade_arr['level']+1]['level'];//上一级会员等级
                $grade_arr['upgrade_name'] = $member_grade[$grade_arr['upgrade']]['level_name'];
                $grade_arr['upgrade_exppoints'] = $member_grade[$grade_arr['upgrade']]['growthvalue'];
                $grade_arr['less_exppoints'] = $grade_arr['upgrade_exppoints'] - $growthvalue;
                $grade_arr['exppoints_rate'] = round(($growthvalue - $member_grade[$grade_arr['level']]['exppoints'])/($grade_arr['upgrade_exppoints'] - $member_grade[$grade_arr['level']]['exppoints'])*100,2);
            }
        }
        return $grade_arr;
    }
    //修改用户密码
    public function getUserPassword($password=null,$user=null){
        $password=md5($password);
        $condition=array();
        $condtion['member_name']=$user;
        $update=array();
        $update=array('member_passwd'=>$password);
        return $this->table('member')->where($condtion)->update($update);
    }
    //修改用户密码_张金凤——2017.08.20
    public function getUserPassword_wap($password=null,$tel=null){
        $password=md5($password);
        $condition=array();
        $condtion['member_mobile']=$tel;
        $update=array();
        $update=array('member_passwd'=>$password);
        return $this->table('member')->where($condtion)->update($update);
    }
    //修改用户的预存款
    public function eidtPdrAmount($condition,$update){
        return $this->table('member')->where($condition)->update($update);
    }

    public function isMemberExist($member_id){
        return $this->table('member')->where($member_id);
    }

}