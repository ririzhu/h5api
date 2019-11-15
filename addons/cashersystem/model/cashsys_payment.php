<?php
/**
 * 支付方式
 *
 * 
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class cashsys_paymentModel extends Model {
    /**
     * 开启状态标识
     * @var unknown
     */
    const STATE_OPEN = 1;
    
    public function __construct() {
        parent::__construct('cashsys_payment');
    }

	/**
	 * 读取单行信息
	 *
	 * @param
	 * @return array 数组格式的返回结果
	 */
	public function getPaymentInfo($condition = array()) {
		return $this->where($condition)->find();
	}

	/**
	 * 读开启中的取单行信息
	 *
	 * @param
	 * @return array 数组格式的返回结果
	 */
	public function getPaymentOpenInfo($condition = array()) {
	    $condition['payment_state'] = self::STATE_OPEN;
	    return $this->where($condition)->find();
	}
	
	/**
	 * 读取多行
	 *
	 * @param 
	 * @return array 数组格式的返回结果
	 */
	public function getPaymentList($condition = array()){
        return $this->where($condition)->select();
	}
	
	/**
	 * 读取开启中的支付方式
	 *
	 * @param
	 * @return array 数组格式的返回结果
	 */
	public function getPaymentOpenList($condition = array()){
	    $condition['payment_state'] = self::STATE_OPEN;
	    return $this->where($condition)->key('payment_code')->select();
	}
	
	/**
	 * 更新信息
	 *
	 * @param array $param 更新数据
	 * @return bool 布尔类型的返回结果
	 */
	public function editPayment($data, $condition){
		return $this->where($condition)->update($data);
	}

}
