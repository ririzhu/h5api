
var ssys_expire_day = 0;
var one_day_time = (24*60*60*1000);
// 检查 当前插件是否开启
function check_plugin_status()
{
	var return_flag = false;


	return return_flag;
}

// 获取 推手 相关配置
function get_spreader_setting()
{
	$.ajax({
		type:'post',
		url:ApiUrl+"/index.php?app=api&mod=get_share_setting&sld_addons=spreader",
		data:{},
		dataType:'json',
		async:false,
		success:function(result){
			if (result.ssys_expire_day) {
				ssys_expire_day = result.ssys_expire_day;
			}
		}
	});

}

// 添加 商品所属推手信息
function add_goods_spreader_cookie(gid,share_id,ssys_expire_day,ssys_expire_time)
{
	var exp = new Date();
	var now_time = exp.getTime();

	var ssys_share_ids,all_share_ids;

	all_share_ids = ssys_share_ids = getcookie('ssys_share_ids');
	if (ssys_share_ids) {
		var share_ids = [];
		share_ids = ssys_share_ids.split(',');
		share_ids.push(share_id);
		share_ids = share_ids.filter(function(element,index,self){
		    return self.indexOf(element) == index;     //indexOf只返回元素在数组中第一次出现的位置，如果与元素位置不一致，说明该元素在前面已经出现过，是重复元素
		});
		var share_ids_str = share_ids.join(',');
		share_id_val = share_ids_str;
	}else{
		share_id_val = share_id;
	}


	// 获取其他推手信息
	all_share_ids = all_share_ids.split(',');

	// 剔除当前分享标示
	all_share_ids = all_share_ids.filter(function(element,index,self){
	    return element != share_id;
	});
	
	// 将该商品的其他推手标示 剔除
	$.each(all_share_ids,function(k,item){
		var goods_cookie_name = 'ssys_gid_'+item+'_'+gid;
		var goods_cookie_time_name = 'ssys_share_time_'+item+'_'+gid;
		delCookie(goods_cookie_name);
		delCookie(goods_cookie_time_name);
	});

	// 存储 cookie
	addcookie("ssys_share_ids",share_id_val, ssys_expire_day);
	addcookie("ssys_share_time_"+share_id,(now_time+ssys_expire_time)*1, ssys_expire_day);
	// 可以成为 推广人 的 推手
	addcookie("ssys_main_share_id",share_id, ssys_expire_day);
	addcookie("ssys_main_share_id_time",(now_time+ssys_expire_time)*1, ssys_expire_day);

	addcookie("ssys_gid_"+share_id+'_'+gid,gid, ssys_expire_day);
	addcookie("ssys_share_time_"+share_id+"_"+gid,(now_time+ssys_expire_time)*1, ssys_expire_day);

}

// 获取 商品（含集合） 对应的 推广标示 ；若无则清除 商品id
function get_gids_spreader_code(spreader_gid){
	var all_share_ids;
	var last_result_obj = {};
	var result_array = [];
	var result_str = '';

	all_share_ids = getcookie('ssys_share_ids');
	all_share_ids = all_share_ids.split(',');

	// 查询当前 商品是否已有所属 推手
	$.each(all_share_ids,function(k,item){
		var goods_cookie_name = 'ssys_gid_'+item+'_'+spreader_gid;
		var goods_cookie_time_name = 'ssys_share_time_'+item+'_'+spreader_gid;
		var goods_spreader_code = getcookie(goods_cookie_name);
		var goods_spreader_time = getcookie(goods_cookie_time_name);
		if (goods_spreader_code && goods_spreader_time) {
			// 验证标示是否过期
			var exp = new Date();
			var now_time = exp.getTime();
			if (now_time <= goods_spreader_time || goods_spreader_time == 0) {
				// 未过期
				var result_item_str = spreader_gid+'|'+item;

				result_array.push(result_item_str);
			}

		}
	});

	result_str = result_array.join(',');

	return result_str;
}

// 当前商品common 下的所有商品ID
function get_spu_all_gid(spreader_gid,share_id,ssys_expire_day,ssys_expire_time){
	$.ajax({
		type:'post',
		url:ApiUrl+"/index.php?app=api&mod=get_spu_all_gid&sld_addons=spreader",
		data:{spreader_gid:spreader_gid},
		dataType:'json',
		async:false,
		success:function(result){
			if (result.state == 200) {
				var spreader_gids = result.data;
				if (spreader_gids.length) {
					for (var i = 0; i < spreader_gids.length; i++) {
						add_goods_spreader_cookie(spreader_gids[i],share_id,ssys_expire_day,ssys_expire_time);
					}
				}
			}
		}
	});
}

if (check_plugin_status()) {
	function get_check_spreader(spreader_gid){
	    // 查询 商品 是否存在

	    var spreader_result = '';

	    if (Object.prototype.toString.call(spreader_gid)=='[object Array]') {
	    	// 多个
	    	var spreader_more = [];
	    	var spreader_item = '';
	    	$.each(spreader_gid,function(k,item){
	    		spreader_item = get_gids_spreader_code(item);
	    		spreader_more.push(spreader_item);
	    	});
	    	spreader_result = spreader_more.join(',');
	    }else{
	    	// 单个
	    	// 查询 是否存在 推广标示
	    	spreader_result = get_gids_spreader_code(spreader_gid);
	    }

	    return spreader_result;
	}

	var share_id = GetQueryString("shareId");
	var spreader_gid = GetQueryString("gid");

	get_spreader_setting();

	var ssys_expire_time = ssys_expire_day * one_day_time;

	if (share_id && spreader_gid) {
		
		get_spu_all_gid(spreader_gid,share_id,ssys_expire_day,ssys_expire_time);

	}

}