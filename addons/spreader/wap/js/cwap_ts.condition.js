$(function(){
    var ssys_key = getcookie("ssys_key");
    if(!ssys_key){
        window.location.href = WapSiteUrl + "/cwap_the_login.html";
    }
    $.ajax({
        url:ApiUrl+"/index.php?app=index&mod=ts_condition&sld_addons=spreader",
        data:{},
        type:'post',
        dataType:'json',
        success:function(res){
            if(res.status == 200){
                if(res.data.ssys_become_ts_open==1) {
                $('.condition1').html(res.data.ssys_ts_condition1_money);
                if(parseFloat(res.data.ssys_ts_condition2_goodsmoney)>0){
                    $('.condition2').html('购买活动专区任意商品达到'+res.data.ssys_ts_condition2_goodsmoney+'元即可开通');
                }else{
                    $('.condition2').html('购买活动专区任意礼包商品即可开通');
                }
                }else{
                    $('.shenqing').click();
                }

            }
        }
    });
    $.ajax({
        url:ApiUrl+"/index.php?app=index&mod=ts_condition_goods&sld_addons=spreader",
        data:{ssys_key:ssys_key},
        type:'post',
        dataType:'json',
        success:function(res){
            if(res.status == 200){
                var str = '';
                $.each(res.data,function(k,v){
                    str += '<div class="goods"><div class="divimg" data-id="'+ v.gid+'"><img src="'+ v.goods_image +'" alt=""></div> <p>'+ v.goods_name +'</p> <span>&yen;'+ v.goods_price +'</span> <a href="'+SiteUrl+'/cwap/cwap_confirm.html?markgid='+v.gid+'&buynum=1&gid='+ v.gid+'">立即购买</a></div>';
                });
                $('.goodsList').html(str);
                $('.divimg').on('click',function(){
                    var gid = $(this).data('id');
                    window.location.href = SiteUrl+'/cwap/cwap_confirm.html?markgid='+gid+'&buynum=1&gid='+ gid;
                });
            }else if(res.status  == 355){
                window.location.href = WapSiteUrl + "/cwap_the_login.html";
            }
        }
    });
    //申请成为推手按钮
    $('.shenqing').on('click',function(){
        $.ajax({
            url:ApiUrl+"/index.php?app=index&mod=judge_ts_condition&sld_addons=spreader",
            data:{ssys_key:ssys_key},
            type:'post',
            dataType:'json',
            success:function(res){
                if(res.status == 355){
                    window.location.href = WapSiteUrl + "/cwap_the_login.html";
                }else if(res.status == 200){
                    window.location.href = WapSiteUrl + "/cwap_user.html";
                }else if(res.status == 255){
                    layer.open({
                        content: res.data.condition2_p +'<br>目前:'+res.data.condition2+'<br><br>'+ res.data.condition1_p+'<br>目前:'+res.data.condition1+'元。'
                        ,btn: '我知道了'
                    });
                }
            }
        });
    });
});