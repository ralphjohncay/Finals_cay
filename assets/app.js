// Bootstrap (jQuery, Axios, Stimulus, etc.)
import './bootstrap.js';

// Global CSS
import './styles/app.css';
import './styles/form.css';
import './styles/view.css';
// Global DataTables CSS (Bootstrap 5 theme + responsive) for all pages
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css';

// Do NOT import page-specific scripts here. Each page includes its own Encore entry
// (e.g., {{ encore_entry_script_tags('orderindex') }}), preventing double initialization.

console.log('App JS loaded: all page scripts imported!');
