jQuery(function($){
    $('form#payment-momo').submit(function(event) {
        event.preventDefault();
        $('#x_momo_payment_loading').addClass('showing');
        setTimeout(function(){
            $('#x_momo_payment_loading').removeClass('showing');
        }, 3000)
        // currentPage++; // Do currentPage + 1, because we want to load the next page

        // $.ajax({
        //     type: 'POST',
        //     url: '/wp-admin/admin-ajax.php',
        //     dataType: 'html',
        //     data: {
        //     action: 'weichie_load_more',
        //     paged: currentPage,
        //     },
        //     success: function (res) {
        //     $('.publication-list').append(res);
        //     }
        // });
    });
})