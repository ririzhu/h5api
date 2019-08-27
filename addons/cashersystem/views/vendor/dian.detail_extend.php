<?php if (C("sld_cashersystem") && C("cashersystem_isuse")) { ?>
            <dl>
                <dt>设置店长:</dt>
                <dd class="select_admin">
                    <select name="member_id">
                        <option value="0"><?php echo $lang['请选择'];?></option>
                        <?php foreach ($output['store_info']['leader_users'] as $key => $value): ?>
                            <option <?php if($output['store_info']['member_id'] == $value['id'] ){ ?>selected<?php } ?> value="<?php echo $value['id']; ?>"><?php echo $value['casher_name']; ?></option>
                        <?php endforeach ?>
                    </select>
                </dd>
            </dl>

<!--            <dl>-->
<!--                <dt>设置收银员-->:<!--</dt>-->
<!--                <dd class="casherlabel_dd">-->
<!--                    <select name="casherlabel" class="casherlabel">-->
<!--                        <option value="0">--><?php //echo $lang['请选择'];?><!--</option>-->
<!--                        --><?php //foreach ($output['store_info']['all_casher_users'] as $key => $value): ?>
<!--                            <option value="--><?php //echo $value['id']; ?><!--">--><?php //echo $value['casher_name']; ?><!--</option>-->
<!--                        --><?php //endforeach ?>
<!--                    </select>-->
<!---->
<!--                    --><?php //foreach ($output['store_info']['casher_users'] as $key=>$value){?>
<!---->
<!--                        <a class="ss-item region-code bbc_ms-btn-mini wq"  href="javascript:void(0);" data-region_code="--><?php //echo $value['id']?><!--" data-region_name="--><?php //echo $value['casher_name'];?><!--"><i title="移除" class="iconfontfa fa-close add_iconcolor"></i>--><?php //echo $value['casher_name'];?><!--</a>-->
<!---->
<!--                    --><?php //}?>
<!--                    <p class="hint"></p>-->
<!---->
<!--                    <input type="hidden" name="casher_ids" value="--><?php //echo $output['store_info']['casher_users_val']; ?><!--" id="casher_ids">-->
<!---->
<!--                </dd>-->
<!--            </dl>-->

            <script type="text/javascript" src="<?php echo MALL_URL; ?>/addons/cashersystem/data/js/dian.detail_extend.js"></script>
<?php } ?>