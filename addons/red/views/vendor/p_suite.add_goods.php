<div class="div-goods-select" style="width: 780px;">
    <table class="search-form">
        <tbody>
        <tr>
            <!--        <th>&nbsp;</th>-->
            <th><?php echo $lang['bundling_goods_store_class'];?></th>
            <td class="w150"><select name="stc_id" class="w150">
                    <option value="0"><?php echo $lang['请选择'];?></option>
                    <?php if (!empty($output['innercategory'])){?>
                        <?php foreach ($output['innercategory'] as $val) { ?>
                            <option value="<?php echo $val['stc_id']; ?>" <?php if($val['stc_id'] == $_GET['stc_id']) echo 'selected="selected"';?>><?php echo $val['stc_name']; ?></option>
                            <?php if (is_array($val['child']) && count($val['child'])>0){?>
                                <?php foreach ($val['child'] as $child_val){?>
                                    <option value="<?php echo $child_val['stc_id']; ?>" <?php if($child_val['stc_id'] == $_GET['stc_id']) echo 'selected="selected"';?>>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $child_val['stc_name']; ?></option>
                                <?php }}}}?>
                </select></td>
            <th><?php echo $lang['bundling_goods_name'];?></th>
            <td class="w160"><input type="text" name="b_search_keyword" class="text" value="<?php echo $_GET['keyword'];?>" /></td>
            <td class="tc w70"><a href="<?php echo BASE_VENDOR_URL;?>/index.php?app=action_red&mod=bundling_add_goods&sld_addons=red" bbctype="search_a" class="bbc_ms-btn"><i class="iconfontfa fa-search"></i><?php echo $lang['搜索'];?></a></td>
            <td class="w150"></td>
        </tr>
        </tbody>
    </table>
    <div class="search-result" style="width:739px;">
        <?php if(!empty($output['goods_list']) && is_array($output['goods_list'])){ ?>
            <ul class="goods-list" bbctype="bundling_goods_add_tbody" style=" width:760px;">
                <?php foreach ($output['goods_list'] as $val){?>
                    <li bbctype="<?php echo $val['gid'];?>">
                        <div class="goods-thumb"><img src="<?php echo cthumb($val['goods_image'], 240, $_SESSION['vid']);?>" bbctype="<?php echo $val['goods_image'];?>" /></div>
                        <dl class="goods-info">
                            <dt><a href="#" target="_blank" title="<?php echo $lang['bundling_goods_name'].'/'.$lang['bundling_goods_code'];?><?php echo $val['goods_name'];?><?php  if($val['goods_serial'] != ''){ echo $val['goods_serial'];}?>"><?php echo $val['goods_name'];?></a></dt>
                            <dd><?php echo $lang['bundling_goods_price'];?>¥<?php echo $val['goods_price'];?></dd>
                            <dd><?php echo $lang['bundling_goods_storage'];?><?php echo $val['goods_storage'].$lang['件'];?></dd>
                        </dl>
                        <div class="data-param" data-param="{gid:<?php echo $val['gid'];?>,image:'<?php echo $val['goods_image'];?>',src:'<?php echo cthumb($val['goods_image'], 60, $_SESSION['vid']);?>',gname:'<?php echo $val['goods_name'];?>',gprice:'<?php echo $val['goods_price'];?>',gstorang:'<?php echo $val['goods_storage'];?>'}"><a href="JavaScript:void(0);" class="bbc_ms-btn-mini bbc_ms-btn-green" onclick="bundling_goods_add($(this))"><i class="iconfontfa fa-plus add_rule_iconcolor"></i><?php echo '添加商品';?></a></div>
                    </li>
                <?php }?>
            </ul>
        <?php }else{?>
            <div class="norecord">
                <div class="warning-option"><i class="iconfontfa fa-exclamation-triangle"></i><span><?php echo $lang['无数据'];?></span></div>
            </div>
        <?php }?>
        <?php if(!empty($output['goods_list']) && is_array($output['goods_list'])){?>
            <div class="pagination"><?php echo $output['show_page']; ?></div>
        <?php }?>
    </div>
</div>
<script>
    $(function(){
        /* ajax添加商品  */
        $('.demo').unbind().ajaxContent({
            event:'click', //mouseover
            loaderType:"img",
            loadingMsg:VENDOR_TEMPLATES_URL+"/images/loading.gif",
            target:'#bundling_add_goods_ajaxContent'
        });

        $('a[bbctype="search_a"]').click(function(){

            $(this).attr('href', $(this).attr('href')+'&stc_id='+$('select[name="stc_id"]').val()+ '&' +$.param({'keyword':$('input[name="b_search_keyword"]').val()}));
            $('a[bbctype="search_a"]').ajaxContent({
                event:'dblclick', //mouseover
                loaderType:'img',
                loadingMsg:'<?php echo VENDOR_TEMPLATES_URL;?>/images/loading.gif',
                target:'#bundling_add_goods_ajaxContent'
            });
            $(this).dblclick();
            return false;
        });

        // 验证商品是否已经添加。
        var _bundlingtr = $('#redinfo_ids').val();
        //判断商品是否已经选择
        var _bundlingtrs =new Array();
        _bundlingtrs = _bundlingtr.split(',');
        T = $('ul[bbctype="bundling_goods_add_tbody"] li');
        if(typeof(T) != 'undefined'){
            T.each(function(){
                var _data = $(this).find('.data-param').attr('data-param');
                _data = eval('('+_data+')');
                for (i in _bundlingtrs){
                    var v = _bundlingtrs[i];
                    if(parseFloat(_data.gid) == parseFloat(v)){
                        $(this).children(':last').html('<a href="JavaScript:void(0);" onclick="bundling_operate_delete($(\'#bundling_tr_'+$(this).attr('bbctype')+'\'), '+$(this).attr('bbctype')+')" class="bbc_ms-btn-mini bbc_ms-btn-orange"><i class="iconfontfa fa-ban add_rule_iconcolor"></i>移除商品</a>');
                    }
                }
            });
        }
    });

    /* 添加商品 */
    function bundling_goods_add(o){
        // 验证商品是否已经添加。
        var _bundlingtr = $('#redinfo_ids').val();
        if(typeof(_bundlingtr) != 'undefined'){
//            if(_bundlingtr.length == <?php //echo C('promotion_bundling_goods_sum');?>//){
//                alert('<?php //printf($lang['bundling_goods_add_enough_prompt'], C('promotion_bundling_goods_sum'));?>//');
//                return false;
//            }
        }
        //判断商品是否已经选择
        var _bundlingtrs =new Array();
        _bundlingtrs = _bundlingtr.split(',');
        //判断商品是否已经选择
        eval('var _data = ' + o.parent().attr('data-param'));
        for (i in _bundlingtrs){
            var v = _bundlingtrs[i];
            if(v == ''){
                _bundlingtrs.splice(i,1);
            }
            if(parseFloat(_data.gid) == parseFloat(v)){
                return false;
            }
        }
        _bundlingtrs.push(_data.gid);
        _bundlingtr = _bundlingtrs.join(',');
        $('#redinfo_ids').val(_bundlingtr);

        $('li[bbctype="' + _data.gid + '"]').children(':last').html('<a href="JavaScript:void(0);" class="bbc_ms-btn-mini bbc_ms-btn-orange" onclick="bundling_operate_delete($(\'#bundling_tr_' + _data.gid + '\'), ' + _data.gid + ')"><i class="iconfontfa fa-ban add_rule_iconcolor"></i>移除商品</a>');
    }
    /* 删除商品 */
    function bundling_operate_delete(o, id){
        // 验证商品是否已经添加。
        var _bundlingtr = $('#redinfo_ids').val();
        //判断商品是否已经选择
        var _bundlingtrs = new Array();
        _bundlingtrs = _bundlingtr.split(',');
        //判断商品是否已经选择
        for (i in _bundlingtrs){
            var v = _bundlingtrs[i];
            if(parseFloat(id) == parseFloat(v)){
                _bundlingtrs.splice(i,1);
            }
            if(v == ''){
                _bundlingtrs.splice(i,1);
            }
        }
        _bundlingtr = _bundlingtrs.join(',');
        $('#redinfo_ids').val(_bundlingtr);

        $('li[bbctype="'+id+'"]').children(':last').html('<a href="JavaScript:void(0);" onclick="bundling_goods_add($(this))" class="bbc_ms-btn-mini bbc_ms-btn-green"><i class="iconfontfa fa-plus"></i>添加商品</a>');
    }

</script>