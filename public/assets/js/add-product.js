const apiUrl = '/api/products.php';
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
const changeLogInput = document.getElementById('changeLog');

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
    description: formData.get('description') || '',
    category: formData.get('category') || '',
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
