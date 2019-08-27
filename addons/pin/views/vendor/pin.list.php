<?php defined('DYMall') or exit('Access Invalid!');?>
<link href="<?php echo STATIC_SITE_URL;?>/js/layer/theme/default/layer.css" rel="stylesheet" type="text/css"/>
<style>
    .layui-layer-prompt .layui-layer-title { display: block !important;}
    .bd-line.active{ transform: scale(1.05); box-shadow: 0 0 20px #ccc}
</style>
<div class="tabmenu">
  <?php include template('layout/submenu');?>

    <a href="<?php echo urlAddons('add');?>" class="bbc_ms-btn bbc_ms-btn-green" title="新增拼团"><i class="iconfontfa fa-plus-circle add_iconcolor"></i>新增拼团</a>

</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">
<div class="alert alert-block mt10">
      <ul class="mt5">
        <li>1、点击新增团购按钮可以添加团购活动</li>
      </ul>
</div>
<table class="search-form">
  <form method="get">
      <input type="hidden" name="app" value="action" />
      <input type="hidden" name="mod" value="pin_list" />
      <input type="hidden" name="sld_addons" value="pin" />
    <tr>
      <th><?php echo $lang['tuan_index_activity_state'];?></th>
      <td class="w100"><select name="tuan_state" class="w90">
          <?php if(is_array($output['tuan_state_array'])) { ?>
          <?php foreach($output['tuan_state_array'] as $key=>$val) { ?>
          <option value="<?php echo $key;?>" <?php if($key == $_GET['tuan_state']) { echo 'selected';}?>><?php echo $val;?></option>
          <?php } ?>
          <?php } ?>
        </select></td>
    <th>分类名称</th>
    <td class="w100"><select name="type" class="w90">
            <option value="">全部</option>
            <?php if(is_array($output['types'])) { ?>
                <?php foreach($output['types'] as $key=>$val) { ?>
                    <option value="<?php echo $val['id'];?>" <?php if($val['id'] == $_GET['type']) { echo 'selected';}?>><?php echo $val['sld_typename'];?></option>
                <?php } ?>
            <?php } ?>
        </select></td>
      <th>商品名称</th>
      <td class="w160"><input class="text" type="text" name="tuan_name" value="<?php echo $_GET['tuan_name'];?>"/></td>
      <td class="w70 tc"><label class="submit-border"><input type="submit" class="submit submit_search" value="<?php echo $lang['搜索'];?>" /></label></td>
      <td>&nbsp;</td>
    </tr>
  </form>
</table>
<table class="bbc_ms-table-style">
  <thead>
    <tr>
      <th class="w50">活动id</th>
      <th class="w50">封面</th>
      <th class="w50">产品</th>
      <th class="w50">分类</th>
      <th class="w130">开始时间</th>
      <th class="w130">结束时间</th>
      <th class="w90">已功/付款/参与</th>
      <th class="w90">状态</th>
      <th class="w110">操作</th>
    </tr>
  </thead>
  <tbody>
    <?php if(!empty($output['group']) && is_array($output['group'])){?>
    <?php foreach($output['group'] as $key=>$group){?>
    <tr class="bd-line">
      <td><?php echo $group['id'];?></td>
        <td><div class="pic-thumb"><a href="../index.php?app=goods&gid=<?php echo $group['sld_gid'];?>" target="_blank"><img src="<?php echo gthumb($group['sld_pic'], 'small');?>"/></a></div></td>
        <td class="tl"><dl class="goods-name">
                <dt><a target="_blank" href="../index.php?app=goods&gid=<?php echo $group['sld_gid'];?>"><?php echo $group['goods_name'];?></a></dt>
            </dl></td>
      <td><?php echo $group['sld_typename'];?></td>
      <td><?php echo $group['start_time_text'];?></td>
      <td><?php echo $group['end_time_text'];?></td>
          <td><?php echo $group['cheng'].'/'.$group['youxiao'].'/'.$group['zong']?></td>
      <td><?php echo $group['tuan_state_text'];?></td>
      <td>
          <?php if($group['tuan_state_text'] == '等待开始' ){?> <!--未开始-->
              <a href="<?php echo urlAddons('edit')?>&id=<?php echo $group['id'];?>">编辑</a>
              <a href="javascript:void(0);" onclick="ajax_get_confirm('确定要删除吗？', '<?php echo urlAddons('delete').'&id='.$group['id'];?>');"><p>删除</p></a>
              <a href="javascript:ajaxget('<?php echo urlAddons('delete')?>&id=<?php echo $group['id'];?>')"></a>
          <?php }?>
          <?php if($group['tuan_state_text'] == '进行中' ){?><!--已开始-->
              <?php if($group['zong']<1 &&1==2){?><!--如果没人参加是可以编辑的-->
                    <a href="<?php echo urlAddons('edit')?>&id=<?php echo $group['id'];?>">编辑</a>
                <?php }else{?>
                    <a href="<?php echo urlAddons('view')?>&id=<?php echo $group['id'];?>">查看</a>
              <?php }?>
              <a href="<?php echo urlAddons('team_list')?>&id=<?php echo $group['id'];?>">查看团队</a>
              <!--<a href="javascript:void(0);" class="add_stock">增加库存</a>-->
              <a href="javascript:void(0);" onclick="ajax_get_confirm('确定要停止吗？停止后，该活动无法再次打开', '<?php echo urlAddons('stoppin').'&id='.$group['id'];?>');"><p>终止</p></a>
          <?php }?>
          <?php if($group['tuan_state_text'] == '已结束' ){?>
              <a href="<?php echo urlAddons('view')?>&id=<?php echo $group['id'];?>">查看</a>
              <a href="<?php echo urlAddons('team_list')?>&id=<?php echo $group['id'];?>">查看团队</a>
              <?php if($group['zong']<1){?><!--已结束活动 参团人数为零时 可删除-->
              <a href="javascript:void(0);" onclick="ajax_get_confirm('确定要删除吗？', '<?php echo urlAddons('delete').'&id='.$group['id'];?>');"><p>删除</p></a>
              <?php }?>
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
<div class="vendor_bottom_logo"><?php include template('footer');?></div>
<script type="text/javascript" src="<?php echo STATIC_SITE_URL;?>/js/layer/layer.js" charset="utf-8"></script>
<script>
    $('.add_stock').click(function () {
        var ele= $(this);
        ele.parents('tr.bd-line').addClass('active');
        layer.prompt({title: '要增加多少库存？'},function(val, index){
            layer.msg('得到了'+val);
            layer.close(index);
        });
        setTimeout(function(){
            ele.parents('tr.bd-line').removeClass('active');
            setTimeout(function(){
                ele.parents('tr.bd-line').addClass('active');
                setTimeout(function(){
                    ele.parents('tr.bd-line').removeClass('active');
                    setTimeout(function(){
                        ele.parents('tr.bd-line').addClass('active');
                        setTimeout(function(){
                            ele.parents('tr.bd-line').removeClass('active');
                            setTimeout(function(){
                                ele.parents('tr.bd-line').addClass('active');
                                setTimeout(function(){
                                    ele.parents('tr.bd-line').removeClass('active');
                                }, 300);
                            }, 200);
                        }, 300);
                    }, 200);
                }, 300);
            }, 200);
        }, 300);
        return false;
    });
</script>
