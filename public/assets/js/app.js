const apiUrl = '/api/products.php';

const state = {
  page: 1,
  perPage: 20,
  sheet: '',
  query: '',
  totalPages: 1,
};

const selectedIds = new Set();

const tableBody = document.getElementById('productsTableBody');
const resultInfo = document.getElementById('resultInfo');
const pageInfo = document.getElementById('pageInfo');
const searchInput = document.getElementById('searchInput');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const selectAllRows = document.getElementById('selectAllRows');
const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function updateDeleteButtonState() {
  const count = selectedIds.size;
  deleteSelectedBtn.disabled = count === 0;
  deleteSelectedBtn.textContent = count > 0 ? `Delete Selected (${count})` : 'Delete Selected';
}

function updateSelectAllState(items) {
  if (!items.length) {
    selectAllRows.checked = false;
    selectAllRows.indeterminate = false;
    return;
  }

  const selectedOnPage = items.filter((item) => selectedIds.has(Number(item.id))).length;
  selectAllRows.checked = selectedOnPage === items.length;
  selectAllRows.indeterminate = selectedOnPage > 0 && selectedOnPage < items.length;
}

async function deleteSelectedRows() {
  const ids = Array.from(selectedIds.values());
  if (!ids.length) {
    return;
  }

  const confirmed = window.confirm(`Delete ${ids.length} selected product(s)?`);
  if (!confirmed) {
    return;
  }

  const response = await fetch(apiUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      action: 'delete',
      ids,
    }),
  });

  const payload = await response.json();
  if (!response.ok || !payload.data) {
    window.alert('Delete failed. Please try again.');
    return;
  }

  for (const id of ids) {
    selectedIds.delete(Number(id));
  }

  await loadProducts();
}

async function loadProducts() {
  const params = new URLSearchParams({
    page: String(state.page),
    perPage: String(state.perPage),
  });

  if (state.sheet) params.set('sheet', state.sheet);
  if (state.query) params.set('q', state.query);

  const response = await fetch(`${apiUrl}?${params.toString()}`);
  const payload = await response.json();

  if (!response.ok || !payload.data) {
    resultInfo.textContent = 'Error loading products';
    return;
  }

  const { items, total, totalPages, page } = payload.data;
  state.totalPages = totalPages || 1;
  state.page = page || 1;

  resultInfo.textContent = `${total} products`;
  pageInfo.textContent = `Page ${state.page} / ${state.totalPages}`;

  tableBody.innerHTML = '';

  if (!items.length) {
    tableBody.innerHTML = '<tr><td colspan="6">No products found.</td></tr>';
    updateDeleteButtonState();
    updateSelectAllState(items);
    return;
  }

  for (const item of items) {
    const row = document.createElement('tr');
    const price = item.price ? `${item.price} ${item.currency || ''}` : '-';
    const numericId = Number(item.id);
    const checked = selectedIds.has(numericId) ? 'checked' : '';

    row.innerHTML = `
      <td><input type="checkbox" class="row-select" data-id="${numericId}" ${checked} aria-label="Select row ${numericId}" /></td>
      <td>${escapeHtml(item.sku || '-')}</td>
      <td>${escapeHtml(item.product_name || '-')}</td>
      <td>${escapeHtml(item.category || '-')}</td>
      <td>${escapeHtml(price)}</td>
      <td><a class="link-btn" href="edit-product.php?id=${item.id}">Edit</a></td>
    `;

    tableBody.appendChild(row);
  }

  for (const checkbox of document.querySelectorAll('.row-select')) {
    checkbox.addEventListener('change', () => {
      const rowId = Number(checkbox.dataset.id || '0');
      if (checkbox.checked) {
        selectedIds.add(rowId);
      } else {
        selectedIds.delete(rowId);
      }

      updateDeleteButtonState();
      updateSelectAllState(items);
    });
  }

  updateDeleteButtonState();
  updateSelectAllState(items);
}

for (const tab of document.querySelectorAll('.tab')) {
  tab.addEventListener('click', () => {
    for (const current of document.querySelectorAll('.tab')) {
      current.classList.remove('active');
    }

    tab.classList.add('active');
    state.sheet = tab.dataset.sheet || '';
    state.page = 1;
    loadProducts();
  });
}

document.getElementById('searchBtn').addEventListener('click', () => {
  state.query = searchInput.value.trim();
  state.page = 1;
  loadProducts();
});

searchInput.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
    state.query = searchInput.value.trim();
    state.page = 1;
    loadProducts();
  }
});

prevBtn.addEventListener('click', () => {
  if (state.page <= 1) return;
  state.page -= 1;
  loadProducts();
});

nextBtn.addEventListener('click', () => {
  if (state.page >= state.totalPages) return;
  state.page += 1;
  loadProducts();
});

selectAllRows.addEventListener('change', () => {
  const rowCheckboxes = document.querySelectorAll('.row-select');

  for (const checkbox of rowCheckboxes) {
    const rowId = Number(checkbox.dataset.id || '0');
    checkbox.checked = selectAllRows.checked;

    if (selectAllRows.checked) {
      selectedIds.add(rowId);
    } else {
      selectedIds.delete(rowId);
    }
  }

  updateDeleteButtonState();
  selectAllRows.indeterminate = false;
});

deleteSelectedBtn.addEventListener('click', () => {
  deleteSelectedRows();
});

loadProducts();
