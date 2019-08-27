<?php if (C("sld_spreader") && C("spreader_isuse")) { ?>
		<dl>
			<dt><?php echo $lang['store_goods_index_goods_spreader']; ?>:</dt>
			<dd>
				<ul class="bbc_ms-form-radio-list">
					<li>
						<label>
							<input name="is_spreader_goods" value="1" <?php if (!empty($output['goods']) && $output['goods']['is_spreader_goods'] == 1) { ?>checked="checked" <?php } ?> type="radio" />
							<?php echo $lang['是'];?>
						</label>
					</li>
					<li>
						<label>
							<input name="is_spreader_goods" value="0" <?php if ( (!empty($output['goods']) && $output['goods']['is_spreader_goods'] == 0) || (empty($output['goods']))) { ?>checked="checked" <?php } ?> type="radio"/>
							<?php echo $lang['否'];?>
						</label>
					</li>
				</ul>
				<p class="hint"><?php echo $lang['store_goods_index_spreader_tip'];?></p>
			</dd>
		</dl>

		<dl class="spreader_price">
		    <dt bbc_type="no_spec"><?php echo $lang['store_goods_index_goods_spreader_price']; ?>:</dt>
		    <dd bbc_type="no_spec">
		        <input name="spreader_goods_yj_amount" value="<?php echo sldPriceFormat($output['goods']['spreader_goods_yj_amount']); ?>" type="text"  class="text w60 spreader_price_number" /><em class="add-on"><i class="iconfontfa fa-jpy"></i></em> <span></span>
		        <p class="hint"><?php echo $lang['store_goods_index_goods_spreader_price_tip'];?></p>
		    </dd>
		</dl>
		
		<script type="text/javascript" src="<?php echo MALL_URL; ?>/addons/spreader/data/js/goods_online_extend.js"></script>
<?php } ?>