$(function() {
    Array.prototype.unique = function() {
        var e = [];
        for (var t = 0; t < this.length; t++) {
            if (e.indexOf(this[t]) == -1) e.push(this[t])
        }
        return e
    };
    var e = decodeURIComponent(GetQueryString("keyword"));
    if (e) {
        $("#keyword").val(e)
        writeClear($("#keyword"))
    }
    $("#keyword").on("input",
        function() {
            var e = $.trim($("#keyword").val());
            if (e == "") {
                $("#search_tip_list_container").hide();
                $(this).parents('.header-all').find(".input-del").hide();
            } else {
                $.getJSON(ApiUrl + "/index.php?app=index&mod=city_site_list", {
                    term: $("#keyword").val()
                },
                function(e) {
                    if (!e.datas.error) {
                        var t = e.datas;
                        t.WapSiteUrl = WapSiteUrl;
                        t.current_data = current_data;
                        if (count(t.citylist) > 0) {
                            $("#search_tip_list_container").html(template.render("search_tip_list_script", t)).show()
                        } else {
                            $("#search_tip_list_container").hide()
                        }
                    }
                });
                $(this).parents('.header-inp').find(".input-del").show();
            }
    });

    $(".input-del").click(function() {
        $(this).hide();
        $("#search_tip_list_container").empty().hide();
    });

    function get_city_site_list(current_data){
        $.getJSON(ApiUrl + "/index.php?app=index&mod=city_site_list",
            function(e) {
                var t = e.datas;
                t.WapSiteUrl = WapSiteUrl;
                t.current_data = current_data;
                $("#hot_city_list_container").html(template.render("hot_list", t));
                $("#all_city_list_container").html(template.render("search_his_list", t));
        });
    }

    get_city_site_list(current_data);

    // 切换 分站
    $(".city_search").on("click",".chang-city-item",function(e){
        var site_id = $(this).data('site_id');

        $.ajax({
            type: 'get',
            url: ApiUrl + "/index.php?app=index&mod=change_current_city_site",
            dataType: 'json',
            data: {
                site_id:site_id
            },
            async: false,
            success: function (e) {
                var bid = 0;
                if (e.code==200) {
                    var change_data = {};
                    change_data.bid = bid = e.datas.bid;
                    change_data.area_name = area_name = e.datas.area_name;
                    change_data.site_id = site_id = e.datas.site_id;

                    change_current_city_site_data(change_data);
                    reload_current_data(change_data);
                    get_city_site_list(change_data);
                    window.location.href="./index.html";
                }

            }
        });
    });

    function reload_current_data(current_data){
        // 当前 分站信息
        if (current_data.bid > 0) {
            // 非全国站
            $(".current-city-show").find(".current-city-name").text(current_data.area_name);
        }else{
            $(".current-city-show").find(".current-city-name").text('全国');
        }
    }

    reload_current_data(current_data);

});