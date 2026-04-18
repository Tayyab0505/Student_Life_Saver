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
    setTimeout(() => {
        $('.alert-dismissible').fadeOut(600, function () {
            $(this).remove();
        });
    }, 4000);

    // Confirm before delete
    $(document).on('click', '[data-confirm]', function (e) {
        const msg = $(this).data('confirm') || 'Are you sure?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    });
    
});