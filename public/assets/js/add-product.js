const apiUrl = '/api/products.php';
const optionsApiUrl = '/api/options.php';
const form = document.getElementById('addProductForm');
const resultBox = document.getElementById('addProductResult');
const netWeightInput = document.getElementById('netWeightGrams');
const grossWeightInput = document.getElementById('grossWeightGrams');
const taraWeightInput = document.getElementById('taraWeightGrams');
const skuInput = document.getElementById('sku');
const holdbarhedMonthsInput = document.getElementById('holdbarhedMonths');
const holdbarhedTextInput = document.getElementById('holdbarhedText');
const productPhotoUrlInput = document.getElementById('productPhotoUrl');
const databladUrlInput = document.getElementById('databladUrl');
const categorySelect = document.getElementById('categorySelect');
const newCategoryInput = document.getElementById('newCategoryInput');
const addCategoryBtn = document.getElementById('addCategoryBtn');
const smagsvarianterSelect = document.getElementById('smagsvarianterSelect');
const newSmagsvariantInput = document.getElementById('newSmagsvariantInput');
const addSmagsvariantBtn = document.getElementById('addSmagsvariantBtn');
const formVarianterSelect = document.getElementById('formVarianterSelect');
const newFormVariantInput = document.getElementById('newFormVariantInput');
const addFormVariantBtn = document.getElementById('addFormVariantBtn');
const folieVarianterSelect = document.getElementById('folieVarianterSelect');
const newFolieVariantInput = document.getElementById('newFolieVariantInput');
const addFolieVariantBtn = document.getElementById('addFolieVariantBtn');
const finishSelect = document.getElementById('finishSelect');
const newFinishInput = document.getElementById('newFinishInput');
const addFinishBtn = document.getElementById('addFinishBtn');
const changeLogInput = document.getElementById('changeLog');

function getSelectedCategories() {
  return Array.from(categorySelect.selectedOptions).map((option) => option.value).filter(Boolean);
}

function getSelectedSmagsvarianter() {
  return Array.from(smagsvarianterSelect.selectedOptions).map((option) => option.value).filter(Boolean);
}

function getSelectedOptions(selectElement) {
  return Array.from(selectElement.selectedOptions).map((option) => option.value).filter(Boolean);
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

async function loadCategoryOptions() {
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
  } catch {
  }
}

addCategoryBtn.addEventListener('click', () => {
  const category = String(newCategoryInput.value || '').trim();
  if (!category) return;
  ensureCategoryOption(category, true);
  newCategoryInput.value = '';
});

function ensureSmagsvariantOption(value, selected = false) {
  const label = String(value || '').trim();
  if (!label) return;

  const existing = Array.from(smagsvarianterSelect.options).find((opt) => opt.value.toLowerCase() === label.toLowerCase());
  if (existing) {
    existing.selected = selected || existing.selected;
    return;
  }

  const option = document.createElement('option');
  option.value = label;
  option.textContent = label;
  option.selected = selected;
  smagsvarianterSelect.appendChild(option);
}

async function loadSmagsvarianterOptions() {
  try {
    const response = await fetch(`${optionsApiUrl}?group=smagsvarianter`);
    const payload = await response.json();
    if (!response.ok || !payload.data) {
      return;
    }

    const variants = Array.isArray(payload.data.values) ? payload.data.values : [];
    smagsvarianterSelect.innerHTML = '';
    for (const variant of variants) {
      ensureSmagsvariantOption(variant, false);
    }
  } catch {
  }
}

addSmagsvariantBtn.addEventListener('click', () => {
  const variant = String(newSmagsvariantInput.value || '').trim();
  if (!variant) return;
  ensureSmagsvariantOption(variant, true);
  newSmagsvariantInput.value = '';
});

function ensureOption(selectElement, value, selected = false) {
  const label = String(value || '').trim();
  if (!label) return;

  const existing = Array.from(selectElement.options).find((opt) => opt.value.toLowerCase() === label.toLowerCase());
  if (existing) {
    existing.selected = selected || existing.selected;
    return;
  }

  const option = document.createElement('option');
  option.value = label;
  option.textContent = label;
  option.selected = selected;
  selectElement.appendChild(option);
}

async function loadReusableList(groupKey, selectElement) {
  try {
    const response = await fetch(`${optionsApiUrl}?group=${encodeURIComponent(groupKey)}`);
    const payload = await response.json();
    if (!response.ok || !payload.data) {
      return;
    }

    const options = Array.isArray(payload.data.values) ? payload.data.values : [];
    selectElement.innerHTML = '';
    for (const option of options) {
      ensureOption(selectElement, option, false);
    }
  } catch {
  }
}

addFormVariantBtn.addEventListener('click', () => {
  const value = String(newFormVariantInput.value || '').trim();
  if (!value) return;
  callOptionsApiAdd('form_varianter', value, formVarianterSelect, newFormVariantInput);
});

addFolieVariantBtn.addEventListener('click', () => {
  const value = String(newFolieVariantInput.value || '').trim();
  if (!value) return;
  callOptionsApiAdd('folie_varianter', value, folieVarianterSelect, newFolieVariantInput);
});

addFinishBtn.addEventListener('click', () => {
  const value = String(newFinishInput.value || '').trim();
  if (!value) return;
  callOptionsApiAdd('finish', value, finishSelect, newFinishInput);
});

async function callOptionsApiAdd(group, value, selectElement, inputElement) {
  try {
    await fetch(optionsApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add', group, value }),
    });
  } catch {
  }

  ensureOption(selectElement, value, true);
  if (inputElement) inputElement.value = '';
}

function updateTaraWeight() {
  const netWeight = Number(netWeightInput.value || 0);
  const grossWeight = Number(grossWeightInput.value || 0);

  if (!Number.isFinite(netWeight) || !Number.isFinite(grossWeight)) {
    taraWeightInput.value = '';
    return;
  }

  taraWeightInput.value = String(grossWeight - netWeight);
}

netWeightInput.addEventListener('input', updateTaraWeight);
grossWeightInput.addEventListener('input', updateTaraWeight);

function updateSigdetsoedtAutoFields() {
  const sku = String(skuInput.value || '').trim();
  const encodedSku = encodeURIComponent(sku);

  productPhotoUrlInput.value = sku
    ? `https://filbank.dk/database/sigdetsoedt/produktfoto/${encodedSku}.png`
    : '';
  databladUrlInput.value = sku
    ? `https://filbank.dk/database/sigdetsoedt/datablade/${encodedSku}.pdf`
    : '';

  const months = Number(holdbarhedMonthsInput.value || 0);
  holdbarhedTextInput.value = Number.isFinite(months) && months > 0
    ? `ca. ${months} måneder, ved korrekt opbevaring`
    : '';
}

skuInput.addEventListener('input', updateSigdetsoedtAutoFields);
holdbarhedMonthsInput.addEventListener('input', updateSigdetsoedtAutoFields);

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const formData = new FormData(form);
  const payload = {
    action: 'create',
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
    smagsvarianter: getSelectedSmagsvarianter(),
    form_varianter: getSelectedOptions(formVarianterSelect),
    folie_varianter: getSelectedOptions(folieVarianterSelect),
    finish: getSelectedOptions(finishSelect),
    description: formData.get('description') || '',
    category: getSelectedCategories(),
    price: formData.get('price') || '',
    currency: formData.get('currency') || '',
    weight: formData.get('weight') || '',
    dimensions: formData.get('dimensions') || '',
    shipping_info: formData.get('shipping_info') || '',
  };

  resultBox.textContent = 'Saving product...';

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

    resultBox.textContent = JSON.stringify({
      message: 'Product saved successfully.',
      ...result.data,
    }, null, 2);

    changeLogInput.value = String(result.data.change_log || 'Saved.');
    taraWeightInput.value = String(result.data.tara_weight_grams ?? taraWeightInput.value);
    holdbarhedTextInput.value = String(result.data.holdbarhed_text ?? holdbarhedTextInput.value);
    productPhotoUrlInput.value = String(result.data.product_photo_url ?? productPhotoUrlInput.value);
    databladUrlInput.value = String(result.data.datablad_url ?? databladUrlInput.value);

    form.reset();
    document.getElementById('sheetName').value = 'SIGDETSØDT';
    for (const option of categorySelect.options) {
      option.selected = false;
    }
    for (const option of smagsvarianterSelect.options) {
      option.selected = false;
    }
    for (const option of formVarianterSelect.options) {
      option.selected = false;
    }
    for (const option of folieVarianterSelect.options) {
      option.selected = false;
    }
    for (const option of finishSelect.options) {
      option.selected = false;
    }
    changeLogInput.value = String(result.data.change_log || 'Saved.');
    taraWeightInput.value = String(result.data.tara_weight_grams ?? '');
    holdbarhedTextInput.value = String(result.data.holdbarhed_text ?? '');
    productPhotoUrlInput.value = String(result.data.product_photo_url ?? '');
    databladUrlInput.value = String(result.data.datablad_url ?? '');
  } catch (error) {
    resultBox.textContent = JSON.stringify({
      error: 'Could not save product.',
      details: String(error),
    }, null, 2);
  }
});

updateSigdetsoedtAutoFields();
loadCategoryOptions();
loadSmagsvarianterOptions();
loadReusableList('form_varianter', formVarianterSelect);
loadReusableList('folie_varianter', folieVarianterSelect);
loadReusableList('finish', finishSelect);
