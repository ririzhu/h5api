var key = getcookie("key");
if(!key){
    window.location.href = WapSiteUrl + "/cwap_login.html";
}
var order_id = GetQueryString("order_id");
$(function(){

    $.ajax({
        url:ApiUrl+'/index.php?app=userorder&mod=order_desc&sld_addons=points',
        type:'post',
        data:{key:key,order_id:order_id},
        dataType:'json',
        success:function(e){
            checklogin(e.login);
            if(e.status == 200) {
                var data = e.data.orderinfo;
                var address = e.data.address;
                $('#deposit').html(template.render('goods_list',data));
                $('.convert').html(template.render('title_desc',data));
                $('.To_change').html(template.render('order_handle',data));
                $('.member_order_address').html(template.render('member_address',address));
                //取消订单
                $(document).on('click','.rollback_order',function(){
                    var order_id = data.point_orderid;
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
                    var order_id = data.point_orderid;
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
                    var order_id = data.point_orderid;
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
                    var order_id = data.point_orderid;
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
            }else{
                layer.open({
                    content: e.msg
                    ,skin: 'msg'
                    ,time: 2 //2秒后自动关闭
                });
            }
        },
        complete:function(xhr){
            xhr = null;
        }
    })
});