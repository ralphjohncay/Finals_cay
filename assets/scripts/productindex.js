import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css';
import '../styles/productindex.css';

function initProductsTable() {
  const $table = $('#productsTable');
  if (!$table.length) return;

  if ($table.data('dt-initialized')) return;

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
    paging: false, // Disable pagination
    searching: false, // Disable search
    dom: 't', // Only table, no controls
    responsive: { details: false }, // keep header/body aligned, no child rows
    autoWidth: false,
    info: false, // Hide pagination info text
    columnDefs: [
      { orderable: false, targets: 4 }, // actions
      { className: 'dt-desc', targets: 1 }, // description wraps
      { className: 'dt-nowrap text-end', targets: 2 }, // price
      { className: 'dt-nowrap text-center', targets: 4 } // actions
    ],
    language: {
      emptyTable: 'No products found'
    }
  });

  $table.data('dt-initialized', true);
}

function wireRowActions() {
  // Delegate to the table container
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.getAttribute('data-action');
    if (action === 'view' || action === 'edit') {
      const href = btn.getAttribute('data-href');
      if (href) window.location.href = href;
      return;
    }

    if (action === 'delete') {
      const form = btn.closest('form.js-delete-form');
      if (form) {
        if (confirm('Delete this product?')) {
          form.submit();
        }
      }
    }
  });
}

// lifecycle hooks
function onLoad() {
  initProductsTable();
  wireRowActions();
}

document.addEventListener('DOMContentLoaded', onLoad);
// Turbo support
document.addEventListener('turbo:load', onLoad);

document.addEventListener('turbo:before-render', () => {
  const $table = $('#productsTable');
  if ($table.length && $.fn.DataTable.isDataTable($table)) {
    try {
      $table.DataTable().clear().destroy();
    } catch (e) {
      try { $table.DataTable().destroy(true); } catch (_) {}
    }
    $table.removeData('dt-initialized');
  }
});
