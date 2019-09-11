<?php
/**
 * 购买行为
 *
 */
defined('DYMall') or exit('Access Invalid!');
class buy_1 {

    /**
     * 取得商品最新的属性及促销[购物车]
     * @param unknown $cart_list
     */
    public function getGoodsCartList($cart_list) {

        $cart_list = $this->_getOnlineCartList($cart_list);

        //优惠套装
        $this->_getBundlingCartList($cart_list);

        //抢购
        $this->getTuanCartList($cart_list);

        //限时折扣
        $this->getXianshiCartList($cart_list);

        //赠品
        $this->_getGiftCartList($cart_list);

        return $cart_list;

    }

    /**
     * 取得商品最新的属性及促销[立即购买]
     * @param int $gid
     * @param int $quantity
     * @return array
     */
    public function getGoodsOnlineInfo($gid,$quantity) {
        $goods_info = $this->_getGoodsOnlineInfo($gid,$quantity);

        //抢购
        $this->getTuanInfo($goods_info);

        //限时折扣
        $this->getXianshiInfo($goods_info,$goods_info['goods_num']);

        //赠品
        $this->_getGoodsGiftList($goods_info);

        return $goods_info;
    }

    /**
     * 商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
     * @param unknown $store_cart_list 以店铺ID分组的购物车商品信息
     * @return array
     */
    public function calcCartList($store_cart_list) {
        if (empty($store_cart_list) || !is_array($store_cart_list)) return array($store_cart_list,array(),0);

        //存放每个店铺的商品总金额
        $store_goods_total = array();
        //存放本次下单所有店铺商品总金额
        $order_goods_total = 0;
    
        foreach ($store_cart_list as $vid => $store_cart) {
            $tmp_amount = 0;
            foreach ($store_cart as $key => $cart_info) {
                $store_cart[$key]['goods_total'] = sldPriceFormat($cart_info['goods_price'] * $cart_info['goods_num']);
                $store_cart[$key]['goods_image_url'] = cthumb($store_cart[$key]['goods_image']);
                $tmp_amount += $store_cart[$key]['goods_total'];
            }
            $store_cart_list[$vid] = $store_cart;
            $store_goods_total[$vid] = sldPriceFormat($tmp_amount);
        }
        return array($store_cart_list,$store_goods_total);
    }

    /**
     * 取得店铺级优惠 - 跟据商品金额返回每个店铺当前符合的一条活动规则，如果有赠品，则自动追加到购买列表，价格为0
     * @param unknown $store_goods_total 每个店铺的商品金额小计，以店铺ID为下标
     * @return array($premiums_list,$mansong_rule_list) 分别为赠品列表[下标自增]，店铺满送规则列表[店铺ID为下标]
     */
    public function getMansongRuleCartListByTotal($store_goods_total) {
        if (!C('promotion_allow') || empty($store_goods_total) || !is_array($store_goods_total)) return array(array(),array());

        $model_mansong = Model('p_mansong');
        $model_goods = Model('goods');

        //定义赠品数组，下标为店铺ID
        $premiums_list = array();
        //定义满送活动数组，下标为店铺ID
        $mansong_rule_list = array();

        foreach ($store_goods_total as $vid => $goods_total) {
            $rule_info = $model_mansong->getMansongRuleByStoreID($vid,$goods_total);
            if (is_array($rule_info) && !empty($rule_info)) {
                //即不减金额，也找不到促销商品时(已下架),此规则无效
                if (empty($rule_info['discount']) && empty($rule_info['mansong_goods_name'])) {
                    continue;
                }
                $rule_info['desc'] = $this->_parseMansongRuleDesc($rule_info);
                $rule_info['discount'] = sldPriceFormat($rule_info['discount']);
                $mansong_rule_list[$vid] = $rule_info;
                //如果赠品在售,有库存,则追加到购买列表
                if (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage'])) {
                    $data = array();
                    $data['gid'] = $rule_info['gid'];
                    $data['goods_name'] = $rule_info['mansong_goods_name'];
                    $data['goods_num'] = 1;
                    $data['goods_price'] = 0.00;
                    $data['goods_image'] = $rule_info['goods_image'];
                    $data['goods_image_url'] = cthumb($rule_info['goods_image']);
                    $data['goods_storage'] = $rule_info['goods_storage'];
                    $premiums_list[$vid][] = $data;
                }
            }
        }
        return array($premiums_list,$mansong_rule_list);
    }

    /**
     * 重新计算每个店铺最终商品总金额(最初计算金额减去各种优惠/加运费)
     * @param array $store_goods_total 店铺商品总金额
     * @param array $preferential_array 店铺优惠活动内容
     * @param string $preferential_type 优惠类型，目前只有一个 'mansong'
     * @return array 返回扣除优惠后的店铺商品总金额
     */
    public function reCalcGoodsTotal($store_goods_total, $preferential_array, $preferential_type) {
        $deny = empty($store_goods_total) || !is_array($store_goods_total) || empty($preferential_array) || !is_array($preferential_array);
        if ($deny) return $store_goods_total;
    
        switch ($preferential_type) {
            case 'mansong':
                if (!C('promotion_allow')) return $store_goods_total;
                foreach ($preferential_array as $vid => $rule_info) {
                    if (is_array($rule_info) && $rule_info['discount'] > 0) {
                        $store_goods_total[$vid] -= $rule_info['discount'];
                    }
                }
                break;
    
            case 'voucher':
                if (!C('voucher_allow')) return $store_goods_total;
                foreach ($preferential_array as $vid => $voucher_info) {
                    $store_goods_total[$vid] -= $voucher_info['quan_price'];
                }
                break;
    
            case 'freight':
                foreach ($preferential_array as $vid => $freight_total) {
                    $store_goods_total[$vid] += $freight_total;
                }
                break;
        }
        return $store_goods_total;
    }

    /**
     * 取得店铺可用的优惠券
     * @param array $store_goods_total array(店铺ID=>商品总金额)
     * @return array
     */
    public function getStoreAvailableVoucherList($store_goods_total, $member_id) {
        if (!C('voucher_allow')) return $store_goods_total;
        $voucher_list = array();
        $model_voucher = Model('quan');
        foreach ($store_goods_total as $vid => $goods_total) {
            $condition = array();
            $condition['voucher_vid'] = $vid;
            $condition['voucher_owner_id'] = $member_id;
            $voucher_list[$vid] = $model_voucher->getCurrentAvailableVoucher($condition,$goods_total);
        }
        return $voucher_list;
    }

    /**
     * 验证传过来的优惠券是否可用有效，如果无效，直接删除
     * @param array $input_voucher_list 优惠券列表
     * @param array $store_goods_total (店铺ID=>商品总金额)
     * @return array
     */
    public function reParseVoucherList($input_voucher_list = array(), $store_goods_total = array(), $member_id) {
        if (empty($input_voucher_list) || !is_array($input_voucher_list)) return array();
        $store_voucher_list = $this->getStoreAvailableVoucherList($store_goods_total, $member_id);
        foreach ($input_voucher_list as $vid => $voucher) {
            $tmp = $store_voucher_list[$vid];
            if (is_array($tmp) && isset($tmp[$voucher['voucher_t_id']])) {
                $input_voucher_list[$vid]['voucher_id'] = $tmp[$voucher['voucher_t_id']]['voucher_id'];
                $input_voucher_list[$vid]['voucher_code'] = $tmp[$voucher['voucher_t_id']]['voucher_code'];
                $input_voucher_list[$vid]['voucher_owner_id'] = $tmp[$voucher['voucher_t_id']]['voucher_owner_id'];
            } else {
                unset($input_voucher_list[$vid]);
            }
        }
        return $input_voucher_list;
    }

    /**
     * 判断商品是不是限时折扣中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算
     * @param array $goods_info
     * @param number $quantity 购买数量
     */
    public function getXianshiInfo( & $goods_info, $quantity) {
        if (empty($quantity)) $quantity = 1;
        if (!C('promotion_allow') || empty($goods_info['xianshi_info'])) return ;
        $goods_info['xianshi_info']['down_price'] = sldPriceFormat($goods_info['goods_price'] - $goods_info['xianshi_info']['xianshi_price']);
        if ($quantity >= $goods_info['xianshi_info']['lower_limit']) {
            $goods_info['goods_price'] = $goods_info['xianshi_info']['xianshi_price'];
            $goods_info['promotions_id'] = $goods_info['xianshi_info']['xianshi_id'];
            $goods_info['ifxianshi'] = true;
        }
    }

    /**
     * 输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
     * @param $buy_list 商品列表
     * @return 返回 以支付方式为下标分组的商品列表
     */
    public function getOfflineGoodsPay($buy_list) {
        //以支付方式为下标，存放购买商品
        $buy_goods_list = array();
        $offline_pay = Model('payment')->getPaymentOpenInfo(array('payment_code'=>'offline'));
        if ($offline_pay) {
            //下单里包括平台自营商品并且平台已开启货到付款，则显示货到付款项及对应商品数量,取出支持货到付款的店铺ID组成的数组，目前就一个，DEFAULT_PLATFORM_STORE_ID
            $offline_store_id_array = model('store')->getOwnShopIds();
            foreach ($buy_list as $value) {
                //if (in_array($value['vid'],$offline_store_id_array)) {
                    $buy_goods_list['offline'][] = $value;
                //} else {
                //    $buy_goods_list['online'][] = $value;
                //}
            }
        }
        return $buy_goods_list;
    }

    /**
     * 计算每个店铺(所有店铺级优惠活动)总共优惠多少金额
     * @param array $store_goods_total 最初店铺商品总金额
     * @param array $store_final_goods_total 去除各种店铺级促销后，最终店铺商品总金额(不含运费)
     * @return array
     */
    public function getStorePromotionTotal($store_goods_total, $store_final_goods_total) {
        if (!is_array($store_goods_total) || !is_array($store_final_goods_total)) return array();
        $store_promotion_total = array();
        foreach ($store_goods_total as $vid => $goods_total) {
            $store_promotion_total[$vid] = abs($goods_total - $store_final_goods_total[$vid]);
        }
        return $store_promotion_total;
    }

    /**
     * 返回需要计算运费的店铺ID组成的数组 和 免运费店铺ID及免运费下限金额描述
     * @param array $store_goods_total 每个店铺的商品金额小计，以店铺ID为下标
     * @return array
     */
    public function getStoreFreightDescList($store_goods_total) {
        if (empty($store_goods_total) || !is_array($store_goods_total)) return array(array(),array());

        //定义返回数组
        $need_calc_sid_array = array();
        $cancel_calc_sid_array = array();

        //如果商品金额未达到免运费设置下线，则需要计算运费
        $condition = array('vid' => array('in',array_keys($store_goods_total)));
        $store_list = Model('vendor')->getStoreOnlineList($condition,null,'','vid,store_free_price');
        foreach ($store_list as $store_info) {
            $limit_price = floatval($store_info['store_free_price']);
            if ($limit_price == 0 || $limit_price > $store_goods_total[$store_info['vid']]) {
                //需要计算运费
                $need_calc_sid_array[] = $store_info['vid'];
            } else {
                //返回免运费金额下限
                $cancel_calc_sid_array[$store_info['vid']]['free_price'] = $limit_price;
                $cancel_calc_sid_array[$store_info['vid']]['desc'] = sprintf('满%s免运费',$limit_price);
            }
        }
        return array($need_calc_sid_array,$cancel_calc_sid_array);
    }

    /**
     * 取得店铺运费(使用运费模板的商品运费不会计算，但会返回模板信息)
     * 先将免运费的店铺运费置0，然后算出店铺里没使用运费模板的商品运费之和 ，存到iscalced下标中
     * 然后再计算使用运费模板的信息(array(店铺ID=>array(运费模板ID=>购买数量))，放到nocalced下标里
     * @param array $buy_list 购买商品列表
     * @param array $free_freight_sid_list 免运费的店铺ID数组
     */
    public function getStoreFreightList($buy_list = array(), $free_freight_sid_list) {
        //定义返回数组
        $return = array();
        //先将免运费的店铺运费置0(格式:店铺ID=>0)
        $freight_list = array();
        if (!empty($free_freight_sid_list) && is_array($free_freight_sid_list)) {
            foreach ($free_freight_sid_list as $vid) {
                $freight_list[$vid] = 0;
            }
        }

        //然后算出店铺里没使用运费模板(优惠套装商品除外)的商品运费之和(格式:店铺ID=>运费)
        //定义数组，存放店铺优惠套装商品运费总额 vid=>运费
        $store_bl_goods_freight = array();
        foreach ($buy_list as $key => $goods_info) {
            //免运费店铺的商品不需要计算
            if (in_array($goods_info['vid'], $free_freight_sid_list)) {
                unset($buy_list[$key]);
                continue;
            }
            //优惠套装商品运费另算
            if (intval($goods_info['bl_id'])) {
                unset($buy_list[$key]);
                $store_bl_goods_freight[$goods_info['vid']] = $goods_info['bl_id'];
                continue;
            }
            if (!intval($goods_info['transport_id']) &&  !in_array($goods_info['vid'],$free_freight_sid_list)) {
                $freight_list[$goods_info['vid']] += $goods_info['goods_freight'];
                unset($buy_list[$key]);
            }
        }
        //计算优惠套装商品运费
        if (!empty($store_bl_goods_freight)) {
            $model_bl = Model('p_bundling');
            foreach (array_unique($store_bl_goods_freight) as $vid => $bl_id) {
                $bl_info = $model_bl->getBundlingInfo(array('bl_id'=>$bl_id));
                if (!empty($bl_info)) {
                    $freight_list[$vid] += $bl_info['bl_freight'];
                }
            }
        }

        $return['iscalced'] = $freight_list;

        //最后再计算使用运费模板的信息(店铺ID，运费模板ID，购买数量),使用使用相同运费模板的商品数量累加
        $freight_list = array();
        foreach ($buy_list as $goods_info) {
            $freight_list[$goods_info['vid']][$goods_info['transport_id']] += $goods_info['goods_num'];
        }
        $return['nocalced'] = $freight_list;

        return $return;
    }

    /**
     * 根据地区选择计算出所有店铺最终运费
     * @param array $freight_list 运费信息(店铺ID，运费，运费模板ID，购买数量)
     * @param int $city_id 市级ID
     * @return array 返回店铺ID=>运费
     */
    public function calcStoreFreight($freight_list, $city_id) {
        if (!is_array($freight_list) || empty($freight_list) || empty($city_id)) return;

        //免费和固定运费计算结果
        $return_list = $freight_list['iscalced'];

        //使用运费模板的信息(array(店铺ID=>array(运费模板ID=>购买数量))
        $nocalced_list = $freight_list['nocalced'];

        //然后计算使用运费运费模板的在该$city_id时的运费值
        if (!empty($nocalced_list) && is_array($nocalced_list)) {
            //如果有商品使用的运费模板，先计算这些商品的运费总金额
            $model_transport = Model('transport');
            foreach ($nocalced_list as $vid => $value) {
                if (is_array($value)) {
                    foreach ($value as $transport_id => $buy_num) {
                        $freight_total = $model_transport->calc_transport($transport_id,$buy_num, $city_id);
                        if (empty($return_list[$vid])) {
                            $return_list[$vid] = $freight_total;
                        } else {
                            $return_list[$vid] += $freight_total;
                        }
                    }
                }
            }
        }

        return $return_list;
    }

    /**
     * 追加赠品到下单列表,并更新购买数量
     * @param array $store_cart_list 购买列表
     * @param array $store_premiums_list 赠品列表
     * @param array $store_mansong_rule_list 满即送规则
     */
    public function appendPremiumsToCartList($store_cart_list, $store_premiums_list = array(), $store_mansong_rule_list = array(), $member_id) {
        if (empty($store_cart_list)) return array();

        //处理商品级赠品
        foreach ($store_cart_list as $vid => $cart_list) {
            foreach ($cart_list as $cart_info) {
                if (empty($cart_info['gift_list'])) continue;
                if (!is_array($store_premiums_list)) $store_premiums_list = array();
                if (!array_key_exists($vid,$store_premiums_list)) $store_premiums_list[$vid] = array();
                $zenpin_info = array();
                foreach ($cart_info['gift_list'] as $gift_info) {
                    $zenpin_info['gid'] = $gift_info['gift_goodsid'];
                    $zenpin_info['goods_name'] = $gift_info['gift_goodsname'];
                    $zenpin_info['goods_image'] = $gift_info['gift_goodsimage'];
                    $zenpin_info['goods_storage'] = $gift_info['goods_storage'];
                    $zenpin_info['goods_num'] = $cart_info['goods_num'] * $gift_info['gift_amount'];
                    $store_premiums_list[$vid][] = $zenpin_info;
                }
            }
        }

        //取得每种商品的库存[含赠品]
        $goods_storage_quantity = $this->_getEachGoodsStorageQuantity($store_cart_list,$store_premiums_list);

        //取得每种商品的购买量[不含赠品]
        $goods_buy_quantity = $this->_getEachGoodsBuyQuantity($store_cart_list);
        foreach ($goods_buy_quantity as $gid => $quantity) {
            $goods_storage_quantity[$gid] -= $quantity;
            if ($goods_storage_quantity[$gid] < 0) {
                //商品库存不足，请重购买
                return false;
            }
        }
        //将赠品追加到购买列表
        
        if(is_array($store_premiums_list)) {
            foreach ($store_premiums_list as $vid => $goods_list) {
                $zp_list = array();
                $gift_desc = '';
                foreach ($goods_list as $goods_info) {
                    //如果没有库存了，则不再送赠品
                    if ($goods_storage_quantity[$goods_info['gid']] == 0) {
                        $gift_desc = '，赠品库存不足，未能全部送出 ';
                        continue;
                    }

                    
                    $new_data = array();
                    $new_data['buyer_id'] = $member_id;
                    $new_data['vid'] = $vid;
                    $new_data['store_name'] = $store_cart_list[$vid][0]['store_name'];
                    $new_data['gid'] = $goods_info['gid'];
                    $new_data['goods_name'] = $goods_info['goods_name'];
                    $new_data['goods_price'] = 0;
                    $new_data['goods_image'] = $goods_info['goods_image'];
                    $new_data['bl_id'] = 0;
                    $new_data['state'] = true;
                    $new_data['storage_state'] = true;
                    $new_data['gc_id'] = 0;
                    $new_data['transport_id'] = 0;
                    $new_data['goods_freight'] = 0;
                    $new_data['goods_vat'] = 0;
                    $new_data['goods_total'] = 0;
                    $new_data['ifzengpin'] = true;

                    //计算赠送数量，有就赠，赠完为止
                    if ($goods_storage_quantity[$goods_info['gid']] - $goods_info['goods_num'] >= 0) {
                        $goods_buy_quantity[$goods_info['gid']] += $goods_info['goods_num'];
                        $goods_storage_quantity[$goods_info['gid']] -= $goods_info['goods_num'];
                        $new_data['goods_num'] = $goods_info['goods_num'];
                    } else {
                        $new_data['goods_num'] = $goods_storage_quantity[$goods_info['gid']];
                        $goods_buy_quantity[$goods_info['gid']] += $goods_storage_quantity[$goods_info['gid']];
                        $goods_storage_quantity[$goods_info['gid']] = 0;
                    }
                    if (array_key_exists($goods_info['gid'],$zp_list)) {
                        $zp_list[$goods_info['gid']]['goods_num'] += $new_data['goods_num'];
                    } else {
                        $zp_list[$goods_info['gid']] = $new_data;
                    }
                }
                sort($zp_list);
                $store_cart_list[$vid] = array_merge($store_cart_list[$vid],$zp_list);

                $store_mansong_rule_list[$vid]['desc'] .= $gift_desc;
                $store_mansong_rule_list[$vid]['desc'] = trim($store_mansong_rule_list[$vid]['desc'],'，');
            }
        }
        return array($store_cart_list,$goods_buy_quantity,$store_mansong_rule_list);
    }

    /**
     * 充值卡支付,依次循环每个订单 不考虑
     * 如果充值卡足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function rcbPay($order_list, $input, $buyer_info) {
        $member_id = $buyer_info['member_id'];
        $member_name = $buyer_info['member_name'];

        $available_rcb_amount = floatval($buyer_info['available_rc_balance']);
        if ($available_rcb_amount <= 0) return;

        $model_order = Model('order');
        $model_pd = Model('predeposit');
        foreach ($order_list as $key => $order_info) {

            //货到付款的订单跳过
            if ($order_info['payment_code'] == 'offline') continue;

            $order_amount = floatval($order_info['order_amount']);


            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;
            $data_pd['amount'] = $order_info['order_amount'];
            $data_pd['order_sn'] = $order_info['order_sn'];

            if ($available_rcb_amount >= $order_amount) {
                //立即支付，订单支付完成
                $model_pd->changeRcb('order_pay',$data_pd);
                $available_rcb_amount -= $order_amount;

                //记录订单日志(已付款)
                $data = array();
                $data['order_id'] = $order_info['order_id'];
                $data['log_role'] = 'buyer';
                $data['log_msg'] = L('完成了付款');
                $data['log_orderstate'] = ORDER_STATE_PAY;
                $insert = $model_order->addOrderLog($data);
                if (!$insert) {
                    throw new Exception('记录订单充值卡支付日志出现错误');
                }

                //订单状态 置为已支付
                $data_order = array();

                $order_list[$key]['order_state'] = $data_order['order_state'] = ORDER_STATE_SUCCESS;
                $data_order['payment_time'] = TIMESTAMP;
                $data_order['finnshed_time'] = TIMESTAMP;
                $data_order['payment_code'] = 'predeposit';
                $data_order['rcb_amount'] = $order_info['order_amount'];
                $result = $model_order->editOrder($data_order,array('order_id'=>$order_info['order_id']));
                if (!$result) {
                    throw new Exception('订单更新失败');
                }

                //支付成功拼团处理
                if($order_info['pin_id']) {
                    //取消本活动的其他的未付款订单
                    $wqre=M('pin')->paidPin($order_info);
                    if(!$wqre['succ']){
                        throw new Exception($wqre['msg']);
                    }
                }

                // 发送商家提醒
                $param = array();
                $param['code'] = 'new_order';
                $param['vid'] = $order_info['vid'];
                $param['param'] = array(
                        'order_sn' => $order_info['order_sn']
                );
                QueueClient::push('sendStoreMsg', $param);

                //发送门店提醒
                if($order_info['dian_id']>0){
                    $params = array();
                    $params['code'] = 'dian_new_order';
                    $params['vid'] = $order_info['dian_id'];
                    $params['param'] = array(
                        'order_sn' => $order_info['order_sn']
                    );
                    QueueClient::push('sendDianMsg', $params);
                }
            } else {
                //暂冻结充值卡,后面还需要 API彻底完成支付
                if ($available_rcb_amount > 0) {
                    $data_pd['amount'] = $available_rcb_amount;
                    $model_pd->changeRcb('order_freeze',$data_pd);
                    //支付金额保存到订单
                    $data_order = array();
                    $order_list[$key]['rcb_amount'] = $data_order['rcb_amount'] = $available_rcb_amount;
                    $result = $model_order->editOrder($data_order,array('order_id'=>$order_info['order_id']));
                    $available_rcb_amount = 0;
                    if (!$result) {
                        throw new Exception('订单更新失败');
                    }
                }
            }
        }
        return $order_list;
    }

    /**
     * 预存款支付,依次循环每个订单
     * 如果预存款足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function pdPay($order_list, $input, $buyer_info) {

        $member_id = $buyer_info['member_id'];
        $member_name = $buyer_info['member_name'];

//         $model_payment = Model('payment');
//         $pd_payment_info = $model_payment->getPaymentOpenInfo(array('payment_code'=>'predeposit'));
//         if (empty($pd_payment_info)) return;

        $available_pd_amount = floatval($buyer_info['available_predeposit']);
        if ($available_pd_amount <= 0) return;

        $model_order = Model('order');
        $model_pd = Model('predeposit');

        foreach ($order_list as $order_info) {

            //货到付款的订单、已经充值卡支付的订单跳过
            if ($order_info['payment_code'] == 'offline') continue;
            if ($order_info['order_state'] == ORDER_STATE_PAY) continue;

            $order_amount = floatval($order_info['order_amount']) - floatval($order_info['rcb_amount']);

            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;
            $data_pd['amount'] = $order_amount;
            $data_pd['order_sn'] = $order_info['order_sn'];

            if ($available_pd_amount >= $order_amount) {
                //预存款立即支付，订单支付完成
                $model_pd->changePd('order_pay',$data_pd);
                $available_pd_amount -= $order_amount;

                //支付被冻结的充值卡
                $rcb_amount = floatval($order_info['rcb_amount']);
                if ($rcb_amount > 0) {
                    $data_pd = array();
                    $data_pd['member_id'] = $member_id;
                    $data_pd['member_name'] = $member_name;
                    $data_pd['amount'] = $rcb_amount;
                    $data_pd['order_sn'] = $order_info['order_sn'];
                    $model_pd->changeRcb('order_comb_pay',$data_pd);
                }

                //记录订单日志(已付款)
                $data = array();
                $data['order_id'] = $order_info['order_id'];
                $data['log_role'] = 'buyer';
                $data['log_msg'] = L('完成了付款');
                $data['log_orderstate'] = ORDER_STATE_PAY;
                $insert = $model_order->addOrderLog($data);
                if (!$insert) {
                    throw new Exception('记录订单预存款支付日志出现错误');
                }

                //支付成功拼团处理
                if($order_info['pin_id']) {
                    //取消本活动的其他的未付款订单
                    $wqre=M('pin')->paidPin($order_info);
                    if(!$wqre['succ']){
                        throw new Exception($wqre['msg']);
                    }
                }

                //订单状态 置为已支付
                $data_order = array();
                    $data_order['order_state'] = ORDER_STATE_SUCCESS;
                $data_order['payment_time'] = TIMESTAMP;
                $data_order['finnshed_time'] = TIMESTAMP;
                $data_order['payment_code'] = 'predeposit';
                $data_order['pd_amount'] = $order_info['order_amount'];
                $result = $model_order->editOrder($data_order,array('order_id'=>$order_info['order_id']));
                if (!$result) {
                    throw new Exception('订单更新失败');
                }
                // 发送商家提醒
                $param = array();
                $param['code'] = 'new_order';
                $param['vid'] = $order_info['vid'];
                $param['param'] = array(
                        'order_sn' => $order_info['order_sn']
                );
                QueueClient::push('sendStoreMsg', $param);
                //发送门店提醒
                if($order_info['dian_id']>0){
                    $params = array();
                    $params['code'] = 'dian_new_order';
                    $params['vid'] = $order_info['dian_id'];
                    $params['param'] = array(
                        'order_sn' => $order_info['order_sn']
                    );
                    QueueClient::push('sendDianMsg', $params);
                }

                // 支付成功发送买家消息
                $param = array();
                $param['code'] = 'order_payment_success';
                $param['member_id'] = $order_info['buyer_id'];
                $param['param'] = array(
                        'order_sn' => $order_info['order_sn'],
                        'order_url' => urlShop('userorder', 'show_order', array('order_id' => $order_info['order_id'])),

                        'first' => '您有一笔订单支付成功',
                        'keyword1' => $order_info['order_sn'],
                        'keyword2' => date('Y年m月d日 H时i分',time()),
                        'keyword3' => sldPriceFormat($order_amount),
                        'remark' => '如有问题，请联系我们',
                        
                        'url' => WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_info['order_id']
                );
                $param['system_type']=2;
                $param['link']=WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_info['order_id'];
                QueueClient::push('sendMemberMsg', $param);
                //极光推送商户——新订单提醒
                //获取seller_name
//                $seller_info_jpush = Model('seller')->getSellerInfo(array('vid'=>$order_info['vid']));
//                $jpush = Logic('sld_jpush');
//                if($jpush->sld_check_jpush_isopen() ){
//                    $jpush->send_special_detail_url('您有一笔新订单，请及时查看',['type'=>'pay_success','order_id'=>$order_info['order_id'],'order_sn'=>$order_info['order_sn']],[$seller_info_jpush['seller_name']]);
//                }
            } else {
                //暂冻结预存款,后面还需要 API彻底完成支付
                if ($available_pd_amount > 0) {
                    $data_pd['amount'] = $available_pd_amount;
                    $model_pd->changePd('order_freeze',$data_pd);
                    //预存款支付金额保存到订单
                    $data_order = array();
                    $data_order['pd_amount'] = $available_pd_amount;
                    $result = $model_order->editOrder($data_order,array('order_id'=>$order_info['order_id']));
                    $available_pd_amount = 0;
                    if (!$result) {
                        throw new Exception('订单更新失败');
                    }
                }
            }
        }
    }

    /**
     * 特殊订单站内支付处理
     */
    public function extendInPay($order_list) {
        //处理预定订单
        if ($order_list[0]['order_type'] == 2) {
            $model_order_book = Model('order_book');
            $order_info = $order_list[0];
            $data = array();
            if (!empty($order_info['rcb_amount'])) {
                $data['book_rcb_amount'] = $order_info['rcb_amount'];
            }
            if (!empty($order_info['pd_amount'])) {
                $data['book_pd_amount'] = $order_info['pd_amount'];
            }

            //如果未使用站内余额，返回
            if (empty($order_info['rcb_amount']) && empty($order_info['pd_amount'])) {
                return callback(true);
            }

            if ($order_info['order_state'] == ORDER_STATE_PAY) {
                //使用站内余额即可全部支付，说明支付完成，记录支付时间和支付方式
                $data['book_pay_time'] = TIMESTAMP;
                $data['book_pay_name'] = '站内余额支付';
                //更新预定人数
                $order_goods_info = Model('order')->getOrderGoodsInfo(array('order_id'=>$order_info['order_id']),'gid','rec_id asc');
                $update = Model('goods')->editGoods(array('book_buyers'=>array('exp','book_buyers+1')),array('gid'=>$order_goods_info['gid']));
                if (!$update) {
                    throw new Exception('更新商品预定人数失败');
                }
            }
            $condition = array();
            $condition['book_order_id'] = $order_info['order_id'];
            if (empty($order_info['book_list'][0]['book_pay_time'])) {
                //付定金或全款
                $condition['book_id'] = $order_info['book_list'][0]['book_id'];
            } else {
                //付尾款
                $condition['book_id'] = $order_info['book_list'][1]['book_id'];
            }
            $update = $model_order_book->editOrderBook($data,$condition);
            if (!$update) {
                throw new Exception('更新站内余额失败');
            }
        }
        return callback(true);
    }

    /**
     * 生成支付单编号(两位随机 + 从2000-01-01 00:00:00 到现在的秒数+微秒+会员ID%1000)，该值会传给第三方支付接口
     * 长度 =2位 + 10位 + 3位 + 3位  = 18位
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @return string
     */
    public function makePaySn($member_id) {
        return mt_rand(10,99)
              . sprintf('%010d',time() - 946656000)
              . sprintf('%03d', (float) microtime() * 1000)
              . sprintf('%03d', (int) $member_id % 1000);
    }

    /**
     * 订单编号生成规则，n(n>=1)个订单表对应一个支付表，
     * 生成订单编号(年取1位 + $pay_id取13位 + 第N个子订单取2位)
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @param $pay_id 支付表自增ID
     * @return string
     */
    public function makeOrderSn($pay_id) {
        //记录生成子订单的个数，如果生成多个子订单，该值会累加
        static $num;
        if (empty($num)) {
            $num = 1;
        } else {
            $num ++;
        }
        return (date('y',time()) % 9+1) . sprintf('%013d', $pay_id) . sprintf('%02d', $num);
    }

    /**
     * 更新库存与销量 王强标记更新库存（该方法没被使用）
     *
     * @param array $buy_items 商品ID => 购买数量
     */
    public function editGoodsNum($buy_items) {
        foreach ($buy_items as $gid => $buy_num) {
            $data = array('goods_storage'=>array('exp','goods_storage-'.$buy_num),'goods_salenum'=>array('exp','goods_salenum+'.$buy_num));
            $result = Model('goods')->editGoods($data,array('gid'=>$gid));
            if (!$result) throw new Exception(L('系统异常,生成订单失败'));
        }
    }

    /**
     * 取得店铺级活动 - 每个店铺可用的满即送活动规则列表
     * @param unknown $store_id_array 店铺ID数组
     */
    public function getMansongRuleList($store_id_array) {
        if (!C('promotion_allow') || empty($store_id_array) || !is_array($store_id_array)) return array();
        $model_mansong = Model('p_mansong');
        $mansong_rule_list = array();
        foreach ($store_id_array as $vid) {
            $store_mansong_rule = $model_mansong->getMansongInfoByStoreID($vid);
            if (!empty($store_mansong_rule['rules']) && is_array($store_mansong_rule['rules'])) {
                foreach ($store_mansong_rule['rules'] as $rule_info) {
                    //如果减金额 或 有赠品(在售且有库存)
                    if (!empty($rule_info['discount']) || (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage']))) {
                        $mansong_rule_list[$vid][] = $this->_parseMansongRuleDesc($rule_info);
                    }
                }
            }
        }
        return $mansong_rule_list;
    }

    /**
     * 取得哪些店铺有满免运费活动
     * @param array $store_id_array 店铺ID数组
     * @return array
     */
    public function getFreeFreightActiveList($store_id_array) {
        if (empty($store_id_array) || !is_array($store_id_array)) return array();
    
        //定义返回数组
        $store_free_freight_active = array();
    
        //如果商品金额未达到免运费设置下线，则需要计算运费
        $condition = array('vid' => array('in',$store_id_array));
        $store_list = Model('vendor')->getStoreOnlineList($condition,null,'','vid,store_free_price');
        foreach ($store_list as $store_info) {
            $limit_price = floatval($store_info['store_free_price']);
            if ($limit_price > 0) {
                $store_free_freight_active[$store_info['vid']] = sprintf('满%s免运费',$limit_price);
            }
        }
        return $store_free_freight_active;
    }

    /**
     * 取得收货人地址信息
     * @param array $address_info
     * @return array
     */
    public function getReciverAddr($address_info = array()) {
        if (intval($address_info['dlyp_id'])) {
            $reciver_info['phone'] = trim($address_info['dlyp_mobile'].($address_info['dlyp_telephony'] ? ','.$address_info['dlyp_telephony'] : null),',');
            $reciver_info['tel_phone'] = $address_info['dlyp_telephony'];
            $reciver_info['mob_phone'] = $address_info['dlyp_mobile'];
            $reciver_info['address'] = $address_info['dlyp_area_info'].' '.$address_info['dlyp_address'];
            $reciver_info['area'] = $address_info['dlyp_area_info'];
            $reciver_info['street'] = $address_info['dlyp_address'];
            $reciver_info['dlyp'] = 1;
            $reciver_info = serialize($reciver_info);
            $reciver_name = $address_info['dlyp_address_name'];
        } else {
            $reciver_info['phone'] = trim($address_info['mob_phone'].($address_info['tel_phone'] ? ','.$address_info['tel_phone'] : null),',');
            $reciver_info['mob_phone'] = $address_info['mob_phone'];
            $reciver_info['tel_phone'] = $address_info['tel_phone'];
            $reciver_info['address'] = $address_info['area_info'].' '.$address_info['address'];
            $reciver_info['area'] = $address_info['area_info'];
            $reciver_info['street'] = $address_info['address'];
            $reciver_info = serialize($reciver_info);
            $reciver_name = $address_info['true_name'];  
        }
        return array($reciver_info, $reciver_name);
    }

    /**
     * 整理发票信息
     * @param array $invoice_info 发票信息数组
     * @return string
     */
    public function createInvoiceData($invoice_info){
        //发票信息
        $inv = array();
        if ($invoice_info['inv_state'] == 1) {
            $inv['类型'] = '普通发票 ';
            $inv['抬头'] = $invoice_info['inv_title_select'] == 'person' ? '个人' : $invoice_info['inv_title'];
            $inv['内容'] = $invoice_info['inv_content'];
        } elseif (!empty($invoice_info)) {
            $inv['单位名称'] = $invoice_info['inv_company'];
            $inv['纳税人识别号'] = $invoice_info['inv_code'];
            $inv['注册地址'] = $invoice_info['inv_reg_addr'];
            $inv['注册电话'] = $invoice_info['inv_reg_phone'];
            $inv['开户银行'] = $invoice_info['inv_reg_bname'];
            $inv['银行账户'] = $invoice_info['inv_reg_baccount'];
            $inv['收票人姓名'] = $invoice_info['inv_rec_name'];
            $inv['收票人手机号'] = $invoice_info['inv_rec_mobphone'];
            $inv['收票人省份'] = $invoice_info['inv_rec_province'];
            $inv['送票地址'] = $invoice_info['inv_goto_addr'];
        }
        return !empty($inv) ? serialize($inv) : serialize(array());
    }
    
    /**
     * 计算本次下单中每个店铺订单是货到付款还是线上支付,店铺ID=>付款方式[online在线支付offline货到付款]
     * @param array $store_id_array 店铺ID数组
     * @param boolean $if_offpay 是否支持货到付款 true/false
     * @param string $pay_name 付款方式 online/offline
     * @return array
     */
    public function getStorePayTypeList($store_id_array, $if_offpay, $pay_name) {
        $store_pay_type_list = array();
        if ($_POST['pay_name'] == 'online') {
            foreach ($store_id_array as $vid) {
                $store_pay_type_list[$vid] = 'online';
            }
        } else {
            $offline_pay = Model('payment')->getPaymentOpenInfo(array('payment_code'=>'offline'));
            if ($offline_pay) {
                //下单里包括平台自营商品并且平台已开启货到付款
                $offline_store_id_array = model('store')->getOwnShopIds();
                foreach ($store_id_array as $vid) {
                    //if (in_array($vid,$offline_store_id_array)) {
                        $store_pay_type_list[$vid] = 'offline';
                    //} else {
                    //    $store_pay_type_list[$vid] = 'online';
                    //}
                }
            }
        }
        return $store_pay_type_list;
    }

    /**
     * 直接购买时返回最新的在售商品信息（需要在售）
     *
     * @param int $gid 所购商品ID
     * @param int $quantity 购买数量
     * @return array
     */
    private function _getGoodsOnlineInfo($gid,$quantity) {
        //取目前在售商品
        $goods_info = Model('goods')->getGoodsOnlineInfoAndPromotionById($gid);
        if(empty($goods_info)){
            return null;
        }
        $new_array = array();
        $new_array['goods_num'] = $goods_info['is_fcode'] ? 1 : $quantity;
        $new_array['gid'] = $gid;
        $new_array['goods_commonid'] = $goods_info['goods_commonid'];
        $new_array['gc_id'] = $goods_info['gc_id'];
        $new_array['vid'] = $goods_info['vid'];
        $new_array['goods_name'] = $goods_info['goods_name'];
        $new_array['goods_price'] = $goods_info['goods_price'];
        $new_array['store_name'] = $goods_info['store_name'];
        $new_array['goods_image'] = $goods_info['goods_image'];
        $new_array['transport_id'] = $goods_info['transport_id'];
        $new_array['goods_freight'] = $goods_info['goods_freight'];
        $new_array['goods_vat'] = $goods_info['goods_vat'];
        $new_array['goods_storage'] = $goods_info['goods_storage'];
        $new_array['goods_storage_alarm'] = $goods_info['goods_storage_alarm'];
        $new_array['is_fcode'] = $goods_info['is_fcode'];
        $new_array['have_gift'] = $goods_info['have_gift'];
        $new_array['state'] = true;
        $new_array['storage_state'] = intval($goods_info['goods_storage']) < intval($quantity) ? false : true;
        $new_array['tuan_info'] = $goods_info['tuan_info'];
        $new_array['xianshi_info'] = $goods_info['xianshi_info'];

        //填充必要下标，方便后面统一使用购物车方法与模板
        //cart_id=gid,优惠套装目前只能进购物车,不能立即购买
        $new_array['cart_id'] = $gid;
        $new_array['bl_id'] = 0;

        
        return $new_array;
    }
    
    /**
     * 直接购买时，判断商品是不是正在抢购中，如果是，按抢购价格计算，购买数量若超过抢购规定的上限，则按抢购上限计算
     * @param array $goods_info
     */
    public function getTuanInfo(& $goods_info = array()) {
        if (!C('tuan_allow') || empty($goods_info['tuan_info'])) return ;
        $tuan_info = $goods_info['tuan_info'];
        $goods_info['goods_price'] = $tuan_info['tuan_price'];
        if ($tuan_info['upper_limit'] && $goods_info['goods_num'] > $tuan_info['upper_limit']) {
            $goods_info['goods_num'] = $tuan_info['upper_limit'];
        }
        $goods_info['upper_limit'] = $tuan_info['upper_limit'];
        $goods_info['promotions_id'] = $goods_info['tuan_id'] = $tuan_info['tuan_id'];
        $goods_info['iftuan'] = true;
    }

    /**
     * 取得某商品赠品列表信息
     * @param array $goods_info
     */
    private function _getGoodsGiftList( & $goods_info) {
        if (!$goods_info['have_gift']) return ;
        $gift_list = Model('goods_gift')->getGoodsGiftListByGoodsId($goods_info['gid']);
        //取得赠品当前信息，如果未在售踢除，如果在售取出库存
        if (empty($gift_list)) return array();
        $model_goods = Model('goods');
        foreach ($gift_list as $k => $v) {
            $goods_online_info = $model_goods->getGoodsOnlineInfoByID($v['gift_goodsid'],'goods_storage');
            if (empty($goods_online_info)) {
                unset($gift_list[$k]);
            } else {
                $gift_list[$k]['goods_storage'] = $goods_online_info['goods_storage'];
            }
        }
        $goods_info['gift_list'] = $gift_list;
    }


    /**
     * 取商品最新的在售信息
     * @param unknown $cart_list
     * @return array
     */
    private function _getOnlineCartList($cart_list) {
        if (empty($cart_list) || !is_array($cart_list)) return $cart_list;
        //验证商品是否有效
        $goods_id_array = array();
        foreach ($cart_list as $key => $cart_info) {
            if (!intval($cart_info['bl_id'])) {
                $goods_id_array[] = $cart_info['gid'];
            }
        }
        $model_goods = Model('goods');
        $goods_online_list = $model_goods->getGoodsOnlineListAndPromotionByIdArray($goods_id_array);
        $goods_online_array = array();
        foreach ($goods_online_list as $goods) {
            $goods_online_array[$goods['gid']] = $goods;
        }

        foreach ((array)$cart_list as $key => $cart_info) {
            if (intval($cart_info['bl_id'])) continue;
            $cart_list[$key]['state'] = true;
            $cart_list[$key]['storage_state'] = true;
            if (in_array($cart_info['gid'],array_keys($goods_online_array))) {
                $goods_online_info = $goods_online_array[$cart_info['gid']];
                $cart_list[$key]['goods_commonid'] = $goods_online_info['goods_commonid'];
                $cart_list[$key]['goods_name'] = $goods_online_info['goods_name'];
                $cart_list[$key]['gc_id'] = $goods_online_info['gc_id'];
                $cart_list[$key]['goods_image'] = $goods_online_info['goods_image'];
                $cart_list[$key]['goods_price'] = $goods_online_info['goods_price'];
                $cart_list[$key]['transport_id'] = $goods_online_info['transport_id'];
                $cart_list[$key]['goods_freight'] = $goods_online_info['goods_freight'];
                $cart_list[$key]['goods_vat'] = $goods_online_info['goods_vat'];
                $cart_list[$key]['goods_storage'] = $goods_online_info['goods_storage'];
                $cart_list[$key]['goods_storage_alarm'] = $goods_online_info['goods_storage_alarm'];
                $cart_list[$key]['is_fcode'] = $goods_online_info['is_fcode'];
                $cart_list[$key]['have_gift'] = $goods_online_info['have_gift'];
                if ($cart_info['goods_num'] > $goods_online_info['goods_storage']) {
                    $cart_list[$key]['storage_state'] = false;
                }
                $cart_list[$key]['tuan_info'] = $goods_online_info['tuan_info'];
                $cart_list[$key]['xianshi_info'] = $goods_online_info['xianshi_info'];
            } else {
                //如果商品下架
                $cart_list[$key]['state'] = false;
                $cart_list[$key]['storage_state'] = false;
            }
        }
    
        return $cart_list;
    }

    /**
     *  直接购买时，判断商品是不是正在抢购中，如果是，按抢购价格计算，购买数量若超过抢购规定的上限，则按抢购上限计算
     * @param array $cart_list
     */
    public function getTuanCartList(& $cart_list) {
        if (!C('promotion_allow') || empty($cart_list)) return ;
        $model_goods = Model('goods');
        foreach ($cart_list as $key => $cart_info) {
            if ($cart_info['bl_id'] === '1' || empty($cart_info['tuan_info'])) continue;
            $this->getTuanInfo($cart_info);
            $cart_list[$key] = $cart_info;
        }
    }

    /**
     * 批量判断购物车内的商品是不是限时折扣中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算
     * 并标识该商品为限时商品
     * @param array $cart_list
     */
    public function getXianshiCartList(& $cart_list) {
        if (!C('promotion_allow') || empty($cart_list)) return ;
        foreach ($cart_list as $key => $cart_info) {
            if ($cart_info['bl_id'] === '1' || empty($cart_info['xianshi_info'])) continue;
            $this->getXianshiInfo($cart_info, $cart_info['goods_num']);
            $cart_list[$key] = $cart_info;
        }
    }

    /**
     * 取得购物车商品的赠品列表[商品级赠品]
     *
     * @param array $cart_list
     */
    private function _getGiftCartList(& $cart_list) {
        foreach ($cart_list as $k => $cart_info) {
            if ($cart_info['bl_id']) continue;
            $this->_getGoodsGiftList($cart_info);
            $cart_list[$k] = $cart_info;
        }
    }

    /**
     * 取得购买车内组合销售信息以及包含的商品及有效状态
     * @param array $cart_list
     */
    private function _getBundlingCartList(& $cart_list) {
        if (!C('promotion_allow') || empty($cart_list)) return ;
        $model_bl = Model('p_bundling');
        $model_goods = Model('goods');
        foreach ($cart_list as $key => $cart_info) {
            if (!intval($cart_info['bl_id'])) continue;
            $cart_list[$key]['state'] = true;
            $cart_list[$key]['storage_state'] = true;
            $bl_info = $model_bl->getBundlingInfo(array('bl_id'=>$cart_info['bl_id']));
    
            //标志优惠套装是否处于有效状态
            if (empty($bl_info) || !intval($bl_info['bl_state'])) {
                $cart_list[$key]['state'] = false;
            }
    
            //取得优惠套装商品列表
            $cart_list[$key]['bl_goods_list'] = $model_bl->getBundlingGoodsList(array('bl_id'=>$cart_info['bl_id']));
    
            //取最新在售商品信息
            $goods_id_array = array();
            foreach ($cart_list[$key]['bl_goods_list'] as $goods_info) {
                $goods_id_array[] = $goods_info['gid'];
            }
            $goods_list = $model_goods->getGoodsOnlineListAndPromotionByIdArray($goods_id_array);
            $goods_online_list = array();
            foreach ($goods_list as $goods_info) {
                $goods_online_list[$goods_info['gid']] = $goods_info;
            }
            unset($goods_list);
    
            //使用最新的商品名称、图片,如果一旦有商品下架，则整个套装置置为无效状态
            $total_down_price = 0;
            foreach ($cart_list[$key]['bl_goods_list'] as $k => $goods_info) {
                if (array_key_exists($goods_info['gid'],$goods_online_list)) {
                    $goods_online_info = $goods_online_list[$goods_info['gid']];
                    //如果库存不足，标识false
                    if ($cart_info['goods_num'] > $goods_online_info['goods_storage']) {
                        $cart_list[$key]['storage_state'] = false;
                    }
                    $cart_list[$key]['bl_goods_list'][$k]['gid'] = $goods_online_info['gid'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_commonid'] = $goods_online_info['goods_commonid'];
                    $cart_list[$key]['bl_goods_list'][$k]['vid'] = $goods_online_info['vid'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_name'] = $goods_online_info['goods_name'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_image'] = $goods_online_info['goods_image'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_storage'] = $goods_online_info['goods_storage'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_storage_alarm'] = $goods_online_info['goods_storage_alarm'];
                    $cart_list[$key]['bl_goods_list'][$k]['gc_id'] = $goods_online_info['gc_id'];
                    //每个商品直降多少
                    $total_down_price += $cart_list[$key]['bl_goods_list'][$k]['down_price'] = sldPriceFormat($goods_online_info['goods_price'] - $goods_info['bl_goods_price']);
                } else {
                    //商品已经下架
                    $cart_list[$key]['state'] = false;
                    $cart_list[$key]['storage_state'] = false;
                }
            }
            $cart_list[$key]['down_price'] = sldPriceFormat($total_down_price);
        }
    }

    /**
     * 取得每种商品的库存
     * @param array $store_cart_list 购买列表
     * @param array $store_premiums_list 赠品列表
     * @return array 商品ID=>库存
     */
    private function _getEachGoodsStorageQuantity($store_cart_list, $store_premiums_list = array()) {
        if(empty($store_cart_list) || !is_array($store_cart_list)) return array();
        $goods_storage_quangity = array();
        foreach ($store_cart_list as $store_cart) {
            foreach ($store_cart as $cart_info) {
                if (!intval($cart_info['bl_id'])) {
                    //正常商品
                    $goods_storage_quangity[$cart_info['gid']] = $cart_info['goods_storage'];
                } elseif (!empty($cart_info['bl_goods_list']) && is_array($cart_info['bl_goods_list'])) {
                    //优惠套装
                    foreach ($cart_info['bl_goods_list'] as $goods_info) {
                        $goods_storage_quangity[$goods_info['gid']] = $goods_info['goods_storage'];
                    }
                }
            }
        }
        //取得赠品商品的库存
        if (is_array($store_premiums_list)) {
            foreach ($store_premiums_list as $vid => $goods_list) {
                foreach($goods_list as $goods_info) {
                    if (!isset($goods_storage_quangity[$goods_info['gid']])) {
                        $goods_storage_quangity[$goods_info['gid']] = $goods_info['goods_storage'];
                    }
                }
            }
        }
        return $goods_storage_quangity;
    }
    
    /**
     * 取得每种商品的购买量
     * @param array $store_cart_list 购买列表
     * @return array 商品ID=>购买数量
     */
    private function _getEachGoodsBuyQuantity($store_cart_list) {
        if(empty($store_cart_list) || !is_array($store_cart_list)) return array();
        $goods_buy_quangity = array();
        foreach ($store_cart_list as $store_cart) {
            foreach ($store_cart as $cart_info) {
                if (!intval($cart_info['bl_id'])) {
                    //正常商品
                    $goods_buy_quangity[$cart_info['gid']] += $cart_info['goods_num'];
                } elseif (!empty($cart_info['bl_goods_list']) && is_array($cart_info['bl_goods_list'])) {
                    //优惠套装
                    foreach ($cart_info['bl_goods_list'] as $goods_info) {
                        $goods_buy_quangity[$goods_info['gid']] += $cart_info['goods_num'];
                    }
                }
            }
        }
        return $goods_buy_quangity;
    }

    /**
     * 得到所购买的id和数量
     *
     */
    private function _parseItems($cart_id) {
        //存放所购商品ID和数量组成的键值对
        $buy_items = array();
        if (is_array($cart_id)) {
            foreach ($cart_id as $value) {
                if (preg_match_all('/^(\d{1,10})\|(\d{1,6})$/', $value, $match)) {
                    $buy_items[$match[1][0]] = $match[2][0];
                }
            }
        }
        return $buy_items;
    }

    /**
     * 拼装单条满即送规则页面描述信息
     * @param array $rule_info 满即送单条规则信息
     * @return string
     */
    private function _parseMansongRuleDesc($rule_info) {
        if (empty($rule_info) || !is_array($rule_info)) return;
        $discount_desc = !empty($rule_info['discount']) ? '减'.$rule_info['discount'] : '';
        $goods_desc = (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage'])) ?
        " 送<a href='".urlShop('goods','index',array('gid'=>$rule_info['gid']))."' title='{$rule_info['mansong_goods_name']}' target='_blank'>[赠品]</a>" : '';
        return sprintf('满%s%s%s',$rule_info['price'],$discount_desc,$goods_desc);
    }

}
