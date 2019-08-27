<?php
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/26
 * Time: 21:43
 */
defined('DYMall') or exit('Access Invalid!');
?>

<link href="<?php echo STATIC_SITE_URL;?>/js/layer/theme/default/layer.css" rel="stylesheet" type="text/css"/>
<style>
    .layui-layer-prompt .layui-layer-title { display: block !important;}
    .bd-line.active{ transform: scale(1.05); box-shadow: 0 0 20px #ccc}
</style>
<div class="tabmenu">
    <ul class="tab pngFix">
        <li class="active"><a href="index.php?app=presale&mod=index&sld_addons=presale">预售列表</a></li></ul>
    <a href="index.php?app=presale&mod=add&sld_addons=presale" class="bbc_ms-btn bbc_ms-btn-green" title="新增预售"><i class="iconfontfa fa-plus-circle add_iconcolor"></i>新增预售</a>

</div>
<div class="vendor_empty"></div>
<div class="ven_content_wrap_padding">
    <div class="alert alert-block mt10">
        <ul class="mt5">
            <li>1、点击新增预售按钮可以添加预售活动</li>
        </ul>
    </div>
    <table class="search-form">
        <form method="get">
            <input type="hidden" name="app" value="presale" />
            <input type="hidden" name="mod" value="index" />
            <input type="hidden" name="sld_addons" value="presale" />
            <tr>
                <th><?php echo $lang['tuan_index_activity_state'];?></th>
                <td class="w100"><select name="status" class="w90">
                        <?php if(is_array($output['prestate'])) { ?>
                            <?php foreach($output['prestate'] as $key=>$val) { ?>
                                <option value="<?php echo $key;?>" <?php if($key == $_GET['status']) { echo 'selected';}?>><?php echo $val;?></option>
                            <?php } ?>
                        <?php } ?>
                    </select></td>
                <th>分类名称</th>
                <td class="w100"><select name="cateid" class="w90">
                        <option value="">全部</option>
                        <?php if(is_array($output['cate'])) { ?>
                            <?php foreach($output['cate'] as $key=>$val) { ?>
                                <option value="<?php echo $val['id'];?>" <?php if($val['id'] == $_GET['cateid']) { echo 'selected';}?>><?php echo $val['class_name'];?></option>
                            <?php } ?>
                        <?php } ?>
                    </select></td>
                <th>商品名称</th>
                <td class="w160"><input class="text" type="text" name="goods_name" value="<?php echo $_GET['goods_name'];?>"/></td>
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
            <th class="w90">成功/参与</th>
            <th class="w90">状态</th>
            <th class="w110">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if(!empty($output['list']) && is_array($output['list'])){?>
            <?php foreach($output['list'] as $key=>$group){?>
                <tr class="bd-line">
                    <td><?php echo $group['id'];?></td>
                    <td><div class="pic-thumb"><a href="../index.php?app=goods&gid=<?php echo $group['gid'];?>" target="_blank"><img src="<?php echo gthumb($group['pre_pic'], 'small');?>"/></a></div></td>
                    <td class="tl"><dl class="goods-name">
                            <dt><a target="_blank" href="../index.php?app=goods&gid=<?php echo $group['gid'];?>"><?php echo $group['goods_name'];?></a></dt>
                        </dl></td>
                    <td><?php echo $output['cate'][$group['pre_category']]['class_name'];?></td>
                    <td><?php echo date('Y-m-d H:i:s',$group['pre_start_time']);?></td>
                    <td><?php echo date('Y-m-d H:i:s',$group['pre_end_time']);?></td>
                    <td><?php echo $group['cheng'].'/'.$group['zong']?></td>
                    <td><?php echo $group['pre_status']?'正常':'已终止';?></td>
                    <td>
                        <?php if($group['look']){?>
                            <a href="<?php echo urlAddons('view')?>&id=<?php echo $group['pre_id'];?>">查看</a>
                        <?php }if($group['look_user']){?>
                             <a href="<?php echo urlAddons('team_list')?>&id=<?php echo $group['pre_id'];?>">查看团队</a>
                        <?php }if($group['edit']){?>
                            <a href="<?php echo urlAddons('add')?>&id=<?php echo $group['pre_id'];?>">编辑</a>
                        <?php }if($group['del']){?>
                            <a href="javascript:void(0);" onclick="ajax_get_confirm('确定要删除吗？', '<?php echo urlAddons('delete').'&id='.$group['pre_id'];?>');"><p>删除</p></a>
                        <?php }if($group['stop']){?>
                            <a href="javascript:void(0);" onclick="ajax_get_confirm('确定要停止吗？停止后，该活动无法再次打开', '<?php echo urlAddons('stoppresale').'&id='.$group['pre_id'];?>');"><p>终止</p></a>
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
