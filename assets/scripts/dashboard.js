// assets/scripts/dashboard.js

// Import the dashboard CSS
import '../styles/dashboard.css';

// Optional: jQuery if you want DOM manipulation
import $ from 'jquery';

// Optional: Chart.js for dashboard charts (install via npm if you use it)
// import Chart from 'chart.js/auto';

$(document).ready(() => {
    // ===== Card hover effect =====
    $('.card').hover(
        function() {
            $(this).addClass('hovered');
        },
        function() {
            $(this).removeClass('hovered');
        }
    );

    // ===== Example: Dashboard counters =====
    $('.counter').each(function () {
        const $this = $(this);
        const countTo = parseInt($this.text(), 10);
        $({ countNum: 0 }).animate({ countNum: countTo }, {
            duration: 1000,
            easing: 'swing',
            step: function () {
                $this.text(Math.floor(this.countNum));
            },
            complete: function () {
                $this.text(this.countNum);
            }
        });
    });

    // ===== Example: Initialize DataTables if you have tables =====
    if ($('#dashboardTable').length) {
        $('#dashboardTable').DataTable({
            pageLength: 10,
            lengthChange: false,
            ordering: true,
            responsive: true,
            autoWidth: false,
            columnDefs: [
                { orderable: false, targets: -1 } // last column = actions
            ],
            language: {
                search: "Search:",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: { previous: "Prev", next: "Next" }
            }
        });
    }

    // ===== Optional: Tooltips for buttons or badges =====
    $('[data-bs-toggle="tooltip"]').tooltip();

    // ===== Optional: Charts =====
    // Example: If you have a canvas with id "salesChart"
    // const ctx = document.getElementById('salesChart').getContext('2d');
    // const salesChart = new Chart(ctx, {
    //     type: 'line',
    //     data: {
    //         labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
    //         datasets: [{
    //             label: 'Sales',
    //             data: [12, 19, 14, 20, 25],
    //             backgroundColor: 'rgba(59, 130, 246, 0.2)',
    //             borderColor: 'rgba(59, 130, 246, 1)',
    //             borderWidth: 2
    //         }]
    //     },
    //     options: {
    //         responsive: true,
    //         maintainAspectRatio: false
    //     }
    // });
});
