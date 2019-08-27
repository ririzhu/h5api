<?php defined('DYMall') or exit('Access Invalid!');?>
<link href="<?php echo STATIC_SITE_URL;?>/js/layer/theme/default/layer.css" rel="stylesheet" type="text/css"/>
<style>
    .layui-layer-prompt .layui-layer-title { display: block !important;}
    .bd-line.active{ transform: scale(1.05); box-shadow: 0 0 20px #ccc}
    .bbc_ms-table-style tbody td { line-height: 22px;}
</style>
<div class="tabmenu">
  <?php include template('layout/submenu');?>
</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">
<table class="search-form">
  <form method="get">
      <input type="hidden" name="app" value="action" />
      <input type="hidden" name="mod" value="red_user_list" />
      <input type="hidden" name="sld_addons" value="red" />
      <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
    <tr>
        <th>关键词</th>
        <td class="w120"><input class="text" placeholder="输入红包关键词" type="text" name="red_title" value="<?php echo $_GET['red_title'];?>"/></td>
      <th>状态</th>
      <td class="w100"><select name="red_status" class="w90">
          <option value="" <?php if($_GET['red_status'] ==='' || !isset($_GET['red_status'])) { echo 'selected';}?>>全部</option>
          <?php foreach($output['redstatus'] as $key=>$val) { ?>
          <option value="<?php echo $key;?>" <?php if($key == $_GET['red_status'] && $_GET['red_status']!='') { echo 'selected';}?>><?php echo $val;?></option>
          <?php } ?>
        </select></td>

        <td class="w70 tc"><label class="submit-border"><input type="submit" class="submit submit_search" value="<?php echo $lang['搜索'];?>" /></label></td>
    </tr>
  </form>
</table>
<table class="bbc_ms-table-style">
  <thead>
    <tr>
      <th class="w50">编号</th>
      <th class="w50">红包</th>
      <th class="w120">序号</th>
      <th class="w50">面值</th>
      <th class="w90">状态</th>
      <th class="w90">会员</th>
      <th class="w90">领取时间</th>
      <th class="w50">使用时间</th>
      <th class="w110">操作</th>
    </tr>
  </thead>
  <tbody>
    <?php if(!empty($output['list']) && is_array($output['list'])){?>
    <?php foreach($output['list'] as $key=>$group){?>
    <tr class="bd-line">

      <td><?php echo $group['id'];?></td>
      <td><?php echo $group['red_title'];?></td>
      <td><?php echo $group['reduser_proof'];?></td>
      <td>￥<?php echo $group['redinfo_money'];?></td>
      <td><?php echo $group['reduser_use']?'已使用':'未使用';?></td>
      <td><?php echo $group['member_name'];?></td>
      <td><?php echo date('Y-m-d H:i:s',$group['reduser_get']);?></td>
      <td><?php echo $group['reduser_use']?date('Y-m-d H:i:s',$group['reduser_use']):'——';?></td>
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
