const apiUrl = '/api/products.php';
const form = document.getElementById('addProductForm');
const resultBox = document.getElementById('addProductResult');
const netWeightInput = document.getElementById('netWeightGrams');
const grossWeightInput = document.getElementById('grossWeightGrams');
const taraWeightInput = document.getElementById('taraWeightGrams');
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

    form.reset();
    document.getElementById('sheetName').value = 'SIGDETSØDT';
    changeLogInput.value = String(result.data.change_log || 'Saved.');
    taraWeightInput.value = String(result.data.tara_weight_grams ?? '');
  } catch (error) {
    resultBox.textContent = JSON.stringify({
      error: 'Could not save product.',
      details: String(error),
    }, null, 2);
  }
});
