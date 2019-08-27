var key = getcookie('key');
if(!key){
    window.location.href = WapSiteUrl+'/cwap_login.html';
}
//显示页数
var page = 10;
//第几页,三个分页,通过对象存储
var pn = 1;
//是否最后一页
var ishasmore=true;
//搜索类型all:全部,add:增加,desc:减少
var type = 'all';
//状态下标
var index;
$(function(){
    //获取用户信息
    $.get(ApiUrl+'/index.php?app=points_member_center&mod=getUserMemberInfo&&sld_addons=points',{key:key},function(res){
        if(res.login == 0){
            window.location.href = WapSiteUrl+'/cwap_login.html';
        }
        if(res.status == 200){
            $('#pointsnum').html(res.data);
        }
    },'json');
    get_list();
    //下拉加载
    $(window).scroll(function() {
        if ($(window).scrollTop()!=0 && $(window).scrollTop() + $(window).height() > $(document).height() - 1) {
            get_list();
        }
    });

    //点击切换效果
    $(".tab_btn span").click(function(){
        index=$(this).index();
        //初始化分页,
        pn = 1;
        ishasmore = true;
        $('.tab').html('');

        $(".tab_btn span").removeAttr("class","active");
        $(this).attr("class","active");
        $(".tab").css("display","none");
        type = $(".tab").eq(index).attr('data-type');
        get_list();
        $(".tab").eq(index).css("display","block");

    });
});

function get_list()
{
    if(ishasmore){
        $.ajax({
            url:ApiUrl+'/index.php?app=points_member_center&mod=getUserPointsDesc&sld_addons=points',
            data:{key:key,type:type,page:page,pn:pn},
            type:'get',
            dataType:'json',
            success:function(res){

                if(res.login == 0){
                    window.location.href = WapSiteUrl+'/cwap_login.html';
                }
                if(res.status == 200){

                    $('.loading').remove();
                    var list = res.data;
                    $('ul[data-type="'+type+'"]').append(template.render('points-list-tmpl',list));
                    ishasmore = res.data.ishasmore.hasmore;
                    pn++;
                }else if(res.status == 255){
                    $('ul[data-type="'+type+'"]').html('<li class="loading"><div class=""><i></i></div>暂无数据...</li>');

                }

            },
            complete:function(xhr){
                xhr = null;
            }
        });
    }

}
