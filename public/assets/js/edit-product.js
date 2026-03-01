const apiUrl = '/api/products.php';
const optionsApiUrl = '/api/options.php';

const form = document.getElementById('editProductForm');
const resultBox = document.getElementById('editProductResult');
const dynamicFieldsContainer = document.getElementById('dynamicFields');
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
const bestilIntervalUnitSelect = document.getElementById('bestilIntervalUnitSelect');
const newBestilIntervalUnitInput = document.getElementById('newBestilIntervalUnitInput');
const addBestilIntervalUnitBtn = document.getElementById('addBestilIntervalUnitBtn');

const readOnlyExtraFields = new Set([
  'change_log',
  'last_saved_at',
  'tara_weight_grams',
  'holdbarhed_text',
  'product_photo_url',
  'datablad_url',
]);

const removedLegacyExtraFields = new Set([
  'extra_form_varianter',
  'extra_finish',
  'extra_sheet_name',
  'extra_smagsvarianter',
  'extra_folie_varianter',
]);

function normalizeExtraKey(value) {
  return String(value || '').trim().toLowerCase();
}

function isRemovedLegacyExtraField(value) {
  return removedLegacyExtraFields.has(normalizeExtraKey(value));
}

const knownFields = new Set([
  'active',
  'barcode',
  'hostedshop_id',
  'supplier',
  'brand',
  'stk_pr_kolli',
  'stk_1_4_pl',
  'stk_1_2_pl',
  'stk_1_1_pl',
  'inkl_fragt',
  'bestil_interval',
  'bestil_interval_unit',
  'min_ordre',
  'leveringstid',
  'produktionstid',
  'levering_text',
  'produkt_maal',
  'opstart_pr',
  'opstart_genbestil',
  'opstart_genbestil_avance',
  'opstart_genbestil_vejl',
  'opstart',
  'opstart_avance',
  'opstart_vejl',
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
const leveringstidInput = document.getElementById('leveringstid');
const produktionstidInput = document.getElementById('produktionstid');
const leveringTextInput = document.getElementById('leveringText');
const opstartGenbestilInput = document.getElementById('opstartGenbestil');
const opstartGenbestilAvanceInput = document.getElementById('opstartGenbestilAvance');
const opstartGenbestilVejlInput = document.getElementById('opstartGenbestilVejl');
const opstartInput = document.getElementById('opstart');
const opstartAvanceInput = document.getElementById('opstartAvance');
const opstartVejlInput = document.getElementById('opstartVejl');
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

function parseList(value) {
  if (Array.isArray(value)) {
    return value.map((item) => String(item).trim()).filter(Boolean);
  }

  return String(value || '')
    .split('|')
    .map((item) => item.trim())
    .filter(Boolean);
}

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

async function loadSmagsvarianterOptions(initialSelected = []) {
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

    for (const variant of initialSelected) {
      ensureSmagsvariantOption(variant, true);
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

async function loadReusableList(groupKey, selectElement, initialSelected = [], includeEmptyOption = false) {
  try {
    const response = await fetch(`${optionsApiUrl}?group=${encodeURIComponent(groupKey)}`);
    const payload = await response.json();
    if (!response.ok || !payload.data) {
      return;
    }

    const options = Array.isArray(payload.data.values) ? payload.data.values : [];
    selectElement.innerHTML = '';
    if (includeEmptyOption) {
      const empty = document.createElement('option');
      empty.value = '';
      empty.textContent = 'Select unit';
      selectElement.appendChild(empty);
    }
    for (const option of options) {
      ensureOption(selectElement, option, false);
    }
    for (const selected of initialSelected) {
      ensureOption(selectElement, selected, true);
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

addBestilIntervalUnitBtn.addEventListener('click', () => {
  const value = String(newBestilIntervalUnitInput.value || '').trim();
  if (!value) return;
  callOptionsApiAdd('bestil_interval_unit', value, bestilIntervalUnitSelect, newBestilIntervalUnitInput);
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

  const leveringstid = Number(leveringstidInput.value || 0);
  const produktionstid = Number(produktionstidInput.value || 0);
  const hasLeveringstid = String(leveringstidInput.value || '').trim() !== '';
  const hasProduktionstid = String(produktionstidInput.value || '').trim() !== '';
  if (hasLeveringstid || hasProduktionstid) {
    const sum = (Number.isFinite(leveringstid) ? leveringstid : 0) + (Number.isFinite(produktionstid) ? produktionstid : 0);
    leveringTextInput.value = `${sum} hverdage efter godkendt korrektur`;
  } else {
    leveringTextInput.value = '';
  }

  const opstartGenbestil = Number(opstartGenbestilInput.value || 0);
  const opstartGenbestilAvance = Number(opstartGenbestilAvanceInput.value || 0);
  const hasOpstartGenbestil = String(opstartGenbestilInput.value || '').trim() !== '';
  const hasOpstartGenbestilAvance = String(opstartGenbestilAvanceInput.value || '').trim() !== '';
  if (hasOpstartGenbestil || hasOpstartGenbestilAvance) {
    const kost = Number.isFinite(opstartGenbestil) ? opstartGenbestil : 0;
    const avancePct = Number.isFinite(opstartGenbestilAvance) ? opstartGenbestilAvance : 0;
    const sum = kost + (kost * (avancePct / 100));
    opstartGenbestilVejlInput.value = sum.toFixed(2);
  } else {
    opstartGenbestilVejlInput.value = '';
  }

  const opstart = Number(opstartInput.value || 0);
  const opstartAvance = Number(opstartAvanceInput.value || 0);
  const hasOpstart = String(opstartInput.value || '').trim() !== '';
  const hasOpstartAvance = String(opstartAvanceInput.value || '').trim() !== '';
  if (hasOpstart || hasOpstartAvance) {
    const kost = Number.isFinite(opstart) ? opstart : 0;
    const avancePct = Number.isFinite(opstartAvance) ? opstartAvance : 0;
    const sum = kost + (kost * (avancePct / 100));
    opstartVejlInput.value = sum.toFixed(2);
  } else {
    opstartVejlInput.value = '';
  }

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
leveringstidInput.addEventListener('input', updateCalculatedReadOnlyFields);
produktionstidInput.addEventListener('input', updateCalculatedReadOnlyFields);
opstartGenbestilInput.addEventListener('input', updateCalculatedReadOnlyFields);
opstartGenbestilAvanceInput.addEventListener('input', updateCalculatedReadOnlyFields);
opstartInput.addEventListener('input', updateCalculatedReadOnlyFields);
opstartAvanceInput.addEventListener('input', updateCalculatedReadOnlyFields);
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

  const extra = (product.extra_data && typeof product.extra_data === 'object') ? product.extra_data : {};
  const selectedSmagsvarianter = parseList(extra.smagsvarianter || []);
  const selectedFormVarianter = parseList(extra.form_varianter || []);
  const selectedFolieVarianter = parseList(extra.folie_varianter || []);
  const selectedFinish = parseList(extra.finish || []);
  const selectedBestilIntervalUnit = String(extra.bestil_interval_unit ?? '');

  document.getElementById('active').checked = toBoolean(extra.active ?? false);
  document.getElementById('barcode').value = String(extra.barcode ?? '');
  document.getElementById('hostedshopId').value = String(extra.hostedshop_id ?? '');
  document.getElementById('supplier').value = String(extra.supplier ?? '');
  document.getElementById('brand').value = String(extra.brand ?? '');
  document.getElementById('stkPrKolli').value = String(extra.stk_pr_kolli ?? '');
  document.getElementById('stkQuarterPl').value = String(extra.stk_1_4_pl ?? '');
  document.getElementById('stkHalfPl').value = String(extra.stk_1_2_pl ?? '');
  document.getElementById('stkFullPl').value = String(extra.stk_1_1_pl ?? '');
  document.getElementById('inklFragt').checked = toBoolean(extra.inkl_fragt ?? false);
  document.getElementById('bestilInterval').value = String(extra.bestil_interval ?? '');
  document.getElementById('minOrdre').value = String(extra.min_ordre ?? '');
  document.getElementById('produktMaal').value = String(extra.produkt_maal ?? '');
  document.getElementById('leveringstid').value = String(extra.leveringstid ?? '');
  document.getElementById('produktionstid').value = String(extra.produktionstid ?? '');
  document.getElementById('opstartPr').value = String(extra.opstart_pr ?? '');
  document.getElementById('opstartGenbestil').value = String(extra.opstart_genbestil ?? '');
  document.getElementById('opstartGenbestilAvance').value = String(extra.opstart_genbestil_avance ?? '');
  document.getElementById('opstart').value = String(extra.opstart ?? '');
  document.getElementById('opstartAvance').value = String(extra.opstart_avance ?? '');
  document.getElementById('netWeightGrams').value = String(extra.net_weight_grams ?? '');
  document.getElementById('grossWeightGrams').value = String(extra.gross_weight_grams ?? '');
  document.getElementById('holdbarhedMonths').value = String(extra.holdbarhed_months ?? '');
  document.getElementById('glutenfri').checked = toBoolean(extra.glutenfri ?? false);
  document.getElementById('veggie').checked = toBoolean(extra.veggie ?? false);
  document.getElementById('vegan').checked = toBoolean(extra.vegan ?? false);
  document.getElementById('komposterbar').checked = toBoolean(extra.komposterbar ?? false);
  changeLogInput.value = String(extra.change_log ?? '');
  loadSmagsvarianterOptions(selectedSmagsvarianter);
  loadReusableList('form_varianter', formVarianterSelect, selectedFormVarianter);
  loadReusableList('folie_varianter', folieVarianterSelect, selectedFolieVarianter);
  loadReusableList('finish', finishSelect, selectedFinish);
  loadReusableList('bestil_interval_unit', bestilIntervalUnitSelect, selectedBestilIntervalUnit ? [selectedBestilIntervalUnit] : [], true);

  dynamicFieldsContainer.innerHTML = '';
  for (const [key, value] of Object.entries(extra)) {
    if (readOnlyExtraFields.has(key) || knownFields.has(key) || isRemovedLegacyExtraField(key)) {
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
  if (extra.levering_text !== undefined && extra.levering_text !== null) {
    leveringTextInput.value = String(extra.levering_text);
  }
  if (extra.opstart_genbestil_vejl !== undefined && extra.opstart_genbestil_vejl !== null) {
    opstartGenbestilVejlInput.value = String(extra.opstart_genbestil_vejl);
  }
  if (extra.opstart_vejl !== undefined && extra.opstart_vejl !== null) {
    opstartVejlInput.value = String(extra.opstart_vejl);
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
    stk_pr_kolli: formData.get('stk_pr_kolli') || '',
    stk_1_4_pl: formData.get('stk_1_4_pl') || '',
    stk_1_2_pl: formData.get('stk_1_2_pl') || '',
    stk_1_1_pl: formData.get('stk_1_1_pl') || '',
    inkl_fragt: document.getElementById('inklFragt').checked,
    bestil_interval: formData.get('bestil_interval') || '',
    bestil_interval_unit: formData.get('bestil_interval_unit') || '',
    min_ordre: formData.get('min_ordre') || '',
    leveringstid: formData.get('leveringstid') || '',
    produktionstid: formData.get('produktionstid') || '',
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
    produkt_maal: formData.get('produkt_maal') || '',
    opstart_pr: formData.get('opstart_pr') || '',
    opstart_genbestil: formData.get('opstart_genbestil') || '',
    opstart_genbestil_avance: formData.get('opstart_genbestil_avance') || '',
    opstart: formData.get('opstart') || '',
    opstart_avance: formData.get('opstart_avance') || '',
  };

  for (const input of dynamicFieldsContainer.querySelectorAll('input')) {
    const key = String(input.name || '').replace(/^extra_/, '');
    if (!key || isRemovedLegacyExtraField(key) || isRemovedLegacyExtraField(`extra_${key}`)) continue;
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
    if (result.data.levering_text !== undefined) {
      leveringTextInput.value = String(result.data.levering_text ?? '');
    }
    if (result.data.opstart_genbestil_vejl !== undefined) {
      opstartGenbestilVejlInput.value = String(result.data.opstart_genbestil_vejl ?? '');
    }
    if (result.data.opstart_vejl !== undefined) {
      opstartVejlInput.value = String(result.data.opstart_vejl ?? '');
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
