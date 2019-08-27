<?php defined('DYMall') or exit('Access Invalid!');?>
<style>
    .search-form td{ padding: 10px;}
    body,html{ background: transparent; }
    .search-form{ min-width: auto; border:1px #e2e2e2 solid; margin-bottom: 0; }
</style>


<form method="get">
<input type="hidden" name="app" value="manage">
<input type="hidden" name="mod" value="iframe">
<input type="hidden" name="sld_addons" value="firstDiscount">
<table class="search-form">
    <tr>
      <th>商品名称</th>
      <td class="w160"><input type="text" class="text w150" name="p_name" value="<?php echo $_GET['p_name']; ?>"/></td>
      <td class="w70 tc"><label class="submit-border"><input type="submit" class="submit submit_search" value="<?php echo $lang['搜索'];?>" /></label></td>
      <td>&nbsp;</td>
    </tr>
</table>
</form>
    <ul>
    <?php if (!empty($output['goods_list'])) { ?>
    <?php foreach($output['goods_list'] as $val) { ?>
            <li></li>
    <?php } ?>
    </ul>
    <?php } else { ?>
        <div class="norecord" style="text-align: center;"><div style="margin-bottom: 30px" class="warning-option"><i class="iconfontfa fa-exclamation-triangle"></i><span></span></div>
        <?php echo $lang['无数据'];?></div>
    <?php } ?>

    <?php if (!empty($output['goods_list'])) { ?>
    <div class="pagination"><?php echo $output['show_page']; ?></div>
    <?php } ?>
</div>
<script>
    var have_ids = '<?php echo $_GET['have_id'];?>';
    var have_ids_arr = have_ids.split(',');
    var p_name = "<?php echo $_GET['p_name'];?>";
    var p_tag = "<?php echo $_GET['p_tag'];?>";
    var pn = "<?php echo $_GET['pn'];?>";
    function select_video(ele) {
        var id = $(ele).parents('tr').data('video_id');
        if(have_ids_arr.indexOf(id)<0 && $(ele).find('p').html()=='选择') {
            have_ids_arr.push(id);
            parent.addVideo($(ele).parents('tr'));
            have_ids = have_ids_arr.join(',');
            window.location= 'index.php?app=videos&mod=iframe&have_id='+have_ids+'&pn='+pn+'&p_name='+p_name+'&p_tag='+p_tag;
        }
    }
</script>