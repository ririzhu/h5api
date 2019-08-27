var key = getcookie("key");
if(!key){
    window.location.href = WapSiteUrl + "/cwap_login.html";
}
var password, rcb_pay, pd_pay, payment_code;
function toPay(a, e, p) {
    $.ajax({
        type: "post",
        url: ApiUrl + "/index.php?app=" + e + "&mod=" + p,
        data: {
            key: key,
            pay_sn: a,
        },
        dataType: "json",
        success: function(p) {
            if (p.datas.error) {
                $.sDialog({
                    skin: "red",
                    content: p.datas.error,
                    okBtn: false,
                    cancelBtn: false
                });
                return false
            }
            $.animationUp({
                valve: "",
                scroll: ""
            });
            $("#onlineTotal").html(p.datas.pay_info.pay_amount);
            if (!p.datas.pay_info.member_paypwd) {
                $("#wrapperPaymentPassword").find(".input-box-help").show()
            }
            var s = false;
            if (parseFloat(p.datas.pay_info.payed_amount) <= 0) {
                if (parseFloat(p.datas.pay_info.member_available_pd) == 0 && parseFloat(p.datas.pay_info.member_available_rcb) == 0) {
                    $("#internalPay").hide()
                } else {
                    $("#internalPay").show();
                    if (parseFloat(p.datas.pay_info.member_available_rcb) != 0) {
                        $("#wrapperUseRCBpay").show();
                        $("#availableRcBalance").html(parseFloat(p.datas.pay_info.member_available_rcb).toFixed(2))
                    } else {
                        $("#wrapperUseRCBpay").hide()
                    }
                    if (parseFloat(p.datas.pay_info.member_available_pd) != 0) {
                        $("#wrapperUsePDpy").show();
                        $("#availablePredeposit").html(parseFloat(p.datas.pay_info.member_available_pd).toFixed(2))
                    } else {
                        $("#wrapperUsePDpy").hide()
                    }
                }
            } else {
                $("#internalPay").hide()
            }
            password = "";
            $("#paymentPassword").on("change",
                function() {
                    password = $(this).val()
                });
            rcb_pay = 0;
            pd_pay = 0;
            payment_code = "";
            $("#useRCBpay").click(function() {
                if ($(this).prop("checked")) {
                    s = true;
                    $("#wrapperPaymentPassword").show();
                    rcb_pay = 1
                } else {
                    if (pd_pay == 1) {
                        s = true;
                        $("#wrapperPaymentPassword").show()
                    } else {
                        s = false;
                        $("#wrapperPaymentPassword").hide()
                    }
                    rcb_pay = 0
                }
            });



            $("#usePDpy").click(function() {
                $("input[name='payment_code']").prop('checked',false);
                if ($(this).prop("checked")) {
                    s = true;
                    $("#wrapperPaymentPassword").show();
                    pd_pay = 1;
                    payment_code='predeposit';
                } else {
                    if (rcb_pay == 1) {
                        s = true;
                        $("#wrapperPaymentPassword").show()
                    } else {
                        s = false;
                        $("#wrapperPaymentPassword").hide()
                    }


                    if(payment_code=='alipay' || payment_code=='weixin' || payment_code=='wxpay_jsapi'){
                        pd_pay = 0;

                    }else{
                        pd_pay = 0;
                        payment_code='';
                    }

                }

            });

            if (!$.isEmptyObject(p.datas.pay_info.payment_list)) {
                var t = false;
                var r = false;
                var n = navigator.userAgent.match(/MicroMessenger\/(\d+)\./);
                if (parseInt(n && n[1] || 0) >= 5) {
                    t = true  //微信浏览器
                } else {
                    r = true  //非微信浏览器
                }
                for (var o = 0; o < p.datas.pay_info.payment_list.length; o++) {
                    var i = p.datas.pay_info.payment_list[o].payment_code;
                    if (i == "alipay" && r) {
                        $("#" + i).parents("label").show();
                        if (payment_code == "") {
                            // payment_code = i;
                        }
                    }
                    if (i == "wxpay_jsapi" && t) {
                        $("#" + i).parents("label").show();
                        if (payment_code == "") {
                            // payment_code = i;
                        }
                    }
                }
            }
            $("#alipay").click(function() {
                payment_code = "alipay";
                if ($("#usePDpy").prop("checked")) {
                    pd_pay = 0;
                    $("#usePDpy").prop("checked",false);
                    $("#usePDpy").parents('label').removeClass('checked');
                    $("#wrapperPaymentPassword").hide();
                }
            });
            $("#wxpay_jsapi").click(function() {
                payment_code = "wxpay_jsapi";
                if ($("#usePDpy").prop("checked")) {
                    pd_pay = 0;
                    $("#usePDpy").prop("checked",false);
                    $("#usePDpy").parents('label').removeClass('checked');
                    $("#wrapperPaymentPassword").hide();
                }
            });
            $(".bbctouch-pay span").click(function(event){
                event.stopPropagation();
            });
            $("#toPay").click(function() {
                // payment_code = $("input[name='payment_code']:checked").val();
                // 余额是否 选中
                if ($("#usePDpy").prop("checked")) {
                    s = true;
                    pd_pay = 1;
                    payment_code='predeposit';
                }else{
                    payment_code = $("input[name='payment_code']:checked").val()
                }

                if(!password){
                    password = $('#paymentPassword').val();
                }

                if (payment_code == "" || payment_code==undefined) {
                    $.sDialog({
                        skin: "red",
                        content: "请选择支付方式",
                        okBtn: false,
                        cancelBtn: false
                    });
                    return false
                }

                if (s) {
                    if (password == "") {
                        $.sDialog({
                            skin: "red",
                            content: "请填写支付密码",
                            okBtn: false,
                            cancelBtn: false
                        });
                        return false
                    }

                    // if(parseFloat(p.datas.pay_info.pay_amount)>parseFloat(p.datas.pay_info.member_available_pd)){
                    //     $.sDialog({
                    //         skin: "red",
                    //         content: "余额不足,请选择其它支付方式",
                    //         okBtn: false,
                    //         cancelBtn: false
                    //     });
                    //     return false
                    // }


                    $.ajax({
                        type: "post",
                        url: ApiUrl + "/index.php?app=buy&mod=check_pay_pwd",
                        dataType: "json",
                        async:false,
                        data: {
                            key: key,
                            password: password,
                            pay_sn:a
                        },
                        success: function(p) {
                            if (p.datas.error) {
                                $.sDialog({
                                    skin: "red",
                                    content: p.datas.error,
                                    okBtn: false,
                                    cancelBtn: false
                                });
                                return false
                            }else{
                                goToPayment(a, e == "buy" ? "pay_new": "vr_pay_new")
                            }
                        }
                    })
                } else {
                    goToPayment(a, e == "buy" ? "pay_new": "vr_pay_new")
                }
            })
        }
    })
}
function goToPayment(a, e) {
                location.href = ApiUrl + "/index.php?app=pay&mod=" + e + "&key=" + key + "&pay_sn=" + a + "&password=" + password + "&rcb_pay=" + rcb_pay + "&pd_pay=" + pd_pay + "&payment_code=" + payment_code
}