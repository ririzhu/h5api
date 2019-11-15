var key = getcookie("key");
if(!key){
    window.location.href = WapSiteUrl + "/cwap_login.html";
}
$(function(){
    $.ajax({
        url:ApiUrl+'/index.php?app=userorder&mod=cartlist&sld_addons=points',
        data:{key:key},
        type:'post',
        dataType:'json',
        success:function(e){
            //checklogin(e.login);
            if(e.status == 200){
                $('.show_data').html(template.render('suite_tmp', e));
                countpoints();
                //删除
                $(document).on('click','.delete span',function(){
                    var ids = [];
                    $(".check_item:checked").each(function(){
                        ids.push($(this).data('cartid'));
                    });
                    console.log(ids);
                    $.ajax({
                        url:ApiUrl+'/index.php?app=userorder&mod=delcart&sld_addons=points',
                        data:{key:key,cartid:ids},
                        type:'post',
                        dataType:'json',
                        success:function(e){
                            checklogin(e.login);
                            if(e.status == 200) {
                                $("input[type='checkbox']:checked").parent('li').remove();
                                countpoints();
                            }
                        }
                    });
                    $(".delete").css("right","-16.5rem");

                });
                //勾选
                $('.check_item').on('change',function(){
                    countpoints();
                });
                //新增,减少
                $('.minus,.plus').on('click',function(){
                    var numselect = $(this).parent('.cart-num-minus-plus').find('.cart_num');
                    var num = numselect.html();
                    var cartid = numselect.data('cartid');
                    if($(this).hasClass('minus')){
                        $.ajax({
                            url:ApiUrl+'/index.php?app=userorder&mod=handlecart&sld_addons=points',
                            data:{key:key,type:'desc',cartid:cartid},
                            type:'post',
                            dataType:'json',
                            success:function(e){
                                checklogin(e.login);
                                if(e.status == 200) {
                                    numselect.html((parseInt(numselect.html())-1)<=0?1:(parseInt(numselect.html())-1));
                                    countpoints();
                                }else{
                                    layer.open({
                                        content: e.msg
                                        ,skin: 'msg'
                                        ,time: 2 //2秒后自动关闭
                                    });
                                }
                            }
                        });

                    }
                    if($(this).hasClass('plus')){
                        $.ajax({
                            url:ApiUrl+'/index.php?app=userorder&mod=handlecart&sld_addons=points',
                            data:{key:key,type:'add',cartid:cartid},
                            type:'post',
                            dataType:'json',
                            success:function(e){
                                checklogin(e.login);
                                if(e.status == 200) {
                                    numselect.html(parseInt(numselect.html())+1);
                                    countpoints();
                                }else{
                                    layer.open({
                                        content: e.msg
                                        ,skin: 'msg'
                                        ,time: 2 //2秒后自动关闭
                                    });
                                }

                            }
                        });

                    }
                });
                //全选
                $('.all_checkbox').on('change',function(){
                    if($(this).prop('checked')){
                        $('.check_item').prop('checked',true);
                    }else{
                        $('.check_item').prop('checked',false);
                    }

                    countpoints();
                });
                //去支付
                $('.buy_now').on('click',function(){
                    var cartinfo = '';

                    $(".check_item:checked").each(function(){
                        var num = $(this).parent('li').eq(0).find('.cart_num').html();
                        cartinfo += $(this).data('cartid')+'|'+ num+',';
                    });
                    cartinfo = cartinfo.substring(0, cartinfo.lastIndexOf(','));
                    if(cartinfo != ''){
                        window.location.href = 'cwap_Integral_order.html?ifcart=1&cart_id='+cartinfo;
                    }else{
                        layer.open({
                            content: '请选择购物车商品'
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                    }
                });
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
//统计价格
function countpoints()
{
    var all_money = 0;
    $(document).find('.check_item').each(function(){
        if($(this).prop('checked')){
            all_money += parseInt($(this).data('price')) * $(this).parent('li').find('.cart_num').html();
        }
    });
    $('#cart_money').html(all_money);
}