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
});