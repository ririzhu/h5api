 <!DOCTYPE html>
<html>
 <head>
  <meta charset="UTF-8">
  <title>支付详情</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <meta name="format-detection" content="telephone=no">
  <link rel="stylesheet" type="text/css" href="../css/cwap_base.css">
  <link rel="stylesheet" type="text/css" href="../css/font-awesome.min.css">
  <link rel="stylesheet" type="text/css" href="../css/cwap_main.css">
  <link rel="stylesheet" type="text/css" href="../css/cwap_child.css">
  <link rel="stylesheet" type="text/css" href="../css/cwap_cart.css">
  <style>
  span{
    color: #ff8400;
  }
  .contact_fixed{
   text-align: center;
  /* margin-right: 50px; */
   font-size: 17px;
   color: #fff;
   background: #ED5564;
   width: 90%;
   margin: 0 auto;
   padding: 6px;
   border-radius: 5px;
  }
</style> 
 </head> 
 <body>
 <header id="header_user" class="fixed">
  <div class="header-wrap">
   <div class="header-l">
    <a href="javascript:history.go(-1)">
     <i class="back"></i>
    </a>
   </div>
   <div class="header-title">
    <h1>支付详情</h1>
   </div>
   <div class="header-r">
    <a id="header-nav" href="javascript:void(0);"><i class="more"></i></a>
   </div>
  </div>
  <div class="bbctouch-nav-layout">
   <div class="bbctouch-nav-menu">
    <span class="arrow"></span>
    <ul>
     <li><a href="../index.html"><i class="home"></i>首页</a></li>
     <li><a href="../cwap_pro_cat.html"><i class="categroy"></i>分类</a></li>
<!--     <li><a href="../cwap_cart.html"><i class="cart"></i>购物车</a></li>-->
     <li><a href="../cwap_user.html"><i class="member"></i>我的商城</a></li>
<!--     <li><a href="../cwap_im_list.html"><i class="message"></i>消息</a></li>-->
    </ul>
   </div>
  </div>
 </header>
   <article >
     <div id="main" style="margin-top:5px"> 
       <p class="totalprice" style="text-indent: 15px;">金额：<span><?php echo $_POST['total_fee'];?></span></p>
      <div class="p_content" style="font-size: 15px;color: #333;padding: 33px 30px;">
      <p class="ordernum" style="margin-bottom: 7px;">交易单号：<span><?php echo $_POST['order_id'];?></span></p>
      <p>订单状态：<span>交易成功</span></p>
      <p>下单时间：<span><?php echo date('Y-m-d H:i:s',time());?></span></p>
      </div>
       <div class="contact_fixed"> 
     <div class="cartSettlement01"> 
      <a onclick="" href="../cwap_order_list.html" class="full_btn" style="color: #fff;">确定</a>
     </div> 
    </div> 
     </div> 
  </article>
 <script type="text/javascript" src="../js/zepto.min.js"></script>
 <script type="text/javascript" src="../js/cwap_config.js"></script>
 <script type="text/javascript" src="../js/template.js"></script>
 <script type="text/javascript" src="../js/cwap_common.js"></script>
 <script type="text/javascript" src="../js/simple-plugin.js"></script>
 <script>
  $("#header-nav").click(function () {
   $(".bbctouch-nav-layout").toggleClass('show');
  });
 </script>
 </body>
</html>