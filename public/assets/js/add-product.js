const apiUrl = '/api/products.php';
const form = document.getElementById('addProductForm');
const resultBox = document.getElementById('addProductResult');

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const formData = new FormData(form);
  const payload = {
    action: 'create',
    sheet_name: formData.get('sheet_name') || 'SIGDETSØDT',
    sku: formData.get('sku') || '',
    product_name: formData.get('product_name') || '',
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

    form.reset();
    document.getElementById('sheetName').value = 'SIGDETSØDT';
  } catch (error) {
    resultBox.textContent = JSON.stringify({
      error: 'Could not save product.',
      details: String(error),
    }, null, 2);
  }
});
