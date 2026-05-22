import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css';
// Import page stylesheet after DataTables CSS so overrides are obvious
import '../styles/serviceindex.css';

function initServicesTable() {
    const $table = $('#servicesTable');
    if (!$table.length) return;

    // avoid duplicate init within same lifecycle
    if ($table.data('dt-initialized')) return;

    // if already initialized (e.g., after Turbo), destroy first
    if ($.fn.DataTable.isDataTable($table)) {
        try {
            $table.DataTable().clear().destroy();
        } catch (e) {
            try { $table.DataTable().destroy(true); } catch (_) {}
        }
    }

    $table.DataTable({
        destroy: true,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        pagingType: 'full_numbers',
        dom: '<"dt-toolbar d-flex justify-content-between align-items-center"lf>t<"dt-footer d-flex justify-content-between align-items-center"ip>',
        responsive: true,
        autoWidth: false,
        columnDefs: [{ orderable: false, targets: 5 }], // 6 columns total, last is Actions
        language: {
            search: "Search services:",
            info: "Showing _START_ to _END_ of _TOTAL_ services",
            paginate: { first: 'First', previous: "Prev", next: "Next", last: 'Last' },
            emptyTable: "No services found"
        }
    });

    $table.data('dt-initialized', true);
}

document.addEventListener('DOMContentLoaded', initServicesTable);
document.addEventListener('turbo:load', initServicesTable);

document.addEventListener('turbo:before-render', () => {
    const $table = $('#servicesTable');
    if ($table.length && $.fn.DataTable.isDataTable($table)) {
        try {
            $table.DataTable().clear().destroy();
        } catch (e) {
            try { $table.DataTable().destroy(true); } catch (_) {}
        }
        $table.removeData('dt-initialized');
    }
});
