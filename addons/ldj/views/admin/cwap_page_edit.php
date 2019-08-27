<?php defined('DYMall') or exit('Access Invalid!');?>
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/colpick/css/colpick.css">
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/colpick/css/demo.css">
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/colpick/css/style.css">
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/js/bootstrap/bootstrap.css">
<link rel="stylesheet" type="text/css" href="<?php echo ADMIN_TEMPLATES_URL;?>/css/response_new.css">
<link href="<?php echo STATIC_SITE_URL;?>/js/layer/theme/default/layer.css" rel="stylesheet" type="text/css"/>
<div class="page">
  <!-- 页面导航 -->
  <div class="fixed-bar">
    <div class="item-title">
      <ul class="tab-base">
        <?php   foreach($output['menu'] as $menu) {  if($menu['menu_key'] == $output['menu_key']) { ?>
        <li><a href="JavaScript:void(0);" class="current"><span><?php echo $menu['menu_name'];?></span></a></li>
        <?php }  else { ?>
        <li><a href="<?php echo $menu['menu_url'];?>" ><span><?php echo $menu['menu_name'];?></span></a></li>
        <?php  } }  ?>
      </ul>
    </div>
  </div>
  <div class="fixed-flag"></div>
  <!-- 列表 -->
  <div class="mb-special-layout">
    <div class="module-list_new">
            <ul class="mobile_zujian">
                <li style="background: url('<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/lunbo_li.png')no-repeat;" data_type="lunbo"><span class="mz_text">轮播图</span></li>
                <li style="background: url('<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/nav_li.png')no-repeat;" data_type="nav"><span class="mz_text">导航</span></li>
                <!--<li  style="background: url('<?php /*echo ADMIN_TEMPLATES_URL;*/?>/images/zidingyi/huodong_li.png')no-repeat;" data_type="huodong"><span class="mz_text">活动</span></li>-->
                <li  style="background: url('<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/tpzh_li.png')no-repeat;" data_type="tupianzuhe"><span class="mz_text">图片组合</span></li>
                <!--<li style="background: url('<?php /*echo ADMIN_TEMPLATES_URL;*/?>/images/zidingyi/tjsp_li.png')no-repeat;" data_type="tuijianshangpin"><span class="mz_text">推荐商品</span></li>-->
                <!--<li style="background: url('<?php /*echo ADMIN_TEMPLATES_URL;*/?>/images/zidingyi/dapei_li.png')no-repeat;" data_type="dapei"><span class="mz_text">搭配</span></li>-->
                <li  style="background: url('<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/fwb_li.png')no-repeat;" data_type="fuwenben"><span class="mz_text">富文本</span></li>
                <li  style="background: url('<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/kefu_li.png')no-repeat;" data_type="kefu"><span class="mz_text">客服</span></li>
                <li  style="background: url('<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/gonggao_li.png')no-repeat;" data_type="gonggao"><span class="mz_text">公告</span></li>
                <li  style="background: url('<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/fzkb_li.png')no-repeat;" data_type="fuzhukongbai"><span class="mz_text">辅助空白</span></li>
                <li style="background: url('<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/fzx_li.png')no-repeat;" data_type="fuzhuxian"><span class="mz_text">辅助线</span></li>
            </ul>

      </div>
    <div id="part_left">
        <form id="subject_save" method="post" action="<?php echo urlAdmin('sld_cwap_home', 'zdy_save',['sld_addons'=>'ldj']);?>">
            <input type="hidden" name="page_type" value="<?php echo $_GET['type']?>">
            <input type="hidden" name="id" value="<?php echo $_GET['id'];?>">
          <div class="mb-item-box">
          <div id="item_list" class="item-list">
<div class="special-item" style="height: 50px;margin-top: -60px"></div>


            <?php if(!empty($output['list']) && is_array($output['list'])&&count($output['list']['item_data'])>0) {?>
            <?php foreach($output['list']['item_data'] as $key => $value) {?>
                   <?php if($value['type']=='kefu'){?>
                        <div class="special-item kefu_part default_kefu_part">
                            <div bbctype="item_content" class="content">
                                <div class="kefu_part_input"><input class="input_kefu"  name="item_data[<?php echo $key;?>][type]" type="hidden" value="kefu"/>
                                    <input class="input_kefu_text" name="item_data[<?php echo $key;?>][text]"  type="hidden"  value="<?php echo $value['text'];?>"/>
                                    <input class="input_kefu_tel" name="item_data[<?php echo $key;?>][tel]" type="hidden" value="<?php echo $value['tel'];?>"/></div>
                                <i class="kefutel_icon iconfontfa fa-phone"></i><span class="kefu_text"><?php echo $value['text'];?></span><span
                                    class="kefu_tel"><?php echo $value['tel'];?></span><i
                                    class="right_jiantou iconfontfa fa-chevron-right"></i></div>
                            <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i
                                        class="iconfontfa fa-trash-o"></i></a></div>
                        </div>
                    <?php }?>
                 <?php if($value['type']=='lunbo'){?>
                        <div data_lunbo_index_t="<?php echo $key;?>" class="special-item lunbo_part default_lunbo_part">
                            <div bbctype="item_content" class="content">
                                <div class="lunbo_part_input">
                                    <input class="input_lunbo" name="item_data[<?php echo $key;?>][type]" type="hidden" value="lunbo"/>
                                </div>
                                <div class="lunbo_upload_input">
                                    <ul>
                                        <?php foreach ($value['data'] as $lunbo_k => $lunbo_v){?>
                                        <li data_lunbo_input="<?php echo $lunbo_k;?>">
                                            <input class="input_lunbo_img" name="item_data[<?php echo $key;?>][data][<?php echo $lunbo_k;?>][img]" type="hidden" value="<?php echo $lunbo_v[img];?>"/>
                                            <input class="input_lunbo_title" name="item_data[<?php echo $key;?>][data][<?php echo $lunbo_k;?>][title]" type="hidden" value="<?php echo $lunbo_v[title];?>"/>
                                            <input class="input_lunbo_url_type" name="item_data[<?php echo $key;?>][data][<?php echo $lunbo_k;?>][url_type]" type="hidden" value="<?php echo $lunbo_v[url_type];?>"/>
                                            <input class="input_lunbo_url" name="item_data[<?php echo $key;?>][data][<?php echo $lunbo_k;?>][url]" type="hidden" value="<?php echo $lunbo_v[url];?>"/>
                                        </li>
                                        <?php }?>
                                    </ul>
                                </div>
                                <div class="lunbo_part_zhanshi">
                                    <img src ="<?php $img='';for($i=count($value['data'])-1;$i>=0;$i--){if(!empty($value['data'][$i]['img'])){$img = $value['data'][$i]['img'];break;}}echo $img?$img:ADMIN_TEMPLATES_URL.'/images/zidingyi/wap_def_lunbo.jpg';?>"/>
                                </div>
                            </div>
                            <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i class="iconfontfa fa-trash-o"></i></a></div>
                        </div>

                 <?php }?>
                <?php if($value['type']=='fuwenben'){?>
                        <div class="special-item fuwenben_part">
                            <div bbctype="item_content" class="content">
                                <div class="fuwenben_part_input"><input class="input_fuwenben" name="item_data[<?php echo $key;?>][type]" type="hidden" value="fuwenben"/>
                                    <input  class="input_fuwenben_text" name="item_data[<?php echo $key;?>][text]" type="hidden" value='<?php echo $value[text];?>'/></div>
                                <div class="fuwenben_con">
                                    <?php if(!empty($value[text])){?>
                                        <?php echo htmlspecialchars_decode($value[text]);?>
                                    <?php }else{?>
                                        <div class="default_fuwenben_con">
                                            点此编辑『富文本』内容:你可以对文字进行加粗、斜体、下划线、删除线、文字颜色、背景色、以及字号大小等简单排版操作。也可在这里插入图片、并对图片加上超级链接，方便用户点击。
                                        </div>
                                    <?php }?>
                                </div>
                            </div>
                            <div class="handle"><a bbctype="btn_del_item" data-item-id="23" href="javascript:;"><i class="iconfontfa fa-trash-o"></i></a></div>
                        </div>
                <?php }?>
                <?php if($value['type']=='gonggao'){?>
                        <div class="special-item gonggao_part default_gonggao_part gonggao_'+length+'">
                            <div bbctype="item_content" class="content">
                                <div class="gonggao_part_input">
                                    <input class="input_gonggao" name="item_data[<?php echo $key;?>][type]" type="hidden" value="gonggao"/>
                                    <input class="input_gonggao_lianjie_type" name="item_data[<?php echo $key;?>][lianjie_type]" type="hidden" value="<?php echo $value[lianjie_type];?>"/>
                                    <input class="input_gonggao_lianjie_url" name="item_data[<?php echo $key;?>][lianjie_url]" type="hidden" value="<?php echo $value[lianjie_url];?>"/>
                                    <input  class="input_gonggao_text" name="item_data[<?php echo $key;?>][text]" type="hidden"  value="<?php echo $value[text];?>"/></div>
                                <div class="gonggao_part_zhanshi">
                                    <span class="gonggao_icon">
                                        <i class="iconfontfa fa-volume-up"></i></span>
                                    <span class="gonggao_area">
                                        <?php if($value[text]){?>
                                            <?php echo $value[text];?>
                                        <?php }else{?>
                                            公告：请填写内容,将会在手机上滚动显示!!!
                                        <?php }?>
                                    </span>
                                </div>
                            </div>
                            <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i class="iconfontfa fa-trash-o"></i></a></div>
                        </div>
                <?php }?>
                <?php if($value['type']=='tuijianshangpin'){?>
                        <div data_tjsp_index="<?php echo $key;?>" class="special-item tuijianshangpin_part default_tuijianshangpin_part">
                            <div bbctype="item_content" class="content">
                                <div class="tuijianshangpin_part_input">
                                    <input class="input_tuijianshangpin" name="item_data[<?php echo $key;?>][type]" type="hidden" value="tuijianshangpin"/>
                                    <input class="input_tuijian_style" name="item_data[<?php echo $key;?>][show_style]" type="hidden" value="<?php echo $value[show_style];?>"/>
                                    <input class="input_tuijian_title" name="item_data[<?php echo $key;?>][isshow_title]" type="hidden" value="<?php echo $value[isshow_title];?>"/>
                                    <input class="input_tuijian_price" name="item_data[<?php echo $key;?>][isshow_price]" type="hidden" value="<?php echo $value[isshow_price];?>"/>
                                </div>
                                <div class="tuijianshangpin_part_zhanshi">
                                    <section class="mod-goods-list <?php if(!$value[isshow_title]){echo 'hide_title';}?> <?php if(!$value[isshow_price]){echo 'hide_price';}?>">
                                        <ul class="goods-list style-<?php echo $value[show_style];?>">
                                            <?php if(!empty($value[data][goods_info])&&is_array($value[data][goods_info])){?>
                            <?php foreach ($value[data][goods_info] as $k => $v){?>
                                   <li>
                                        <input class="add_gid" name="item_data[<?php echo $key;?>][data][gid][]" type="hidden" value="<?php echo $v['gid'];?>">
                                        <a href="javascript:;">
                                            <div class="goods-image"><img src="<?php echo $v['goods_image'];?>"></div>
                                            <div class="goods-info">
                                                <p class="goods-title" ><?php echo $v['goods_name'];?></p>
                                                <p class="goods-price" >￥<?php echo $v['show_price'];?></p>
                                            </div>
                                        </a>
                                    </li>
                        <?php }?>
                                            <?php }?>
                                    </section>
                                </div>
                            </div>
                            <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i
                                        class="iconfontfa fa-trash-o"></i></a></div>
                        </div>
                <?php }?>
                 <?php if($value['type']=='fzkb'){?>
                    <div class="special-item fzkb_part default_fzkb_part">
                        <div bbctype="item_content" class="content">
                            <div class="fzkb_part_input">
                                <input class="input_fzkb" name="item_data[<?php echo $key;?>][type]" type="hidden" value="fzkb"/>
                                <input class="input_fzkb_text" name="item_data[<?php echo $key;?>][text]" type="hidden" value="<?php echo $value[text];?>"/>
                                <input class="input_fzkb_color" name="item_data[<?php echo $key;?>][color]" type="hidden" value="<?php echo $value[color];?>"/>
                            </div>
                            <div class="fzkb_content" style="height: <?php echo $value[text];?>px;background-color: #<?php echo $value[color];?>">
                            </div>
                        </div>
                        <div class="handle"><a bbctype="btn_del_item" data-item-id="23" href="javascript:;"><i
                                    class="iconfontfa fa-trash-o"></i></a></div>
                    </div>
                <?php }?>
                    <?php if($value['type']=='fzx'){?>
                    <div class="special-item fzx_part default_fzx_part">
                        <div bbctype="item_content" class="content">
                            <div class="fzx_part_input">
                                <input class="input_fzx" name="item_data[<?php echo $key;?>][type]" type="hidden" value="fzx"/>
                                <input class="input_fzx_text" name="item_data[<?php echo $key;?>][val]" type="hidden" value="<?php echo $value[val];?>"/>
                            </div>
                            <div class="fzx_content fzx_<?php echo $value[val];?>" >
                                <hr>
                            </div>
                        </div>
                        <div class="handle"><a bbctype="btn_del_item" data-item-id="23" href="javascript:;"><i class="iconfontfa fa-trash-o"></i></a></div>
                    </div>
                <?php }?>
                <?php if($value['type']=='nav'){?>
                        <div data_nav_index_t="<?php echo $key;?>" class="special-item nav_part default_nav_part">
                            <div bbctype="item_content" class="content">
                                <div class="nav_part_input">
                                    <input class="input_nav" name="item_data[<?php echo $key;?>][type]" type="hidden" value="nav"/>
                                    <input class="input_nav_style_set" name="item_data[<?php echo $key;?>][style_set]" type="hidden" value="<?php echo $value[style_set];?>"/>
                                    <input class="input_nav_icon_set" name="item_data[<?php echo $key;?>][icon_set]" type="hidden" value="<?php echo $value[icon_set];?>"/>
                                    <input class="input_nav_slide" name="item_data[<?php echo $key;?>][slide]" type="hidden" value="<?php echo $value[slide];?>"/>
                                </div>
                                <div class="nav_part_zhanshi">
                                    <section class="nav_section">
                                        <?php if($value[style_set] == 'tag-nav'){
                                            $new_class = 'mod-tag-nav';
                                        }else{
                                            if($value[icon_set] == 'up'){
                                                $new_class = 'mod-nav';
                                            }else if($value[icon_set] == 'left'){
                                                $new_class = 'mod-nav before-icon';
                                            }else if($value[icon_set] == 'no-icon'){
                                                $new_class = 'mod-nav no-icon';
                                            }
                                        }?>
                                        <ul class="<?php echo $new_class;?>">
                                            <?php foreach ($value[data] as $k => $v){?>
                                            <li data_nav_index="<?php echo $k;?>">
                                                <div class="nav_li_input">
                                                    <input class="input_nav_data_img" name="item_data[<?php echo $key;?>][data][<?php echo $k;?>][img]" type="hidden" value="<?php echo $v[img];?>"/>
                                                    <input class="input_nav_data_name" name="item_data[<?php echo $key;?>][data][<?php echo $k;?>][name]" type="hidden" value="<?php echo $v[name];?>"/>
                                                    <input class="input_nav_data_type" name="item_data[<?php echo $key;?>][data][<?php echo $k;?>][url_type]" type="hidden" value="<?php echo $v[url_type];?>"/>
                                                    <input class="input_nav_data_url" name="item_data[<?php echo $key;?>][data][<?php echo $k;?>][url]" type="hidden" value="<?php echo $v[url];?>"/>
                                                </div>
                                                <a href="javascript:;">
                                                    <i style="width: <?php echo $value[slide];?>px; height: <?php echo $value[slide];?>px;"><img style="width: <?php echo $value[slide];?>px; height: <?php echo $value[slide];?>px;" src="<?php echo $v[img];?>"></i>
                                                    <span><?php echo $v[name];?></span>
                                                </a>
                                            </li>
                        <?php }?>
                                        </ul>
                                    </section>
                                </div>
                            </div>
                            <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i
                                        class="iconfontfa fa-trash-o"></i></a></div>
                        </div>
                <?php }?>
                <?php if($value['type']=='dapei'){?>
                    <div class="special-item dapei_part default_dapei_part" data_dapei_index="<?php echo $key;?>">
                        <div bbctype="item_content" class="content">
                            <div class="dapei_part_input">
                                <input class="input_dapei" name="item_data[<?php echo $key;?>][type]" type="hidden" value="dapei"/>
                                <input class="input_dapei_img" name="item_data[<?php echo $key;?>][dapei_img]" type="hidden" value="<?php echo $value[dapei_img];?>"/>
                                <input class="input_dapei_title" name="item_data[<?php echo $key;?>][dapei_title]" type="hidden" value="<?php echo $value[dapei_title];?>"/>
                                <input class="input_dapei_desc" name="item_data[<?php echo $key;?>][dapei_desc]" type="hidden" value="<?php echo $value[dapei_desc];?>"/>
                            </div>
                            <div class="dapei_part_zhanshi">
                                <div class="com-title"><?php echo $value[dapei_title];?></div>
                                <?php if(!empty($value[dapei_img])){?>
                                    <img width="100%" src="<?php echo $value[dapei_img];?>">
                                <?php }?>
                                <div class="com-desc"><?php echo $value[dapei_desc];?></div>
                                <section class="mod-goods-com ">
                                    <div class="swiper-goods-container">
                                        <div  class="swiper-wrapper">
                                            <?php if (isset($value[data][goods_info])&&!empty($value[data][goods_info])&&is_array($value[data][goods_info])){?>
                                            <?php foreach ($value[data][goods_info] as $dapei_k => $dapei_v){?>
                                            <div  class="swiper-slide">
                                                <input class="add_dapei_gid" name="item_data[<?php echo $key;?>][data][gid][]" type="hidden" value="<?php echo $dapei_v[gid];?>">
                                                <a>
                                                    <div class="goods-image"><img  src="<?php echo $dapei_v[goods_image];?>"></div>
                                                    <div class="goods-info"><p class="goods-title">
                                                            <?php echo $dapei_v[goods_name];?></p>
                                                        <p class="goods-price">￥<?php echo $dapei_v[goods_price];?></p></div>
                                                </a>
                                            </div>
                                            <?php }?>
                                            <?php }?>


                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                        <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i
                                    class="iconfontfa fa-trash-o"></i></a></div>
                    </div>
                <?php }?>
<?php if($value['type']=='tupianzuhe'){?>
    <div input_index="<?php echo $key;?>" class="special-item tupianzuhe_part default_tupianzuhe_part">
        <div bbctype="item_content" class="content">
            <div class="tupianzuhe_part_input">
                <input class="input_tupianzuhe" name="item_data[<?php echo $key;?>][type]" type="hidden" value="tupianzuhe"/>
                <input class="input_tupianzuhe_sele_style" name="item_data[<?php echo $key;?>][sele_style]" type="hidden" value="<?php echo $value['sele_style'];?>"/>
            </div>
            <div class="tupianzuhe_part_zhanshi">
                <div class="modules-ad">
                    <div class="style_template">
                    <?php if($value['sele_style'] == 0 || $value['sele_style'] == 1 || $value['sele_style'] == 2 || $value['sele_style'] == 3){?>
                        <div class="image-list style<?php echo $value['sele_style'];?>" >
                            <ul class="clearfix">
                                <?php if(!empty($value['data'])&&is_array($value['data'])){?>
                                <?php foreach ($value['data'] as $tpzh_k => $tpzh_v){?>
                                <li style="height:auto">
                                    <a tpzh_index="<?php echo $tpzh_k*1+1;?>">
                                        <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][<?php echo $tpzh_k;?>][img]" type="hidden" value="<?php echo $tpzh_v['img'];?>"/>
                                        <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][<?php echo $tpzh_k;?>][title]" type="hidden" value="<?php echo $tpzh_v['title'];?>"/>
                                        <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][<?php echo $tpzh_k;?>][url_type]" type="hidden" value="<?php echo $tpzh_v['url_type'];?>"/>
                                        <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][<?php echo $tpzh_k;?>][url]" type="hidden" value="<?php echo $tpzh_v['url'];?>"/>
                                        <img class="lazy" src="<?php echo $tpzh_v['img'];?>">
                                    </a>
                                </li>
                            <?php }?>
                            <?php }?>
                            </ul>
                        </div>
                    <?php }else if($value['sele_style'] == 4){?>
                        <div class="image-ad clearfix images-tpl">
                            <div>
                                <a tpzh_index="1" style="width:148px;height:156px;" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][1][img]" type="hidden" value="<?php echo $value['data'][0]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][1][title]" type="hidden" value="<?php echo $value['data'][0]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][1][url_type]" type="hidden" value="<?php echo $value['data'][0]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][1][url]" type="hidden" value="<?php echo $value['data'][0]['url'];?>"/>
                                    <?php if($value['data'][0]['img']){?>
                                        <img class="lazy"  src="<?php echo $value['data'][0]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy"  src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300x320.jpg">
                                    <?php }?>
                                </a>
                            </div>
                            <div>
                                <a tpzh_index="2" style="width:148px;height:74px;" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][2][img]" type="hidden" value="<?php echo $value['data'][1]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][2][title]" type="hidden" value="<?php echo $value['data'][1]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][2][url_type]" type="hidden" value="<?php echo $value['data'][1]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][2][url]" type="hidden" value="<?php echo $value['data'][1]['url'];?>"/>
                                    <?php if($value['data'][1]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][1]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
                                    <?php }?>
                                </a>
                                <a  tpzh_index="3" style="width:148px;height:74px;" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][3][img]" type="hidden" value="<?php echo $value['data'][2]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][3][title]" type="hidden" value="<?php echo $value['data'][2]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][3][url_type]" type="hidden" value="<?php echo $value['data'][2]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][3][url]" type="hidden" value="<?php echo $value['data'][2]['url'];?>"/>
                                    <?php if($value['data'][2]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][2]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
                                    <?php }?>
                                </a>
                            </div>
                        </div>
                    <?php }else if($value['sele_style'] == 5){?>
                        <div class="image-ad2 clearfix images-tpl">
                            <div class="clearfix">
                                <a tpzh_index="1" style="width:98.66666666666667px;height:98.66666666666667px;">
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][1][img]" type="hidden" value="<?php echo $value['data'][0]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][1][title]" type="hidden" value="<?php echo $value['data'][0]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][1][url_type]" type="hidden" value="<?php echo $value['data'][0]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][1][url]" type="hidden" value="<?php echo $value['data'][0]['url'];?>"/>
                                    <?php if($value['data'][0]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][0]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                                    <?php }?>
                                </a>
                                <a tpzh_index="2" style="width:197.33333333333334px;height:98.66666666666667px">
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][2][img]" type="hidden" value="<?php echo $value['data'][1]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][2][title]" type="hidden" value="<?php echo $value['data'][1]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][2][url_type]" type="hidden" value="<?php echo $value['data'][1]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][2][url]" type="hidden" value="<?php echo $value['data'][1]['url'];?>"/>
                                    <?php if($value['data'][1]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][1]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_400x200.jpg">
                                    <?php }?>
                                </a>
                            </div>
                            <div class="clearfix">
                                <a tpzh_index="3" style="width:197.33333333333334px;height:98.66666666666667px" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][3][img]" type="hidden" value="<?php echo $value['data'][2]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][3][title]" type="hidden" value="<?php echo $value['data'][2]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][3][url_type]" type="hidden" value="<?php echo $value['data'][2]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][3][url]" type="hidden" value="<?php echo $value['data'][2]['url'];?>"/>
                                    <?php if($value['data'][2]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][2]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_400x200.jpg">
                                    <?php }?>
                                </a>
                                <a tpzh_index="4" style="width:98.66666666666667px;height:98.66666666666667px;">
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][4][img]" type="hidden" value="<?php echo $value['data'][3]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][4][title]" type="hidden" value="<?php echo $value['data'][3]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][4][url_type]" type="hidden" value="<?php echo $value['data'][3]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][4][url]" type="hidden" value="<?php echo $value['data'][3]['url'];?>"/>
                                    <?php if($value['data'][3]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][3]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                                    <?php }?>
                                </a>
                            </div>
                        </div>
                    <?php }else if($value['sele_style'] == 6){?>
                        <div class="image-ad3 clearfix images-tpl" style="">
                            <div>
                                <a tpzh_index="1" style="width:148px;height:74px;">
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][1][img]" type="hidden" value="<?php echo $value['data'][0]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][1][title]" type="hidden" value="<?php echo $value['data'][0]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][1][url_type]" type="hidden" value="<?php echo $value['data'][0]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][1][url]" type="hidden" value="<?php echo $value['data'][0]['url'];?>"/>
                                    <?php if($value['data'][0]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][0]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
                                    <?php }?>
                                </a>
                                <a tpzh_index="2" style="width:148px;height:148px;" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][2][img]" type="hidden" value="<?php echo $value['data'][1]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][2][title]" type="hidden" value="<?php echo $value['data'][1]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][2][url_type]" type="hidden" value="<?php echo $value['data'][1]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][2][url]" type="hidden" value="<?php echo $value['data'][1]['url'];?>"/>
                                    <?php if($value['data'][1]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][1]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
                                    <?php }?>
                                </a>
                            </div>
                            <div>
                                <a tpzh_index="3" style="width:148px;height:148px;" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][3][img]" type="hidden" value="<?php echo $value['data'][2]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][3][title]" type="hidden" value="<?php echo $value['data'][2]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][3][url_type]" type="hidden" value="<?php echo $value['data'][2]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][3][url]" type="hidden" value="<?php echo $value['data'][2]['url'];?>"/>
                                    <?php if($value['data'][2]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][2]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
                                    <?php }?>
                                </a>
                                <a tpzh_index="4" style="width:148px;height:74px;" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][4][img]" type="hidden" value="<?php echo $value['data'][3]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][4][title]" type="hidden" value="<?php echo $value['data'][3]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][4][url_type]" type="hidden" value="<?php echo $value['data'][3]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][4][url]" type="hidden" value="<?php echo $value['data'][3]['url'];?>"/>
                                    <?php if($value['data'][3]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][3]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
                                    <?php }?>
                                </a>
                            </div>
                        </div>
                    <?php }else if($value['sele_style'] == 7){?>
                        <div class="image-ad4 clearfix images-tpl">
                            <div><a tpzh_index="1" style="width:96px;height:96px;">
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][1][img]" type="hidden" value="<?php echo $value['data'][0]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][1][title]" type="hidden" value="<?php echo $value['data'][0]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][1][url_type]" type="hidden" value="<?php echo $value['data'][0]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][1][url]" type="hidden" value="<?php echo $value['data'][0]['url'];?>"/>
                                    <?php if($value['data'][0]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][0]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                                    <?php }?>
                                </a>
                                <a tpzh_index="2" style="width:96px;height:96px;">
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][2][img]" type="hidden" value="<?php echo $value['data'][1]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][2][title]" type="hidden" value="<?php echo $value['data'][1]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][2][url_type]" type="hidden" value="<?php echo $value['data'][1]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][2][url]" type="hidden" value="<?php echo $value['data'][1]['url'];?>"/>
                                    <?php if($value['data'][1]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][1]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                                    <?php }?>
                                </a>
                            </div>
                            <div>
                                <a tpzh_index="3" style="width:96px;height:96px;" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][3][img]" type="hidden" value="<?php echo $value['data'][2]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][3][title]" type="hidden" value="<?php echo $value['data'][2]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][3][url_type]" type="hidden" value="<?php echo $value['data'][2]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][3][url]" type="hidden" value="<?php echo $value['data'][2]['url'];?>"/>
                                    <?php if($value['data'][2]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][2]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                                    <?php }?>
                                </a>
                                <a tpzh_index="4" style="width:96px;height:96px;">
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][4][img]" type="hidden" value="<?php echo $value['data'][3]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][4][title]" type="hidden" value="<?php echo $value['data'][3]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][4][url_type]" type="hidden" value="<?php echo $value['data'][3]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][4][url]" type="hidden" value="<?php echo $value['data'][3]['url'];?>"/>
                                    <?php if($value['data'][3]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][3]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                                    <?php }?>
                                </a>
                            </div>
                            <div>
                                <a tpzh_index="5" style="width:96px;height:200px;" >
                                    <input class="input_tupianzuhe_img" name="item_data[<?php echo $key;?>][data][5][img]" type="hidden" value="<?php echo $value['data'][4]['img'];?>"/>
                                    <input class="input_tupianzuhe_title" name="item_data[<?php echo $key;?>][data][5][title]" type="hidden" value="<?php echo $value['data'][4]['title'];?>"/>
                                    <input class="input_tupianzuhe_url_type" name="item_data[<?php echo $key;?>][data][5][url_type]" type="hidden" value="<?php echo $value['data'][4]['url_type'];?>"/>
                                    <input class="input_tupianzuhe_url" name="item_data[<?php echo $key;?>][data][5][url]" type="hidden" value="<?php echo $value['data'][4]['url'];?>"/>
                                    <?php if($value['data'][4]['img']){?>
                                        <img class="lazy" src="<?php echo $value['data'][4]['img'];?>">
                                    <?php }else{?>
                                        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200x420.jpg">
                                    <?php }?>
                                </a></div>
                        </div>
                    <?php }?>
                    </div>
                </div>
            </div>
        </div>
        <div class="handle">
            <a bbctype="btn_del_item" href="javascript:;">
                <i class="iconfontfa fa-trash-o"></i>
            </a>
        </div>
    </div>
<?php }?>

                <?php if($value['type']=='huodong'){?>
                    <div input_index="<?php echo $key;?>" class="special-item huodong_part default_huodong_part">
                        <div bbctype="item_content" class="content">
                            <div class="huodong_part_input">
                                <input class="input_huodong" name="item_data[<?php echo $key;?>][type]" type="hidden" value="huodong"/>
                                <input class="input_huodong_sele_style" name="item_data[<?php echo $key;?>][sele_style]" type="hidden" value="<?php echo $value['sele_style'];?>"/>
                            </div>
                            <div class="huodong_part_zhanshi">
                                <div class="modules-huodong">
                                    <div class="style_template">
                                    <?php if($value['sele_style'] == 0){?>
    <div class="setting-values" style="display: none">
        <input class="input_huodong_top_title" name="item_data[<?php echo $key;?>][data][top][top][0][title]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['title'];?>"/>
        <!-- <input class="input_huodong_top_url_type" name="item_data[<?php echo $key;?>][data][top][top][0][url_type]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['url_type'];?>"/>
        <input class="input_huodong_top_url" name="item_data[<?php echo $key;?>][data][top][top][0][url]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['url'];?>"/> -->

        <input class="input_huodong_left_title" name="item_data[<?php echo $key;?>][data][left][top][0][title]" type="hidden" value="<?php echo $value['data']['left']['top'][0]['title'];?>"/>
        <input class="input_huodong_left_sub_title" name="item_data[<?php echo $key;?>][data][left][top][0][subtitle]" type="hidden" value="<?php echo $value['data']['left']['top'][0]['subtitle'];?>"/>
        <?php if( $value['data']['left']['top'][0]['gid'] ){?>
        <input class="input_huodong_left_gid" name="item_data[<?php echo $key;?>][data][left][top][0][gid]" type="hidden" value="<?php echo $value['data']['left']['top'][0]['gid']; ?>">
        <?php } ?>

        <input class="input_huodong_right_top_title" name="item_data[<?php echo $key;?>][data][right][top][0][title]" type="hidden" value="<?php echo $value['data']['right']['top'][0]['title'];?>"/>
        <input class="input_huodong_right_top_sub_title" name="item_data[<?php echo $key;?>][data][right][top][0][subtitle]" type="hidden" value="<?php echo $value['data']['right']['top'][0]['subtitle'];?>"/>

        <?php if(!empty($value['data']['right']['top'][0]['gid'])&&is_array($value['data']['right']['top'][0]['gid'])){?>
        <?php foreach ($value['data']['right']['top'][0]['gid'] as $k => $v){?>
        <input class="input_huodong_right_top_gid" name="item_data[<?php echo $key;?>][data][right][top][0][gid][]" type="hidden" value="<?php echo $v?>">
        <?php } ?>
        <?php } ?>


        <input class="input_huodong_right_bottom_title_1" name="item_data[<?php echo $key;?>][data][right][bottom][1][title]" type="hidden" value="<?php echo $value['data']['right']['bottom'][1]['title'];?>"/>
        <input class="input_huodong_right_bottom_sub_title_1" name="item_data[<?php echo $key;?>][data][right][bottom][1][subtitle]" type="hidden" value="<?php echo $value['data']['right']['bottom'][1]['subtitle'];?>"/>

        <?php if(!empty($value['data']['right']['bottom'][1]['gid'])&&is_array($value['data']['right']['bottom'][1]['gid'])){?>
        <?php foreach ($value['data']['right']['bottom'][1]['gid'] as $k => $v){?>
        <input class="input_huodong_right_bottom_gid_1" name="item_data[<?php echo $key;?>][data][right][bottom][1][gid][]" type="hidden" value="<?php echo $v?>">
        <?php } ?>
        <?php } ?>
        
        <input class="input_huodong_right_bottom_title_2" name="item_data[<?php echo $key;?>][data][right][bottom][2][title]" type="hidden" value="<?php echo $value['data']['right']['bottom'][2]['title'];?>"/>
        <input class="input_huodong_right_bottom_sub_title_2" name="item_data[<?php echo $key;?>][data][right][bottom][2][subtitle]" type="hidden" value="<?php echo $value['data']['right']['bottom'][2]['subtitle'];?>"/>

        <?php if(!empty($value['data']['right']['bottom'][2]['gid'])&&is_array($value['data']['right']['bottom'][2]['gid'])){?>
        <?php foreach ($value['data']['right']['bottom'][2]['gid'] as $k => $v){?>
        <input class="input_huodong_right_bottom_gid_2" name="item_data[<?php echo $key;?>][data][right][bottom][2][gid][]" type="hidden" value="<?php echo $v?>">
        <?php } ?>
        <?php } ?>
    </div>
    <div class="huodong-content">
        <div class="huodong-top">
            <a href="javascript:;">
                <div class="huodong-top-bg" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_top_bg-0.jpg) no-repeat;background-size: 100%;"></div>
                <div class="huodong-top-title"><?php echo $value['data']['top']['top'][0]['title'] ? $value['data']['top']['top'][0]['title'] : '顶部标题';?></div>
            </a>
        </div>
        <div class="huodong-main">
            <div class="huodong-left" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_left_bg.jpeg) no-repeat;background-size: 100%;">
                <div class="huodong-left-top">
                    <div class="huodong-left-top-layout">
                        <div class="main-title"><?php echo $value['data']['left']['top'][0]['title'] ? $value['data']['left']['top'][0]['title'] : '全民拼团';?></div>
                        <div class="sub-title"><span><?php echo $value['data']['left']['top'][0]['subtitle'] ? $value['data']['left']['top'][0]['subtitle'] : '邂逅好物 发现理想生活';?></span></div>
                        <div class="countdown" data-end_time_str="<?php echo $value['data']['left']['top'][0]['goods_info']['extend_data']['sld_end_time'] ? $value['data']['left']['top'][0]['goods_info']['extend_data']['sld_end_time'] : ''; ?>">
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="huodong-left-goods">
                    <div class="huodong-left-goods-layout">
                        <a href="#">
                            <div class="goods-thumb">
                                <?php if ($value['data']['left']['top'][0]['goods_info']['goods_image']): ?>
                                <img src="<?php echo $value['data']['left']['top'][0]['goods_info']['goods_image']; ?>">
                                    <?php else: ?>
                                <div class="empty-img"></div>
                                <?php endif ?>
                            </div>
                            <div class="goods-price">
                                <div class="sale-price">
                                    ¥<span class="money-number"><?php echo $value['data']['left']['top'][0]['goods_info']['promotion_price'] ? $value['data']['left']['top'][0]['goods_info']['promotion_price'] : '0.00'; ?></span>
                                </div>
                                <div class="market-price">
                                    ¥<span class="money-number"><?php echo $value['data']['left']['top'][0]['goods_info']['goods_marketprice'] ? $value['data']['left']['top'][0]['goods_info']['goods_marketprice'] : '0.00'; ?></span>
                                </div>
                            </div>
                            <div class="goods-other">
                                <div class="goods-extend-data">
                                    <span class="goods_tuan_p_num"><em><?php echo $value['data']['left']['top'][0]['goods_info']['extend_data']['sld_team_count'] ? $value['data']['left']['top'][0]['goods_info']['extend_data']['sld_team_count'] : '0'; ?></em>人团</span>
                                    <span>|</span>
                                    <span>去开团</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="huodong-right">
                <div class="huodong-right-top">
                    <div class="huodong-top-title">
                        <div class="main-title"><?php echo $value['data']['right']['top'][0]['title'] ? $value['data']['right']['top'][0]['title'] : '标题';?></div>
                        <div class="sub-title"><span><?php echo $value['data']['right']['top'][0]['subtitle'] ? $value['data']['right']['top'][0]['subtitle'] : '子标题';?></span></div>
                    </div>
                    <div class="huodong-goods-list">
                        <?php if(!empty($value['data']['right']['top'][0]['goods_info'])&&is_array($value['data']['right']['top'][0]['goods_info'])){?>
                        <?php foreach ($value['data']['right']['top'][0]['goods_info'] as $k => $v){?>
                        <div class="huodong-goods-item">
                            <div class="goods-thumb">
                                <?php if ($v['goods_image']): ?>
                                <img src="<?php echo $v['goods_image']; ?>">
                                    <?php else: ?>
                                <div class="empty-img"></div>
                                <?php endif ?>
                            </div>
                        </div>
                        <?php } ?>
                        <?php }else{ ?>
                        <div class="huodong-goods-item">
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                        </div>
                        <div class="huodong-goods-item">
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                        </div>
                        <div class="huodong-goods-item">
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="huodong-right-bottom">
                    <div class="huodong-goods-list">
                        <div class="huodong-goods-item">
                            <div class="huodong-top-title">
                                <div class="main-title"><?php echo $value['data']['right']['bottom'][1]['title'] ? $value['data']['right']['bottom'][1]['title'] : '标题';?></div>
                                <div class="sub-title"><span><?php echo $value['data']['right']['bottom'][1]['subtitle'] ? $value['data']['right']['bottom'][1]['subtitle'] : '子标题';?></span></div>
                            </div>
                            <div class="goods-thumb">
                                <?php if ($value['data']['right']['bottom'][1]['goods_info'][0]['goods_image']): ?>
                                <img src="<?php echo $value['data']['right']['bottom'][1]['goods_info'][0]['goods_image']; ?>">
                                    <?php else: ?>
                                <div class="empty-img"></div>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="huodong-goods-item" style="border-width: 1px 0px 1px 0px;">
                            <div class="huodong-top-title">
                                <div class="main-title"><?php echo $value['data']['right']['bottom'][2]['title'] ? $value['data']['right']['bottom'][2]['title'] : '标题';?></div>
                                <div class="sub-title"><span><?php echo $value['data']['right']['bottom'][2]['subtitle'] ? $value['data']['right']['bottom'][2]['subtitle'] : '子标题';?></span></div>
                            </div>
                            <div class="goods-thumb">
                                <?php if ($value['data']['right']['bottom'][2]['goods_info'][0]['goods_image']): ?>
                                <img src="<?php echo $value['data']['right']['bottom'][2]['goods_info'][0]['goods_image']; ?>">
                                    <?php else: ?>
                                <div class="empty-img"></div>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                                    <?php }else if($value['sele_style'] == 1){?>
    <div class="setting-values" style="display: none">
        <input class="input_huodong_top_title" name="item_data[<?php echo $key;?>][data][top][top][0][title]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['title'];?>"/>
        <!-- <input class="input_huodong_top_url_type" name="item_data[<?php echo $key;?>][data][top][top][0][url_type]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['url_type'];?>"/>
        <input class="input_huodong_top_url" name="item_data[<?php echo $key;?>][data][top][top][0][url]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['url'];?>"/> -->

        <input class="input_huodong_bottom_left_title_1" name="item_data[<?php echo $key;?>][data][bottom][left][1][title]" type="hidden" value="<?php echo $value['data']['bottom']['left'][1]['title']; ?>"/>
        <input class="input_huodong_bottom_left_sub_title_1" name="item_data[<?php echo $key;?>][data][bottom][left][1][subtitle]" type="hidden" value="<?php echo $value['data']['bottom']['left'][1]['subtitle']; ?>"/>

        <?php if(!empty($value['data']['bottom']['left'][1]['gid'])&&is_array($value['data']['bottom']['left'][1]['gid'])){?>
        <?php foreach ($value['data']['bottom']['left'][1]['gid'] as $k => $v){?>
        <input class="input_huodong_bottom_left_gid_1" name="item_data[<?php echo $key;?>][data][bottom][left][1][gid][]" type="hidden" value="<?php echo $v?>">
        <?php } ?>
        <?php } ?>
        
        <input class="input_huodong_bottom_mid_title_2" name="item_data[<?php echo $key;?>][data][bottom][mid][2][title]" type="hidden" value="<?php echo $value['data']['bottom']['mid'][2]['title']; ?>"/>
        <input class="input_huodong_bottom_mid_sub_title_2" name="item_data[<?php echo $key;?>][data][bottom][mid][2][subtitle]" type="hidden" value="<?php echo $value['data']['bottom']['mid'][2]['subtitle']; ?>"/>

        <?php if(!empty($value['data']['bottom']['mid'][2]['gid'])&&is_array($value['data']['bottom']['mid'][2]['gid'])){?>
        <?php foreach ($value['data']['bottom']['mid'][2]['gid'] as $k => $v){?>
        <input class="input_huodong_bottom_mid_gid_2" name="item_data[<?php echo $key;?>][data][bottom][mid][2][gid][]" type="hidden" value="<?php echo $v?>">
        <?php } ?>
        <?php } ?>

        <input class="input_huodong_bottom_right_title_3" name="item_data[<?php echo $key;?>][data][bottom][right][3][title]" type="hidden" value="<?php echo $value['data']['bottom']['right'][3]['title']; ?>"/>
        <input class="input_huodong_bottom_right_sub_title_3" name="item_data[<?php echo $key;?>][data][bottom][right][3][subtitle]" type="hidden" value="<?php echo $value['data']['bottom']['right'][3]['subtitle']; ?>"/>

        <?php if(!empty($value['data']['bottom']['right'][3]['gid'])&&is_array($value['data']['bottom']['right'][3]['gid'])){?>
        <?php foreach ($value['data']['bottom']['right'][3]['gid'] as $k => $v){?>
        <input class="input_huodong_bottom_right_gid_3" name="item_data[<?php echo $key;?>][data][bottom][right][3][gid][]" type="hidden" value="<?php echo $v?>">
        <?php } ?>
        <?php } ?>
    </div>
    <div class="huodong-content style-1">
        <div class="huodong-top">
            <a href="javascript:;">
                <div class="huodong-top-bg" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_top_bg-1.jpg) no-repeat;background-size: 100%;"></div>
                <div class="huodong-top-title"><?php echo $value['data']['top']['top'][0]['title'] ? $value['data']['top']['top'][0]['title'] : '顶部标题';?></div>
            </a>
        </div>
        <div class="huodong-main">
            <div class="huodong-goods-list">
                <div class="huodong-goods-item">
                    <div class="huodong-top-title">
                        <div class="main-title"><?php echo $value['data']['bottom']['left'][1]['title'] ? $value['data']['bottom']['left'][1]['title'] : '标题';?></div>
                        <div class="sub-title"><span><?php echo $value['data']['bottom']['left'][1]['subtitle'] ? $value['data']['bottom']['left'][1]['subtitle'] : '子标题';?></span></div>
                        <div class="countdown" data-end_time_str="<?php echo $value['data']['bottom']['left'][1]['goods_info'][0]['extend_data']['sld_end_time'] ? $value['data']['bottom']['left'][1]['goods_info'][0]['extend_data']['sld_end_time'] : ''; ?>">
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                    </div>
                    <div class="goods-thumb">
                        <?php if ($value['data']['bottom']['left'][1]['goods_info'][0]['goods_image']): ?>
                        <img src="<?php echo $value['data']['bottom']['left'][1]['goods_info'][0]['goods_image']; ?>">
                            <?php else: ?>
                        <div class="empty-img"></div>
                        <?php endif ?>
                    </div>
                    <div class="huodong-style-1-bottom">
                        <div class="goods-price">
                            <div class="sale-price">
                                ¥<span class="money-number"><?php echo $value['data']['bottom']['left'][1]['goods_info'][0]['promotion_price'] ? $value['data']['bottom']['left'][1]['goods_info'][0]['promotion_price'] : '0.00'; ?></span>
                            </div>
                            <div class="market-price">
                                ¥<span class="money-number"><?php echo $value['data']['bottom']['left'][1]['goods_info'][0]['goods_marketprice'] ? $value['data']['bottom']['left'][1]['goods_info'][0]['goods_marketprice'] : '0.00'; ?></span>
                            </div>
                        </div>
                        <div class="goods-bottom-button">
                            <a href="javascript:;">抢</a>
                        </div>
                    </div>
                </div>
                <div class="huodong-goods-item">
                    <div class="huodong-top-title">
                        <div class="main-title"><?php echo $value['data']['bottom']['mid'][2]['title'] ? $value['data']['bottom']['mid'][2]['title'] : '标题';?></div>
                        <div class="sub-title"><span><?php echo $value['data']['bottom']['mid'][2]['subtitle'] ? $value['data']['bottom']['mid'][2]['subtitle'] : '子标题';?></span></div>
                        <div class="countdown" data-end_time_str="<?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['extend_data']['sld_end_time'] ? $value['data']['bottom']['mid'][2]['goods_info'][0]['extend_data']['sld_end_time'] : ''; ?>">
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                    </div>
                    <div class="goods-thumb">
                        <?php if ($value['data']['bottom']['mid'][2]['goods_info'][0]['goods_image']): ?>
                        <img src="<?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['goods_image']; ?>">
                            <?php else: ?>
                        <div class="empty-img"></div>
                        <?php endif ?>
                    </div>
                    <div class="huodong-style-1-bottom">
                        <div class="goods-price">
                            <div class="sale-price">
                                ¥<span class="money-number"><?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['promotion_price'] ? $value['data']['bottom']['mid'][2]['goods_info'][0]['promotion_price'] : '0.00'; ?></span>
                            </div>
                            <div class="market-price">
                                ¥<span class="money-number"><?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['goods_marketprice'] ? $value['data']['bottom']['mid'][2]['goods_info'][0]['goods_marketprice'] : '0.00'; ?></span>
                            </div>
                        </div>
                        <div class="goods-bottom-button">
                            <a href="javascript:;">抢</a>
                        </div>
                    </div>
                </div>
                <div class="huodong-goods-item">
                    <div class="huodong-top-title">
                        <div class="main-title"><?php echo $value['data']['bottom']['right'][3]['title'] ? $value['data']['bottom']['right'][3]['title'] : '标题';?></div>
                        <div class="sub-title"><span><?php echo $value['data']['bottom']['right'][3]['title'] ? $value['data']['bottom']['right'][3]['subtitle'] : '子标题';?></span></div>
                        <div class="countdown" data-end_time_str="<?php echo $value['data']['bottom']['right'][3]['goods_info'][0]['extend_data']['sld_end_time'] ? $value['data']['bottom']['right'][3]['goods_info'][0]['extend_data']['sld_end_time'] : ''; ?>">
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                    </div>
                    <div class="goods-thumb">
                        <?php if ($value['data']['bottom']['right'][3]['goods_info'][0]['goods_image']): ?>
                        <img src="<?php echo $value['data']['bottom']['right'][3]['goods_info'][0]['goods_image']; ?>">
                            <?php else: ?>
                        <div class="empty-img"></div>
                        <?php endif ?>
                    </div>
                    <div class="huodong-style-1-bottom">
                        <div class="goods-price">
                            <div class="sale-price">
                                ¥<span class="money-number"><?php echo $value['data']['bottom']['right'][3]['goods_info'][0]['promotion_price'] ? $value['data']['bottom']['right'][3]['goods_info'][0]['promotion_price'] : '0.00'; ?></span>
                            </div>
                            <div class="market-price">
                                ¥<span class="money-number"><?php echo $value['data']['bottom']['right'][3]['goods_info'][0]['goods_marketprice'] ? $value['data']['bottom']['right'][3]['goods_info'][0]['goods_marketprice'] : '0.00'; ?></span>
                            </div>
                        </div>
                        <div class="goods-bottom-button">
                            <a href="javascript:;">抢</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                                    <?php }else if($value['sele_style'] == 2){?>
    <div class="setting-values" style="display: none">
        <input class="input_huodong_top_title" name="item_data[<?php echo $key;?>][data][top][top][0][title]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['title'];?>"/>
        <!-- <input class="input_huodong_top_url_type" name="item_data[<?php echo $key;?>][data][top][top][0][url_type]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['url_type'];?>"/>
        <input class="input_huodong_top_url" name="item_data[<?php echo $key;?>][data][top][top][0][url]" type="hidden" value="<?php echo $value['data']['top']['top'][0]['url'];?>"/> -->
        
        <input class="input_huodong_bottom_mid_title_2" name="item_data[<?php echo $key;?>][data][bottom][mid][2][title]" type="hidden" value="<?php echo $value['data']['bottom']['mid'][2]['title']; ?>"/>

        <?php if(!empty($value['data']['bottom']['mid'][2]['gid'])&&is_array($value['data']['bottom']['mid'][2]['gid'])){?>
        <?php foreach ($value['data']['bottom']['mid'][2]['gid'] as $k => $v){?>
        <input class="input_huodong_bottom_mid_gid_2" name="item_data[<?php echo $key;?>][data][bottom][mid][2][gid][]" type="hidden" value="<?php echo $v?>">
        <?php } ?>
        <?php } ?>
    </div>
    <div class="huodong-content style-2">
        <div class="huodong-top">
            <a href="javascript:;">
                <div class="huodong-top-bg" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_top_bg-2.jpg) no-repeat;background-size: 100%;"></div>
                <div class="huodong-top-title"><?php echo $value['data']['top']['top'][0]['title'] ? $value['data']['top']['top'][0]['title'] : '顶部标题';?></div>
            </a>
        </div>
        <div class="huodong-main" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_main_bg-2.jpg) no-repeat;background-size: 100%;">
            <div class="huodong-goods-list">
                <div class="huodong-goods-item">
                    <div class="goods-thumb">
                        <?php if ($value['data']['bottom']['mid'][2]['goods_info'][0]['goods_image']): ?>
                        <img src="<?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['goods_image']; ?>">
                            <?php else: ?>
                        <div class="empty-img"></div>
                        <?php endif ?>
                    </div>
                    <div class="huodong-style-2-right">
                        <div class="countdown" data-end_time_str="<?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['extend_data']['sld_end_time'] ? $value['data']['bottom']['mid'][2]['goods_info'][0]['extend_data']['sld_end_time'] : ''; ?>">
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                        <div class="main-title"><?php echo $value['data']['bottom']['mid'][2]['title'] ? $value['data']['bottom']['mid'][2]['title'] : '标题';?></div>
                        <div class="goods-price">
                            <div class="sale-price">
                                ¥<span class="money-number"><?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['promotion_price'] ? $value['data']['bottom']['mid'][2]['goods_info'][0]['promotion_price'] : '0.00'; ?></span>
                            </div>
                            <div class="market-price">
                                ¥<span class="money-number"><?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['goods_marketprice'] ? $value['data']['bottom']['mid'][2]['goods_info'][0]['goods_marketprice'] : '0.00'; ?></span>
                            </div>
                        </div>
                        <div class="goods-other">
                            <div class="goods-tuan-info">
                                已团<span><?php echo $value['data']['bottom']['mid'][2]['goods_info'][0]['extend_data']['buyed_quantity'] ? $value['data']['bottom']['mid'][2]['goods_info'][0]['extend_data']['buyed_quantity'] : '0.00'; ?></span>件
                            </div>
                            <div class="goods-tuan-btn">
                                <a><span>立即团</span><span class="arrow-right">&gt;</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                                    <?php }; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="handle">
                            <a bbctype="btn_del_item" href="javascript:;">
                                <i class="iconfontfa fa-trash-o"></i>
                            </a>
                        </div>
                    </div>
                <?php }?>


            <?php } ?>
            <?php } ?>
          </div>
        </div>

            <input class="com_page_title" type="hidden" name="page_title" value="<?php echo $output['list']['special_desc'];?>"/>
            <?php if($_GET['type'] == 'home' || $_GET['type'] == 'add_home'){?>
            <input class="com_sousuo_color" type="hidden" name="sousuocolor" value="<?php echo $output['list']['sousuocolor'];?>"/>
            <input class="com_botnav_color" type="hidden" name="botnavcolor" value="<?php echo $output['list']['botnavcolor'];?>"/>
            <?php }?>
        </form>
     </div>

    <!--公告部分的编辑-->
      <div id="common_part_edit">
          <div class="detail_title">
              <span class="title_big">页面基础设置</span>
          </div>
          <div class="com_detail"><span class="input_label">页面标题</span><input name="page_title" type="text" value="<?php echo $output['list']['special_desc'];?>"/></div>
          <?php if($_GET['type'] == 'home' || $_GET['type'] == 'add_home'){?>
          <div class="com_detail">
              <span class="input_label">搜索栏颜色</span>
              <span class="fzkb_picker sousuo_color" style="background-color: #<?php echo $output['list']['sousuocolor'];?>"></span>
          </div>
          <div class="com_detail">
              <span class="input_label">底部导航颜色</span>
              <span class="fzkb_picker bot_nav_color" style="background-color: #<?php echo $output['list']['botnavcolor'];?>"></span>
          </div>
          <?php }?>
      </div>
    <!--具体模块的编辑内容区域-->
      <div id="part_mokuai_detail">

      </div>

  </div>
  <!--底部保存按钮-->
  <div class="zidingyi_save"><span class="zidingyi_save_btn"></span></div>
</div>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/jquery-ui/jquery.ui.js"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/dialog/dialog.js" id="dialog_js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/template.min.js"   charset="utf-8"></script>
<!-- 页面模块模板 -->
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/js/bootstrap/bootstrap.js">
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/nav_fixed_scroll.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/modernizr.custom.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/draggabilly.pkgd.min.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/dragdrop.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/jquery.sortable.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/layer/layer.js" charset="utf-8"></script>
<script src="<?php echo STATIC_SITE_URL;?>/js/perfect-scrollbar.min.js"></script>

<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/fileupload/jquery.iframe-transport.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/fileupload/jquery.ui.widget.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/fileupload/jquery.fileupload.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/kindeditor/kindeditor-min.js"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/jquery.liMarquee.js"></script>
<script src="<?php echo STATIC_SITE_URL;?>/js/jquery.ajaxContent.pack.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/js/jquery-ui/themes/ui-lightness/jquery.ui.css" />
<script src="<?php echo STATIC_SITE_URL;?>/colpick/js/colpick.js"></script>
<script src="<?php echo STATIC_SITE_URL;?>/js/wap_zidingyi.js"></script>
<script>
    var temp_url = '<?php echo ADMIN_TEMPLATES_URL;?>';
    var url_item_add = "<?php echo urlAdmin('cwap_topic', 'special_item_add');?>";
    var url_item_del = "<?php echo urlAdmin('cwap_topic', 'special_item_del');?>";
    var url_item_edit = "<?php echo urlAdmin('cwap_topic', 'special_item_edit');?>";
    var url_upload_image = '<?php echo urlAdmin('cwap_topic', 'topic_image_upload');?>';
    var length = $('.special-item').length-1;
    $(document).ready(function(){
        //滚动条事件
        // $('#item_list').perfectScrollbar();
        //选中模块事件
        $(".special-item").live('click',function () {
            var cur_yuansu = $(this);
            cur_yuansu.parent().find('.special-item').removeClass('sele_special_item');
            cur_yuansu.addClass('sele_special_item');
            if(cur_yuansu.hasClass('nav_part')){
                addDetail('nav',cur_yuansu);
            }
            if(cur_yuansu.hasClass('kefu_part')){
                addDetail('kefu',cur_yuansu);
            }
            if(cur_yuansu.hasClass('lunbo_part')){
                addDetail('lunbo',cur_yuansu);
            }
            if(cur_yuansu.hasClass('fuwenben_part')){
                addDetail('fuwenben',cur_yuansu);
            }
            if(cur_yuansu.hasClass('gonggao_part')){
                addDetail('gonggao',cur_yuansu);
            }
            if(cur_yuansu.hasClass('tuijianshangpin_part')){
                addDetail('tuijianshangpin',cur_yuansu);
            }
            if(cur_yuansu.hasClass('fzkb_part')){
                addDetail('fuzhukongbai',cur_yuansu);
            }
            if(cur_yuansu.hasClass('fzx_part')){
                addDetail('fuzhuxian',cur_yuansu);
            }
            if(cur_yuansu.hasClass('dapei_part')){
                addDetail('dapei',cur_yuansu);
            }
            if(cur_yuansu.hasClass('tupianzuhe_part')){
                addDetail('tupianzuhe',cur_yuansu);
            }
            if(cur_yuansu.hasClass('huodong_part')){
                addDetail('huodong',cur_yuansu);
            }
        });
        //删除模块_17.10.07
        $('#item_list').on('mousedown', '[bbctype="btn_del_item"]', function(event) {
            event.stopPropagation();
            $(this).mouseup();
            var targetdel = $(this).parent().parent();
            layer.confirm('确定删除该模块？', {
                btn: ['确定','取消'], //按钮
                yes:function () {
                    targetdel.remove();
                    $('#part_mokuai_detail').html("");
                    layer.closeAll('dialog');
                }
            })
        });
        //垂直拖拽排序事件_17.10.07
        $('#item_list').live('mousedown',function () {
            $('#item_list').sortable();
        });

    });

    //拖拽事件_17.10.07
    $('.mobile_zujian li').live('mousedown',function () {
        (function() {
            var body = document.body,
                dropArea = document.getElementById( 'item_list' ),//要触发事件的区域
                droppableArr = [], dropAreaTimeout;flagdrop = 0;flagstart = 0;flagend = 0;
            // initialize droppables
            [].slice.call( document.querySelectorAll( '#item_list .special-item' )).forEach( function( el ) {
                droppableArr.push( new Droppable( el, {
                    //拖拽进入目标区域所触发的事件  draggableEl为拖动的元素  el为拖到位置的上一个元素
                    onDrop : function( instance, draggableEl ) {
                        if(flagdrop == 0){
                            flagdrop = 1;
                            var li_type = $(draggableEl).attr('data_type');
                            //如果拖拽的li的类型为nav
                            if(li_type == 'nav'){
                                addNav(el);
                            }else if(li_type == 'lunbo'){
                                addLunBo(el);
                            }else if(li_type == 'gonggao'){
                                addGongGao(el);
                            }else if(li_type == 'kefu'){
                                addKeFu(el);
                            }else if(li_type == 'fuwenben'){
                                addFuWenBen(el);
                            }else if(li_type == 'tupianzuhe'){
                                addTuPianZuHe(el);
                            }else if(li_type == 'tuijianshangpin'){
                                addTuiJianGoods(el);
                            }else if(li_type == 'fuzhukongbai'){
                                addFuZhuKongBai(el);
                            }else if(li_type == 'fuzhuxian'){
                                addFuZhuXian(el);
                            }else if(li_type == 'dapei'){
                                addDaPei(el);
                            }else if(li_type == 'huodong'){
                                addHuoDong(el);
                            }
                        }

                    }
                },dropArea ) );
            } );

            // initialize draggable(s)
            [].slice.call(document.querySelectorAll( '.mobile_zujian li' )).forEach( function( el ) {
                new Draggable( el, droppableArr, {
                    draggabilly : { containment: document.body },
                    onStart : function() {
                        if(flagstart == 0){
                            flagstart = 1;
                            //拖拽开始
                            clearTimeout( dropAreaTimeout );
                        }
                    },
                    onEnd : function( wasDropped ) {
                        if(flagend == 0){
                            flagend = 1;
                            var afterDropFn = function() {
                            };

                            if( !wasDropped ) {
                                afterDropFn();
                            }
                            else {
                                // after some time hide drop area and remove class 'drag-active' from body
                                clearTimeout( dropAreaTimeout );
                                dropAreaTimeout = setTimeout( afterDropFn, 400 );
                            }
                        }

                    }
                } );
            } );
        })();
    });
    // 模拟一次点击
    $('.mobile_zujian li').eq(0).trigger('mousedown');

    //初始化公告的滚动方法
    if($('.gonggao_part_zhanshi').length>0){
        $('.gonggao_part_zhanshi').each(function (i,v) {
            $(v).find('.gonggao_area').liMarquee();
        });
    }
    //保存按钮表单提交
    $('.zidingyi_save_btn').live('click',function () {
        $('#subject_save').submit();
    });

    //推荐商品编辑里面--搜索商品功能
    $('.btn_mb_special_goods_search').live('click', function() {
        var url = '<?php echo urlAdmin('sld_cwap_home', 'goods_list_zdy',['sld_addons'=>'ldj']);?>';
        var keyword = $(this).parents('.search-goods').find('.txt_goods_name').val();
        var goods_activity_type = $(this).parents('.search-goods').find('.goods_activity_type option:selected').val();
        if(keyword || goods_activity_type) {
            $(this).parents('.search-goods').find(".mb_special_goods_list").load(url, {keyword: keyword,goods_activity_type:goods_activity_type});
        }
    });

    //推荐商品编辑里面--显示风格事件
    $('#part_mokuai_detail .tuijian_goods_detail .radio input[type=radio]').live('change',function () {
        var check_val = $(this).val();
        var curpart = $('.sele_special_item');
        var caozuo_ob = curpart.find('.tuijianshangpin_part_zhanshi .goods-list');
        var new_class = 'goods-list style-'+check_val;
        //判断是否有这个类，没有的话添加，并把别的移除掉  有的话不处理
        if(!caozuo_ob.hasClass(new_class)){
            caozuo_ob.removeClass();
            caozuo_ob.addClass(new_class);
        }
        //替换input_tuijian_style的内容
        curpart.find('.input_tuijian_style').val(check_val);
    });
    //推荐商品编辑里面--显示设置事件
    $('#part_mokuai_detail .tuijian_goods_detail .checkbox input[type=checkbox]').live('change',function () {
        var curpart = $('.sele_special_item');
        var target_ob = curpart.find('.tuijianshangpin_part_zhanshi .mod-goods-list');
        var curval = $(this).val();
        var input_val = 0;
        if($(this).prop("checked")){
            if(target_ob.hasClass('hide_'+curval)){
                target_ob.removeClass('hide_'+curval);
            }
            input_val = 1;
        }else{
            if(!target_ob.hasClass('hide_'+curval)){
                target_ob.addClass('hide_'+curval);
            }
            input_val = 0;
        }
        //替换input_tuijian_setting的内容
        curpart.find('.input_tuijian_'+curval).val(input_val);
    });
    //搭配模块编辑里面--搜索商品功能
    $('#btn_dapei_goods_search').live('click', function() {
        var url = '<?php echo urlAdmin('sld_cwap_home', 'goods_list_zdy',['sld_addons'=>'ldj']);?>';
        var keyword = $(this).parents('.search-goods').find('.txt_goods_name').val();
        var goods_activity_type = $(this).parents('.search-goods').find('.goods_activity_type option:selected').val();
        if(keyword || goods_activity_type) {
            $(this).parents('.search-goods').find(".mb_special_goods_list").load(url, {keyword: keyword,goods_activity_type: goods_activity_type});
        }
    });
    //辅助线设置里面--显示风格事件
    $('#part_mokuai_detail .fzx_detail .radio input[type=radio]').live('change',function () {
        var check_val = $(this).val();
        var curpart = $('.sele_special_item');
        var caozuo_ob = curpart.find('.fzx_content');
        var new_class = 'fzx_content fzx_'+check_val;
        //判断是否有这个类，没有的话添加，并把别的移除掉  有的话不处理
            caozuo_ob.attr('class',new_class);
        //替换input_tuijian_style的内容
        curpart.find('.input_fzx_text').val(check_val);
    });

</script>
<!--推荐商品 展示新添加的商品-->
<script id="tuijian_goods_template" type="text/html">
    <% for (var i in goods_info) { %>
    <li>
        <input class="add_gid" name="item_data[<%=total_length%>][data][gid][]" type="hidden" value="<%=goods_info[i].gid%>">
        <a href="javascript:;">
            <div class="goods-image"><img src="<%=goods_info[i].goods_image%>"></div>
            <div class="goods-info">
                <p class="goods-title" ><%=goods_info[i].goods_name%></p>
                <p class="goods-price" ><%=goods_info[i].goods_price%></p>
            </div>
        </a>
    </li>
    <% }%>
</script>
<!--推荐商品 拖拽添加内容-->
<script id="tuijian_goods_dtemp" type="text/html">
    <div data_tjsp_index="<%=total_length%>" class="special-item tuijianshangpin_part default_tuijianshangpin_part">
        <div bbctype="item_content" class="content">
            <div class="tuijianshangpin_part_input">
                <input class="input_tuijianshangpin" name="item_data[<%=total_length%>][type]" type="hidden" value="tuijianshangpin"/>
                <input class="input_tuijian_style" name="item_data[<%=total_length%>][show_style]" type="hidden" value="small"/>
                <input class="input_tuijian_title" name="item_data[<%=total_length%>][isshow_title]" type="hidden" value="1"/>
                <input class="input_tuijian_price" name="item_data[<%=total_length%>][isshow_price]" type="hidden" value="1"/>
            </div>
            <div class="tuijianshangpin_part_zhanshi">
                <section class="mod-goods-list">
                    <ul class="goods-list style-small">
                        <li>
                            <a>
                                <div class="goods-image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/tj_goods_01.jpg"></div>
                                <div class="goods-info">
                                    <p class="goods-title">第1个商品名称</p>
                                    <p class="goods-price"> ￥9.00</p>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a>
                                <div class="goods-image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/tj_goods_01.jpg"></div>
                                <div class="goods-info">
                                    <p class="goods-title" >第2个商品名称</p>
                                    <p class="goods-price" > ￥18.00</p>
                                </div>
                            </a>
                        </li>
                        <li><a>
                                <div class="goods-image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/tj_goods_01.jpg"></div>
                                <div class="goods-info"><p class="goods-title">第3个商品名称</p>
                                    <p class="goods-price">￥27.00</p>
                                </div>
                            </a></li>
                        <li>
                            <a>
                                <div class="goods-image"><img  src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/tj_goods_01.jpg"></div>
                                <div class="goods-info"><p class="goods-title">第4个商品名称</p>
                                    <p class="goods-price">￥36.00</p>
                                </div>
                            </a>
                        </li>
                </section>
            </div>
        </div>
        <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i
                    class="iconfontfa fa-trash-o"></i></a></div>
    </div>
</script>
<!--推荐商品的编辑内容-->
<script id="tuijian_goods_detail" type="text/html">
    <div class="detail_title"><span class="title_big">推荐商品编辑</span></div>
        <div class="detail_content tuijian_goods_detail">
        <div class="form-group">
        <label class="control-label">显示风格</label>
        <div class="clearfix">
        <div class="radio pull-left tjsp_label tjsp_label_radio">
        <label>
         <% if(show_style == 'big'){ %>
        <input type="radio" name="size" value="big" checked >大图
         <% } else{%>
        <input type="radio" name="size" value="big">大图
         <% } %>
        </label>
        </div>
        <div class="radio pull-left tjsp_label tjsp_label_radio">
        <label>
            <% if(show_style == 'small'){ %>
            <input type="radio" checked name="size" value="small">矩阵
            <% } else{%>
            <input type="radio"  name="size" value="small">矩阵
            <% } %>
        </label>
        </div>
        <div class="radio pull-left  tjsp_label tjsp_label_radio">
        <label>
            <% if(show_style == 'list'){ %>
            <input type="radio" checked name="size" value="list">列表
            <% } else{%>
            <input type="radio" name="size" value="list">列表
            <% } %>
        </label>
        </div>
        </div>
        </div>
        <div class="form-group">
        <label class="control-label">显示设置</label>
        <div class="clearfix">
        <div class="checkbox pull-left tjsp_label">
        <label >
        <% if(isshow_title == 1){ %>
        <input type="checkbox" name="show_title" value="title" checked >标题
        <% }else{ %>
        <input type="checkbox" name="show_title" value="title" >标题
        <% } %>
        </label>
        </div>
        <div class="checkbox pull-left tjsp_label">
        <label>
            <% if(isshow_price == 1){ %>
            <input type="checkbox" name="show_price" value="price" checked >价格
            <% }else{ %>
            <input type="checkbox" name="show_price" value="price">价格
            <% } %>
        </label>
        </div>
        </div>
        </div>
        </div>
        <div class="add_recomgoods">
        <div class="search-goods">
        <h3>选择商品添加</h3>
            <select class="goods_activity_type">
                <?php foreach ($output['searchExtendFields'] as $key => $value): ?>
                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                <?php endforeach ?>
            </select>
    <input class="txt_goods_name" type="text" class="txt w200" name="">
            <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
        <div class="mb_special_goods_list"></div>
        </div>
        </div>
</script>
<!--辅助空白拖动增加内容-->
<script id="fzkb_dtemp" type="text/html">
    <div class="special-item fzkb_part default_fzkb_part">
        <div bbctype="item_content" class="content">
        <div class="fzkb_part_input">
        <input class="input_fzkb" name="item_data[<%=total_length%>][type]" type="hidden" value="fzkb"/>
        <input class="input_fzkb_text" name="item_data[<%=total_length%>][text]" type="hidden" value="30"/>
        <input class="input_fzkb_color" name="item_data[<%=total_length%>][color]" type="hidden" value="fff"/>
        </div>
        <div class="fzkb_content" style="background-color: #fff;">
        </div>
        </div>
        <div class="handle"><a bbctype="btn_del_item" data-item-id="23" href="javascript:;"><i
    class="iconfontfa fa-trash-o"></i></a></div>
        </div>
</script>
<!--辅助空白选中的编辑内容-->
<script id="fzkb_detail" type="text/html">
    <div class="detail_title fzkb_con">
        <span class="title_big">空白高度设置</span>
    </div>
    <div class="detail_content">
    <span class="fzkb_con_tip">空白高度</span>
    <span class=" fzkb_slider fzkb_slider<%=total_length%>">
    </span>
    <span class="slide_num"><%=slider_num%></span><span class="danwei">&nbsp;px</span>
    <span class="fzkb_con_tip">空白颜色</span>
    <span class="fzkb_picker fzkb_picker<%=total_length%>" style="background-color: #<%=color%>"></span>
    </div>
</script>
<!--辅助线拖动增加内容-->
<script id="fzx_dtemp" type="text/html">
<div class="special-item fzx_part default_fzx_part">
    <div bbctype="item_content" class="content">
        <div class="fzx_part_input">
            <input class="input_fzx" name="item_data[<%=total_length%>][type]" type="hidden" value="fzx"/>
            <input class="input_fzx_text" name="item_data[<%=total_length%>][val]" type="hidden" value="solid"/>
        </div>
        <div class="fzx_content fzx_solid" >
            <hr>
        </div>
    </div>
    <div class="handle"><a bbctype="btn_del_item" data-item-id="23" href="javascript:;"><i class="iconfontfa fa-trash-o"></i></a></div>
</div>
</script>
<!--辅助线选中的编辑内容-->
<script id="fzx_detail" type="text/html">
    <div class="detail_title"><span class="title_big">辅助线设置</span></div>
    <div class="detail_content fzx_detail">
        <div class="form-group">
            <label class="control-label fzx_con_tip">显示风格</label>
            <div class="clearfix">
                <div class="radio pull-left solid">
                    <label>
                        <hr/><span class="name">实线</span>
                        <span><% if(slider_num == 'solid'){ %>
                        <input type="radio" name="size" value="solid" checked >
                        <% }else{ %>
                        <input type="radio" name="size" value="solid" >
                        <% } %></span>
                    </label>
                </div>
                <div class="radio pull-left dotted">
                    <label>
                        <hr/><span  class="name">虚线</span>
                        <span>
                        <% if(slider_num == 'dashed'){ %>
                        <input type="radio"  name="size" checked value="dashed">
                        <% }else{ %>
                        <input type="radio"  name="size" value="dashed">
                        <% } %>
                            </span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</script>
<!--轮播 拖拽添加内容-->
<script id="lunbo_dtemp" type="text/html">
    <div data_lunbo_index_t="<%=total_length%>" class="special-item lunbo_part default_lunbo_part">
        <div bbctype="item_content" class="content">
            <div class="lunbo_part_input">
                <input class="input_lunbo" name="item_data[<%=total_length%>][type]" type="hidden" value="lunbo"/>
            </div>
            <div class="lunbo_upload_input">
                <ul>
                    <li data_lunbo_input="1">
                        <input class="input_lunbo_img" name="item_data[<%=total_length%>][data][1][img]" type="hidden" value=""/>
                        <input class="input_lunbo_title" name="item_data[<%=total_length%>][data][1][title]" type="hidden" value=""/>
                        <input class="input_lunbo_url_type" name="item_data[<%=total_length%>][data][1][url_type]" type="hidden" value=""/>
                        <input class="input_lunbo_url" name="item_data[<%=total_length%>][data][1][url]" type="hidden" value=""/>
                    </li>
                </ul>
            </div>
            <div class="lunbo_part_zhanshi">
                <img src ="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_lunbo.jpg"/>
            </div>
        </div>
        <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i class="iconfontfa fa-trash-o"></i></a></div>
    </div>
</script>
<!--轮播 编辑详细信息-->
<script id="lunbo_detail" type="text/html">
    <div class="detail_title"><span class="title_big">轮播设置</span></div>
    <div class="detail_content lunbo_detail">
        <div class="add_lunbo_btn"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/add_nav_btn.png"></div>
        <div class="single_lunbo_edit">
            <% for(var i in list_info){ %>
            <div data_lunbo_index="<%=list_info[i].index%>" class="dialog_item_edit_image">
                <div class="upload-thumb">
                    <% if(list_info[i].img){ %>
                    <img class="dialog_item_image" src="<%=list_info[i].img%>">
                    <% }else{ %>
                    <img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/lunbo_640.jpg">
                    <% } %>
                    <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png">
</span>
                </div>
                <div class="dialog-handle-box clearfix">
                    <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="<%=list_info[i].title%>" class="dialog_item_image_name" type="text"></div>
                    <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                            <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                                <option value="">-请选择-</option>
                               <!-- <% if(list_info[i].url_type == "keyword"){ %>
                                <option selected value="keyword">关键字</option>
                                <% }else{ %>
                                <option value="keyword">关键字</option>
                                <% } %>
                                <% if(list_info[i].url_type == "special"){ %>
                                <option selected value="special">专题编号</option>
                                <% }else{ %>
                                <option  value="special">专题编号</option>
                                <% } %>
                                <% if(list_info[i].url_type == "goods"){ %>
                                <option selected value="goods">商品编号</option>
                                <% }else{ %>
                                <option value="goods">商品编号</option>
                                <% } %>-->
                                <% if(list_info[i].url_type == "url"){ %>
                                <option selected value="url">链接</option>
                                <% }else{ %>
                                <option value="url">链接</option>
                                <% } %>
                            </select><input value="<%=list_info[i].url%>" class="dialog_item_image_data" type="text"><br><span
                                class="dialog_item_image_desc"></span></div>
                    </div>
                </div>
                <span class="del_lunbo_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
            </div>
            <% } %>
        </div>
    </div>
</script>
<!--轮播编辑模块 添加图片需要增加的内容-->
<script id="lunbo_add_pic" type="text/html">
        <div data_lunbo_index="<%=num%>" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/lunbo_640.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                            <option value="">-请选择-</option>

<!--                            <option value="keyword">关键字</option>-->
<!---->
<!--                            <option  value="special">专题编号</option>-->
<!---->
<!--                            <option value="goods">商品编号</option>-->

                            <option value="url">链接</option>
                        </select><input value="" class="dialog_item_image_data" type="text"><br><span
                            class="dialog_item_image_desc"></span></div>
                </div>
            </div>
            <span class="del_lunbo_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>
</script>
<!--轮播编辑模块 增加一个轮播图片需要增加的一组存放数据的input值-->
<script id="lunbo_add_pic_input" type="text/html">
    <li data_lunbo_input="<%=num%>">
        <input class="input_lunbo_img" name="item_data[<%=data_lunbo_index_t%>][data][<%=num%>][img]" type="hidden" value=""/>
        <input class="input_lunbo_title" name="item_data[<%=data_lunbo_index_t%>][data][<%=num%>][title]" type="hidden" value=""/>
        <input class="input_lunbo_url_type" name="item_data[<%=data_lunbo_index_t%>][data][<%=num%>][url_type]" type="hidden" value=""/>
        <input class="input_lunbo_url" name="item_data[<%=data_lunbo_index_t%>][data][<%=num%>][url]" type="hidden" value=""/>
    </li>
</script>
<!--导航 拖拽添加内容-->
<script id="nav_dtemp" type="text/html">
    <div data_nav_index_t="<%=total_length%>" class="special-item nav_part default_nav_part">
        <div bbctype="item_content" class="content">
        <div class="nav_part_input">
        <input class="input_nav" name="item_data[<%=total_length%>][type]" type="hidden" value="nav"/>
        <input class="input_nav_style_set" name="item_data[<%=total_length%>][style_set]" type="hidden" value="nav"/>
        <input class="input_nav_icon_set" name="item_data[<%=total_length%>][icon_set]" type="hidden" value="up"/>
        <input class="input_nav_slide" name="item_data[<%=total_length%>][slide]" type="hidden" value="30"/>
        </div>
        <div class="nav_part_zhanshi">
        <section class="nav_section">
        <ul class="mod-nav">
        <li data_nav_index="0">
        <div class="nav_li_input">
            <input class="input_nav_data_img" name="item_data[<%=total_length%>][data][0][img]" type="hidden" value="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png"/>
            <input class="input_nav_data_name" name="item_data[<%=total_length%>][data][0][name]" type="hidden" value=""/>
            <input class="input_nav_data_type" name="item_data[<%=total_length%>][data][0][url_type]" type="hidden" value=""/>
            <input class="input_nav_data_url" name="item_data[<%=total_length%>][data][0][url]" type="hidden" value=""/>
        </div>
        <a href="javascript:;">
        <i style="width: 30px; height: 30px;"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png"></i>
        <span>导航</span>
        </a>
        </li>
        <li data_nav_index="1">
            <div class="nav_li_input">
                <input class="input_nav_data_img" name="item_data[<%=total_length%>][data][1][img]" type="hidden" value="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png"/>
                <input class="input_nav_data_name" name="item_data[<%=total_length%>][data][1][name]" type="hidden" value=""/>
                <input class="input_nav_data_type" name="item_data[<%=total_length%>][data][1][url_type]" type="hidden" value=""/>
                <input class="input_nav_data_url" name="item_data[<%=total_length%>][data][1][url]" type="hidden" value=""/>
            </div>
        <a href="javascript:;">
        <i style="width: 30px; height: 30px;"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png"></i>
        <span>导航</span>
        </a>
        </li>
        <li data_nav_index="2">
            <div class="nav_li_input">
                <input class="input_nav_data_img" name="item_data[<%=total_length%>][data][2][img]" type="hidden" value="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png"/>
                <input class="input_nav_data_name" name="item_data[<%=total_length%>][data][2][name]" type="hidden" value=""/>
                <input class="input_nav_data_type" name="item_data[<%=total_length%>][data][2][url_type]" type="hidden" value=""/>
                <input class="input_nav_data_url" name="item_data[<%=total_length%>][data][2][url]" type="hidden" value=""/>
            </div>
        <a href="javascript:;">
        <i style="width: 30px; height: 30px;">
        <img  src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png">
        </i>
        <span>导航</span>
        </a>
        </li>
        <li data_nav_index="3">
            <div class="nav_li_input">
                <input class="input_nav_data_img" name="item_data[<%=total_length%>][data][3][img]" type="hidden" value="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png"/>
                <input class="input_nav_data_name" name="item_data[<%=total_length%>][data][3][name]" type="hidden" value=""/>
                <input class="input_nav_data_type" name="item_data[<%=total_length%>][data][3][url_type]" type="hidden" value=""/>
                <input class="input_nav_data_url" name="item_data[<%=total_length%>][data][3][url]" type="hidden" value=""/>
            </div>
        <a href="javascript:;">
        <i style="width: 30px; height: 30px;">
        <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png">
        </i>
        <span>导航</span>
        </a>
        </li>
        </ul>
        </section>
        </div>
        </div>
        <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i
    class="iconfontfa fa-trash-o"></i></a></div>
        </div>
</script>
<!--导航 编辑详细信息-->
<script id="nav_detail" type="text/html">
    <div class="detail_title"><span class="title_big">导航设置</span></div>
    <div class="detail_content nav_detail">
        <form class="form-horizontal" name="form" novalidate="">
            <div class="form-group nav_style_set">
                <label class=" control-label">显示风格</label>
                <div class="controls">
                    <label class="radio inline">
                        <% if(style_set == 'nav'){ %>
                        <input type="radio" name="style" checked value="nav" >导航
                        <% }else{ %>
                        <input type="radio" name="style"  value="nav" >导航
                        <% } %>
                    </label>
                    <label class="radio inline">
                        <% if(style_set == 'tag-nav'){ %>
                        <input type="radio" name="style" checked value="tag-nav">分组
                        <% }else{ %>
                        <input type="radio" name="style" value="tag-nav">分组
                        <% } %>
                    </label>
                </div>
            </div>
            <% if(style_set == 'nav'){ %>
            <div class="form-group show_icon_set" >
                <% }else{ %>
                <div class="form-group show_icon_set"  style="display: none">
                    <% } %>
                    <label
                        class=" control-label">显示图标
                    </label>
                    <div class="controls ">
                        <label class="radio inline">
                            <% if(icon_set == 'up'){ %>
                            <input type="radio" name="display_icon" checked value="up">图标居上
                            <% }else{ %>
                            <input type="radio" name="display_icon" value="up">图标居上
                            <% } %>
                        </label>
                        <label class="radio inline">
                            <% if(icon_set == 'left'){ %>
                            <input type="radio" name="display_icon" checked value="left">图标居左
                            <% }else{ %>
                            <input type="radio" name="display_icon" value="left">图标居左
                            <% } %>
                        </label>
                        <label class="radio inline">
                            <% if(icon_set == 'no-icon'){ %>
                            <input type="radio" name="display_icon" checked  value="no-icon">不显示图标
                            <% }else{ %>
                            <input type="radio" name="display_icon" value="no-icon" >不显示图标
                            <% } %>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label">图标大小</label>
                    <div class="controls  nav_icon_slide nav_icon_slide<%=total_length%>">
                    </div>
                    <label class="slide_height"><%=slider_num%></label><label class="xiangsu control-label"> px</label>
                </div>
        </form>
        <div class="add_nav_btn"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/add_nav_btn.png"></div>
        <div class="single_nav_edit">
            <% for (var i in data) { %>
            <div data_nav_index="<%=i%>" class="dialog_item_edit_image">
                <div class="upload-thumb"><img class="dialog_item_image" src="<%=data[i].src%>">
                    <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
                </div>
                <div class="dialog-handle-box clearfix">
                    <div class="nav_con_name"><span class="nav_label">文字</span><input value="<%=data[i].name%>" class="dialog_item_image_name" type="text"></div>
                    <div class="nav_con_url"><span class="nav_label">链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                            <option value="">-请选择-</option>
                           <!-- <% if(data[i].url_type == "keyword"){ %>
                            <option selected value="keyword">关键字</option>
                            <% }else{ %>
                            <option value="keyword">关键字</option>
                            <% } %>
                            <% if(data[i].url_type == "special"){ %>
                            <option selected value="special">专题编号</option>
                            <% }else{ %>
                            <option  value="special">专题编号</option>
                            <% } %>
                            <% if(data[i].url_type == "goods"){ %>
                            <option selected value="goods">商品编号</option>
                            <% }else{ %>
                            <option value="goods">商品编号</option>
                            <% } %>-->
                            <% if(data[i].url_type == "url"){ %>
                            <option selected value="url">链接</option>
                            <% }else{ %>
                            <option value="url">链接</option>
                            <% } %>
                        </select><input value="<%=data[i].url%>" class="dialog_item_image_data" type="text"><br><span
                            class="dialog_item_image_desc"></span></div>
                    </div>
                </div>
                <span class="del_nav_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
            </div>
            <% } %>


        </div>
    </div>
</script>
<!--导航编辑模块 每个导航的编辑模块内容-->
<script id="nav_detail_every" type="text/html">
    <div data_nav_index="<%=num%>" class="dialog_item_edit_image">
        <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png">
            <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
        </div>
        <div data_index="1" class="dialog-handle-box clearfix">
            <div class="nav_con_name"><span class="nav_label">文字</span><input value="" class="dialog_item_image_name" type="text"></div>
            <div class="nav_con_url"><span class="nav_label">链接</span><div style="display: inline-block">
                <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                    <option value="">-请选择-</option>
<!--                    <option value="keyword">关键字</option>-->
<!--                    <option value="special">专题编号</option>-->
<!--                    <option value="goods">商品编号</option>-->
                    <option value="url">链接</option>
                </select><input value="" class="dialog_item_image_data" type="text"><br><span
                    class="dialog_item_image_desc"></span></div></div>
        </div>
        <span class="del_nav_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
    </div>
</script>
<!--增加手机里导航块内容-->
<script id="nav_part_every" type="text/html">
    <li data_nav_index="<%=num%>">
        <div class="nav_li_input">
            <input class="input_nav_data_img" name="item_data[<%=total_length%>][data][<%=num%>][img]" type="hidden" value="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png"/>
            <input class="input_nav_data_name" name="item_data[<%=total_length%>][data][<%=num%>][name]" type="hidden" value=""/>
            <input class="input_nav_data_type" name="item_data[<%=total_length%>][data][<%=num%>][url_type]" type="hidden" value=""/>
            <input class="input_nav_data_url" name="item_data[<%=total_length%>][data][<%=num%>][url]" type="hidden" value=""/>
        </div>
        <a href="javascript:;">
            <i style="width: 30px; height: 30px;">
                <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_nav.png">
            </i>
            <span>导航</span>
        </a>
    </li>
</script>
<!--搭配 拖拽添加内容-->
<script id="dapei_dtemp" type="text/html">
    <div class="special-item dapei_part default_dapei_part" data_dapei_index="<%=total_length%>">
        <div bbctype="item_content" class="content">
            <div class="dapei_part_input">
            <input class="input_dapei" name="item_data[<%=total_length%>][type]" type="hidden" value="dapei"/>
            <input class="input_dapei_img" name="item_data[<%=total_length%>][dapei_img]" type="hidden" value="<?php echo ADMIN_TEMPLATES_URL?>/images/zidingyi/wap_def_dapei.png"/>
            <input class="input_dapei_title" name="item_data[<%=total_length%>][dapei_title]" type="hidden" value=""/>
            <input class="input_dapei_desc" name="item_data[<%=total_length%>][dapei_desc]" type="hidden" value=""/>
            </div>
            <div class="dapei_part_zhanshi">
                <div class="com-title" style=""></div>
                <div class="com-desc" style=""></div>
                 <section class="mod-goods-com ">
        <div class="swiper-goods-container">
        <div  class="swiper-wrapper">
            <div  class="swiper-slide">
                <a>
                <div class="goods-image"><img  src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/dapei_goods01.jpg"></div>
                <div class="goods-info"><p class="goods-title">
                第1个商品名称</p>
                <p class="goods-price">￥9.00</p></div>
                </a>
            </div>
            <div  class="swiper-slide">
                <a>
                <div class="goods-image"><img  src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/dapei_goods02.jpg"></div>
                <div class="goods-info"><p class="goods-title">
                第2个商品名称</p>
                <p class="goods-price">￥18.00</p></div>
                </a>
            </div>
            <div class="swiper-slide ">
                <a>
                <div class="goods-image">
                <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/dapei_goods03.jpg">
                </div>
                <div class="goods-info">
                <p class="goods-title">
                第3个商品名称</p>
                <p class="goods-price">￥27.00</p>
                </div>
                </a>
            </div>
        </div>
        </div>
    </section>
    </div>
    </div>
    <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i
    class="iconfontfa fa-trash-o"></i></a></div>
        </div>
</script>
<script id="dapei_detail" type="text/html">
    <div class="detail_title"><span class="title_big">搭配模块设置</span></div>
    <div class="detail_content dapei_detail">
        <div class="dapei_detail_tip">选择图片可以给商品搭配一张主图</div>
        <div class="single_dapei_edit">
            <div class="dialog_item_edit_image">
                <div class="upload-thumb">
                    <img class="dialog_item_image" src="<%=img%>">
                    <span class="upload_img_span">
                        <input class="btn_upload_image" type="file" name="special_image">
                        <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png">
                    </span>

                </div>
                <div class="dialog-handle-box clearfix">
                    <div class="dapei_con_title">
                        <span class="dapei_label">搭配标题</span>
                        <input value="<%=title%>" class="dialog_item_image_name" type="text">
                    </div>
                    <div class="dapei_con_desc">
                        <span class="dapei_label">搭配简介</span>
                        <input value="<%=desc%>" class="dialog_item_image_desc" type="text">
                    </div>
                </div>
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
            <select class="goods_activity_type">
                <?php foreach ($output['searchExtendFields'] as $key => $value): ?>
                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                <?php endforeach ?>
            </select>
                <input class="txt_goods_name" type="text" class="txt w200" name="" placeholder="输入关键词搜索">
                <a id="btn_dapei_goods_search" class="btn-search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list"></div>
            </div>
        </div>
    </div>
</script>
<!--搭配模块 展示新添加的商品-->
<script id="dapei_template" type="text/html">
    <% for (var i in goods_info) { %>
    <div class="swiper-slide">
        <input class="add_dapei_gid" name="item_data[<%=total_length%>][data][gid][]" type="hidden" value="<%=goods_info[i].gid%>">
        <a>
            <div class="goods-image"><img  src="<%=goods_info[i].goods_image%>"></div>
            <div class="goods-info"><p class="goods-title ng-binding"><%=goods_info[i].goods_name%></p>
                <p class="goods-price">￥<%=goods_info[i].goods_price%></p></div>
        </a>
    </div>
    <% }%>

</script>
<!--活动模板 其他样式  -->
<script id="huodong_template_0" type="text/html">
    <div class="setting-values" style="display: none">
        <input class="input_huodong_top_title" name="item_data[<%=input_index%>][data][top][top][0][title]" type="hidden" value=""/>
        <!-- <input class="input_huodong_top_url_type" name="item_data[<%=input_index%>][data][top][top][0][url_type]" type="hidden" value=""/>
        <input class="input_huodong_top_url" name="item_data[<%=input_index%>][data][top][top][0][url]" type="hidden" value=""/> -->

        <input class="input_huodong_left_title" name="item_data[<%=input_index%>][data][left][top][0][title]" type="hidden" value=""/>
        <input class="input_huodong_left_sub_title" name="item_data[<%=input_index%>][data][left][top][0][subtitle]" type="hidden" value=""/>

        <input class="input_huodong_right_top_title" name="item_data[<%=input_index%>][data][right][top][0][title]" type="hidden" value=""/>
        <input class="input_huodong_right_top_sub_title" name="item_data[<%=input_index%>][data][right][top][0][subtitle]" type="hidden" value=""/>

        <input class="input_huodong_right_bottom_title_1" name="item_data[<%=input_index%>][data][right][bottom][1][title]" type="hidden" value=""/>
        <input class="input_huodong_right_bottom_sub_title_1" name="item_data[<%=input_index%>][data][right][bottom][1][subtitle]" type="hidden" value=""/>
        
        <input class="input_huodong_right_bottom_title_2" name="item_data[<%=input_index%>][data][right][bottom][2][title]" type="hidden" value=""/>
        <input class="input_huodong_right_bottom_sub_title_2" name="item_data[<%=input_index%>][data][right][bottom][2][subtitle]" type="hidden" value=""/>
    </div>
    <div class="huodong-content">
        <div class="huodong-top">
            <a href="javascript:;">
                <div class="huodong-top-bg" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_top_bg-0.jpg) no-repeat;background-size: 100%;"></div>
                <div class="huodong-top-title">顶部标题</div>
            </a>
        </div>
        <div class="huodong-main">
            <div class="huodong-left" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_left_bg.jpeg) no-repeat;background-size: 100%;">
                <div class="huodong-left-top">
                    <div class="huodong-left-top-layout">
                        <div class="main-title">全民拼团</div>
                        <div class="sub-title"><span>邂逅好物 发现理想生活</span></div>
                        <div class="countdown">
                            
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="huodong-left-goods">
                    <div class="huodong-left-goods-layout huodong-left-goods-item">
                        <a href="#">
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                            <div class="goods-price">
                                <div class="sale-price">
                                    ¥<span class="money-number">300.00</span>
                                </div>
                                <div class="market-price">
                                    ¥<span class="money-number">300.00</span>
                                </div>
                            </div>
                            <div class="goods-other">
                                <div class="goods-extend-data">
                                    <span><em>3<em>人团</span>
                                    <span>|</span>
                                    <span>去开团</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="huodong-right">
                <div class="huodong-right-top">
                    <div class="huodong-top-title">
                        <div class="main-title">顶部标题</div>
                        <div class="sub-title"><span>顶部标题</span></div>
                    </div>
                    <div class="huodong-goods-list">
                        <div class="huodong-goods-item">
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                        </div>
                        <div class="huodong-goods-item">
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                        </div>
                        <div class="huodong-goods-item">
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="huodong-right-bottom">
                    <div class="huodong-goods-list">
                        <div class="huodong-goods-item">
                            <div class="huodong-top-title">
                                <div class="main-title">顶部标题</div>
                                <div class="sub-title"><span>顶部标题</span></div>
                            </div>
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                        </div>
                        <div class="huodong-goods-item" style="border-width: 1px 0px 1px 0px;">
                            <div class="huodong-top-title">
                                <div class="main-title">顶部标题</div>
                                <div class="sub-title"><span>顶部标题</span></div>
                            </div>
                            <div class="goods-thumb">
                                <div class="empty-img"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<script id="huodong_template_1" type="text/html">
    <div class="setting-values" style="display: none">
        <input class="input_huodong_top_title" name="item_data[<%=input_index%>][data][top][top][0][title]" type="hidden" value=""/>
        <!-- <input class="input_huodong_top_url_type" name="item_data[<%=input_index%>][data][top][top][0][url_type]" type="hidden" value=""/>
        <input class="input_huodong_top_url" name="item_data[<%=input_index%>][data][top][top][0][url]" type="hidden" value=""/> -->

        <input class="input_huodong_bottom_left_title_1" name="item_data[<%=input_index%>][data][bottom][left][1][title]" type="hidden" value=""/>
        <input class="input_huodong_bottom_left_sub_title_1" name="item_data[<%=input_index%>][data][bottom][left][1][subtitle]" type="hidden" value=""/>
        
        <input class="input_huodong_bottom_mid_title_2" name="item_data[<%=input_index%>][data][bottom][mid][2][title]" type="hidden" value=""/>
        <input class="input_huodong_bottom_mid_sub_title_2" name="item_data[<%=input_index%>][data][bottom][mid][2][subtitle]" type="hidden" value=""/>

        <input class="input_huodong_bottom_right_title_3" name="item_data[<%=input_index%>][data][bottom][right][3][title]" type="hidden" value=""/>
        <input class="input_huodong_bottom_right_sub_title_3" name="item_data[<%=input_index%>][data][bottom][right][3][subtitle]" type="hidden" value=""/>

    </div>
    <div class="huodong-content style-1">
        <div class="huodong-top">
            <a href="javascript:;">
                <div class="huodong-top-bg" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_top_bg-1.jpg) no-repeat;background-size: 100%;"></div>
                <div class="huodong-top-title">顶部标题</div>
            </a>
        </div>
        <div class="huodong-main">
            <div class="huodong-goods-list">
                <div class="huodong-goods-item">
                    <div class="huodong-top-title">
                        <div class="main-title">顶部标题</div>
                        <div class="sub-title"><span>顶部标题</span></div>
                        <div class="countdown">
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                    </div>
                    <div class="goods-thumb">
                        <div class="empty-img"></div>
                    </div>
                    <div class="huodong-style-1-bottom">
                        <div class="goods-price">
                            <div class="sale-price">
                                ¥<span class="money-number">300.00</span>
                            </div>
                            <div class="market-price">
                                ¥<span class="money-number">300.00</span>
                            </div>
                        </div>
                        <div class="goods-bottom-button">
                            <a href="javascript:;">抢</a>
                        </div>
                    </div>
                </div>
                <div class="huodong-goods-item">
                    <div class="huodong-top-title">
                        <div class="main-title">顶部标题</div>
                        <div class="sub-title"><span>顶部标题</span></div>
                        <div class="countdown">
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                    </div>
                    <div class="goods-thumb">
                        <div class="empty-img"></div>
                    </div>
                    <div class="huodong-style-1-bottom">
                        <div class="goods-price">
                            <div class="sale-price">
                                ¥<span class="money-number">300.00</span>
                            </div>
                            <div class="market-price">
                                ¥<span class="money-number">300.00</span>
                            </div>
                        </div>
                        <div class="goods-bottom-button">
                            <a href="javascript:;">抢</a>
                        </div>
                    </div>
                </div>
                <div class="huodong-goods-item">
                    <div class="huodong-top-title">
                        <div class="main-title">顶部标题</div>
                        <div class="sub-title"><span>顶部标题</span></div>
                        <div class="countdown">
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                    </div>
                    <div class="goods-thumb">
                        <div class="empty-img"></div>
                    </div>
                    <div class="huodong-style-1-bottom">
                        <div class="goods-price">
                            <div class="sale-price">
                                ¥<span class="money-number">300.00</span>
                            </div>
                            <div class="market-price">
                                ¥<span class="money-number">300.00</span>
                            </div>
                        </div>
                        <div class="goods-bottom-button">
                            <a href="javascript:;">抢</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<script id="huodong_template_2" type="text/html">
    <div class="setting-values" style="display: none">
        <input class="input_huodong_top_title" name="item_data[<%=input_index%>][data][top][top][0][title]" type="hidden" value=""/>
        <!-- <input class="input_huodong_top_url_type" name="item_data[<%=input_index%>][data][top][top][0][url_type]" type="hidden" value=""/>
        <input class="input_huodong_top_url" name="item_data[<%=input_index%>][data][top][top][0][url]" type="hidden" value=""/> -->
        
        <input class="input_huodong_bottom_mid_title_2" name="item_data[<%=input_index%>][data][bottom][mid][2][title]" type="hidden" value=""/>
    </div>
    <div class="huodong-content style-2">
        <div class="huodong-top">
            <a href="javascript:;">
                <div class="huodong-top-bg" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_top_bg-2.jpg) no-repeat;background-size: 100%;"></div>
                <div class="huodong-top-title">顶部标题</div>
            </a>
        </div>
        <div class="huodong-main" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_main_bg-2.jpg) no-repeat;background-size: 100%;">
            <div class="huodong-goods-list">
                <div class="huodong-goods-item">
                    <div class="goods-thumb">
                        <div class="empty-img"></div>
                    </div>
                    <div class="huodong-style-2-right">
                        <div class="countdown">
                            
                            <div class="countdown-main">
                                <span class="hours countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="min countdown-num">00</span>
                                <span class="countdown-break-flag">:</span>
                                <span class="sec countdown-num">00</span>
                            </div>
                        </div>
                        <div class="main-title">顶部标题</div>
                        <div class="goods-price">
                            <div class="sale-price">
                                ¥<span class="money-number">300.00</span>
                            </div>
                            <div class="market-price">
                                ¥<span class="money-number">300.00</span>
                            </div>
                        </div>
                        <div class="goods-other">
                            <div class="goods-tuan-info">
                                已团<span>0</span>件
                            </div>
                            <div class="goods-tuan-btn">
                                <a><span>立即团</span><span class="arrow-right">&gt;</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<!--活动模板   -->
<script id="huodong_dtemp" type="text/html">
    <div input_index="<%=total_length%>" class="special-item huodong_part default_huodong_part">
        <div bbctype="item_content" class="content">
            <div class="huodong_part_input">
                <input class="input_huodong" name="item_data[<%=total_length%>][type]" type="hidden" value="huodong"/>
                <input class="input_huodong_sele_style" name="item_data[<%=total_length%>][sele_style]" type="hidden" value="0"/>
            </div>
            <div class="huodong_part_zhanshi">
                <div class="modules-huodong">
                    <div class="style_template">
                        <div class="setting-values" style="display: none">
                            <input class="input_huodong_top_title" name="item_data[<%=total_length%>][data][top][top][0][title]" type="hidden" value=""/>
                            <!-- <input class="input_huodong_top_url_type" name="item_data[<%=total_length%>][data][top][top][0][url_type]" type="hidden" value=""/>
                            <input class="input_huodong_top_url" name="item_data[<%=total_length%>][data][top][top][0][url]" type="hidden" value=""/> -->

                            <input class="input_huodong_left_title" name="item_data[<%=total_length%>][data][left][top][0][title]" type="hidden" value=""/>
                            <input class="input_huodong_left_sub_title" name="item_data[<%=total_length%>][data][left][top][0][subtitle]" type="hidden" value=""/>

                            <input class="input_huodong_right_top_title" name="item_data[<%=total_length%>][data][right][top][0][title]" type="hidden" value=""/>
                            <input class="input_huodong_right_top_sub_title" name="item_data[<%=total_length%>][data][right][top][0][subtitle]" type="hidden" value=""/>

                            <input class="input_huodong_right_bottom_title_1" name="item_data[<%=total_length%>][data][right][bottom][1][title]" type="hidden" value=""/>
                            <input class="input_huodong_right_bottom_sub_title_1" name="item_data[<%=total_length%>][data][right][bottom][1][subtitle]" type="hidden" value=""/>
                            
                            <input class="input_huodong_right_bottom_title_2" name="item_data[<%=total_length%>][data][right][bottom][2][title]" type="hidden" value=""/>
                            <input class="input_huodong_right_bottom_sub_title_2" name="item_data[<%=total_length%>][data][right][bottom][2][subtitle]" type="hidden" value=""/>
                        </div>
                        <div class="huodong-content">
                            <div class="huodong-top">
                                <a href="javascript:;">
                                    <div class="huodong-top-bg" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_top_bg-0.jpg) no-repeat;background-size: 100%;"></div>
                                    <div class="huodong-top-title">顶部标题</div>
                                </a>
                            </div>
                            <div class="huodong-main">
                                <div class="huodong-left" style="background:url(<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/huodong_left_bg.jpeg) no-repeat;background-size: 100%;">
                                    <div class="huodong-left-top">
                                        <div class="huodong-left-top-layout">
                                            <div class="main-title">全民拼团</div>
                                            <div class="sub-title"><span>邂逅好物 发现理想生活</span></div>
                                            <div class="countdown">
                                                
                                                <div class="countdown-main">
                                                    <span class="hours countdown-num">00</span>
                                                    <span class="countdown-break-flag">:</span>
                                                    <span class="min countdown-num">00</span>
                                                    <span class="countdown-break-flag">:</span>
                                                    <span class="sec countdown-num">00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="huodong-left-goods">
                                        <div class="huodong-left-goods-layout">
                                            <a href="#">
                                                <div class="goods-thumb">
                                                    <div class="empty-img"></div>
                                                </div>
                                                <div class="goods-price">
                                                    <div class="sale-price">
                                                        ¥<span class="money-number">300.00</span>
                                                    </div>
                                                    <div class="market-price">
                                                        ¥<span class="money-number">300.00</span>
                                                    </div>
                                                </div>
                                                <div class="goods-other">
                                                    <div class="goods-extend-data">
                                                        <span><em>3</em>人团</span>
                                                        <span>|</span>
                                                        <span>去开团</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="huodong-right">
                                    <div class="huodong-right-top">
                                        <div class="huodong-top-title">
                                            <div class="main-title">顶部标题</div>
                                            <div class="sub-title"><span>顶部标题</span></div>
                                        </div>
                                        <div class="huodong-goods-list">
                                            <div class="huodong-goods-item">
                                                <div class="goods-thumb">
                                                    <div class="empty-img"></div>
                                                </div>
                                            </div>
                                            <div class="huodong-goods-item">
                                                <div class="goods-thumb">
                                                    <div class="empty-img"></div>
                                                </div>
                                            </div>
                                            <div class="huodong-goods-item">
                                                <div class="goods-thumb">
                                                    <div class="empty-img"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="huodong-right-bottom">
                                        <div class="huodong-goods-list">
                                            <div class="huodong-goods-item">
                                                <div class="huodong-top-title">
                                                    <div class="main-title">顶部标题</div>
                                                    <div class="sub-title"><span>顶部标题</span></div>
                                                </div>
                                                <div class="goods-thumb">
                                                    <div class="empty-img"></div>
                                                </div>
                                            </div>
                                            <div class="huodong-goods-item" style="border-width: 1px 0px 1px 0px;">
                                                <div class="huodong-top-title">
                                                    <div class="main-title">顶部标题</div>
                                                    <div class="sub-title"><span>顶部标题</span></div>
                                                </div>
                                                <div class="goods-thumb">
                                                    <div class="empty-img"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i class="iconfontfa fa-trash-o"></i></a></div>
    </div>
</script>
<!--活动 点击编辑详情内容-->
<script id="huodong_detail" type="text/html">
    <div class="detail_title"><span class="title_big">活动设置</span></div>
    <div class="detail_content huodong_detail">
        <div class="img-style">
            <ul class="clearfix">
                <li <% if(style==0){ %> class="active " <% } %> data-style="0">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_huodong_style01.jpg">
                </li>
                <li <% if(style==1){ %> class="active " <% } %> data-style="1">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_huodong_style02.jpg">
                </li>
                <li <% if(style==2){ %> class="active " <% } %> data-style="2">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_huodong_style03.jpg">
                </li>
            </ul>
        </div>
        <div class="change_detail">
             <%  if(style==0){ %>
    <div class="single_tpzh_edit">
        <div class="huodong-top-setting huodong-setting-item">
            <div class="huodong-setting-item-name">顶部</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">标题</span>
                <input class="huodong_top_text huodong_setting_item-top_text" data-for="input_huodong_top_title" type="text" value="<%=input_info.input_huodong_top_title%>">
            </div>
            <!-- <div class="det_line_div huodong_top_url huodong_setting_item-top_text" style="margin: 0;padding: 0px;">
                <span class="text">链接</span>
                <div style="display: inline-block">
                    <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" data-for="input_huodong_top_url_type" name="">
                        <option value="">-请选择-</option>
                        <option <% if(input_info.input_huodong_top_url_type=='keyword'){ %>selected="selected" <% } %> value="keyword">关键字</option>
                        <option <% if(input_info.input_huodong_top_url_type=='special'){ %>selected="selected" <% } %> value="special">专题编号</option>
                        <option <% if(input_info.input_huodong_top_url_type=='goods'){ %>selected="selected" <% } %> value="goods">商品编号</option>
                        <option <% if(input_info.input_huodong_top_url_type=='url'){ %>selected="selected" <% } %> value="url">链接</option>
                    </select>
                    <input value="<%=input_info.input_huodong_top_url%>" class="dialog_item_image_data" data-for="input_huodong_top_url" type="text">
                </div>
                <br>
                <span style="width: 68px;height: 1px;"></span>
                <span class="dialog_item_image_desc"></span>
            </div> -->
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">左侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text huodong_setting_item-left_text" data-for="input_huodong_left_title" type="text" value="<%=input_info.input_huodong_left_title%>">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text huodong_setting_item-left_sub_text" data-for="input_huodong_left_sub_title" type="text" value="<%=input_info.input_huodong_left_sub_title%>">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="1">拼团</option>
                </select>
                <input class="txt_goods_name" type="text" class="txt w200" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_left_gid" data-func="huodong_tpl_0_goods_item_0" data-max_len="1"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">右侧上方</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_top_title" type="text" value="<%=input_info.input_huodong_right_top_title%>">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_top_sub_title" type="text" value="<%=input_info.input_huodong_right_top_sub_title%>">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择3个商品,模板中有3个商品后再次添加时,会将之前的商品清空。</div>
                <select class="goods_activity_type">
                    <option value="1">拼团</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_right_top_gid" data-func="huodong_tpl_0_goods_item_1" data-max_len="3"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">右侧下方左侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_bottom_title_1" type="text" value="<%=input_info.input_huodong_right_bottom_title_1%>">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_bottom_sub_title_1" type="text" value="<%=input_info.input_huodong_right_bottom_sub_title_1%>">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="1">拼团</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_right_bottom_gid_1" data-func="huodong_tpl_0_goods_item_2" data-max_len="1"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">右侧下方右侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_bottom_title_2" type="text" value="<%=input_info.input_huodong_right_bottom_title_2%>">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_bottom_sub_title_2" type="text" value="<%=input_info.input_huodong_right_bottom_sub_title_2%>">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="1">拼团</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_right_bottom_gid_2" data-func="huodong_tpl_0_goods_item_2" data-max_len="1"></div>
            </div>
        </div>

    </div>
            <% }else if(style==1){ %>
    <div class="single_tpzh_edit">
        <div class="huodong-top-setting huodong-setting-item">
            <div class="huodong-setting-item-name">顶部</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">标题</span>
                <input class="huodong_top_text huodong_setting_item-top_text" data-for="input_huodong_top_title" type="text" value="<%=input_info.input_huodong_top_title%>">
            </div>
            <!-- <div class="det_line_div huodong_top_url huodong_setting_item-top_text" style="margin: 0;padding: 0px;">
                <span class="text">链接</span>
                <div style="display: inline-block">
                    <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" data-for="input_huodong_top_url_type" name="">
                        <option value="">-请选择-</option>
                        <option <% if(input_info.input_huodong_top_url_type=='keyword'){ %>selected="selected" <% } %> value="keyword">关键字</option>
                        <option <% if(input_info.input_huodong_top_url_type=='special'){ %>selected="selected" <% } %> value="special">专题编号</option>
                        <option <% if(input_info.input_huodong_top_url_type=='goods'){ %>selected="selected" <% } %> value="goods">商品编号</option>
                        <option <% if(input_info.input_huodong_top_url_type=='url'){ %>selected="selected" <% } %> value="url">链接</option>
                    </select>
                    <input value="<%=input_info.input_huodong_top_url%>" class="dialog_item_image_data" data-for="input_huodong_top_url" type="text">
                </div>
                <br>
                <span style="width: 68px;height: 1px;"></span>
                <span class="dialog_item_image_desc"></span>
            </div> -->
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">底部下方左侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_left_title_1" type="text" value="<%=input_info.input_huodong_bottom_left_title_1%>">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_left_sub_title_1" type="text" value="<%=input_info.input_huodong_bottom_left_sub_title_1%>">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="3">限时折扣</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_bottom_left_gid_1" data-func="huodong_tpl_1_goods_item_0" data-max_len="1"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">底部下方中间</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_mid_title_2" type="text" value="<%=input_info.input_huodong_bottom_mid_title_2%>">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_mid_sub_title_2" type="text" value="<%=input_info.input_huodong_bottom_mid_sub_title_2%>">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="3">限时折扣</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_bottom_mid_gid_2" data-func="huodong_tpl_1_goods_item_1" data-max_len="1"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">底部下方右侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_right_title_3" type="text" value="<%=input_info.input_huodong_bottom_right_title_3%>">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_right_sub_title_3" type="text" value="<%=input_info.input_huodong_bottom_right_sub_title_3%>">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="3">限时折扣</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_bottom_right_gid_3" data-func="huodong_tpl_1_goods_item_2" data-max_len="1"></div>
            </div>
        </div>

    </div>
            <% }else if(style==2){ %>
    <div class="single_tpzh_edit">
        <div class="huodong-top-setting huodong-setting-item">
            <div class="huodong-setting-item-name">顶部</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">标题</span>
                <input class="huodong_top_text huodong_setting_item-top_text" data-for="input_huodong_top_title" type="text" value="<%=input_info.input_huodong_top_title%>">
            </div>
            <!-- <div class="det_line_div huodong_top_url huodong_setting_item-top_text" style="margin: 0;padding: 0px;">
                <span class="text">链接</span>
                <div style="display: inline-block">
                    <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" data-for="input_huodong_top_url_type" name="">
                        <option value="">-请选择-</option>
                        <option <% if(input_info.input_huodong_top_url_type=='keyword'){ %>selected="selected" <% } %> value="keyword">关键字</option>
                        <option <% if(input_info.input_huodong_top_url_type=='special'){ %>selected="selected" <% } %> value="special">专题编号</option>
                        <option <% if(input_info.input_huodong_top_url_type=='goods'){ %>selected="selected" <% } %> value="goods">商品编号</option>
                        <option <% if(input_info.input_huodong_top_url_type=='url'){ %>selected="selected" <% } %> value="url">链接</option>
                    </select>
                    <input value="<%=input_info.input_huodong_top_url%>" class="dialog_item_image_data" data-for="input_huodong_top_url" type="text">
                </div>
                <br>
                <span style="width: 68px;height: 1px;"></span>
                <span class="dialog_item_image_desc"></span>
            </div> -->
        </div>

        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">底部下方中间</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_mid_title_2" type="text" value="<%=input_info.input_huodong_bottom_mid_title_2%>">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="2">团购</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_bottom_mid_gid_2" data-func="huodong_tpl_2_goods_item_0" data-max_len="1"></div>
            </div>
        </div>

    </div>
            <% } %>
        </div>
    </div>
</script>
<script id="huodong_detail_sele_0" type="text/html">
    <div class="single_tpzh_edit">
        <div class="huodong-top-setting huodong-setting-item">
            <div class="huodong-setting-item-name">顶部</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">标题</span>
                <input class="huodong_top_text huodong_setting_item-top_text" data-for="input_huodong_top_title" type="text" value="">
            </div>
            <!-- <div class="det_line_div huodong_top_url huodong_setting_item-top_text" style="margin: 0;padding: 0px;">
                <span class="text">链接</span>
                <div style="display: inline-block">
                    <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" data-for="input_huodong_top_url_type" name="">
                        <option value="">-请选择-</option>
                        <option selected="selected" value="keyword">关键字</option>
                        <option value="special">专题编号</option>
                        <option value="goods">商品编号</option>
                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" data-for="input_huodong_top_url" type="text">
                </div>
                <br>
                <span style="width: 68px;height: 1px;"></span>
                <span class="dialog_item_image_desc"></span>
            </div> -->
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">左侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text huodong_setting_item-left_text" data-for="input_huodong_left_title" type="text" value="">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text huodong_setting_item-left_sub_text" data-for="input_huodong_left_sub_title" type="text" value="">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="1">拼团</option>
                </select>
                <input class="txt_goods_name" type="text" class="txt w200" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_left_gid" data-func="huodong_tpl_0_goods_item_0" data-max_len="1"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">右侧上方</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_top_title" type="text" value="">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_top_sub_title" type="text" value="">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择3个商品,模板中有3个商品后再次添加时,会将之前的商品清空。</div>
                <select class="goods_activity_type">
                    <option value="1">拼团</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_right_top_gid" data-func="huodong_tpl_0_goods_item_1" data-max_len="3"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">右侧下方左侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_bottom_title_1" type="text" value="">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_bottom_sub_title_1" type="text" value="">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="1">拼团</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_right_bottom_gid_1" data-func="huodong_tpl_0_goods_item_2" data-max_len="1"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">右侧下方右侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_bottom_title_2" type="text" value="">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_right_bottom_sub_title_2" type="text" value="">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="1">拼团</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_right_bottom_gid_2" data-func="huodong_tpl_0_goods_item_2" data-max_len="1"></div>
            </div>
        </div>

    </div>
</script>
<script id="huodong_detail_sele_1" type="text/html">
    <div class="single_tpzh_edit">
        <div class="huodong-top-setting huodong-setting-item">
            <div class="huodong-setting-item-name">顶部</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">标题</span>
                <input class="huodong_top_text huodong_setting_item-top_text" data-for="input_huodong_top_title" type="text" value="">
            </div>
            <!-- <div class="det_line_div huodong_top_url huodong_setting_item-top_text" data-for="input_huodong_top_title" style="margin: 0;padding: 0px;">
                <span class="text">链接</span>
                <div style="display: inline-block">
                    <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" data-for="input_huodong_top_url_type" name="">
                        <option value="">-请选择-</option>
                        <option selected="selected" value="keyword">关键字</option>
                        <option value="special">专题编号</option>
                        <option value="goods">商品编号</option>
                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" data-for="input_huodong_top_url" type="text">
                </div>
                <br>
                <span style="width: 68px;height: 1px;"></span>
                <span class="dialog_item_image_desc"></span>
            </div> -->
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">底部下方左侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_left_title_1" type="text" value="">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_left_sub_title_1" type="text" value="">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="3">限时折扣</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_bottom_left_gid_1" data-func="huodong_tpl_1_goods_item_0" data-max_len="1"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">底部下方中间</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_mid_title_2" type="text" value="">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_mid_sub_title_2" type="text" value="">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="3">限时折扣</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_bottom_mid_gid_2" data-func="huodong_tpl_1_goods_item_1" data-max_len="1"></div>
            </div>
        </div>
        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">底部下方右侧</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_right_title_3" type="text" value="">
            </div>
            <div class="det_line_div huodong_top_url" style="margin: 0;padding: 0px;">
                <span class="text">子标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_right_sub_title_3" type="text" value="">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="3">限时折扣</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_bottom_right_gid_3" data-func="huodong_tpl_1_goods_item_2" data-max_len="1"></div>
            </div>
        </div>

    </div>
</script>=
<script id="huodong_detail_sele_2" type="text/html">
    <div class="single_tpzh_edit">
        <div class="huodong-top-setting huodong-setting-item">
            <div class="huodong-setting-item-name">顶部</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">标题</span>
                <input class="huodong_top_text huodong_setting_item-top_text" data-for="input_huodong_top_title" type="text" value="">
            </div>
            <!-- <div class="det_line_div huodong_top_url huodong_setting_item-top_text" style="margin: 0;padding: 0px;">
                <span class="text">链接</span>
                <div style="display: inline-block">
                    <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" data-for="input_huodong_top_url_type" name="">
                        <option value="">-请选择-</option>
                        <option selected="selected" value="keyword">关键字</option>
                        <option value="special">专题编号</option>
                        <option value="goods">商品编号</option>
                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" data-for="input_huodong_top_url" type="text">
                </div>
                <br>
                <span style="width: 68px;height: 1px;"></span>
                <span class="dialog_item_image_desc"></span>
            </div> -->
        </div>

        <div class="huodong-setting-item">
            <div class="huodong-setting-item-name">底部下方中间</div>
            <div class="det_line_div" style="margin: 0;padding: 0px;">
                <span class="text">主标题</span>
                <input class="huodong_top_text" data-for="input_huodong_bottom_mid_title_2" type="text" value="">
            </div>
            <div class="search-goods">
                <h3>选择商品添加</h3>
                <div class="setting-tips">只能选择1个商品</div>
                <select class="goods_activity_type">
                    <option value="2">团购</option>
                </select>
                <input type="text" class="txt w200 txt_goods_name" name="">
                <a class="btn-search btn_mb_special_goods_search" href="javascript:;" title="搜索"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/sousuo.png"></a>
                <div class="mb_special_goods_list" data-for="input_huodong_bottom_mid_gid_2" data-func="huodong_tpl_2_goods_item_0" data-max_len="1"></div>
            </div>
        </div>

    </div>
</script>
<!--图片组合 style0的样式组合-->
<script id="tupianzuhe_template_0" type="text/html">
    <div class="image-list style<%=style_index%>" >
        <ul class="clearfix">
            <li style="height:auto">
                <a  tpzh_index="1">
                    <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][1][img]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][1][title]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][1][url_type]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][1][url]" type="hidden" value=""/>
                    <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg">
                </a>
            </li>
            <li tpzh_index="2" style="height:auto">
                <a tpzh_index="2">
                    <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][2][img]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][2][title]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][2][url_type]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][2][url]" type="hidden" value=""/>
                    <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640_shen.jpg">
                </a>
            </li>
        </ul>
    </div>
</script>
<!--图片组合 style1的样式组合-->
<script id="tupianzuhe_template_1" type="text/html">
    <div class="image-list style<%=style_index%>" >
        <ul class="clearfix">
            <li style="height:auto">
                <a tpzh_index="1">
                    <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][1][img]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][1][title]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][1][url_type]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][1][url]" type="hidden" value=""/>
                    <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg">
                </a>
            </li>
            <li style="height:auto">
                <a tpzh_index="2">
                    <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][2][img]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][2][title]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][2][url_type]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][2][url]" type="hidden" value=""/>
                    <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg">
                </a>
            </li>
        </ul>
    </div>
</script>
<!--图片组合 style2的样式组合-->
<script id="tupianzuhe_template_2" type="text/html">
    <div class="image-list style2" >
        <ul class="clearfix">
            <li style="height:auto">
                <a  tpzh_index="1" style="padding-bottom: 8px;" >
                    <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][1][img]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][1][title]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][1][url_type]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][1][url]" type="hidden" value=""/>
                    <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
                </a>
            </li>
            <li  style="height:auto">
                <a tpzh_index="2" style="padding-bottom: 8px;" >
                    <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][2][img]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][2][title]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][2][url_type]" type="hidden" value=""/>
                    <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][2][url]" type="hidden" value=""/>
                    <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
                </a>
            </li>
        </ul>
    </div>
</script>
<!--图片组合 style3的样式组合-->
<script id="tupianzuhe_template_3" type="text/html">
    <div class="image-list style3">
        <ul class="clearfix ng-scope">
        <li  style="height:auto">
        <a tpzh_index="1" style="padding-bottom: 8px;">
            <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][1][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][1][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][1][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][1][url]" type="hidden" value=""/>
        <img  class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
        </a>
        </li>
        <li  style="height:auto">
        <a tpzh_index="2" style="padding-bottom: 8px;">
            <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][2][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][2][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][2][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][2][url]" type="hidden" value=""/>
        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
        </a>
        </li>
        <li  style="height:auto">
        <a tpzh_index="3" style="padding-bottom: 8px;">
            <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][3][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][3][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][3][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][3][url]" type="hidden" value=""/>
        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
        </a>
        </li>
        </ul>
        </div>
</script>
<!--图片组合 style4的样式组合-->
<script id="tupianzuhe_template_4" type="text/html">
    <div class="image-ad clearfix images-tpl">
        <div>
            <a tpzh_index="1" style="width:148px;height:156px;" >
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][1][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][1][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][1][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][1][url]" type="hidden" value=""/>
                <img class="lazy"  src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300x320.jpg">
            </a>
        </div>
        <div>
            <a tpzh_index="2" style="width:148px;height:74px;" >
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][2][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][2][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][2][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][2][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
            </a>
            <a  tpzh_index="3" style="width:148px;height:74px;" >
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][3][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][3][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][3][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][3][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
            </a>
        </div>
    </div>
</script>
<!--图片组合 style5的样式组合-->
<script id="tupianzuhe_template_5" type="text/html">
    <div class="image-ad2 clearfix images-tpl">
        <div class="clearfix">
            <a tpzh_index="1" style="width:98.66666666666667px;height:98.66666666666667px;">
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][1][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][1][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][1][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][1][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
            </a>
            <a tpzh_index="2" style="width:197.33333333333334px;height:98.66666666666667px">
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][2][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][2][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][2][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][2][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_400x200.jpg">
            </a>
        </div>
        <div class="clearfix">
            <a tpzh_index="3" style="width:197.33333333333334px;height:98.66666666666667px" >
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][3][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][3][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][3][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][3][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_400x200.jpg">
            </a>
            <a tpzh_index="4" style="width:98.66666666666667px;height:98.66666666666667px;">
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][4][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][4][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][4][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][4][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
            </a>
        </div>
    </div>
</script>
<!--图片组合 style6的样式组合-->
<script id="tupianzuhe_template_6" type="text/html">
    <div class="image-ad3 clearfix images-tpl" style="">
        <div>
        <a tpzh_index="1" style="width:148px;height:74px;">
            <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][1][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][1][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][1][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][1][url]" type="hidden" value=""/>
        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
        </a>
        <a tpzh_index="2" style="width:148px;height:148px;" >
            <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][2][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][2][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][2][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][2][url]" type="hidden" value=""/>
        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
        </a>
        </div>
        <div>
        <a tpzh_index="3" style="width:148px;height:148px;" >
            <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][3][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][3][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][3][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][3][url]" type="hidden" value=""/>
            <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
        </a>
        <a tpzh_index="4" style="width:148px;height:74px;" >
            <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][4][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][4][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][4][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][4][url]" type="hidden" value=""/>
        <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
        </a>
        </div>
        </div>
</script>
<!--图片组合 style7的样式组合-->
<script id="tupianzuhe_template_7" type="text/html">
    <div class="image-ad4 clearfix images-tpl">
        <div><a tpzh_index="1" style="width:96px;height:96px;">
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][1][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][1][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][1][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][1][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
            </a>
            <a tpzh_index="2" style="width:96px;height:96px;">
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][2][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][2][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][2][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][2][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
            </a>
        </div>
        <div>
            <a tpzh_index="3" style="width:96px;height:96px;" >
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][3][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][3][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][3][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][3][url]" type="hidden" value=""/>
                <img class="lazy"  src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
            </a>
            <a tpzh_index="4" style="width:96px;height:96px;">
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][4][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][4][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][4][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][4][url]" type="hidden" value=""/>
                <img class="lazy" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
            </a>
        </div>
        <div>
            <a tpzh_index="5" style="width:96px;height:200px;" >
                <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][5][img]" type="hidden" value=""/>
                <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][5][title]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][5][url_type]" type="hidden" value=""/>
                <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][5][url]" type="hidden" value=""/>
                <img class="lazy"  src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200x420.jpg">
            </a></div>
    </div>
</script>
<!--图片组合 拖拽添加内容-->
<script  id="tupianzuhe_dtemp" type="text/html">
    <div input_index="<%=total_length%>" class="special-item tupianzuhe_part default_tupianzuhe_part">
        <div bbctype="item_content" class="content">
        <div class="tupianzuhe_part_input">
        <input class="input_tupianzuhe" name="item_data[<%=total_length%>][type]" type="hidden" value="tupianzuhe"/>
        <input class="input_tupianzuhe_sele_style" name="item_data[<%=total_length%>][sele_style]" type="hidden" value="0"/>
        </div>
        <div class="tupianzuhe_part_zhanshi">
        <div class="modules-ad">
        <div class="style_template">
        <div class="image-list style0" >
        <ul class="clearfix">
        <li style="height:auto">
        <a tpzh_index="1">
            <input class="input_tupianzuhe_img" name="item_data[<%=total_length%>][data][1][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=total_length%>][data][1][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=total_length%>][data][1][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=total_length%>][data][1][url]" type="hidden" value=""/>
            <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg">
        </a>
        </li>
        <li style="height:auto">
        <a tpzh_index="2">
            <input class="input_tupianzuhe_img" name="item_data[<%=total_length%>][data][2][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=total_length%>][data][2][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=total_length%>][data][2][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=total_length%>][data][2][url]" type="hidden" value=""/>
            <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640_shen.jpg">
        </a>
        </li>
        </ul>
        </div>
        </div>
        </div>
        </div>
        </div>
        <div class="handle"><a bbctype="btn_del_item" href="javascript:;"><i
    class="iconfontfa fa-trash-o"></i></a></div>
        </div>
</script>
<!--图片组合 点击编辑详情内容-->
<script id="tupianzuhe_detail" type="text/html">
    <div class="detail_title"><span class="title_big">图片组合设置</span></div>
    <div class="detail_content tupianzuhe_detail">
        <div class="img-style">
            <ul class="clearfix">
                <li <% if(style==0){ %> class="active " <% } %> data-style="0">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_style01.png">
                </li>
                <li <% if(style==1){ %> class="active " <% } %> data-style="1">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_style02.png">
                </li>
                <li <% if(style==2){ %> class="active " <% } %> data-style="2">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_style03.png">
                </li>
                <li <% if(style==3){ %> class="active " <% } %> data-style="3">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_style04.png">
                </li>
                <li <% if(style==4){ %> class="active " <% } %> data-style="4">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_style05.png">
                </li>
                <li <% if(style==5){ %> class="active " <% } %> data-style="5">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_style06.png">
                </li>
                <li <% if(style==6){ %> class="active " <% } %> data-style="6">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_style07.png">
                </li>
                <li <% if(style==7){ %> class="active " <% } %> data-style="7">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_style08.png">
                </li>
            </ul>
        </div>
        <div class="change_detail">
        <div class="add_tpzh_btn"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/add_pic_btn.png"></div>
        <div class="single_tpzh_edit">
            <% for(var i in input_info){ %>
            <div tpzh_index_det="<%=i*1+1%>" class="dialog_item_edit_image">

                <div class="upload-thumb"><img class="dialog_item_image"
                   <% if(input_info[i].img){ %>
                        src="<%=input_info[i].img%>"
                   <% }else{ %>
                        <% if(style==0||style==1){ %>
                    src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg"
                        <% }else if(style==2){ %>
                    src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg"
                        <% }else if(style==3){ %>
                    src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg"
                        <% }else if(style==4){ %>
                            <% if(i==0){ %>
                    src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300x320.jpg"
                            <% }else if(i==1||i==2){ %>
                    src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg"
                            <% }  %>
                        <% }else if(style==5){ %>
                            <% if(i==0||i==3){ %>
                            src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg"
                            <% }else if(i==1||i==2){ %>
                            src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_400x200.jpg"
                            <% }  %>
                        <% }else if(style==6){ %>
                            <% if(i==0||i==3){ %>
                            src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg"
                            <% }else if(i==1||i==2){ %>
                            src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg"
                            <% }  %>
                        <% }else if(style==7){ %>
                    <% if(i==4){ %>
                    src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200x420.jpg"
                    <% }else { %>
                    src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg"
                    <% }  %>
                        <% } %>
                   <% } %>
                    >
                    <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
                </div>
                <div class="dialog-handle-box clearfix">
                    <div class="nav_con_name"><span class="nav_label">图片标题</span><input <% if(input_info[i].title){ %>value="<%=input_info[i].title%>"<% }else{ %>value=""<% } %> class="dialog_item_image_name" type="text"></div>
                    <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                            <option value="">-请选择-</option>
                           <!-- <option <% if(input_info[i].url_type=='keyword'){ %>checked="checked"<% } %>  value="keyword">关键字</option>

                            <option <% if(input_info[i].url_type=='special'){ %>checked="checked"<% } %>  value="special">专题编号</option>

                            <option <% if(input_info[i].url_type=='goods'){ %>checked="checked"<% } %> value="goods">商品编号</option>-->

                            <option <% if(input_info[i].url_type=='url'){ %>checked="checked"<% } %> value="url">链接</option>
                        </select><input <% if(input_info[i].url){ %>value="<%=input_info[i].url%>"<% }else{ %>value=""<% } %>  class="dialog_item_image_data" type="text"><br><span
                            class="dialog_item_image_desc"></span></div>
                    </div>
                </div>
                <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
            </div>
            <% } %>
        </div>
        </div>
    </div>
</script>
<!--图片组合 选择style0的编辑详情内容  -->
<script id="tupianzuhe_detail_sele_0" type="text/html">
    <div class="add_tpzh_btn"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/add_pic_btn.png"></div>
    <div class="single_tpzh_edit">
        <div tpzh_index_det="1" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div>
                </div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>

        <div tpzh_index_det="2" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>


    </div>
</script>
<!--图片组合 选择style1的编辑详情内容  -->
<script id="tupianzuhe_detail_sele_1" type="text/html">
    <div class="add_tpzh_btn"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/add_pic_btn.png"></div>
    <div class="single_tpzh_edit">
        <div tpzh_index_det="1" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>

        <div tpzh_index_det="2" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_640.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>
    </div>
</script>
<!--图片组合 选择style2的编辑详情内容  -->
<script id="tupianzuhe_detail_sele_2" type="text/html">
    <div class="add_tpzh_btn"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/add_pic_btn.png"></div>
    <div class="single_tpzh_edit">
        <div tpzh_index_det="1" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">
                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>

        <div tpzh_index_det="2" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>
    </div>
</script>
<!--图片组合 选择style3的编辑详情内容  -->
<script id="tupianzuhe_detail_sele_3" type="text/html">
    <div class="add_tpzh_btn"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/add_pic_btn.png"></div>
    <div class="single_tpzh_edit">
        <div tpzh_index_det="1" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>

        <div tpzh_index_det="2" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>

        <div tpzh_index_det="3" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
            <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
        </div>
    </div>
</script>
<!--图片组合 选择style4的编辑详情内容  -->
<script id="tupianzuhe_detail_sele_4" type="text/html">
    <div class="single_tpzh_edit">
        <div tpzh_index_det="1" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300x320.jpg">
                <span class="upload_img_span">
                    <input class="btn_upload_image" type="file" name="special_image">
                    <img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png">
                </span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div  tpzh_index_det="2" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div  tpzh_index_det="3" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>
    </div>
</script>
<!--图片组合 选择style5的编辑详情内容  -->
<script id="tupianzuhe_detail_sele_5" type="text/html">
    <div class="single_tpzh_edit">
        <div  tpzh_index_det="1" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div  tpzh_index_det="2" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_400x200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div  tpzh_index_det="3" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_400x200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div  tpzh_index_det="4" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select>
                    <input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>
    </div>
</script>
<!--图片组合 选择style6的编辑详情内容  -->
<script id="tupianzuhe_detail_sele_6" type="text/html">
    <div class="single_tpzh_edit">
        <div tpzh_index_det="1" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div tpzh_index_det="2" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div tpzh_index_det="3" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_300.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div tpzh_index_det="4" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wdth_300x150.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>
    </div>
</script>
<!--图片组合 选择style7的编辑详情内容  -->
<script id="tupianzuhe_detail_sele_7" type="text/html">
    <div class="single_tpzh_edit">
        <div tpzh_index_det="1" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div tpzh_index_det="2" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div tpzh_index_det="3" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>

        <div tpzh_index_det="4" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>
        <div tpzh_index_det="5" class="dialog_item_edit_image">
            <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/width_200x420.jpg">
                <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
            </div>
            <div class="dialog-handle-box clearfix">
                <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
                <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                        <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                        <option value="">-请选择-</option>
<!--                        <option value="keyword">关键字</option>-->
<!---->
<!--                        <option  value="special">专题编号</option>-->
<!---->
<!--                        <option value="goods">商品编号</option>-->

                        <option value="url">链接</option>
                    </select><input value="" class="dialog_item_image_data" type="text"><br><span
                        class="dialog_item_image_desc"></span></div></div>
            </div>
        </div>
    </div>
</script>
<!--图片组合 添加图片内容-->
<script id="tupianzuhe_detail_every" type="text/html">
    <div tpzh_index_det="<%=num%>" class="dialog_item_edit_image">
        <div class="upload-thumb"><img class="dialog_item_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_add.png">
            <span class="upload_img_span"><input class="btn_upload_image" type="file" name="special_image"><img src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/upload_pic.png"></span>
        </div>
        <div class="dialog-handle-box clearfix">
            <div class="nav_con_name"><span class="nav_label">图片标题</span><input value="" class="dialog_item_image_name" type="text"></div>
            <div class="nav_con_url"><span class="nav_label">图片链接</span><div style="display: inline-block">
                    <select style="height: 38px;width:150px;border:1px solid #3b7ed1" class="dialog_item_image_type" name="">

                    <option value="">-请选择-</option>
<!--                    <option value="keyword">关键字</option>-->
<!---->
<!--                    <option  value="special">专题编号</option>-->
<!---->
<!--                    <option value="goods">商品编号</option>-->

                    <option value="url">链接</option>
                </select><input value="" class="dialog_item_image_data" type="text"><br><span
                    class="dialog_item_image_desc"></span></div></div>
        </div>
        <span class="del_tpzh_edit_info"><i class="iconfontfa fa-trash-o"></i></span>
    </div>
</script>
<!--图片组合 添加图片对应的手机里内容-->
<script id="tupianzuhe_template_every" type="text/html">
    <li style="height:auto">
        <a  tpzh_index="<%=num%>">
            <input class="input_tupianzuhe_img" name="item_data[<%=input_index%>][data][<%=num%>][img]" type="hidden" value=""/>
            <input class="input_tupianzuhe_title" name="item_data[<%=input_index%>][data][<%=num%>][title]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url_type" name="item_data[<%=input_index%>][data][<%=num%>][url_type]" type="hidden" value=""/>
            <input class="input_tupianzuhe_url" name="item_data[<%=input_index%>][data][<%=num%>][url]" type="hidden" value=""/>
            <img class="lazy" alt="" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/zidingyi/wap_def_add.png">
        </a>
    </li>
</script>
<script type="text/javascript">
    load_countdown_val();
</script>