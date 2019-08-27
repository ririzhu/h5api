var key = getcookie("key");
if(!key){
    window.location.href = WapSiteUrl + "/cwap_login.html";
}
var s = GetQueryString("s");
var page = 10;
var pn = 1;
var ishasmore = true;
$(function(){
    if(s){
        $('li[bbc_type="'+s+'"]').css({"border-bottom":" 1px solid #EC3737"});
    }else{
        $('.nav ul li').eq(0).css({"border-bottom":" 1px solid #EC3737"});
    }
    if(!s){
        s = 1;
    }
    get_list();
    //下拉加载
    $(window).scroll(function() {
        if ($(window).scrollTop()!=0 && $(window).scrollTop() + $(window).height() > $(document).height() - 1) {
            get_list();
        }
    });
    //取消订单
    $(document).on('click','.rollback_order',function(){
        var order_id = $(this).parent('.Btn2').attr('bbc_type');
        layer.open({
            content: '您确定要取消订单吗？'
            ,btn: ['是的', '算了']
            ,yes: function(index){
                $.ajax({
                    url:ApiUrl+'/index.php?app=userorder&mod=rollbackorder&sld_addons=points',
                    data:{key:key,order_id:order_id},
                    type:'post',
                    dataType:'json',
                    success:function(e){
                        checklogin(e.login);
                        if(e.status == 200){
                            location.reload();
                        }else{
                            layer.open({
                                content: e.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                            });
                        }
                    }
                })
            }
            ,no:function(index){
                layer.close(index);
            }
        });
    });

    //去支付
    $(document).on('click','.go_pay',function(){
        var order_id = $(this).parent('.Btn2').attr('bbc_type');
        layer.open({
            content: '您确定要支付订单吗？'
            ,btn: ['是的', '算了']
            ,yes: function(index){
                $.ajax({
                    url:ApiUrl+'/index.php?app=userorder&mod=gotopayorder&sld_addons=points',
                    data:{key:key,order_id:order_id},
                    type:'post',
                    dataType:'json',
                    success:function(e){
                        checklogin(e.login);
                        if(e.status == 200){
                            location.reload();
                        }else{
                            layer.open({
                                content: e.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                            });
                        }
                    }
                })
            }
            ,no:function(index){
                layer.close(index);
            }
        });
    });
    //确认收货
    $(document).on('click','.confirmation',function(){
        var order_id = $(this).parent('.Btn2').attr('bbc_type');
        layer.open({
            content: '您确定收到宝贝了吗？'
            ,btn: ['是的', '没有']
            ,yes: function(index){
                $.ajax({
                    url:ApiUrl+'/index.php?app=userorder&mod=confirmation&sld_addons=points',
                    data:{key:key,order_id:order_id},
                    type:'post',
                    dataType:'json',
                    success:function(e){
                        checklogin(e.login);
                        if(e.status == 200){
                            location.reload();
                        }else{
                            layer.open({
                                content: e.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                            });
                        }
                    }
                })
            }
            ,no:function(index){
                layer.close(index);
            }
        });
    });
    //再次购买
    $(document).on('click','.again_buy',function(){
        var order_id = $(this).parent('.Btn2').attr('bbc_type');
        $.ajax({
            url:ApiUrl+'/index.php?app=userorder&mod=buyagainorder&sld_addons=points',
            data:{key:key,order_id:order_id},
            type:'post',
            dataType:'json',
            success:function(e){
                checklogin(e.login);
                if(e.status == 200){
                    location.href = 'cwap_shopping_cart.html';
                }else{
                    layer.open({
                        content: e.msg
                        ,skin: 'msg'
                        ,time: 2 //2秒后自动关闭
                    });
                }
            }
        })
    });
});
function get_list(){

    if(ishasmore){
        $.ajax({
            url:ApiUrl+'/index.php?app=userorder&mod=getmemberlist&sld_addons=points',
            data:{key:key,s:s,page:page,pn:pn},
            type:'get',
            dataType:'json',
            success:function(res){
                checklogin(res.login);
                if(res.status == 200){
                    var list = res.data;
                    console.log(list);
                    $('#order_list').append(template.render('order-list-tmpl',list));
                    ishasmore = res.data.ishasmore.hasmore;
                    pn++;
                }

            },
            complete:function(xhr){
                xhr = null;
            }
        });
    }
}