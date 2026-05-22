// Page-specific styles for Create New Order
import '../styles/ordernew.css';
// Build a new row from the Symfony prototype
function buildRowFromPrototype(html) {
  const tmp = document.createElement('div');
  tmp.innerHTML = html.trim();

  const row = document.createElement('tr');
  row.className = 'order-item-row';

  const cell = (el) => {
    const td = document.createElement('td');
    if (el) td.appendChild(el);
    return td;
  };

  // Clone inputs to prevent removing from prototype
  const productEl = tmp.querySelector('[name$="[product]"]')?.cloneNode(true);
  const qtyEl = tmp.querySelector('[name$="[quantity]"]');
  
  // Hide any quantity input fields that might be in the prototype
  const qtyInputs = tmp.querySelectorAll('input[name*="[quantity]"]');
  qtyInputs.forEach(input => {
    input.style.display = 'none';
  });

  // Build quantity dropdown using the same name as the prototype quantity input
  let qtySelect = null;
  if (qtyEl) {
    qtySelect = document.createElement('select');
    qtySelect.className = 'form-select qty-dropdown';
    qtySelect.name = qtyEl.getAttribute('name');
    const current = parseInt(qtyEl.getAttribute('value') || '1', 10) || 1;
    // Default to 10 if no product is selected yet (will be updated when product is selected)
    const maxQty = 10;
    for (let i = 1; i <= maxQty; i++) {
      const opt = document.createElement('option');
      opt.value = String(i);
      opt.textContent = String(i);
      if (i === current) opt.selected = true;
      qtySelect.appendChild(opt);
    }
  }

  if (productEl) row.appendChild(cell(productEl));
  if (qtySelect) row.appendChild(cell(qtySelect));

  // Remove button
  const removeTd = cell();
  removeTd.innerHTML = '<button type="button" class="btn btn-sm btn-danger remove-item">Remove</button>';
  row.appendChild(removeTd);

  return row;
}

// Update quantity dropdown based on selected product's stock
function updateQuantityDropdown(row) {
  const productSel = row.querySelector('[name$="[product]"]');
  const qtySelect = row.querySelector('select.qty-dropdown') || row.querySelector('select[name*="[quantity]"]');
  
  if (!productSel || !qtySelect) {
    return;
  }
  
  const selectedOption = productSel.options[productSel.selectedIndex];
  if (!selectedOption || !selectedOption.value) {
    // No product selected, keep default max of 10
    return;
  }
  
  const stock = parseInt(selectedOption.dataset.stock || '10', 10);
  const currentValue = parseInt(qtySelect.value || '1', 10);
  
  // Clear existing options
  qtySelect.innerHTML = '';
  
  // Build options up to available stock
  const maxQty = Math.max(1, stock); // Ensure at least 1 option
  for (let i = 1; i <= maxQty; i++) {
    const opt = document.createElement('option');
    opt.value = String(i);
    opt.textContent = String(i);
    if (i === currentValue || (i === 1 && currentValue > stock)) {
      opt.selected = true;
    }
    qtySelect.appendChild(opt);
  }
  
  // If current value exceeds stock, set to stock max
  if (currentValue > stock) {
    qtySelect.value = String(stock);
  }
}

// Validate quantity against stock and show notification
function validateQuantity(row) {
  const productSel = row.querySelector('[name$="[product]"]');
  const qtySelect = row.querySelector('select.qty-dropdown') || row.querySelector('select[name*="[quantity]"]');
  
  if (!productSel || !qtySelect || !productSel.value) {
    return true; // No product selected, skip validation
  }
  
  const selectedOption = productSel.options[productSel.selectedIndex];
  if (!selectedOption || !selectedOption.value) {
    return true;
  }
  
  const stock = parseInt(selectedOption.dataset.stock || '0', 10);
  const quantity = parseInt(qtySelect.value || '1', 10);
  
  if (quantity > stock) {
    // Show notification
    alert(`⚠️ Quantity Exceeded!\n\nYou have selected ${quantity} items, but only ${stock} are available in stock.\n\nThe quantity has been adjusted to ${stock}.`);
    
    // Reset to max stock
    qtySelect.value = String(stock);
    return false;
  }
  
  return true;
}

// Wire row inputs for syncing and recalculating total
function wireRowBehavior(row) {
  const productSel = row.querySelector('[name$="[product]"]');
  const nameInput = row.querySelector('[name$="[name]"]');
  const qtyInput = row.querySelector('[name$="[quantity]"]');
  const qtySelect = row.querySelector('select.qty-dropdown') || row.querySelector('select[name*="[quantity]"]');

  const syncName = (sel) => {
    if (nameInput && !nameInput.value) {
      const opt = sel.options[sel.selectedIndex];
      if (opt && opt.text && sel.value) nameInput.value = opt.text;
    }
  };

  if (productSel) {
    // Update quantity dropdown immediately if product is already selected
    if (productSel.value) {
      // Use setTimeout to ensure DOM is ready
      setTimeout(() => updateQuantityDropdown(row), 50);
    }
    
    productSel.addEventListener('change', () => {
      syncName(productSel);
      updateQuantityDropdown(row);
      validateQuantity(row);
      updateClientTotal();
    });
  }
  
  // Validate quantity when it changes
  if (qtySelect) {
    qtySelect.addEventListener('change', () => {
      validateQuantity(row);
      updateClientTotal();
    });
  }
  
  if (qtyInput) qtyInput.addEventListener('change', updateClientTotal);
}

// Initialize Order Items
function setupOrderItems() {
  const tbody = document.getElementById('order-items-body');
  if (!tbody) return;

  const proto = tbody.dataset.prototype;
  let index = parseInt(tbody.dataset.index || '0', 10);

  // Wire existing rows and update their quantity dropdowns
  tbody.querySelectorAll('tr.order-item-row').forEach(row => {
    wireRowBehavior(row);
    // Force update quantity dropdown for existing rows after a short delay
    // to ensure DOM is fully ready
    setTimeout(() => {
      const productSel = row.querySelector('[name$="[product]"]');
      if (productSel && productSel.value) {
        updateQuantityDropdown(row);
      }
    }, 100);
  });

  // Create default row if empty
  if (tbody.querySelectorAll('tr.order-item-row').length === 0 && proto) {
    const html = proto.replace(/__name__/g, index);
    index++;
    tbody.dataset.index = String(index);
    const row = buildRowFromPrototype(html);
    tbody.appendChild(row);
    wireRowBehavior(row);
  }

  // Add new row button
  const addBtn = document.getElementById('add-order-item');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const html = proto.replace(/__name__/g, index);
      index++;
      tbody.dataset.index = String(index);
      const row = buildRowFromPrototype(html);
      tbody.appendChild(row);
      wireRowBehavior(row);
      updateClientTotal();
    });
  }

  // Remove row dynamically
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-item')) {
      const row = e.target.closest('tr');
      if (row) row.remove();
      updateClientTotal();
    }
  });

  updateClientTotal();
}

// Recalculate total price
function updateClientTotal() {
  const tbody = document.getElementById('order-items-body');
  if (!tbody) return;

  let total = 0;
  tbody.querySelectorAll('tr.order-item-row').forEach(row => {
    const productSel = row.querySelector('[name$="[product]"]');
    const qtyInput = row.querySelector('[name$="[quantity]"]');
    const qty = qtyInput && qtyInput.value ? parseInt(qtyInput.value, 10) : 1;

    let price = 0;
    if (productSel && productSel.value) {
      const opt = productSel.options[productSel.selectedIndex];
      const p = parseFloat(opt?.dataset?.price || '0');
      price = isNaN(p) ? 0 : p;
    }

    total += price * (isNaN(qty) ? 1 : Math.max(qty, 1));
  });

  const totalInput = document.querySelector('[name$="[totalPrice]"]');
  if (totalInput) totalInput.value = total.toFixed(2);
}

// Validate all order items before submission
function validateAllOrderItems() {
  const tbody = document.getElementById('order-items-body');
  if (!tbody) return true;
  
  let isValid = true;
  const errors = [];
  
  tbody.querySelectorAll('tr.order-item-row').forEach(row => {
    const productSel = row.querySelector('[name$="[product]"]');
    const qtySelect = row.querySelector('select.qty-dropdown') || row.querySelector('select[name*="[quantity]"]');
    
    if (!productSel || !qtySelect || !productSel.value) {
      return; // Skip rows without product
    }
    
    const selectedOption = productSel.options[productSel.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
      return;
    }
    
    const stock = parseInt(selectedOption.dataset.stock || '0', 10);
    const quantity = parseInt(qtySelect.value || '1', 10);
    const productName = selectedOption.text || 'Product';
    
    if (quantity > stock) {
      isValid = false;
      errors.push(`${productName}: Quantity ${quantity} exceeds available stock of ${stock}`);
      // Reset to max stock
      qtySelect.value = String(stock);
    }
  });
  
  if (!isValid) {
    alert(`⚠️ Quantity Exceeded!\n\nYou have exceeded the available stock for some products:\n\n${errors.join('\n')}\n\nThe quantities have been adjusted to match available stock.`);
  }
  
  return isValid;
}

// Save button recalculation
function wireSaveButton() {
  const btn = document.getElementById('save-order');
  const form = document.getElementById('order_form');
  if (!btn || !form) return;

  form.addEventListener('submit', (e) => {
    updateClientTotal(); // Ensure all dynamic fields are counted
    
    // Validate all quantities before submission
    if (!validateAllOrderItems()) {
      e.preventDefault(); // Prevent form submission if validation fails
      return false;
    }
  });
}

// Initialize everything
document.addEventListener('DOMContentLoaded', () => {
  setupOrderItems();
  wireSaveButton();
});
