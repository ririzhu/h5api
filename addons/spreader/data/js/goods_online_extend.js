
// 分销商品 选择参与 分销推广时 才会展示分销价格

function spreader_change(){

	var spreader_isuse = $("input[name='is_spreader_goods']:checked").val();

	if (spreader_isuse > 0) {
		// 验证当前商品是否存在多规格
		var more_spec_yj_amount_input = $("input[data_type='yj_amount']");

		if (!more_spec_yj_amount_input.size()) {
			$("input[name='spreader_goods_yj_amount']").removeAttr("readonly");
			$("input[name='spreader_goods_yj_amount']").removeAttr("style");
		}

		// 多规格 的佣金开启
		$("input[data_type='yj_amount']").removeAttr("readonly");
		$("input[data_type='yj_amount']").removeAttr("style");
	}else{
		$("input[name='spreader_goods_yj_amount']").val('0.00');
		$("input[name='spreader_goods_yj_amount']").attr("readonly","readonly");
		$("input[name='spreader_goods_yj_amount']").attr("style","background:#E7E7E7 none;");

		// 多规格 的佣金屏蔽
		$("input[data_type='yj_amount']").val('0.00');
		$("input[data_type='yj_amount']").attr("readonly","readonly");
		$("input[data_type='yj_amount']").attr("style","background:#E7E7E7 none;");

	}
}
function checkYjNumberPrice(){
	var switch_flag = $("input[name='is_spreader_goods']:checked").val();
	var spreader_price = $("input[name='spreader_goods_yj_amount']").val()*1;

	if (switch_flag == 1) {
		// 验证当前商品是否存在多规格
		var more_spec_yj_amount_input = $("input[data_type='yj_amount']");
		
		if (more_spec_yj_amount_input.size()) {
			var return_check_flag = true;
			more_spec_yj_amount_input.each(function(k,item){
				if (return_check_flag) {
					var item_spreader_price = $(item).val()*1;
					var item_goods_price = $(item).parents('tr').find("input[data_type='price']").val()*1;
					if (item_spreader_price <= 0 || item_spreader_price >= item_goods_price) {
						return_check_flag = false;
					}else{
						return_check_flag = true;
					}
				}
			});
			return return_check_flag;
		}else{
			var goods_price = $("input[name='g_price']").val() * 1;
			
			if (switch_flag == 1 && spreader_price > 0) {
				if (spreader_price >= goods_price) {
					return false;
				}else{
					return true;
				}
			}else{
				return false;
			}
		}

	}else{
		return true;
	}

}

function check_form_submit_before(){
	var check_flag = false;
	
	if (!checkYjNumberPrice() && $("input[name='is_spreader_goods']:checked").val() == 1) {
		var more_spec_yj_amount_input = $("input[data_type='yj_amount']");
		if (more_spec_yj_amount_input.size()) {
			var spreader_price = $('.spreader_price');
	        // 报错
	        var errorHtml = '<label for="g_name" class="error"><i class="icon-exclamation-sign"></i>请合理规格中的分佣金额，不能超过规格商品价格,不能小于0.00。</label>';
	        $(".spreader_price_number").eq(0).focus();
	        spreader_price.find('span').html(errorHtml);
		}else{
			var spreader_price = $('.spreader_price');
	        // 报错
	        var errorHtml = '<label for="g_name" class="error"><i class="icon-exclamation-sign"></i>请填写合理的分佣金额，不能超过商品价格,不能小于0.00。</label>';
	        $(".spreader_price_number").eq(0).focus();
	        spreader_price.find('span').html(errorHtml);
		}
	}else{
		check_flag = true;
	}

	return check_flag;
}

spreader_change();

$("input[name='is_spreader_goods']").change(function(e){
	spreader_change();
});


