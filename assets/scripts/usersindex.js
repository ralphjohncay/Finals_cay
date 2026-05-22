// assets/scripts/usersindex.js

// -------------------------------
// Import jQuery and DataTables
// -------------------------------
import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
// Import page CSS AFTER DataTables CSS to take precedence
import '../styles/usersindex.css';

// -------------------------------
// Initialize DataTable (Turbo-aware, idempotent)
// -------------------------------
function initCustomersTable() {
    const $table = $('#customersTable');
    if (!$table.length) return;

    if ($table.data('dt-initialized')) {
        return;
    }

    if ($.fn.DataTable.isDataTable($table)) {
        try {
            $table.DataTable().clear().destroy();
        } catch (e) {
            try { $table.DataTable().destroy(true); } catch (_) {}
        }
    }

    const dt = $table.DataTable({
        destroy: true,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        pagingType: 'full_numbers',
        dom: '<"dt-toolbar d-flex justify-content-between align-items-center"lf>t<"dt-footer d-flex justify-content-between align-items-center"ip>',
        responsive: true,
        autoWidth: false,
        columnDefs: [
            { orderable: false, targets: 5 },
            { className: 'dt-nowrap text-center', targets: 5 }
        ],
        language: {
            search: "Search customers:",
            info: "Showing _START_ to _END_ of _TOTAL_ customers",
            paginate: { first: 'First', previous: "Prev", next: "Next", last: 'Last' },
            emptyTable: "No customers found"
        }
    });

    $table.data('dt-initialized', true);

    // Wire explicit pagination controls (covers Bootstrap and default renderers)
    const wirePagination = () => {
        const api = dt;
        const $wrapper = $table.closest('.dataTables_wrapper');
        $wrapper.off('click.dt-pg');
        $wrapper.on('click.dt-pg', '.paginate_button.first, .page-link[aria-label="First"]', (e) => { e.preventDefault(); api.page('first').draw('page'); window.scrollTo({ top: 0, behavior: 'smooth' }); });
        $wrapper.on('click.dt-pg', '.paginate_button.previous, .page-link[aria-label="Previous"]', (e) => { e.preventDefault(); api.page('previous').draw('page'); window.scrollTo({ top: 0, behavior: 'smooth' }); });
        $wrapper.on('click.dt-pg', '.paginate_button.next, .page-link[aria-label="Next"]', (e) => { e.preventDefault(); api.page('next').draw('page'); window.scrollTo({ top: 0, behavior: 'smooth' }); });
        $wrapper.on('click.dt-pg', '.paginate_button.last, .page-link[aria-label="Last"]', (e) => { e.preventDefault(); api.page('last').draw('page'); window.scrollTo({ top: 0, behavior: 'smooth' }); });
    };
    wirePagination();
}

document.addEventListener('DOMContentLoaded', initCustomersTable);
document.addEventListener('turbo:load', initCustomersTable);

document.addEventListener('turbo:before-render', () => {
    const $table = $('#customersTable');
    if ($table.length && $.fn.DataTable.isDataTable($table)) {
        try {
            $table.DataTable().clear().destroy();
        } catch (e) {
            try { $table.DataTable().destroy(true); } catch (_) {}
        }
        $table.removeData('dt-initialized');
    }
});
