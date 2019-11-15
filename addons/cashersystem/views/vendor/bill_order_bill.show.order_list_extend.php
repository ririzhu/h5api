<?php if (C("sld_cashersystem") && C("cashersystem_isuse")) { ?>
		<select  style="height: 35px;" name="query_type" class="querySelect">  
		<option value="order" <?php if($_GET['query_type'] == 'order'){?>selected<?php }?>>订单列表</option>
		<option value="c_order" <?php if($_GET['query_type'] == 'c_order'){?>selected<?php }?>>线下订单列表</option>
		<option value="refund" <?php if($_GET['query_type'] == 'refund'){?>selected<?php }?>>退单列表</option>
		</select>
<?php } ?>