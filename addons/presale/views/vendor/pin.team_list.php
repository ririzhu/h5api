<?php defined('DYMall') or exit('Access Invalid!');?>
<script>
    var COMMON_URL = "<?php echo COMMON_URL; ?>";
</script>
<link href="<?php echo ADDONS_URL;?>data/vendor.css" rel="stylesheet" type="text/css">
<div class="tabmenu">
  <ul class="tab pngFix">
    <li class="normal">
      <a href="index.php?app=presale&mod=index&sld_addons=presale">预售列表</a>
    </li>
    <li class="active">
      <a href="index.php?app=presale&mod=team_list&sld_addons=presale">预售销售列表</a>
    </li>
  </ul>

</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">

<table class="search-form">
  <form method="get">
      <input type="hidden" name="app" value="presale" />
      <input type="hidden" name="mod" value="team_list" />
      <input type="hidden" name="sld_addons" value="presale" />
      <input type="hidden" name="id" value="<?php echo $_GET['id']?>" />
    <tr>
      <th>组队状态</th>
      <td class="w100"><select name="tuan_state" class="w90">
        <option value="1" <?php if('1' === $_GET['tuan_state']) { echo 'selected';}?>>全部</option>
        <option value="20" <?php if('20' === $_GET['tuan_state']) { echo 'selected';}?>>已付定金</option>
        <option value="30" <?php if('30' === $_GET['tuan_state']) { echo 'selected';}?>>已付尾款</option>
        </select></td>

      <td class="w70 tc"><label class="submit-border"><input type="submit" class="submit submit_search" value="<?php echo $lang['搜索'];?>" /></label></td>
      <td> 仅显示已付款的预售信息 </td>
    </tr>
  </form>
</table>
<table class="bbc_ms-table-style">
  <thead>
    <tr>
      <th class="w50">会员id</th>
      <th class="w100">会员名称</th>
      <th class="w100">支付定金时间</th>
      <th class="w100">成功时间</th>
      <th class="w80">商品名称</th>
      <th class="w80">商品数量</th>
      <th class="w100">商品图片</th>
    <th class="w50">状态</th>
      <th class="w110">操作</th>
    </tr>
  </thead>
  <tbody>
    <?php if(!empty($output['group']) && is_array($output['group'])){?>
    <?php foreach($output['group'] as $key=>$group){?>
    <tr class="bd-line">
      <td><?php echo $group['buyer_id'];?></td>
      <td><?php echo $group['buyer_name'];?></td>
        <td><?php echo $group['first_time']?date('Y-m-d H:i:s',$group['first_time']):'';?></td>
        <td><?php echo $group['finished_time']?date('Y-m-d H:i:s',$group['finished_time']):'';?></td>
      <td><a target="_blank" href="<?php echo C('main_url')?>/index.php?app=goods&gid=<?php echo $group['gid']?>"><?php echo $group['goods_name'];?></a></td>
      <td><?php echo $group['goods_num'];?></td>
      <td class="r">
        <img src="<?php echo cthumb($group['goods_image'])?>" alt="<?php echo $group['goods_name'];?>" title="<?php echo $group['goods_name'];?>" style="width:60px">
      </td>
      <td><?php if($group['order_state']==20){echo '已付定金';}elseif($group['order_state']==30){echo '已付尾款';}else{echo '未付定金';}?></td>
      <td>
              -
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
