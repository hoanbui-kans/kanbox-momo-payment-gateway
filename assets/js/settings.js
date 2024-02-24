jQuery(function ($) {
    $(document).ready(function () {
        var $repeater = $('.repeater').repeater({
            initEmpty: true,
            show: function () {
                $(this).slideDown();
            },
            hide: function (deleteElement) {
                if (confirm('Are you sure you want to delete this element?')) {
                    $(this).slideUp(deleteElement);
                }
            },
            ready: function (setIndexes) {
            },
            isFirstItemUndeletable: true
        })

        $repeater.setList(script_data);
    });
})