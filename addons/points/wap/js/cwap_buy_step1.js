var key = getcookie("key");
if(!key){
    window.location.href = WapSiteUrl + "/cwap_login.html";
}
var ifcart = GetQueryString("ifcart");
if (ifcart == 1) {
    var cart_id = GetQueryString("cart_id")
} else {
    ifcart = 0;
    var cart_id = GetQueryString("gid") + "|" + GetQueryString("buynum")
}
var pay_name = "online";
var address_id, vat_hash, offpay_hash, offpay_hash_batch, voucher, pd_pay, password, fcode = "",
    rcb_pay, rpt, payment_code;
var message = {};
var freight_hash, city_id, area_id;
var area_info;
var gid;
var change_address_str;
var pay_sn;
var total_fee;
var my_location=[];
var heji;
var lat = '';
var lng = '';
var isgaode = 0;
var page = pagesize;
var pn = 1;
var hasmore = true;
var pt_bili = 0; //积分转换货币比例
var pt_max = 0; //积分转换货币 最大比例
var pt_point = 0; //用户当前积分

var spreader = ''; // 推手标示 集合

// 优惠套装
var is_bundling = GetQueryString('bl_id')?1:0;
var bl_id = GetQueryString('bl_id');

// 推荐组合
var suite = GetQueryString('suite');
var suite_checked = GetQueryString('checked');
$(function() {
    //获取该会员的所有收货地址
    $("#list-address-valve").click(function() {
        $.ajax({
            type: "post",
            url: ApiUrl + "/index.php?app=points_buy&mod=address_list&sld_addons=points",
            data: {
                key: key
            },
            dataType: "json",
            async: false,
            success: function(e) {


                {
                    address:"北京市海淀区西小口东升科技园"
                    address_id:"106"
                    area_id:"37"
                    area_info:"北京 北京市 东城区"
                    city_id:"36"
                    is_default:"1"
                    member_id:"243"
                    mob_phone:"13031159513"
                    tel_phone:null
                    true_name:"郭萧凯"
                }
                checklogin(e.login);
                if (e.datas.address_list == null) {
                    return false
                }
                var a = e.datas;
                a.address_id = address_id;
                var i = template.render("list-address-add-list-script", a);
                $("#list-address-add-list-ul").html(i)
            }
        })
    });
    $.animationLeft({
        valve: "#list-address-valve",
        wrapper: "#list-address-wrapper",
    });
    $("#list-address-add-list-ul").on("click", "li",
        function() {
            $(this).addClass("selected").siblings().removeClass("selected");
            eval("address_info = " + $(this).attr("data-param"));
            change_address_str = "address_info = " + $(this).attr("data-param");
            city_id = address_info.city_id;
            area_id = address_info.area_id;
            _init(address_info.address_id);
            $("#list-address-wrapper").find(".header-l > a").click()
        });
    $.animationLeft({
        valve: "#new-address-valve",
        wrapper: "#new-address-wrapper",
        scroll: ""
    });
    $("#new-address-wrapper").on("click", "#varea_info",
        function() {
            $.areaSelected({
                success: function(e) {
                    city_id = e.area_id_2 == 0 ? e.area_id_1: e.area_id_2;
                    area_id = e.area_id;
                    area_info = e.area_info;
                    $("#varea_info").val(e.area_info)
                }
            })
        });
    $.animationLeft({
        valve: "#invoice-valve",
        wrapper: "#invoice-wrapper",
        scroll: ""
    });
    $.animationLeft({
        valve: "#red-valve",
        wrapper: "#red-wrapper",
        scroll: ""
    });
    template.helper("isEmpty",
        function(e) {
            var a = true;
            $.each(e,
                function(e, i) {
                    a = false;
                    return false
                });
            return a
        });
    template.helper("pf",
        function(e) {
            return parseFloat(e) || 0
        });
    template.helper("p2f",
        function(e) {
            return (parseFloat(e) || 0).toFixed(2)
        });
    var _init = function(ee) {

        // var a = 0;
        heji = 0;
        $.ajax({
            type: "post",
            url: ApiUrl + "/index.php?app=points_buy&mod=confirm&sld_addons=points",
            dataType: "json",
            data: {
                key: key,
                cart_id: cart_id,
                ifcart: ifcart,
            },
            success: function(e) {

                checklogin(e.login);
                if (e.datas.error) {
                    $.sDialog({
                        skin: "red",
                        content: e.datas.error,
                        okBtn: false,
                        cancelBtn: false
                    });
                    return false
                }

                if(!gid){
                    gid=GetQueryString("gid");
                }

                //if(!gid){
                //    gid=[];
                //    var arr = e.datas.store_cart_list;
                //    var arrkey = Object.keys(arr);
                //
                //    arrkey=arrkey[0];
                //    arr= arr[arrkey].goods_list;
                //    console.log(arr);
                //    $.each(arr,function (k,v) {
                //        console.log(888);
                //        gid.push(v.gid);
                //    });
                //}



                e.datas.WapSiteUrl = WapSiteUrl;
                console.log(e.datas);
                $('.num').html(e.datas.all_num);
                $('#totalPrice').html(e.datas.all_money);
                var i = template.render("goods_list", e.datas);
                $("#deposit").html(i);
                //如果没有收货地址为空的话，需要新增收货地址
                if ($.isEmptyObject(e.datas.address_info)) {
                    $.sDialog({
                        skin: "block",
                        content: "请添加地址",
                        okFn: function() {
                            $("#new-address-valve").click()
                        },
                        cancelFn: function() {
                            history.go( - 1)
                        }
                    });
                    return false
                }


                var now_address = e.datas.address_info;
                //更改收货地址（利用地址的相关的id获取相应的运费）
                console.log(now_address,city_id,area_id);
                if (change_address_str) {
                    e.datas.address_info = eval(change_address_str);
                } else {
                    e.datas.address_info = now_address;
                }
                insertHtmlAddress(e.datas.address_info);

            }
        })
    };
    rcb_pay = 0;
    pd_pay=  0;
    _init();
    //把运费给到页面上去
    var insertHtmlAddress = function(e, a) {
        address_id = e.address_id;
        $("#true_name").html(e.true_name);
        $("#mob_phone").html(e.mob_phone);
        $("#address").html(e.area_info + e.address);
        area_id = e.area_id;
        city_id = e.city_id;
    };
    $("#payment-online").click(function() {
        pay_name = "online";
        $("#select-payment-wrapper").find(".header-l > a").click();
        $("#select-payment-valve").find(".current-con").html("在线支付");
        $(this).addClass("sel").siblings().removeClass("sel")
    });
    $("#payment-offline").click(function() {
        pay_name = "offline";
        $("#select-payment-wrapper").find(".header-l > a").click();
        $("#select-payment-valve").find(".current-con").html("货到付款");
        $(this).addClass("sel").siblings().removeClass("sel")
    });
    $.sValid.init({
        rules: {
            vtrue_name: "required",
            vmob_phone: "required",
            varea_info: "required",
            vaddress: "required"
        },
        messages: {
            vtrue_name: "姓名必填！",
            vmob_phone: "手机号必填！",
            varea_info: "地区必填！",
            vaddress: "街道必填！"
        },
        callback: function(e, a, i) {
            if (e.length > 0) {
                var t = "";
                $.map(a,
                    function(e, a) {
                        t += "<p>" + e + "</p>"
                    });
                errorTipsShow(t)
            } else {
                errorTipsHide()
            }
        }
    });
    //新增收货地址
    $("#add_address_form").find(".btn").click(function() {
        if ($.sValid()) {
            var e = {};
            e.key = key;
            e.true_name = $("#vtrue_name").val();
            e.mob_phone = $("#vmob_phone").val();
            e.address = $("#vaddress").val();
            e.city_id = city_id;
            e.area_id = area_id;
            e.area_info = $("#varea_info").val();
            e.is_default = 0;
            $.ajax({
                type: "post",
                url: ApiUrl + "/index.php?app=points_buy&mod=address_add&sld_addons=points",
                data: e,
                dataType: "json",
                success: function(a) {
                    if (!a.datas.error) {
                        e.address_id = a.datas.address_id;
                        _init(e.address_id);
                        $("#new-address-wrapper,#list-address-wrapper").find(".header-l > a").click()
                    }
                }
            })
        }
    });

    $("#ToBuyStep2").click(function() {
        $.ajax({
            type: "post",
            url: ApiUrl + "/index.php?app=points_buy&mod=submitorder&sld_addons=points",
            data: {
                key: key,
                ifcart: ifcart,
                cart_id: cart_id,
                address_id: address_id
            },
            dataType: "json",
            success: function(e) {
                checklogin(e.login);
                //if (e.datas.error) {
                //    $.sDialog({
                //        skin: "red",
                //        content: e.datas.error,
                //        okBtn: false,
                //        cancelBtn: false
                //    });
                //    return false
                //}
                if(e.status == 200){
                    window.location.href = 'cwap_my_order.html';
                }else if(e.status == 266){
                    $.sDialog({
                        skin: "red",
                        content: e.msg,
                        okBtn: false,
                        cancelBtn: false
                    });
                    setTimeout(function(){
                        window.location.href = 'cwap_my_order.html';
                    },1500);
                }else{
                    $.sDialog({
                        skin: "red",
                        content: e.msg,
                        okBtn: false,
                        cancelBtn: false
                    });
                }
            }
        })
    })

    $('a.bbctouch-bottom-mask-close').live('click',function () {
        var href=$('.goods-pic a').attr('href');
        var team_id = GetQueryString('team_id')?GetQueryString('team_id'):'';
        window.location.href=href+'&team_id='+team_id;
    })


});
//积分抵现计算方法
function calc_diyong(deshu) {
    // if(deshu=='0'){
    //     return false;
    // }
    if(isRealNum(deshu)) {
        if(parseInt(deshu)>parseInt($("#max_use").html())){
            deshu = parseFloat($("#max_use").html());
            $("#diyong").prev('input').val(deshu);
        }
        var diyong = deshu / pt_bili;
        $("#diyong").html('￥' + diyong);
        $("#diyong").prev().attr('last',$("#diyong").prev().val());

        //如果抵用金额大于零
        if(deshu>0){
            $("#totalPrice,#onlineTotal").html(heji - diyong);
        }else{
            $("#totalPrice,#onlineTotal").html(heji);
        }
    }else{
        $("#diyong").prev('input').val($("#diyong").prev().attr('last'));
    }
}


//判断是否是非负数
function isRealNum(val){
    var re = /^[0-9]+$/ ;
    return re.test(val)
}
