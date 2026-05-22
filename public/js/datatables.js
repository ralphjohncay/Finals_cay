$(document).ready(function() {
    $('#servicesTable').DataTable({
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        info: true,
        responsive: true,
        columnDefs: [
            { orderable: false, targets: 5 } // Actions column
        ]
    });
});
