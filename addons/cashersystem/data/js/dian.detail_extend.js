    
    // 收银员选择
    $('select[name="casherlabel"]').unbind().change(function () {
        var id=$(this).find('option:selected').val();
        var label_name=$(this).find('option:selected').text();
        var label_id=$('#casher_ids').val();

        if(id!=0){
            // 验证是否已经选择
            if (!checkCasherLAB(id)) {
                alert('该收银员已经选择,请选择其他收银员');
                return false;
            }

            $('select[name="casherlabel"]').after('<a class="ss-item region-code bbc_ms-btn-mini wq"  href="javascript:void(0);" data-region_code="'+id+'" data-region_name="'+label_name+'"><i title="移除" class="iconfontfa fa-close add_iconcolor"></i>'+label_name+'</a>');


            var last=label_id.substr(label_id.length-1,1);
            if(last==','){
                var ids=label_id+id+',';
            }else{
                var ids=label_id+','+id+',';
            }


            $('#casher_ids').val(ids);
        }

    });

    // 收银员 删除
    $('.add_iconcolor').live('click',function () {
        var label_id=$('#casher_ids').val();
        var id=$(this).parents('a').attr('data-region_code');

        if(label_id.indexOf(id+",") > 0){
          var str=label_id.replace(id+',','');
        }else{
          var str=label_id.replace(id,'');
        }

        if(str==','){
            var str=str.replace(',','');
        }

        $('#casher_ids').val(str);

        $(this).parent().remove();

    });

    // 验证收银员是否重复
    function checkCasherLAB($val) {
        var _return = true;
        $('.casherlabel_dd a').each(function(){
            if ($val !=0 && $val == $(this).attr('data-region_code')) {
                _return = false;
            }
        });
        return _return;
    }