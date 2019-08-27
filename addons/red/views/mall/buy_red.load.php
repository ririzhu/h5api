<?php defined('DYMall') or exit('Access Invalid!');?>
<ul>
  <?php foreach($output['inv_list'] as $k=>$val){ ?>
  <li class="inv_item <?php echo $k == 0 ? 'slds-selected-item' : null; ?>">
    <input content="<?php echo $val['content'];?>" id="inv_<?php echo $val['inv_id']; ?>" bbc_type="inv" type="radio" name="inv" value="<?php echo $val['inv_id']; ?>" <?php echo $k == 0 ? 'checked' : null; ?>/>
    <label for="inv_<?php echo $val['inv_id']; ?>">&nbsp;&nbsp;<?php echo $val['content']; ?></label>
    &emsp;&emsp;&emsp;<a href="javascript:void(0);" onclick="delInv(<?php echo $val['inv_id']?>);" class="del">[ <?php echo $lang['删除'];?> ]</a> </li>
  <?php } ?>
  <li class="inv_item">
    <?php if (count($output['inv_list']) < 10) {?>
    <input value="0" bbc_type="inv" id="addinvoice" type="radio" name="inv">
    <label for="addinvoice">&nbsp;&nbsp;<?php w('使用新的发票信息');?></label>
    <?php } else {?>
        <?php w('发票数超限');?>
    <?php }?>
  </li>
  <div id="add_inv_box" style="display:none">
    <form method="POST" id="inv_form" action="index.php">
      <input type="hidden" value="buy" name="app">
      <input type="hidden" value="addinvoice" name="mod">
      <input type="hidden" name="form_submit" value="ok"/>
      <div class="slds-form-default">
        <dl>
          <dt><?php w('发票类型');?>:</dt>
          <dd>
            <label>
              <input type="radio" checked name="invoice_type" value="1">
              <?php w('普通发票');?></label>
            &emsp;&emsp;
            <?php if (!$output['vat_deny']) {?>
            <label>
              <input type="radio" name="invoice_type" value="2">
              <?php w('增值税发票');?></label>
            <?php }?>
          </dd>
        </dl>
      </div>
      <div id="invoice_panel" class="slds-form-default">
        <dl>
          <dt><?php w('发票抬头');?>:</dt>
          <dd>
            <select name="inv_title_select">
              <option value="person"><?php w('个人');?></option>
              <option value="company"><?php w('单位');?></option>
            </select>
            <input class="text w200" style="display:none" name="inv_title" id="inv_title" placeholder="<?php w('单位名称');?>" value="">
              <input class="text w200" style="display:none" name="inv_title_shuihao" id="inv_title_shuihao" placeholder="<?php w('税号');?>" value="">
          </dd>
        </dl>
        <dl>
          <dt><?php w('发票内容');?>:</dt>
          <dd>
            <select id="inv_content" name="inv_content">
                <?php foreach ($output['invoice_content_list'] as $v){?>
                    <option selected value="<?php echo $v;?>"><?php echo $v;?></option>
                <?php }?>
            </select>
          </dd>
        </dl>
      </div>
      <div id="vat_invoice_panel" class="slds-form-default" style="display:none">
        <dl>
          <dt><i class="required">*</i><?php w('单位名称');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_company" value="">
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('纳税人识别号');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_code" value="">
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('注册地址');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_reg_addr" value="">
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('注册电话');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_reg_phone" value="">
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('开户银行');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_reg_bname" value="">
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('银行帐户');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_reg_baccount" value="">
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('发票内容');?>:</dt>
          <dd><?php w('明细');?></dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('收票人姓名');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_rec_name" value="">
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('收票人手机号');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_rec_mobphone" value="">
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('收票人省份');?>:</dt>
          <dd id="region">
            <select>
            </select>
            <input type="hidden" value="" name="city_id" id="city_id">
            <input type="hidden" name="area_id" id="area_id" class="area_ids"/>
            <input type="hidden" name="area_info" id="area_info" class="area_names"/>
          </dd>
        </dl>
        <dl>
          <dt><i class="required">*</i><?php w('送票地址');?>:</dt>
          <dd>
            <input type="text" class="text w200" maxlength="50" name="inv_goto_addr" value="">
          </dd>
        </dl>
      </div>
    </form>
  </div>
</ul>
<div class="hr16"> <a id="hide_invoice_list" class="slds-btn slds-btn-red" href="javascript:void(0);"><?php echo $lang['保存发票信息'];?></a> <a id="cancel_invoice" class="slds-btn ml10" href="javascript:void(0);"><?php w('不需要发票');?></a></div>
<script>
var postResult = false;
function delInv(id){
    $('#invoice_list').load(MALLURL+'/index.php?app=buy&mod=loadinvoice&vat_hash<?php echo $_GET['vat_hash'];?>&del_id='+id);
}
$(function(){
    $.ajaxSetup({async : false});
    //不需要发票
    $('#cancel_invoice').on('click',function(){
        $('#invoice_id').val('');
        hideInvList("<?php w('不需要发票');?>");
    });
    //使用新的发票信息
    $('input[bbc_type="inv"]').on('click',function(){
       	regionInit("region");
        if ($(this).val() == '0') {
            $('.inv_item').removeClass('slds-selected-item');
            $('#add_inv_box').show();
        } else {
            $('.inv_item').removeClass('slds-selected-item');
            $(this).parent().addClass('slds-selected-item');
            $('#add_inv_box').hide();
        }
    });
    <?php if (empty($output['inv_list'])) {?>
    //$('input[bbc_type="inv"]').click();
    <?php } ?>
    //保存发票信息
    $('#hide_invoice_list').on('click',function(){
        var content = '';
        if ($('input[name="inv"]:checked').size() == 0){
        	$('#cancel_invoice').click();
        	return false;
        }
        if ($('input[name="inv"]:checked').val() != '0'){
            //如果选择已保存过的发票信息
            content = $('input[name="inv"]:checked').attr('content');
            $('#invoice_id').val($('input[name="inv"]:checked').val());
            hideInvList(content);
            return false;
        }
        //如果是新增发票信息
        if ($('input[name="invoice_type"]:checked').val() == 1) {
            //如果选择普通发票
            if ($('select[name="inv_title_select"]').val() == 'person') {
                content = "<?php w('普通发票');?>"+" <?php w('个人');?> " + $('select[name="inv_content"]').val();
            } else if ($.trim($('#inv_title').val()) == '' || $.trim($('#inv_title').val()) == "<?php w('单位名称');?>") {
				showDialog("<?php w('请填写');w('单位名称');?>", 'error', '', '', '', '', '', '', '', '', 2);
                return false;
            }else if($.trim($('#inv_title_shuihao').val()) == '' || $.trim($('#inv_title_shuihao').val()) == "<?php w('得改');?>"){
				showDialog("<?php w('请填写');w('税号');?>", 'error', '', '', '', '', '', '', '', '', 2);
				return false;
            }else{
                content = "<?php w('普通发票');?> " + $.trim($('#inv_title').val())+ ' ' + $('#inv_content').val()+ ' ' + $('#inv_title_shuihao').val();
            }
        }else{
            content = "<?php w('增值税发票');?> " + $.trim($('input[name="inv_company"]').val()) + ' ' + $.trim($('input[name="inv_code"]').val()) + ' ' + $.trim($('input[name="inv_reg_addr"]').val());
            //验证增值税发票表单
            if (!$('#inv_form').valid()){
                return false;
            }
        }
        var datas=$('#inv_form').serialize();
        
        $.post('index.php',datas,function(data){
            if (data.state=='success'){
                $('#invoice_id').val(data.id);
                postResult = true;
            }else{
                showDialog(data.msg, 'error','','','','','','','','',2);
                postResult = false;
            }
        },'json');
        if (postResult){
            hideInvList(content);
        }
    });
	$('input[name="invoice_type"]').on('click',function(){
		if ($(this).val() == 1) {
			$('#invoice_panel').show();
			$('#vat_invoice_panel').hide();
		} else {
			$('#invoice_panel').hide();
			$('#vat_invoice_panel').show();
		}
	});
	$('select[name="inv_title_select"]').on('change',function(){
	    if ($(this).val()=='company') {
	        $('#inv_title').show();
	        $('#inv_title_shuihao').show();
	    } else {
	        $('#inv_title').hide();
            $('#inv_title_shuihao').hide();

        }
	});

    $('#inv_form').validate({
        rules : {
            inv_company : {
                required : true
            },
            inv_code : {
                required : true
            },
            inv_reg_addr : {
                required : true
            },
			inv_reg_phone : {
				required : true
			},
            inv_reg_bname : {
                required : true
            },
            inv_reg_baccount : {
                required : true
            },
            inv_rec_name : {
                required : true
            },
            inv_rec_mobphone : {
                required : true
            },            
            area_id : {
                required : true,
                min   : 1,
                checkarea:true
            },
            inv_goto_addr : {
                required : true
            }
        },
        messages : {
			inv_company : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('单位名称');w('不能为空');?>"
			},
			inv_code : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('纳税人识别号');w('不能为空');?>"
			},
			inv_reg_addr : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('注册地址');w('不能为空');?>"
			},
			inv_reg_phone : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('注册电话');w('不能为空');?>"
			},
			inv_reg_bname : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('开户银行');w('不能为空');?>"
			},
			inv_reg_baccount : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('银行帐户');w('不能为空');?>"
			},
			inv_rec_name : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('收票人姓名');w('不能为空');?>"
			},
			inv_rec_mobphone : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('收票人手机号');w('不能为空');?>"
			},
			area_id : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('请选择');w('地区');?>",
				min : '<i class="icon-exclamation-sign"></i>'+"<?php w('请选择');w('地区');?>",
				checkarea:'<i class="icon-exclamation-sign"></i>'+"<?php w('请选择');w('地区');?>",
			},
			inv_goto_addr : {
				required : '<i class="icon-exclamation-sign"></i>'+"<?php w('送票地址');w('不能为空');?>"
			}
        }
    });
});
</script>