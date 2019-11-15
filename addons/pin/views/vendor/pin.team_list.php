<?php defined('DYMall') or exit('Access Invalid!');?>
<script>
    var COMMON_URL = "<?php echo COMMON_URL; ?>";
</script>
<link href="<?php echo ADDONS_URL;?>data/vendor.css" rel="stylesheet" type="text/css">
<div class="tabmenu">
  <?php include template('layout/submenu');?>

</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">

<table class="search-form">
  <form method="get">
      <input type="hidden" name="app" value="action" />
      <input type="hidden" name="mod" value="team_list" />
      <input type="hidden" name="sld_addons" value="pin" />
      <input type="hidden" name="id" value="<?php echo $_GET['id']?>" />
    <tr>
      <th>组队状态</th>
      <td class="w100"><select name="tuan_state" class="w90">
        <option value="">全部</option>
        <option value="0" <?php if('0' === $_GET['tuan_state']) { echo 'selected';}?>>组团中</option>
        <option value="1" <?php if('1' === $_GET['tuan_state']) { echo 'selected';}?>>组团成功</option>
        <option value="2" <?php if('2' === $_GET['tuan_state']) { echo 'selected';}?>>组团失败</option>
        </select></td>

      <td class="w70 tc"><label class="submit-border"><input type="submit" class="submit submit_search" value="<?php echo $lang['搜索'];?>" /></label></td>
      <td> 仅显示已付款的团队成员 </td>
    </tr>
  </form>
</table>
<table class="bbc_ms-table-style">
  <thead>
    <tr>
      <th class="w50">团队id</th>
      <th class="w100">开团时间</th>
      <th class="w100">剩余时间</th>
      <th class="w80">参团人数</th>
      <th>会员</th>
      <th class="w50">状态</th>
      <th class="w110">操作</th>
    </tr>
  </thead>
  <tbody>
    <?php if(!empty($output['group']) && is_array($output['group'])){?>
    <?php foreach($output['group'] as $key=>$group){?>
    <tr class="bd-line">
      <td><?php echo $group['id'];?></td>
      <td><?php echo $group['start_time_text'];?></td>
        <td><?php echo $group['sheng'];?></td>
      <td><?php echo $group['ren'];?></td>
      <td class="team_user">
          <?php foreach($group['users'] as $kkk=>$vvv){ ?>
            <b title="<?php echo $vvv['member_name'];?>"><img src="<?php echo $vvv['member_avatar'];?>" ><?php echo $vvv['member_name'];?>
<!--                <br>--><?php //echo $vvv['time'];?>
            </b>
          <?php }?>
      </td>
      <td><?php echo $group['state'];?></td>
      <td>
          <?php if($group['sld_tuan_status'] == 0 ){?>
              <a href="javascript:ajaxget('<?php echo urlAddons('setSuccess')?>&id=<?php echo $group['id'];?>&pin_id=<?php echo $_GET['id'];?>')">手动成团</a>
          <?php }else{?>
              -
          <?php }?>
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
<div class="vendor_bottom_logo"><img src="<?php echo VENDOR_TEMPLATES_URL;?>/images/vendor_bottom_logo.png"/></div>
