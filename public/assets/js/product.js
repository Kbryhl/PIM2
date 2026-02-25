const apiUrl = '/api/products.php';
const content = document.getElementById('productContent');

async function loadProduct() {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');

  if (!id) {
    content.textContent = 'Missing product id.';
    return;
  }

  const response = await fetch(`${apiUrl}?id=${encodeURIComponent(id)}`);
  const payload = await response.json();

  if (!response.ok || !payload.data) {
    content.textContent = 'Product not found.';
    return;
  }

  const p = payload.data;
  const extraData = p.extra_data && typeof p.extra_data === 'object'
    ? Object.entries(p.extra_data)
      .map(([k, v]) => `<div class="label">${k}</div><div>${String(v ?? '-')}</div>`)
      .join('')
    : '<div class="label">extra</div><div>-</div>';

  content.innerHTML = `
    <div class="detail-grid">
      <div class="label">ID</div><div>${p.id}</div>
      <div class="label">Sheet</div><div>${p.sheet_name || '-'}</div>
      <div class="label">SKU</div><div>${p.sku || '-'}</div>
      <div class="label">Name</div><div>${p.product_name || '-'}</div>
      <div class="label">Category</div><div>${p.category || '-'}</div>
      <div class="label">Price</div><div>${p.price || '-'} ${p.currency || ''}</div>
      <div class="label">Weight</div><div>${p.weight || '-'}</div>
      <div class="label">Dimensions</div><div>${p.dimensions || '-'}</div>
      <div class="label">Shipping</div><div>${p.shipping_info || '-'}</div>
      <div class="label">Description</div><div>${p.description || '-'}</div>
      ${extraData}
    </div>
  `;
}

loadProduct();
