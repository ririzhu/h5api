<?php defined('DYMall') or exit('Access Invalid!'); return array ( 'cash_apply_notice' => array ( 'mmt_code' => 'cash_apply_notice', 'mmt_name' => '提现申请通知', 'mmt_message_switch' => '1', 'mmt_message_content' => '您提交了一份提现申请，申请单号为{$order_sn}。<a href="{$order_url}" target="_blank">点击查看详情</a>', 'mmt_short_switch' => '0', 'mmt_short_number' => '10', 'mmt_short_content' => '【{$site_name}】您提交了一份提现申请，申请单号为{$order_sn}。', 'mmt_mail_switch' => '0', 'mmt_mail_subject' => '{$site_name}提醒：您提交了一份提现申请，申请单号为{$order_sn}。', 'mmt_mail_content' => '<p>
 {$site_name}提醒：
</p>
<p>
 您提交了一份提现申请，申请单号为{$order_sn}。
</p>
<p>
  <a href="{$order_url}" target="_blank">点击查看详情</a>
</p>
<p>
  <br />
</p>
<p>
   <br />
</p>
<p>
   <br />
</p>
<p style="text-align:right;">
 {$site_name}
</p>
<p style="text-align:right;">
   {$mail_send_time}
</p>
<br />', 'mmt_weixin_number' => NULL, 'mmt_weixin_switch' => NULL, 'mmt_weixin_content' => NULL, 'mmt_weixin_id' => NULL, ), 'consult_goods_reply' => array ( 'mmt_code' => 'consult_goods_reply', 'mmt_name' => '商品咨询回复提醒', 'mmt_message_switch' => '1', 'mmt_message_content' => '您关于商品 “{$goods_name}”的咨询，商家已经回复。<a href="{$consult_url}" target="_blank">点击查看回复</a>', 'mmt_short_switch' => '0', 'mmt_short_number' => '10', 'mmt_short_content' => '【{$site_name}】您关于商品 “{$goods_name}” 的咨询，商家已经回复。', 'mmt_mail_switch' => '0', 'mmt_mail_subject' => '{$site_name}提醒：您关于商品 “{$goods_name}”的咨询，商家已经回复。', 'mmt_mail_content' => '<p>
	{$site_name}提醒：
</p>
<p>
	您关注的商品“{$goods_name}” 已经到货。
</p>
<p>
	<a href="{$consult_url}" target="_blank">点击查看回复</a> 
</p>
<p>
	<br />
</p>
<p>
	<br />
</p>
<p>
	<br />
</p>
<p style="text-align:right;">
	{$site_name}
</p>
<p style="text-align:right;">
	{$mail_send_time}
</p>
<br />
<div class="firebugResetStyles firebugBlockBackgroundColor">
	<div style="background-color:transparent ! important;" class="firebugResetStyles">
	</div>
</div>', 'mmt_weixin_number' => NULL, 'mmt_weixin_switch' => NULL, 'mmt_weixin_content' => NULL, 'mmt_weixin_id' => NULL, ), 'order_deliver_success' => array ( 'mmt_code' => 'order_deliver_success', 'mmt_name' => '商品出库提醒', 'mmt_message_switch' => '1', 'mmt_message_content' => '您的订单已经出库。<a href="{$order_url}" target="_blank">点击查看订单</a>', 'mmt_short_switch' => '0', 'mmt_short_number' => '10', 'mmt_short_content' => '【{$site_name}】您的订单已经出库。订单编号 {$order_sn}。', 'mmt_mail_switch' => '0', 'mmt_mail_subject' => '{$site_name}提醒：您的订单已经出库。订单编号 {$order_sn}。', 'mmt_mail_content' => '<p>
    {$site_name}提醒：
</p>
<p>
  您的订单已经出库。订单编号 {$order_sn}。<br />
<a href="{$order_url}" target="_blank">点击查看订单</a>
</p>
<p>
    <br />
</p>
<p>
   <br />
</p>
<p>
   <br />
</p>
<p style="text-align:right;">
 {$site_name}
</p>
<p style="text-align:right;">
   {$mail_send_time}
</p>
<br />', 'mmt_weixin_number' => NULL, 'mmt_weixin_switch' => NULL, 'mmt_weixin_content' => NULL, 'mmt_weixin_id' => NULL, ), 'order_payment_success' => array ( 'mmt_code' => 'order_payment_success', 'mmt_name' => '付款成功提醒', 'mmt_message_switch' => '1', 'mmt_message_content' => '关于订单：{$order_sn}的款项已经收到，请留意出库通知。<a href="{$order_url}" target="_blank">点击查看订单详情</a>', 'mmt_short_switch' => '0', 'mmt_short_number' => '10', 'mmt_short_content' => '【{$site_name}】{$order_sn}的款项已经收到，请留意出库通知。', 'mmt_mail_switch' => '0', 'mmt_mail_subject' => '{$site_name}提醒：{$order_sn}的款项已经收到，请留意出库通知。', 'mmt_mail_content' => '<p>
 {$site_name}提醒：
</p>
<p>
  {$order_sn}的款项已经收到，请留意出库通知。
</p>
<p>
  <a href="{$order_url}" target="_blank">点击查看订单详情</a>
</p>
<p>
  <br />
</p>
<p>
   <br />
</p>
<p>
   <br />
</p>
<p style="text-align:right;">
 {$site_name}
</p>
<p style="text-align:right;">
   {$mail_send_time}
</p>
<br />', 'mmt_weixin_number' => NULL, 'mmt_weixin_switch' => NULL, 'mmt_weixin_content' => NULL, 'mmt_weixin_id' => NULL, ), 'points_change_notice' => array ( 'mmt_code' => 'points_change_notice', 'mmt_name' => '积分变动提醒', 'mmt_message_switch' => '1', 'mmt_message_content' => '你的账户于 {$time} 账户积分有变化，描述：{$desc}，积分变化 ：{$points_amount}。&lt;a href=&quot;{$points_url}&quot; target=&quot;_blank&quot;&gt;点击查看积分&lt;/a&gt;', 'mmt_short_switch' => '0', 'mmt_short_number' => '10', 'mmt_short_content' => '【{$site_name}】你的账户于 {$time} 账户积分有变化，描述：{$desc}，积分变化： {$points_amount}。', 'mmt_mail_switch' => '0', 'mmt_mail_subject' => '{$site_name}提醒：你的账户于 {$time} 账户积分有变化，描述：{$desc}，积分变化： {$points_amount}。', 'mmt_mail_content' => '<p>
    {$site_name}提醒：
</p>
<p>
  你的账户于 {$time} 账户积分有变化，描述：{$desc}，积分变化： {$points_amount}。
</p>
<p>
  <a href="{$points_url}" target="_blank">点击查看积分</a> 
</p>
<p>
  <br />
</p>
<p>
   <br />
</p>
<p>
   <br />
</p>
<p style="text-align:right;">
 {$site_name}
</p>
<p style="text-align:right;">
   {$mail_send_time}
</p>', 'mmt_weixin_number' => NULL, 'mmt_weixin_switch' => NULL, 'mmt_weixin_content' => NULL, 'mmt_weixin_id' => NULL, ), 'predeposit_change' => array ( 'mmt_code' => 'predeposit_change', 'mmt_name' => '余额变动提醒', 'mmt_message_switch' => '1', 'mmt_message_content' => '你的账户于 {$time} 账户资金有变化，描述：{$desc}，可用金额变化 ：{$av_amount}元，冻结金额变化：{$freeze_amount}元。<a href="{$pd_url}" target="_blank">点击查看余额</a>', 'mmt_short_switch' => '0', 'mmt_short_number' => '10', 'mmt_short_content' => '【{$site_name}】你的账户于 {$time} 账户资金有变化，描述：{$desc}，可用金额变化： {$av_amount}元，冻结金额变化：{$freeze_amount}元。', 'mmt_mail_switch' => '0', 'mmt_mail_subject' => '{$site_name}提醒：你的账户于 {$time} 账户资金有变化，描述：{$desc}，可用金额变化： {$av_amount}元，冻结金额变化：{$freeze_amount}元。', 'mmt_mail_content' => '<p>
    {$site_name}提醒：
</p>
<p>
  你的账户于 {$time} 账户资金有变化，描述：{$desc}，可用金额变化：{$av_amount}元，冻结金额变化：{$freeze_amount}元。
</p>
<p>
  <a href="{$pd_url}" target="_blank">点击查看余额</a> 
</p>
<p>
  <br />
</p>
<p>
   <br />
</p>
<p>
   <br />
</p>
<p style="text-align:right;">
 {$site_name}
</p>
<p style="text-align:right;">
   {$mail_send_time}
</p>', 'mmt_weixin_number' => NULL, 'mmt_weixin_switch' => NULL, 'mmt_weixin_content' => NULL, 'mmt_weixin_id' => NULL, ), 'refund_return_notice' => array ( 'mmt_code' => 'refund_return_notice', 'mmt_name' => '退款退货提醒', 'mmt_message_switch' => '1', 'mmt_message_content' => '您的退款退货单有了变化。<a href="{$refund_url}" >点击查看</a>', 'mmt_short_switch' => '0', 'mmt_short_number' => '10', 'mmt_short_content' => '【{$site_name}】您的退款退货单有了变化。退款退货单编号：{$refund_sn}。', 'mmt_mail_switch' => '0', 'mmt_mail_subject' => '{$site_name}提醒：您的退款退货单有了变化。', 'mmt_mail_content' => '<p>
  {$site_name}提醒：
</p>
<p>
  您的退款退货单有了变化。退款退货单编号：{$refund_sn}。
</p>
<p>
    &lt;a href="{$refund_url}" target="_blank"&gt;点击查看&lt;/a&gt;
</p>
<p>
 <br />
</p>
<p>
   <br />
</p>
<p>
   <br />
</p>
<p style="text-align:right;">
 {$site_name}
</p>
<p style="text-align:right;">
   {$mail_send_time}
</p>
<br />', 'mmt_weixin_number' => NULL, 'mmt_weixin_switch' => NULL, 'mmt_weixin_content' => NULL, 'mmt_weixin_id' => NULL, ), ) ?>