// assets/scripts/orderedit.js

// Import page-specific CSS
import '../styles/orderedit.css';

// Optional: import jQuery if you need JS enhancements
import $ from 'jquery';

// Optional: import DataTables if you plan to use tables on this page
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

$(document).ready(() => {
    // Initialize DataTables if there is any table (optional)
    if ($('#ordersTable').length) {
        $('#ordersTable').DataTable({
            pageLength: 10,
            lengthChange: false,
            ordering: true,
            info: true,
            responsive: true,
            autoWidth: false,
            columnDefs: [
                { orderable: false, targets: -1 } // last column = Actions
            ],
            language: {
                search: "Search orders:",
                info: "Showing _START_ to _END_ of _TOTAL_ orders",
                paginate: { previous: "Prev", next: "Next" }
            }
        });
    }

    // Optional: simple form enhancements
    $('form').on('submit', function() {
        // You can add a loading state if needed
        $(this).find('button[type="submit"]').prop('disabled', true).text('Saving...');
    });

    // Optional: tooltips for badges or buttons
    $('[data-bs-toggle="tooltip"]').tooltip();
});
