<?php
/**
 * WAP首页
 *
 */


defined('DYMall') or exit('Access Invalid!');
class indexCtl extends mobileHomeCtl{

    public function __construct() {
        if(!(C('promotion_allow')==1 && C('sld_pintuan_ladder') && C('pin_ladder_isuse'))){
            echo json_encode(['status'=>255,'msg'=>'当前活动尚未开启']);die;
        }
        parent::__construct();
    }
    /**
     * @api {get} index.php?app=index&mod=index&sld_addons=pin_ladder 阶梯拼团分类
     * @apiVersion 0.1.0
     * @apiName index
     * @apiGroup Ladder
     * @apiDescription 阶梯拼团分类
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=index&mod=index&sld_addons=pin_ladder
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
                    {
                        "status": 200,
                        "data": [
                            {
                                "id": "1",
                                "class_name": "美食",
                                "sort": "255"
                            },
                            {
                                "id": "2",
                                "class_name": "美妆",
                                "sort": "255"
                            },
                            {
                                "id": "3",
                                "class_name": "美女",
                                "sort": "255"
                            }
                        ]
                    }
     */
    public function index() {
        $types = M('pin_category','pin_ladder')->getlist(['is_show'=>1]);
        echo  json_encode(['status'=>200,'data'=>$types]);die;
    }
    /**
     * @api {get} index.php?app=index&mod=data&sld_addons=pin_ladder 阶梯拼团首页
     * @apiVersion 0.1.0
     * @apiName data
     * @apiGroup Ladder
     * @apiDescription 阶梯拼团首页
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=index&mod=data&sld_addons=pin_ladder
     * @apiParam {String} tid 分类id
     * @apiParam {Number} page 显示数量
     * @apiParam {Number} pn 当前页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
                                                                                                {
{
    "code": 200,
    "hasmore": false,
    "page_total": 1,
    "datas": {
        "goods": [
            {
                "id": "206",
                "sld_goods_id": "1225",
                "sld_vid": "8",
                "sld_type": "1",
                "sld_pic": "http://site7.55jimu.com/data/upload/mall/tuan/8/8_05957902013504342_max.jpg",
                "sld_start_time": "1542211200",
                "sld_end_time": "1543507200",
                "sld_return_leader": null,
                "sld_max_buy": "2",
                "sld_team_count": "3",
                "sld_success_time": "24.00",
                "sld_status": "1",
                "sld_deposit_money": null,
                "gid": "2048",
                "goods_name": "ceshi 红 5.0 xxl",
                "goods_price": "12.00",
                "sld_pin_price": 0,
                "sales": "0",
                "sheng": -88,
                "sld_ding_price": 100
            }
        ]
    }
}

     */
    public function data(){

        $rows = $_GET['page']?:10;
        $tid = $_GET['tid']?intval($_GET['tid']):0;
        $model = model();
        if($tid){
            $where['sld_type'] = $tid;
        }
        $where['sld_start_time'] = array('lt',TIMESTAMP);
        $where['sld_end_time'] = array('gt',TIMESTAMP);
        $where['sld_status'] = 1;
        $md=M('pin','pin_ladder');
        $goods=$md->table('pin_ladder,goods,pin_goods_ladder')
            ->field('pin_ladder.*,goods.gid,goods.goods_name,goods.goods_price,pin_goods_ladder.sld_pin_price,(select count(*) from bbc_pin_team_user_ladder where sld_pin_id = pin_ladder.id ) as sales')
            ->join('left')
            ->on('pin_ladder.sld_goods_id=goods.goods_commonid,pin_ladder.id=pin_goods_ladder.sld_pin_id')
            ->where($where)
            ->group('pin_ladder.id')
            ->page($rows)
            ->select();
        $page_count = $md->gettotalpage();
        foreach ($goods as $k=>$v){
            $ladder_price = $model->table('pin_money_ladder')->where(['pin_id'=>$v['id']])->order('people_num desc')->find();
            $goods[$k]['sheng'] = $v['goods_price'] -  ($ladder_price['pay_money'] + $v['sld_pin_price']);
            $goods[$k]['sld_ding_price'] = floatval($v['sld_pin_price']);
            $goods[$k]['sld_pin_price'] = floatval($ladder_price['pay_money']);
            $goods[$k]['sld_pic'] = gthumb($v['sld_pic'],'max');
        }
        output_data(array('goods' => $goods), mobile_page($page_count));
    }
    /**
     * @api {get} index.php?app=index&mod=index_data&sld_addons=pin_ladder 阶梯拼团装修首页
     * @apiVersion 0.1.0
     * @apiName index_data
     * @apiGroup Ladder
     * @apiDescription 阶梯拼团装修首页
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=index&mod=index_data&sld_addons=pin_ladder
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
     {
    "code": 200,
    "datas": {
        "tmp_data": [
            {
                "type": "tupianzuhe",
                "sele_style": "0",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806831/s1510806831_05641508311026815.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "kefu",
                "text": "客服电话：",
                "tel": "15288889999"
            },
            {
                "type": "tupianzuhe",
                "sele_style": "0",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806849/s1510806849_05641508492669917.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "nav",
                "style_set": "nav",
                "icon_set": "up",
                "slide": "30",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806857/s1510806857_05641508576004766.png",
                        "name": "商品分类",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806859/s1510806859_05641508594591247.png",
                        "name": "搭配专区",
                        "url_type": "url",
                        "url": "http://site2.slodon.cn/appview/cwap_shop_list.html",
                        "url_type_new": "vendorlist",
                        "url_new": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806865/s1510806865_05641508652988925.png",
                        "name": "清仓打折",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806869/s1510806869_05641508698674373.png",
                        "name": "街拍大赏",
                        "url_type": "special",
                        "url": "6"
                    }
                ]
            },
            {
                "type": "nav",
                "style_set": "nav",
                "icon_set": "up",
                "slide": "30",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806885/s1510806885_05641508853590499.png",
                        "name": "购物车",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806890/s1510806890_05641508908883542.png",
                        "name": "积分",
                        "url_type": "url",
                        "url": "http://site2.slodon.cn/points/",
                        "url_type_new": "points_shop",
                        "url_new": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806931/s1510806931_05641509316991855.png",
                        "name": "足迹",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806935/s1510806935_05641509356104182.png",
                        "name": "个人中心",
                        "url_type": "url",
                        "url": "http://site2.slodon.cn/index.php?app=vendorlist",
                        "url_type_new": "",
                        "url_new": ""
                    }
                ]
            },
            {
                "type": "gonggao",
                "lianjie_type": "url",
                "lianjie_url": "http://www.baidu.com",
                "text": "我是谁？我是百度。"
            },
            {
                "type": "huodong",
                "sele_style": "0",
                "data": {
                    "top": {
                        "top": [
                            {
                                "title": "aaa",
                                "goods_info": null
                            }
                        ]
                    },
                    "left": {
                        "top": [
                            {
                                "title": "aaa",
                                "subtitle": "aaa",
                                "gid": "1801",
                                "goods_info": null
                            }
                        ]
                    },
                    "right": {
                        "top": [
                            {
                                "title": "aa",
                                "subtitle": "aa",
                                "gid": [
                                    "1720",
                                    "1675",
                                    "540"
                                ],
                                "goods_info": {
                                    "2": {
                                        "gid": "540",
                                        "goods_name": "it b+ab女装秋冬亮片字母印花休闲兔毛针织上衣0367XY 灰色 L",
                                        "goods_promotion_price": "0.00",
                                        "goods_marketprice": "568.00",
                                        "goods_price": "168.00",
                                        "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/16/16_05709742546646227_240.jpeg",
                                        "goods_state": "1",
                                        "goods_verify": "1",
                                        "extend_data": null,
                                        "promotion_type": "pin_tuan",
                                        "promotion_price": 100,
                                        "show_price": 100,
                                        "start_time": "1519353300",
                                        "promotion_start_time": "2018年02月23日 10:35",
                                        "end_time": "1545494400",
                                        "promotion_end_time": "2018年12月23日 00:00"
                                    }
                                }
                            }
                        ],
                        "bottom": {
                            "1": {
                                "title": "aa",
                                "subtitle": "aa",
                                "gid": [
                                    "1801"
                                ]
                            },
                            "2": {
                                "title": "aa",
                                "subtitle": "aa",
                                "gid": [
                                    "216"
                                ],
                                "goods_info": [
                                    {
                                        "gid": "216",
                                        "goods_name": "黛熊 孕妇装韩版钉珠孕妇毛衣春秋装新品圆领孕妇针织上衣Z-739",
                                        "goods_promotion_price": "0.00",
                                        "goods_marketprice": "219.00",
                                        "goods_price": "219.00",
                                        "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/23/23_05708154069949596_240.jpg",
                                        "goods_state": "1",
                                        "goods_verify": "1",
                                        "extend_data": null,
                                        "promotion_type": "pin_tuan",
                                        "promotion_price": 180,
                                        "show_price": 180,
                                        "start_time": "1519353720",
                                        "promotion_start_time": "2018年02月23日 10:42",
                                        "end_time": "1545494400",
                                        "promotion_end_time": "2018年12月23日 00:00"
                                    }
                                ]
                            }
                        }
                    }
                }
            },
            {
                "type": "tupianzuhe",
                "sele_style": "0",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806947/s1510806947_05641509480020967.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510806954/s1510806954_05641509547262780.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "tupianzuhe",
                "sele_style": "7",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807058/s1510807058_05641510587674826.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807061/s1510807061_05641510610144478.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807065/s1510807065_05641510650574142.jpg",
                        "title": "s1510807065_05641510650574142.jpg",
                        "url_type": "s1510807065_05641510650574142.jpg",
                        "url": "s1510807065_05641510650574142.jpg"
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807067/s1510807067_05641510677207170.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807070/s1510807070_05641510709312777.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "fuwenben",
                "text": "<strong>  <span style=\"font-size:14px;\">冬尚新</span> <span style=\"color:#999999;\">New Arrival</span></strong>"
            },
            {
                "type": "tupianzuhe",
                "sele_style": "1",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807113/s1510807113_05641511137336712.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807118/s1510807118_05641511182595989.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "fuwenben",
                "text": "<div style=\"text-align:center;\"><strong> <span style=\"font-size:14px;\">人气推荐 </span></strong><span style=\"color:#999999;\">New Hot</span> </div>"
            },
            {
                "type": "tupianzuhe",
                "sele_style": "1",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807153/s1510807153_05641511535769112.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "tupianzuhe",
                "sele_style": "2",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807169/s1510807169_05641511697319426.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807171/s1510807171_05641511719207848.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "fuwenben",
                "text": "<div style=\"text-align:center;\"><strong><span style=\"font-size:14px;\">冬季必备 </span></strong><span style=\"color:#999999;\">WINTER ESSENTIAL</span> </div>"
            },
            {
                "type": "tupianzuhe",
                "sele_style": "1",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807341/s1510807341_05641513417039367.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807347/s1510807347_05641513470179412.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "fuwenben",
                "text": "<div style=\"text-align:center;\"><strong><span style=\"font-size:14px;\">猜你喜欢 </span></strong><span style=\"font-size:14px;\"><span><span style=\"color:#999999;\">RECOMMENDED</span></span></span></div>"
            },
            {
                "type": "tupianzuhe",
                "sele_style": "2",
                "data": [
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807381/s1510807381_05641513815445053.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807384/s1510807384_05641513844611458.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807394/s1510807394_05641513946937148.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    },
                    {
                        "img": "http://site2.slodon.cn/data/upload/mobile/special/s1510807396/s1510807396_05641513965052063.jpg",
                        "title": "",
                        "url_type": "",
                        "url": ""
                    }
                ]
            },
            {
                "type": "huodong",
                "sele_style": "1",
                "data": {
                    "top": {
                        "top": [
                            {
                                "title": "acs",
                                "goods_info": null
                            }
                        ]
                    },
                    "bottom": {
                        "left": {
                            "1": {
                                "title": "adada",
                                "subtitle": "adada",
                                "gid": [
                                    "1937"
                                ],
                                "goods_info": [
                                    {
                                        "gid": "1937",
                                        "goods_name": "aaa",
                                        "goods_promotion_price": "0.00",
                                        "goods_marketprice": "12.00",
                                        "goods_price": "10.00",
                                        "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05941419841198704_240.jpg",
                                        "goods_state": "1",
                                        "goods_verify": "1",
                                        "extend_data": {
                                            "xianshi_gid": "132",
                                            "xianshi_id": "8",
                                            "xianshi_name": "春季限时清仓",
                                            "xianshi_title": "限时折扣",
                                            "xianshi_explain": "春季限时清仓 全场2折起!",
                                            "gid": "1937",
                                            "vid": "8",
                                            "goods_name": "aaa",
                                            "goods_price": "10.00",
                                            "xianshi_price": "9.00",
                                            "goods_image": "8_05941419841198704.jpg",
                                            "start_time": "1519459620",
                                            "end_time": "1545580800",
                                            "lower_limit": "1",
                                            "state": "1",
                                            "xianshi_recommend": "0",
                                            "goods_url": "http://site7.55jimu.com/index.php?app=goods&gid=1937",
                                            "image_url": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05941419841198704_60.jpg",
                                            "xianshi_discount": "9.0折",
                                            "goods_info": {
                                                "gid": "1937",
                                                "goods_commonid": "1338",
                                                "goods_name": "aaa",
                                                "goods_jingle": "aaa",
                                                "vid": "8",
                                                "store_name": "商联达家居店",
                                                "gc_id": "1880",
                                                "gc_id_1": "1868",
                                                "gc_id_2": "1880",
                                                "gc_id_3": "0",
                                                "brand_id": "0",
                                                "goods_price": "10.00",
                                                "goods_promotion_price": "0.00",
                                                "goods_promotion_type": "0",
                                                "goods_marketprice": "12.00",
                                                "goods_serial": "11111111",
                                                "goods_storage_alarm": "1",
                                                "goods_click": "8",
                                                "goods_salenum": "0",
                                                "goods_collect": "0",
                                                "goods_spec": "N;",
                                                "goods_storage": "12",
                                                "goods_image": "8_05941419841198704.jpg",
                                                "goods_state": "1",
                                                "goods_verify": "1",
                                                "goods_addtime": "1540797988",
                                                "goods_edittime": "1540802541",
                                                "areaid_1": "0",
                                                "areaid_2": "0",
                                                "areaid_3": null,
                                                "color_id": "0",
                                                "transport_id": "0",
                                                "goods_freight": "0.00",
                                                "goods_vat": "0",
                                                "goods_commend": "1",
                                                "goods_stcids": ",0,",
                                                "evaluation_good_star": "5",
                                                "evaluation_count": "0",
                                                "is_virtual": "0",
                                                "virtual_indate": "0",
                                                "virtual_limit": "0",
                                                "virtual_invalid_refund": "1",
                                                "is_fcode": "0",
                                                "is_appoint": "0",
                                                "is_presell": "0",
                                                "have_gift": "0",
                                                "is_own_shop": "1",
                                                "goods_rebate": "5",
                                                "goods_barcode": "",
                                                "fenxiao_yongjin": "0.00",
                                                "goods_type": "0",
                                                "sld_ladder_price": null,
                                                "province_id": "0",
                                                "city_id": "0",
                                                "area_id": "0",
                                                "goods_label": ""
                                            },
                                            "goods_salenum": "0",
                                            "goods_storage": "12",
                                            "sld_end_time": "2018-12-24 00:00:00"
                                        },
                                        "promotion_type": "xianshi",
                                        "promotion_price": 9,
                                        "show_price": 9,
                                        "start_time": "1519459620",
                                        "promotion_start_time": "2018年02月24日 16:07",
                                        "end_time": "1545580800",
                                        "promotion_end_time": "2018年12月24日 00:00"
                                    }
                                ]
                            }
                        },
                        "mid": {
                            "2": {
                                "title": "adasd",
                                "subtitle": "adada",
                                "gid": [
                                    "1937"
                                ],
                                "goods_info": [
                                    {
                                        "gid": "1937",
                                        "goods_name": "aaa",
                                        "goods_promotion_price": "0.00",
                                        "goods_marketprice": "12.00",
                                        "goods_price": "10.00",
                                        "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05941419841198704_240.jpg",
                                        "goods_state": "1",
                                        "goods_verify": "1",
                                        "extend_data": {
                                            "xianshi_gid": "132",
                                            "xianshi_id": "8",
                                            "xianshi_name": "春季限时清仓",
                                            "xianshi_title": "限时折扣",
                                            "xianshi_explain": "春季限时清仓 全场2折起!",
                                            "gid": "1937",
                                            "vid": "8",
                                            "goods_name": "aaa",
                                            "goods_price": "10.00",
                                            "xianshi_price": "9.00",
                                            "goods_image": "8_05941419841198704.jpg",
                                            "start_time": "1519459620",
                                            "end_time": "1545580800",
                                            "lower_limit": "1",
                                            "state": "1",
                                            "xianshi_recommend": "0",
                                            "goods_url": "http://site7.55jimu.com/index.php?app=goods&gid=1937",
                                            "image_url": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05941419841198704_60.jpg",
                                            "xianshi_discount": "9.0折",
                                            "goods_info": {
                                                "gid": "1937",
                                                "goods_commonid": "1338",
                                                "goods_name": "aaa",
                                                "goods_jingle": "aaa",
                                                "vid": "8",
                                                "store_name": "商联达家居店",
                                                "gc_id": "1880",
                                                "gc_id_1": "1868",
                                                "gc_id_2": "1880",
                                                "gc_id_3": "0",
                                                "brand_id": "0",
                                                "goods_price": "10.00",
                                                "goods_promotion_price": "0.00",
                                                "goods_promotion_type": "0",
                                                "goods_marketprice": "12.00",
                                                "goods_serial": "11111111",
                                                "goods_storage_alarm": "1",
                                                "goods_click": "8",
                                                "goods_salenum": "0",
                                                "goods_collect": "0",
                                                "goods_spec": "N;",
                                                "goods_storage": "12",
                                                "goods_image": "8_05941419841198704.jpg",
                                                "goods_state": "1",
                                                "goods_verify": "1",
                                                "goods_addtime": "1540797988",
                                                "goods_edittime": "1540802541",
                                                "areaid_1": "0",
                                                "areaid_2": "0",
                                                "areaid_3": null,
                                                "color_id": "0",
                                                "transport_id": "0",
                                                "goods_freight": "0.00",
                                                "goods_vat": "0",
                                                "goods_commend": "1",
                                                "goods_stcids": ",0,",
                                                "evaluation_good_star": "5",
                                                "evaluation_count": "0",
                                                "is_virtual": "0",
                                                "virtual_indate": "0",
                                                "virtual_limit": "0",
                                                "virtual_invalid_refund": "1",
                                                "is_fcode": "0",
                                                "is_appoint": "0",
                                                "is_presell": "0",
                                                "have_gift": "0",
                                                "is_own_shop": "1",
                                                "goods_rebate": "5",
                                                "goods_barcode": "",
                                                "fenxiao_yongjin": "0.00",
                                                "goods_type": "0",
                                                "sld_ladder_price": null,
                                                "province_id": "0",
                                                "city_id": "0",
                                                "area_id": "0",
                                                "goods_label": ""
                                            },
                                            "goods_salenum": "0",
                                            "goods_storage": "12",
                                            "sld_end_time": "2018-12-24 00:00:00"
                                        },
                                        "promotion_type": "xianshi",
                                        "promotion_price": 9,
                                        "show_price": 9,
                                        "start_time": "1519459620",
                                        "promotion_start_time": "2018年02月24日 16:07",
                                        "end_time": "1545580800",
                                        "promotion_end_time": "2018年12月24日 00:00"
                                    }
                                ]
                            }
                        },
                        "right": {
                            "3": {
                                "title": "adasd",
                                "subtitle": "asdas",
                                "gid": [
                                    "1937"
                                ],
                                "goods_info": [
                                    {
                                        "gid": "1937",
                                        "goods_name": "aaa",
                                        "goods_promotion_price": "0.00",
                                        "goods_marketprice": "12.00",
                                        "goods_price": "10.00",
                                        "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05941419841198704_240.jpg",
                                        "goods_state": "1",
                                        "goods_verify": "1",
                                        "extend_data": {
                                            "xianshi_gid": "132",
                                            "xianshi_id": "8",
                                            "xianshi_name": "春季限时清仓",
                                            "xianshi_title": "限时折扣",
                                            "xianshi_explain": "春季限时清仓 全场2折起!",
                                            "gid": "1937",
                                            "vid": "8",
                                            "goods_name": "aaa",
                                            "goods_price": "10.00",
                                            "xianshi_price": "9.00",
                                            "goods_image": "8_05941419841198704.jpg",
                                            "start_time": "1519459620",
                                            "end_time": "1545580800",
                                            "lower_limit": "1",
                                            "state": "1",
                                            "xianshi_recommend": "0",
                                            "goods_url": "http://site7.55jimu.com/index.php?app=goods&gid=1937",
                                            "image_url": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05941419841198704_60.jpg",
                                            "xianshi_discount": "9.0折",
                                            "goods_info": {
                                                "gid": "1937",
                                                "goods_commonid": "1338",
                                                "goods_name": "aaa",
                                                "goods_jingle": "aaa",
                                                "vid": "8",
                                                "store_name": "商联达家居店",
                                                "gc_id": "1880",
                                                "gc_id_1": "1868",
                                                "gc_id_2": "1880",
                                                "gc_id_3": "0",
                                                "brand_id": "0",
                                                "goods_price": "10.00",
                                                "goods_promotion_price": "0.00",
                                                "goods_promotion_type": "0",
                                                "goods_marketprice": "12.00",
                                                "goods_serial": "11111111",
                                                "goods_storage_alarm": "1",
                                                "goods_click": "8",
                                                "goods_salenum": "0",
                                                "goods_collect": "0",
                                                "goods_spec": "N;",
                                                "goods_storage": "12",
                                                "goods_image": "8_05941419841198704.jpg",
                                                "goods_state": "1",
                                                "goods_verify": "1",
                                                "goods_addtime": "1540797988",
                                                "goods_edittime": "1540802541",
                                                "areaid_1": "0",
                                                "areaid_2": "0",
                                                "areaid_3": null,
                                                "color_id": "0",
                                                "transport_id": "0",
                                                "goods_freight": "0.00",
                                                "goods_vat": "0",
                                                "goods_commend": "1",
                                                "goods_stcids": ",0,",
                                                "evaluation_good_star": "5",
                                                "evaluation_count": "0",
                                                "is_virtual": "0",
                                                "virtual_indate": "0",
                                                "virtual_limit": "0",
                                                "virtual_invalid_refund": "1",
                                                "is_fcode": "0",
                                                "is_appoint": "0",
                                                "is_presell": "0",
                                                "have_gift": "0",
                                                "is_own_shop": "1",
                                                "goods_rebate": "5",
                                                "goods_barcode": "",
                                                "fenxiao_yongjin": "0.00",
                                                "goods_type": "0",
                                                "sld_ladder_price": null,
                                                "province_id": "0",
                                                "city_id": "0",
                                                "area_id": "0",
                                                "goods_label": ""
                                            },
                                            "goods_salenum": "0",
                                            "goods_storage": "12",
                                            "sld_end_time": "2018-12-24 00:00:00"
                                        },
                                        "promotion_type": "xianshi",
                                        "promotion_price": 9,
                                        "show_price": 9,
                                        "start_time": "1519459620",
                                        "promotion_start_time": "2018年02月24日 16:07",
                                        "end_time": "1545580800",
                                        "promotion_end_time": "2018年12月24日 00:00"
                                    }
                                ]
                            }
                        }
                    }
                }
            },
            {
                "type": "kefu",
                "text": "客服电话：",
                "tel": "1234567890"
            }
        ],
        "has_more": 0,
        "site_name": ""
    }
}
     */
    /*
     * 首页装修数据
     */
    public function index_data()
    {
        $model_mb_special = M('cwap_home','pin_ladder');
        $model_goods = Model('goods');

        $shop_id = isset($_GET['shop_id']) ? $_GET['shop_id'] : 0;

        $condition['shop_id'] = $shop_id;

        // 城市分站
        $curSldCityId = intval($_GET['bid']) ? intval($_GET['bid']) : 0;
        if($curSldCityId){
            $condition['city_id'] = $curSldCityId;
        }

        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo($condition);
        $data =unserialize($data['home_data']);

        if ($shop_id == 0) {
            // 首页模版分步加载数据
            $limit_group_p = (isset($_GET['lp']) && intval($_GET['lp']) > 0) ? intval($_GET['lp']) : 0; // 第几组
            $has_more = 0; // 是否有下一组数据
            $limit = 0;//(isset($_GET['l']) && intval($_GET['l']) > 0) ? intval($_GET['l']) : 0; // 每组几条数据
            if (($data_count = count($data)) > $limit && $limit > 0) {
                $data_group = array_chunk($data,$limit);
                $data = $data_group[$limit_group_p];
                $next_group_index = $limit_group_p+1;
                if (isset($data_group[$next_group_index])) {
                    $has_more = 1;
                }
            }
        }
        //对数据重新排序
        $data_new = array();
//        print_r($data);die;
        $new_data = array();
        if ($data) {
            foreach ($data as $k => $v){
                if(isset($v['data']) && !empty($v['data'])){
                    foreach ($v['data'] as $i_k => $i_v) {
                        if(isset($i_v['img'])){
                            $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                            //url处理，用户app和小程序的url处理
                            if($i_v['url_type'] == 'url'){
                                $url_new = $this -> match_diy_url($i_v['url']);
                                $i_v['url_type_new'] = $url_new['url_type_new'];
                                $i_v['url_new'] = $url_new['url_new'];
                            }
                            $v['data'][$i_k] = $i_v;
                        }
                    }
                }
                if($v['type'] == 'fuwenben'){
                    $v['text'] = htmlspecialchars_decode($v['text']);
                    $data_new[] = $v;
                }else if($v['type'] == 'tuijianshangpin') {
                    //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                    if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                        foreach ($v['data']['gid'] as $key => $val) {
                            $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                            if (!empty($goods_info)) {
                                $goods_info['goods_image'] = thumb($goods_info, 310);
                                $v['data']['goods_info'][] = $goods_info;
                            }
                        }

                        // 获取最终价格
                        $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                        $data_new[] = $v;
                    }
                }else if($v['type'] == 'dapei') {
                    //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                    if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                        foreach ($v['data']['gid'] as $key => $val) {
                            $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                            if (!empty($goods_info)) {
                                $goods_info['goods_image'] = thumb($goods_info, 310);
                                $v['data']['goods_info'][] = $goods_info;
                            }
                        }

                        // 获取最终价格
                        $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                        $data_new[] = $v;
                    }
                }else if($v['type'] == 'fzkb'){
                    //                $v['text'] = round($v['text']/23,2);//把像素转化为rem 用于做适配
                    $data_new[] = $v;
                }else if($v['type'] == 'lunbo'){
                    $lunbo_data = array();
                    foreach ($v['data'] as $lb_k => $lb_v){
                        $lunbo_data[] = $lb_v;
                    }
                    $v['data'] = $lunbo_data;
                    list($width,$height)=getimagesize($lunbo_data[0]['img']);
                    $v['width'] = 750;
                    $v['height'] = 750*$height/$width;
                    $data_new[] = $v;

                }else if($v['type'] == 'tupianzuhe'){
                    $tupianzuhe_data = array();
                    $tupianzuhe_data['type'] = $v['type'];
                    $tupianzuhe_data['sele_style'] = $v['sele_style'];
                    $new_data = array();
                    foreach ($v['data'] as $lb_k => $lb_v){
                        $new_data[] = $lb_v;
                    }
                    $tupianzuhe_data['data'] = $new_data;
                    $data_new[] = $tupianzuhe_data;
                }else if($v['type'] == 'huodong'){
                    $use_fixed_search_type = true;

                    $huodong_data = array();
                    $huodong_data['type'] = $v['type'];
                    $huodong_data['sele_style'] = $v['sele_style'];

                    switch ($huodong_data['sele_style']) {
                        case '1':
                            // 限时折扣
                            $model_xian = Model('p_xianshi_goods');
                            $xianCondition = array();
                            $xianCondition['state'] = $model_xian::XIANSHI_GOODS_STATE_NORMAL;
                            $xianCondition['start_time'] = array('lt', TIMESTAMP);
                            $xianCondition['end_time'] = array('gt', TIMESTAMP);
                            $xian_goods_list = $model_xian->getXianshiGoodsList($xianCondition);
                            $extend_data_list = array();
                            $goods_ids = array();
                            if (!empty($xian_goods_list)) {

                                foreach ($xian_goods_list as $key => $value) {
                                    $goods_ids[] = $value['gid'];
                                    $value['sld_end_time'] = date("Y-m-d H:i:s",$value['end_time']);
                                    $extend_data_list[$value['gid']] = $value;
                                }
                            }
                            break;
                        case '2':
                            // 团购
                            $model_tuan = Model('tuan');
                            $tuanCondition = array();
                            $tuan_goods_list = $model_tuan->getTuanOnlineList($tuanCondition,'','','gid,tuan_price,virtual_quantity,buy_quantity,tuan_discount,vid,end_time');
                            $extend_data_list = array();
                            $goods_ids = array();
                            foreach ($tuan_goods_list as $key => $value) {
                                $value['sld_end_time'] = (strtotime($value['end_time_text']) > time()) ? $value['end_time_text'] : '';
                                $value['buyed_quantity'] = $value['virtual_quantity'] + $value['buy_quantity'];
                                $goods_ids[] = $value['gid'];
                                $extend_data_list[$value['gid']] = $value;
                            }
                            break;

                        default:
                            // 拼团
                            // 获取拼团 类型的商品(bbc_goods) id

                            $allow_search_type = array(1);

                            $model_pin = M('pin');
                            $pinCondition = array();
                            $pin_goods_list = $model_pin->getPinList($pinCondition,0);
                            $extend_data_list = array();
                            $goods_ids = array();
                            foreach ($pin_goods_list as $key => $value) {
                                $goods_ids[] = $value['gid'];
                                $extend_data_list[$value['gid']] = $value;
                            }
                            break;
                    }

                    if (isset($v['data']) && is_array($v['data']) && !empty($v['data'])) {
                        foreach ( $v['data'] as $huodong_k => $huodong_v){
                            foreach ($huodong_v as $huodong_a_k => $huodong_a_v) {
                                if (is_array($huodong_a_v) && !empty($huodong_a_v)) {
                                    foreach ($huodong_a_v as $huodong_b_k => $huodong_b_v) {
                                        if(isset($huodong_b_v['gid'])){
                                            if (is_array($huodong_b_v['gid']) && !empty($huodong_b_v['gid'])) {
                                                foreach ($huodong_b_v['gid'] as $huodong_c_k => $huodong_c_v) {
                                                    // 获取 商品信息
                                                    $goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_c_v,'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
                                                    if(!empty($goods_info)){
                                                        $goods_info['goods_image'] = thumb($goods_info, 320);
                                                        $goods_info['extend_data'] = $extend_data_list[$huodong_c_v];
                                                        $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'][$huodong_c_k] = $goods_info;
                                                    }
                                                }
                                            }else{
                                                // 获取 商品信息
                                                $goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_b_v['gid'],'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
                                                if(!empty($goods_info)){
                                                    $goods_info['goods_image'] = thumb($goods_info, 320);
                                                    $goods_info['extend_data'] = $extend_data_list[$huodong_b_v['gid']];
                                                    $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = $goods_info;
                                                }
                                            }
                                        }
                                    }
                                }

                                // 获取最终价格
                                $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = Model('goods_activity')->rebuild_goods_data($huodong_v[$huodong_a_k][$huodong_b_k]['goods_info']);
                            }

                            $huodong_data['data'][$huodong_k] = $huodong_v;
                        }
                    }

                    $data_new[] = $huodong_data;
                }else{
                    $data_new[] = $v;
                }

            }
        }
        if ($shop_id == 0) {
            $site_name = C('site_name') ? C('site_name') : '';
            output_data(array('tmp_data'=>$data_new,'has_more'=>$has_more,'site_name'=>$site_name));
//            $this->_output_special(array('tmp_data'=>$data_new,'has_more'=>$has_more,'site_name'=>$site_name), $_GET['type']);
        }else{
            output_data(array('tmp_data'=>$data_new,'has_more'=>$has_more,'site_name'=>$site_name));
//            $this->_output_special($data_new, $_GET['type']);
        }
    }
    public function match_diy_url($url){
        $result = array();
        //        $url = 'http://www.slodon.cn/cwap/cwap_product_detail.html?gid=1177';
        //        $url = 'http://www.slodon.cn/cwap/cwap_go_shop.html?vid=19';
        //        $url = 'http://www.slodon.cn/cwap/cwap_subject.html?topic_id=6';
//                $url = 'http://www.slodon.cn/cwap/cwap_shop_list.html';

        //        $url = 'http://www.slodon.cn/cwap/cwap_product_list.html?keyword=T%E6%81%A4';
        //        $url = 'https://www.guirengou.com/cwap/red_get_list.html';
        //        $url = 'http://www.slodon.cn/cwap/cwap_user_points.html';
        //        $url = 'cwap_product_list.html?gc_id=316';
        //        $url = 'https://www.guirengou.com/cwap/cwap_product_list.html?keyword=%E9%98%B2%E6%99%92%20';


        if(strstr($url,'index.php?app=goods')){
            //url转为商品详情页
            $arr = $this->parse_url_param(htmlspecialchars_decode($url));
            if($arr['gid'] > 0){
                $result['url_type_new'] = 'goods';
                $result['url_new'] = $arr['gid'];
            }
        }else if(strstr($url,'cwap_product_detail.html?gid')){
            //url转为商品详情页
            $arr = $this->parse_url_param($url);
            if($arr['gid'] > 0){
                $result['url_type_new'] = 'goods';
                $result['url_new'] = $arr['gid'];
            }
        }else if(strstr($url,'cwap_go_shop.html?vid')){
            //url转为店铺
            $arr = $this->parse_url_param($url);
            if($arr['vid'] > 0){
                $result['url_type_new'] = 'vendor';
                $result['url_new'] = $arr['vid'];
            }
        }else if(strstr($url,'cwap_subject.html?topic_id')){
            //url转为专题
            $arr = $this->parse_url_param($url);
            if($arr['topic_id'] > 0){
                $result['url_type_new'] = 'special';
                $result['url_new'] = $arr['topic_id'];
            }
        }else if(strstr($url,'cwap_shop_list.html')){
            //url转为店铺列表
            $result['url_type_new'] = 'vendorlist';
            $result['url_new'] = '';

        }else if(strstr($url,'cwap_product_list.html?gc_id')){
            //url转为商品列表（按分类）
            $arr = $this->parse_url_param($url);
            if($arr['gc_id'] > 0){
                $result['url_type_new'] = 'goodscat';
                $result['url_new'] = $arr['gc_id'];
            }
        }else if(strstr($url,'cwap_product_list.html?keyword')){
            //url转为商品列表（按关键词）
            $arr = $this->parse_url_param($url);
            if(isset($arr['keyword'])){
                $result['url_type_new'] = 'goodslist';
                $result['url_new'] = urldecode($arr['keyword']);
            }
        }else if(strstr($url,'cwap_user_points.html')){
            //url转为签到
            $result['url_type_new'] = 'sighlogin';
            $result['url_new'] = '';
        }else if(strstr($url,'red_get_list.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'voucherlist';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_pro_cat.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'fenlei';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_cart.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'cart';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_user.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'usercenter';
            $result['url_new'] = '';
        }else if(strstr($url,'pin_index.html')){
            //url拼团列表页
            $result['url_type_new'] = 'pin_index';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_tuan.html')){
            //url团购列表页
            $result['url_type_new'] = 'tuan_index';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_discount.html')){
            //url团购列表页
            $result['url_type_new'] = 'xianshi_index';
            $result['url_new'] = '';
        }else if(strstr($url,'points')){
            //url积分商城首页
            $result['url_type_new'] = 'points_shop';
            $result['url_new'] = '';
        }else{
            //不满足以上条件则不跳转
            $result['url_type_new'] = '';
            $result['url_new'] = '';
        }
        return $result;
    }
    /**
     * 获取url中的各个参数
     * 类似于 pay_code=alipay&bank_code=ICBC-DEBIT
     * @param type $str
     * @return type
     */
    public function parse_url_param($str)
    {
        $data = array();
        $arr=array();
        $p=array();
        $arr=explode('?', $str);
        $p = explode('&', $arr[1]);
        foreach ($p as $val) {
            $tmp = explode('=', $val);
            $data[$tmp[0]] = $tmp[1];
        }
        return $data;
    }
    /**
     * 首页(用于微信小程序)
     */
    public function index_xcx() {
        $model_mb_special = Model('cwap_home');
        $model_goods = Model('goods');
        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo();
        $data =unserialize($data['home_data']);
        //对数据重新排序
        $data_new = array();
//        print_r($data);die;
        $new_data = array();
        foreach ($data as $k => $v){
            if(isset($v['data']) && !empty($v['data'])){
                foreach ($v['data'] as $i_k => $i_v) {
                    if(isset($i_v['img'])){
                        $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                        $v['data'][$i_k] = $i_v;
                    }
                }
            }
            if($v['type'] == 'fuwenben'){
                $v['text'] = htmlspecialchars_decode($v['text']);
                $data_new[] = $v;
            }else if($v['type'] == 'tuijianshangpin') {
                //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                    foreach ($v['data']['gid'] as $key => $val) {
                        $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                        if (!empty($goods_info)) {
                            $goods_info['goods_image'] = thumb($goods_info, 310);
                            $v['data']['goods_info'][] = $goods_info;
                        }
                    }

                    // 获取最终价格
                    $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                    $data_new[] = $v;
                }
            }else if($v['type'] == 'dapei') {
                //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                    foreach ($v['data']['gid'] as $key => $val) {
                        $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                        if (!empty($goods_info)) {
                            $goods_info['goods_image'] = thumb($goods_info, 310);
                            $v['data']['goods_info'][] = $goods_info;
                        }
                    }
                    
                    // 获取最终价格
                    $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                    $data_new[] = $v;
                }
            }else if($v['type'] == 'fzkb'){
//                $v['text'] = round($v['text']/23,2);//把像素转化为rem 用于做适配
                $data_new[] = $v;
            }else if($v['type'] == 'lunbo'){
                $lunbo_data = array();
                foreach ($v['data'] as $lb_k => $lb_v){
                    $lunbo_data[] = $lb_v;
                }
                $v['data'] = $lunbo_data;
                $data_new[] = $v;
            }else{
                $data_new[] = $v;
            }

        }
        $this->_output_special($data_new, $_GET['type']);
    }
    /**
     * @api {get} index.php?app=index&mod=goods_detail&sld_addons=pin_ladder 商品详情阶梯团购信息
     * @apiVersion 0.1.0
     * @apiName goods_detail
     * @apiGroup Ladder
     * @apiDescription 商品详情阶梯团购信息
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=index&mod=goods_detail&sld_addons=pin_ladder
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} gid 商品id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
        "pin_info": {
            "id": "206",
            "goods_price": "12.00",
            "goods_name": "ceshi 红 5.0 xxl",
            "sld_ding_price": "100.00",
            "sld_status": "1",
            "sld_start_time": "2018-11-15",
            "sld_end_time": "2018-11-30",
            "sld_sheng_time": 889868,
            "sld_success_time": "24.00",
            "sld_max_buy": "2",
            "sld_already_num": 0,
            "sld_all_num": 0,
            "sld_now_price": 0
        },
        "ladder_info": [
            {
                "id": "23",
                "pin_goods_id": "243",
                "pin_id": "206",
                "gid": "2048",
                "people_num": 5
                "pay_money": 20
            }
        ]
    }
}

     */
    public function goods_detail()
    {
        $gid = intval($_GET['gid']);
        $model = Model();
        $return_data = [];
        try{
            $goods_info = $model->table('goods')->where(['gid'=>$gid])->find();
            $pin_info = $model->table('pin_ladder,pin_goods_ladder')->join('left')->on('pin_ladder.id=pin_goods_ladder.sld_pin_id')->where([
                'pin_goods_ladder.sld_gid'=>$gid,
                'pin_ladder.sld_start_time'=>['lt',time()],
                'pin_ladder.sld_end_time'=>['gt',time()],
                'pin_ladder.sld_status'=>1,
            ])->field('pin_ladder.id,pin_ladder.sld_start_time,pin_ladder.sld_end_time,pin_ladder.sld_success_time,pin_ladder.sld_max_buy,pin_ladder.sld_status,pin_goods_ladder.sld_pin_price,pin_goods_ladder.sld_stock,pin_goods_ladder.sld_pin_price')->find();
            $ladder_price = $model->table('pin_money_ladder')->where(['gid'=>$gid,'pin_id'=> $pin_info['id']])->select();
            $pin_order = $model->table('pin_order')->where(['pin_id'=>$pin_info['id'],'gid'=>$gid,'order_state'=>['in','20,30']])->select();
            $return_data['pin_info']['id'] = $pin_info['id'];
            $return_data['pin_info']['goods_price'] = $goods_info['goods_price'];
            $return_data['pin_info']['goods_name'] = $goods_info['goods_name'];
            $return_data['pin_info']['sld_ding_price'] = $pin_info['sld_pin_price'];
            $return_data['pin_info']['sld_status'] = $pin_info['sld_status'];
            $return_data['pin_info']['sld_start_time'] = date('Y-m-d',$pin_info['sld_start_time']);
            $return_data['pin_info']['sld_end_time'] = date('Y-m-d',$pin_info['sld_end_time']);
            $return_data['pin_info']['sld_sheng_time'] = $pin_info['sld_end_time']-time()?:0;
            $return_data['pin_info']['sld_success_time'] = $pin_info['sld_success_time'];
            $return_data['pin_info']['sld_max_buy'] = $pin_info['sld_max_buy'];
            $return_data['pin_info']['sld_already_num'] = count($pin_order);
            $ladder_price_people = low_array_column($ladder_price,'people_num');
            sort($ladder_price_people);
            $ladder_price_people = end($ladder_price_people);
            $return_data['pin_info']['sld_all_num'] = $ladder_price_people?:0;
                //当前价格单独处理
            $return_data['pin_info']['sld_now_price'] = $goods_info['goods_price'];
            array_multisort(low_array_column($ladder_price,'people_num'),SORT_ASC,$ladder_price);
            foreach($ladder_price as $k=>$v) {
                    if($return_data['pin_info']['sld_already_num'] >= $v['people_num']){
                        $return_data['pin_info']['sld_now_price'] = ($v['pay_money']-$pin_info['sld_pin_price'])?:0;
                    }
            }
            $return_data['ladder_info'] = $ladder_price;
            echo json_encode(['status'=>200,'data'=>$return_data]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }

    }
    /**
     * 获取首页title和搜索栏颜色
     */
    public function index_title() {
        $model_mb_special = Model('cwap_home');
        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo();
        //对数据重新排序
        $data_new = array();
        $data_new['title'] = $data['home_desc'];
        $data_new['sousuo_color'] = $data['home_sousuo_color'];
//        print_r($data);die;
        $this->_output_special($data_new, $_GET['type']);
    }
    /**
     * 获取专题页标题
     */
    public function topic_title() {
        $model_mb_special = Model('cwap_topic');
        //获取专题页数据
        $data = $model_mb_special->getTopicInfoByTopicID($_GET['topic_id']);

        //对数据重新排序
        $data_new = array();
        $data_new['title'] = $data['topic_desc'];
        $this->_output_special($data_new, $_GET['type']);
    }


    //获取首页分页的商品数据
    public function getGoodsByPage(){
        $page=$_GET['page'];
        $model_mb_special = Model('mb_special');
        $rows=$_GET['rows'];
        $total=$page*$rows;
        $totals=($page+1)*$rows;
        $data = $model_mb_special->getMbSpecialItemUsableListByID($_GET['special_id']);
        foreach($data as $k=>$v){
            if($k==4){
                $length=count($v['goods']['item']);
                $arr=array();
                if($totals<=$length){
                    //从截取数组长度
                    for($i=$total;$i<$totals;$i++){
                        array_push($arr,$v['goods']['item'][$i]);
                    }
                    $hasmore=1;
                }
                if($total>$length){
                    $hasmore=0;
                }
                if($total<$length && $length<=$totals){
                    $arr=array();
                    for($i=$total;$i<$length;$i++){
                        array_push($arr,$v['goods']['item'][$i]);
                    }
                    $hasmore=0;
                }
            }
        }
        output_data(array('goods_info'=>array_values($arr),'hasmore'=>$hasmore));
    }
    /**
     * 默认搜索词列表
     */
    public function search_key_list() {
        //热门搜索
        $list = @explode(',',C('hot_search'));
        if (!$list || !is_array($list)) {
            $list = array();
        }

        //历史搜索
        if (cookie('his_sh') != '') {
            $his_search_list = explode('~', cookie('his_sh'));
        }

        $data['list'] = $list;
        $data['his_list'] = is_array($his_search_list) ? $his_search_list : array();
        output_data($data);
    }
    /**
     * 默认搜索词列表(用于微信小程序)
     */
    public function search_key_list_xcx() {
        //热门搜索
        $list = @explode(',',C('hot_search'));
        if (!$list || !is_array($list)) {
            $list = array();
        }
        echo json_encode(array('hot_list'=>$list));
    }

    /**
     * 热门搜索列表
     */
    public function search_hot_info() {
        //热门搜索
        if (C('rec_search') != '') {
            $rec_search_list = @unserialize(C('rec_search'));
            $rec_value = array();
            foreach($rec_search_list as $v){
                $rec_value[] = $v['value'];
            }

        }
        output_data(array('hot_info'=>$result ? $rec_value : array()));
    }

    /**
     * 高级搜索
     */
    public function search_adv() {
        $area_list = Model('area')->getAreaList(array('area_deep'=>1),'area_id,area_name');
        if (C('contract_allow') == 1) {
            $contract_list = Model('contract')->getContractItemByCache();
            $_tmp = array();$i = 0;
            foreach ($contract_list as $k => $v) {
                $_tmp[$i]['id'] = $v['cti_id'];
                $_tmp[$i]['name'] = $v['cti_name'];
                $i++;
            }
        }
        output_data(array('area_list'=>$area_list ? $area_list : array(),'contract_list'=>$_tmp));
    }
}
