<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // 生成应用公共文件
    '__file__' => ['common.php'],

    // 定义demo模块的自动生成 （按照实际定义的文件名生成）
    'v1'     => [
        '__file__'   => ['common.php'],
        '__dir__'    => ['behavior', 'controller', 'model', 'view'],
        'controller' => ['Index', 'Base', 'UserType','User','Sms','Cart','Order','Goods','Store','Vendor','Buy','Follow','Payment','Teacher','Chongzhi','Refund'],
        'model'      => ['User', 'UserType','Sms','UserToken','UserCart','UserOrder','Goods','StoreInfo','VendorInfo','Favorable','FavorableQuota','FavorableRule','GoodsActivity','ActivityCache','Tuan','CacheTime','Pxianshigoods','TodayBuy','TodayBuyDetail','SuiteGoods','Cache','MBuy','Stats','UserBuy','Store','Address','Invoice','FirstOrder','Payment','Predeposit','Red','StoreGoods','Grade','EvaluateStore','EvaluateGoods','VendorLabel','Seller','MyGoods','BrowserHistory','SnsGoods','Area','VendorGlmb','GoodsClass','Favorites','Cache','VendorNavigation','Seo','Dian','Transport','Points','Refund','Trade','Search','Citysite','Types','GoodsClassNav','Brand','Attribute','GoodsAttrIndex','Message','Refund','Order','SendMemberMessage','MemberMsgSetting'],
        'view'       => ['index/index'],
    ],
    'v2'     => [
        '__file__'   => ['common.php'],
        '__dir__'    => ['behavior', 'controller', 'model', 'view'],
        'controller' => ['Index', 'Base', 'UserType','User','Sms','Cart','Order','Goods','Store','Vendor','Buy','Follow','Payment','Teacher','Chongzhi','Refund'],
        'model'      => ['User', 'UserType','Sms','UserToken','UserCart','UserOrder','Goods','StoreInfo','VendorInfo','Favorable','FavorableQuota','FavorableRule','GoodsActivity','ActivityCache','Tuan','CacheTime','Pxianshigoods','TodayBuy','TodayBuyDetail','SuiteGoods','Cache',"MBuy","Stats",'UserBuy','Store','Address','Invoice','FirstOrder','Payment','Predeposit','Red','StoreGoods','Grade','EvaluateStore','EvaluateGoods','VendorLabel','Seller','MyGoods','BrowserHistory','SnsGoods','Area','VendorGlmb','GoodsClass','Favorites','Cache','VendorNavigation','Seo','Dian','Transport','Points','Refund','Trade','Search','Citysite','Types','GoodsClassNav','Brand','Attribute','GoodsAttrIndex','Message','Refund','Order','SendMemberMessage','MemberMsgSetting'],
        'view'       => ['index/index'],
    ],

    // 其他更多的模块定义
];
