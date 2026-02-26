const apiUrl = '/api/products.php';

const form = document.getElementById('editProductForm');
const resultBox = document.getElementById('editProductResult');
const dynamicFieldsContainer = document.getElementById('dynamicFields');
const categorySelect = document.getElementById('categorySelect');
const newCategoryInput = document.getElementById('newCategoryInput');
const addCategoryBtn = document.getElementById('addCategoryBtn');

const readOnlyExtraFields = new Set([
  'change_log',
  'last_saved_at',
  'tara_weight_grams',
  'holdbarhed_text',
  'product_photo_url',
  'datablad_url',
]);

const knownFields = new Set([
  'active',
  'barcode',
  'hostedshop_id',
  'supplier',
  'brand',
  'net_weight_grams',
  'gross_weight_grams',
  'holdbarhed_months',
  'glutenfri',
  'veggie',
  'vegan',
  'komposterbar',
]);

const netWeightInput = document.getElementById('netWeightGrams');
const grossWeightInput = document.getElementById('grossWeightGrams');
const taraWeightInput = document.getElementById('taraWeightGrams');
const skuInput = document.getElementById('sku');
const holdbarhedMonthsInput = document.getElementById('holdbarhedMonths');
const holdbarhedTextInput = document.getElementById('holdbarhedText');
const productPhotoUrlInput = document.getElementById('productPhotoUrl');
const databladUrlInput = document.getElementById('databladUrl');
const changeLogInput = document.getElementById('changeLog');

function parseQueryId() {
  const params = new URLSearchParams(window.location.search);
  return Number(params.get('id') || '0');
}

function parseCategoryString(value) {
  if (Array.isArray(value)) {
    return value.map((item) => String(item).trim()).filter(Boolean);
  }

  return String(value || '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
}

function getSelectedCategories() {
  return Array.from(categorySelect.selectedOptions).map((option) => option.value).filter(Boolean);
}

function ensureCategoryOption(category, selected = false) {
  const label = String(category || '').trim();
  if (!label) return;

  const existing = Array.from(categorySelect.options).find((opt) => opt.value.toLowerCase() === label.toLowerCase());
  if (existing) {
    existing.selected = selected || existing.selected;
    return;
  }

  const option = document.createElement('option');
  option.value = label;
  option.textContent = label;
  option.selected = selected;
  categorySelect.appendChild(option);
}

async function loadCategoryOptions(initialSelected = []) {
  try {
    const response = await fetch(`${apiUrl}?categories=1`);
    const payload = await response.json();
    if (!response.ok || !payload.data) {
      return;
    }

    const categories = Array.isArray(payload.data.categories) ? payload.data.categories : [];
    categorySelect.innerHTML = '';

    for (const category of categories) {
      ensureCategoryOption(category, false);
    }

    for (const category of initialSelected) {
      ensureCategoryOption(category, true);
    }
  } catch {
  }
}

addCategoryBtn.addEventListener('click', () => {
  const category = String(newCategoryInput.value || '').trim();
  if (!category) return;
  ensureCategoryOption(category, true);
  newCategoryInput.value = '';
});

function toBoolean(value) {
  if (value === true || value === 1) return true;
  const str = String(value ?? '').toLowerCase().trim();
  return ['1', 'true', 'yes', 'on', 'ja'].includes(str);
}

function updateCalculatedReadOnlyFields() {
  const netWeight = Number(netWeightInput.value || 0);
  const grossWeight = Number(grossWeightInput.value || 0);
  taraWeightInput.value = Number.isFinite(netWeight) && Number.isFinite(grossWeight)
    ? String(grossWeight - netWeight)
    : '';

  const months = Number(holdbarhedMonthsInput.value || 0);
  holdbarhedTextInput.value = Number.isFinite(months) && months > 0
    ? `ca. ${months} måneder, ved korrekt opbevaring`
    : '';

  const sku = String(skuInput.value || '').trim();
  const encodedSku = encodeURIComponent(sku);
  productPhotoUrlInput.value = sku
    ? `https://filbank.dk/database/sigdetsoedt/produktfoto/${encodedSku}.png`
    : '';
  databladUrlInput.value = sku
    ? `https://filbank.dk/database/sigdetsoedt/datablade/${encodedSku}.pdf`
    : '';
}

netWeightInput.addEventListener('input', updateCalculatedReadOnlyFields);
grossWeightInput.addEventListener('input', updateCalculatedReadOnlyFields);
holdbarhedMonthsInput.addEventListener('input', updateCalculatedReadOnlyFields);
skuInput.addEventListener('input', updateCalculatedReadOnlyFields);

function addDynamicField(name, value) {
  const wrapper = document.createElement('div');
  wrapper.className = 'dynamic-field';

  const label = document.createElement('label');
  label.htmlFor = `extra_${name}`;
  label.textContent = name;

  if (typeof value === 'number' || (typeof value === 'string' && /^-?\d+(\.\d+)?$/.test(value))) {
    const input = document.createElement('input');
    input.id = `extra_${name}`;
    input.name = `extra_${name}`;
    input.type = 'number';
    input.step = 'any';
    input.value = String(value ?? '');
    wrapper.appendChild(label);
    wrapper.appendChild(input);
  } else {
    const input = document.createElement('input');
    input.id = `extra_${name}`;
    input.name = `extra_${name}`;
    input.type = 'text';
    input.value = String(value ?? '');
    wrapper.appendChild(label);
    wrapper.appendChild(input);
  }

  dynamicFieldsContainer.appendChild(wrapper);
}

function applyProductToForm(product) {
  document.getElementById('productId').value = String(product.id || '');
  document.getElementById('sheetName').value = product.sheet_name || 'SIGDETSØDT';
  document.getElementById('sku').value = product.sku || '';
  document.getElementById('productName').value = product.product_name || '';
  document.getElementById('description').value = product.description || '';
  const selectedCategories = parseCategoryString(product.category || '');
  loadCategoryOptions(selectedCategories);
  document.getElementById('price').value = product.price || '';
  document.getElementById('currency').value = product.currency || '';
  document.getElementById('weight').value = product.weight || '';
  document.getElementById('dimensions').value = product.dimensions || '';
  document.getElementById('shippingInfo').value = product.shipping_info || '';

  const extra = (product.extra_data && typeof product.extra_data === 'object') ? product.extra_data : {};

  document.getElementById('active').checked = toBoolean(extra.active ?? false);
  document.getElementById('barcode').value = String(extra.barcode ?? '');
  document.getElementById('hostedshopId').value = String(extra.hostedshop_id ?? '');
  document.getElementById('supplier').value = String(extra.supplier ?? '');
  document.getElementById('brand').value = String(extra.brand ?? '');
  document.getElementById('netWeightGrams').value = String(extra.net_weight_grams ?? '');
  document.getElementById('grossWeightGrams').value = String(extra.gross_weight_grams ?? '');
  document.getElementById('holdbarhedMonths').value = String(extra.holdbarhed_months ?? '');
  document.getElementById('glutenfri').checked = toBoolean(extra.glutenfri ?? false);
  document.getElementById('veggie').checked = toBoolean(extra.veggie ?? false);
  document.getElementById('vegan').checked = toBoolean(extra.vegan ?? false);
  document.getElementById('komposterbar').checked = toBoolean(extra.komposterbar ?? false);
  changeLogInput.value = String(extra.change_log ?? '');

  dynamicFieldsContainer.innerHTML = '';
  for (const [key, value] of Object.entries(extra)) {
    if (readOnlyExtraFields.has(key) || knownFields.has(key)) {
      continue;
    }
    addDynamicField(key, value);
  }

  updateCalculatedReadOnlyFields();

  if (extra.tara_weight_grams !== undefined && extra.tara_weight_grams !== null) {
    taraWeightInput.value = String(extra.tara_weight_grams);
  }
  if (extra.holdbarhed_text !== undefined && extra.holdbarhed_text !== null) {
    holdbarhedTextInput.value = String(extra.holdbarhed_text);
  }
  if (extra.product_photo_url !== undefined && extra.product_photo_url !== null) {
    productPhotoUrlInput.value = String(extra.product_photo_url);
  }
  if (extra.datablad_url !== undefined && extra.datablad_url !== null) {
    databladUrlInput.value = String(extra.datablad_url);
  }
}

async function loadProduct() {
  const id = parseQueryId();
  if (!id) {
    resultBox.textContent = JSON.stringify({ error: 'Missing product id in URL.' }, null, 2);
    return;
  }

  const response = await fetch(`${apiUrl}?id=${encodeURIComponent(String(id))}`);
  const payload = await response.json();

  if (!response.ok || !payload.data) {
    resultBox.textContent = JSON.stringify(payload, null, 2);
    return;
  }

  applyProductToForm(payload.data);
  resultBox.textContent = 'Product loaded. You can edit and save.';
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const formData = new FormData(form);
  const payload = {
    action: 'update',
    id: Number(formData.get('id') || 0),
    sheet_name: formData.get('sheet_name') || 'SIGDETSØDT',
    sku: formData.get('sku') || '',
    product_name: formData.get('product_name') || '',
    active: document.getElementById('active').checked,
    barcode: formData.get('barcode') || '',
    hostedshop_id: formData.get('hostedshop_id') || '',
    supplier: formData.get('supplier') || '',
    brand: formData.get('brand') || '',
    net_weight_grams: formData.get('net_weight_grams') || '',
    gross_weight_grams: formData.get('gross_weight_grams') || '',
    holdbarhed_months: formData.get('holdbarhed_months') || '',
    glutenfri: document.getElementById('glutenfri').checked,
    veggie: document.getElementById('veggie').checked,
    vegan: document.getElementById('vegan').checked,
    komposterbar: document.getElementById('komposterbar').checked,
    description: formData.get('description') || '',
    category: getSelectedCategories(),
    price: formData.get('price') || '',
    currency: formData.get('currency') || '',
    weight: formData.get('weight') || '',
    dimensions: formData.get('dimensions') || '',
    shipping_info: formData.get('shipping_info') || '',
  };

  for (const input of dynamicFieldsContainer.querySelectorAll('input')) {
    const key = String(input.name || '').replace(/^extra_/, '');
    if (!key) continue;
    payload[key] = input.value;
  }

  resultBox.textContent = 'Saving changes...';

  try {
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const result = await response.json();

    if (!response.ok) {
      resultBox.textContent = JSON.stringify(result, null, 2);
      return;
    }

    resultBox.textContent = JSON.stringify({ message: 'Product updated.', ...result.data }, null, 2);

    changeLogInput.value = String(result.data.change_log || changeLogInput.value || '');
    if (result.data.tara_weight_grams !== undefined) {
      taraWeightInput.value = String(result.data.tara_weight_grams ?? '');
    }
    if (result.data.holdbarhed_text !== undefined) {
      holdbarhedTextInput.value = String(result.data.holdbarhed_text ?? '');
    }
    if (result.data.product_photo_url !== undefined) {
      productPhotoUrlInput.value = String(result.data.product_photo_url ?? '');
    }
    if (result.data.datablad_url !== undefined) {
      databladUrlInput.value = String(result.data.datablad_url ?? '');
    }
  } catch (error) {
    resultBox.textContent = JSON.stringify({ error: 'Could not update product.', details: String(error) }, null, 2);
  }
});

loadProduct();
