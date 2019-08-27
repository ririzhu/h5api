
// 检查 当前插件是否开启
function check_plugin_status()
{
	var return_flag = false;

	$.ajax({
		type:'post',
		url:ApiUrl+"/index.php?app=api&mod=addons_status&sld_addons=spreader",
		data:{},
		dataType:'json',
		async:false,
		success:function(result){
			return_flag = result;
		}
	 }); 
	return return_flag;
}

if (check_plugin_status()) {
	function get_main_share_id(){
		var spreader_id = '';

		var main_spreader_id = getcookie('ssys_main_share_id') || '';
		var main_spreader_id_time = '';
		if (main_spreader_id) {
			var spreader_id_cookie_time_name = 'ssys_main_share_id_time';
			main_spreader_id_time = getcookie(spreader_id_cookie_time_name);
		}
		if (main_spreader_id && main_spreader_id_time) {
			// 验证标示是否过期
			var exp = new Date();
			var now_time = exp.getTime();
			if (now_time <= main_spreader_id_time || main_spreader_id_time == 0) {
				spreader_id = main_spreader_id;
			}
		}

		return spreader_id;
	}
}