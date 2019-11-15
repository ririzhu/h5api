$(function () {
    $('.login_nav span').on('click',function () {
        var index = $(this).index()
        $('.login_nav span').removeClass('on')
        $(this).addClass('on')
        $('.login_type form').removeClass('on')
        $('.login_type form').eq(index).addClass('on')
    })
})


