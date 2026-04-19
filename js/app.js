console.log("app.js loaded");
$(document).ready(function () {
    // Sidebar toggle
    $('#sidebarToggle').on('click', function () {
        $('#sidebar').addClass('open');
        $('#sidebarOverlay').addClass('show');
    });

    $('#sidebarOverlay').on('click', function () {
        $('#sidebar').removeClass('open');
        $(this).removeClass('show');
    });

    // Auto dismiss alerts
    $(document).ready(function () {
        if ($('.alert-dismissible').length > 0) {
            setTimeout(() => {
                $('.alert-dismissible').fadeOut(600, function () {
                    $(this).remove();
                });
            }, 2000);
        };
    });

    // Confirm before delete
    $(document).on('click', '[data-confirm]', function (e) {
        const msg = $(this).data('confirm') || 'Are you sure?';
        if (!confirm(msg)) {
            e.preventDefault();
        };
    });

});