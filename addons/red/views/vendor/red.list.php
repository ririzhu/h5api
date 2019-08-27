<?php defined('DYMall') or exit('Access Invalid!');?>
<link href="<?php echo STATIC_SITE_URL;?>/js/layer/theme/default/layer.css" rel="stylesheet" type="text/css"/>
<style>
    .layui-layer-prompt .layui-layer-title { display: block !important;}
    .bd-line.active{ transform: scale(1.05); box-shadow: 0 0 20px #ccc}
    .bbc_ms-table-style tbody td { line-height: 22px;}
</style>
<div class="tabmenu">
  <?php include template('layout/submenu');?>
    <a href="<?php echo urlAddons('red_add');?>" class="bbc_ms-btn bbc_ms-btn-green" title="新增优惠券"><i class="iconfontfa fa-plus-circle add_iconcolor"></i>新增优惠券</a>
</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">
<div class="alert alert-block mt10">
      <ul class="mt5">
        <li>1、点击新增优惠券按钮可以添加优惠券</li>
      </ul>
</div>
<table class="search-form">
  <form method="get">
      <input type="hidden" name="app" value="action_red" />
      <input type="hidden" name="mod" value="index" />
      <input type="hidden" name="sld_addons" value="red" />
    <tr>
        <th>关键词</th>
        <td class="w120"><input class="text" type="text" name="red_title" placeholder="输入红包关键词" value="<?php echo $_GET['red_title'];?>"/></td>
      <th>状态</th>
      <td class="w100"><select name="red_status" class="w90">
          <?php if(is_array($output['redstatus'])) { ?>
          <option value="" <?php if($_GET['red_status'] ==='' || !isset($_GET['red_status'])) { echo 'selected';}?>>全部</option>
          <?php foreach($output['redstatus'] as $key=>$val) { ?>
          <option value="<?php echo $key;?>" <?php if($key == $_GET['red_status'] && $_GET['red_status']!='') { echo 'selected';}?>><?php echo $val;?></option>
          <?php } ?>
          <?php } ?>
        </select></td>
    <th>使用有效期</th>
        <td class="searchtime"><input name="redinfo_start" id="add_time_from" type="text" class="text w70" value="<?php echo $_GET['redinfo_start']; ?>" /><label class="add-on"><i class="iconfontfa fa-calendar"></i></label>&nbsp;&#8211;&nbsp;<input name="redinfo_end" id="add_time_to" type="text" class="text w70" value="<?php echo $_GET['redinfo_end']; ?>" /><label class="add-on"><i class="iconfontfa fa-calendar"></i></label></td>
        <td class="w70 tc"><label class="submit-border"><input type="submit" class="submit submit_search" value="<?php echo $lang['搜索'];?>" /></label></td>
    </tr>
  </form>
</table>
<table class="bbc_ms-table-style">
  <thead>
    <tr>
      <th class="w50">编号</th>
      <th class="w120">名称</th>
      <th class="w50">面值</th>
      <th class="w90">有效期</th>
      <th class="w90">发放/领取/使用</th>
      <th class="w50">状态</th>
      <th class="w50">类型</th>
      <th class="w110">操作</th>
    </tr>
  </thead>
  <tbody>
    <?php if(!empty($output['list']) && is_array($output['list'])){?>
    <?php foreach($output['list'] as $key=>$group){?>
    <tr class="bd-line">

      <td><?php echo $group['id'];?></td>
        <td><a href="<?php echo urlAddons('red_view')?>&id=<?php echo $group['id'];?>"><?php echo $group['red_title'];?></a></td>
        <td><dl>
                <dt><?php echo sldPriceFormat($group['min_money']);?></dt>
            </dl></td>
      <td><?php echo $group['redinfo_start_text'];?><br>~<br><?php echo $group['redinfo_end_text'];?></td>
          <td><?php echo $group['red_limit'].'/'.$group['red_hasget'].'/'.$group['red_hasuse']?></td>
      <td><?php echo $group['red_status_text'];?></td>
      <td><?php echo $group['red_front_show']==1?'普通优惠券':'活动优惠券';?></td>
      <td>
          <a href="<?php echo urlAddons('red_view')?>&id=<?php echo $group['id'];?>">查看</a>
          <a href="<?php echo urlAddons('red_user_list')?>&id=<?php echo $group['id'];?>">已发放</a>
          <?php if($group['red_status'] !== '1' ){?><!--已开始-->
          <a href="javascript:void(0);" onclick="ajax_get_confirm('确定要停止发放吗？', '<?php echo urlAddons('red_abolish').'&id='.$group['id'];?>');"><p>停止发放</p></a>
          <?php }?>
          <a href="javascript:void(0);" onclick="ajax_get_confirm('删除后优惠券将停止发放,确定要删除吗？', '<?php echo urlAddons('red_delete').'&id='.$group['id'];?>');"><p>删除</p></a>

      </td>
    </tr>
    <?php }?>
    <?php }else{?>
    <tr>
      <td colspan="20" class="norecord"><div class="warning-option"><i class="iconfontfa fa-exclamation-triangle"></i><span><?php echo $lang['无数据'];?></span></div></td>
    </tr>
    <?php }?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="20"><div class="pagination"><?php echo $output['show_page']; ?></div></td>
    </tr>
  </tfoot>
</table>
</div>

<link rel="stylesheet" type="text/css" href="<?php echo STATIC_SITE_URL;?>/js/jquery-ui/themes/ui-lightness/jquery.ui.css"  />
<script src="<?php echo STATIC_SITE_URL;?>/js/jquery-ui/i18n/jquery.ui.datepicker-zh-CN.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/layer/layer.js" charset="utf-8"></script>
<script>
    $(document).ready(function(){
        $('#add_time_from').datepicker();
        $('#add_time_to').datepicker();
    });
    $('.add_stock').click(function () {
        var ele= $(this);
        ele.parents('tr.bd-line').addClass('active');
        layer.prompt({title: '要增加多少库存？'},function(val, index){
            layer.msg('得到了'+val);
            layer.close(index);
        });
        return false;
    });
</script>

<div class="vendor_bottom_logo"><?php include template('footer');?></div>
