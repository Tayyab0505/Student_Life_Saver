$(document).ready(function () {

    // SIDEBAR TOGGLE
    $('#sidebarToggle').on('click', function () {
        $('#sidebar').addClass('open');
        $('#sidebarOverlay').addClass('show');
    });
    $('#sidebarOverlay').on('click', function () {
        $('#sidebar').removeClass('open');
        $(this).removeClass('show');
    });

    setTimeout(function () {
        $('.alert-dismissible').fadeOut(600, function () {
            $(this).remove();
        });
    }, 2000);

    // CONFIRM BEFORE DELETE
    // Any link with data-confirm="message" shows a confirm dialog
    $(document).on('click', '[data-confirm]', function (e) {
        if (!confirm($(this).data('confirm') || 'Are you sure?')) {
            e.preventDefault();
        }
    });

    // SCHEDULE FORM TOGGLE
    if ($('#toggleFormBtn').length) {
        $('#toggleFormBtn').on('click', function () {
            const panel = $('#scheduleForm');
            const isOpen = panel.hasClass('open');

            panel.toggleClass('open');

            // Swap button label and icon
            $(this).html(
                isOpen
                    ? '<i class="bi bi-plus-lg"></i> Add Class'
                    : '<i class="bi bi-x-lg"></i> Close Form'
            );
        });
        if ($('#scheduleForm').hasClass('open')) {
            $('#toggleFormBtn').html('<i class="bi bi-x-lg"></i> Close Form');
        }
    }

    // ASSIGNMENTS FORM TOGGLE
    if ($('#toggleAssignBtn').length) {
        $('#toggleAssignBtn').on('click', function () {
            const panel = $('#assignForm');
            const isOpen = panel.hasClass('open');
            panel.toggleClass('open');
            $(this).html(
                isOpen
                    ? '<i class="bi bi-plus-lg"></i> Add Assignment'
                    : '<i class="bi bi-x-lg"></i> Close Form'
            );
        });

        if ($('#assignForm').hasClass('open')) {
            $('#toggleAssignBtn').html('<i class="bi bi-x-lg"></i> Close Form');
        }
    }

});