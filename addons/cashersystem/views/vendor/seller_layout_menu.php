<?php if(C('dian') && C('dian_isuse')){?>
<?php if ((C("sld_cashersystem") && C("cashersystem_isuse")) || (C('sld_ldjsystem') && C('ldj_isuse'))) { ?>
	<li class="commen_left_01_li">
		<span><a class="commen_left_01_a" href="<?php echo MALL_URL.'/symanage/'; ?>"><i class="iconfonts fa-users"></i>O2O</a></span>
	</li>
<?php } }?>