<?php if (C("sld_spreader") && C("spreader_isuse")) { ?>
              <th class="w90">佣金金额
                  <div class="batch"><i class="iconfontfa fa-edit" title="批量操作"></i>
                      <div class="batch-input" style="display:none;">
                          <h6>批量设置佣金金额：</h6>
                          <a href="javascript:void(0)" class="close">X</a>
                          <input name="" type="text" class="text stock" />
                          <a href="javascript:void(0)" class="sldbtn-lala" data-type="alarm">设置</a><span class="arrow"></span></div>
                  </div></th>

<script type="text/javascript">
	function creat_spec_extend(spec_bunch,disable_html){
		if (disable_html == '') {
			// 检查是否开启 参与分销推广
			var spreader_isuse = $("input[name='is_spreader_goods']:checked").val();
			if (spreader_isuse == 0) {
				disable_html = ' readonly="readonly" style="background: none rgb(231, 231, 231);"';
			}
		}
		var spec_extend = '<td><input class=\"text texttwo price\" type=\"text\" name=\"spec['+spec_bunch+'][yj_amount]\" data_type=\"yj_amount\" bbc_type=\"'+spec_bunch+'|yj_amount\" value=\"0.00\"'+disable_html+' /><em class=\"add-on\"><i class=\"iconfontfa fa-jpy\"></i></em></td>';
		return spec_extend;
	}
	
	// 计算佣金价格
	function computeYjPrice(){
	    // 计算最低价格
	    var _price = 0;var _price_sign = false;
	    $('input[data_type="yj_amount"]').each(function(){
	        if($(this).val() != '' && $(this)){
	            if(!_price_sign){
	                _price = parseFloat($(this).val());
	                _price_sign = true;
	            }else{
	                _price = (parseFloat($(this).val())  > _price) ? _price : parseFloat($(this).val());
	            }
	        }
	    });
	    $('input[name="spreader_goods_yj_amount"]').val(number_format(_price, 2));
		$('input[name="spreader_goods_yj_amount"]').attr("readonly","readonly");
		$('input[name="spreader_goods_yj_amount"]').attr("style","background:#E7E7E7 none;");
	    
	}
</script>

<?php } ?>