const basePath = window.location.pathname.split('/public/')[0];
const apiUrl = `${basePath}/public/api/products.php`;

const state = {
  page: 1,
  perPage: 20,
  sheet: '',
  query: '',
  totalPages: 1,
};

const tableBody = document.getElementById('productsTableBody');
const resultInfo = document.getElementById('resultInfo');
const pageInfo = document.getElementById('pageInfo');
const searchInput = document.getElementById('searchInput');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

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
    tableBody.innerHTML = '<tr><td colspan="7">No products found.</td></tr>';
    return;
  }

  for (const item of items) {
    const row = document.createElement('tr');
    const price = item.price ? `${item.price} ${item.currency || ''}` : '-';

    row.innerHTML = `
      <td>${item.id}</td>
      <td>${item.sheet_name || '-'}</td>
      <td>${item.sku || '-'}</td>
      <td>${item.product_name || '-'}</td>
      <td>${item.category || '-'}</td>
      <td>${price}</td>
      <td><a class="link-btn" href="product.php?id=${item.id}">Open</a></td>
    `;

    tableBody.appendChild(row);
  }
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

loadProducts();
