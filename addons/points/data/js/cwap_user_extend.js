
function extend_member_build(result,key){
	var extend_html = '';
	if(result.datas.member_info.if_spreader){
		extend_html += '<dl class="mt5">';
		if (result.datas.member_info.is_spreader) {
			extend_html += '<a href="'+result.datas.member_info.spreader_url+'">';
			extend_html += '<img style="width: 100%;" src="/addons/spreader/data/images/shop_becomed_spreader.jpeg">';
			extend_html += '</a>';
		}else{
			extend_html += '<a href="javascript:become_spreader(\''+key+'\');">';
			extend_html += '<img style="width: 100%;" src="/addons/spreader/data/images/shop_become_spreader.jpeg">';
			extend_html += '</a>';
		}
		extend_html += '</dl>';
	}
	$(".my_extend").append(extend_html);
}

function become_spreader(key){
	$.ajax({
		type:'post',
		url:ApiUrl+"/index.php?app=api&mod=become_spreader&sld_addons=spreader",
		data:{key:key},
		dataType:'json',
		success:function(result){
			if (result.state == 200) {
				alert(result.msg);
			}else if(result.state == 256){
				// var r=confirm("您已成为推手,是否跳转到推手平台");
				// if (r) {
					window.location.href=result.data.spreader_url;
				// }
			}else{
				alert(result.msg);
			}
		}
	});
}