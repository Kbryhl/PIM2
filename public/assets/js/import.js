const importForm = document.getElementById('importForm');
const importResult = document.getElementById('importResult');
const basePath = window.location.pathname.split('/public/')[0];
const importApiUrl = `${basePath}/src/api/import.php`;

importForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  importResult.textContent = 'Uploading and importing...';

  const formData = new FormData(importForm);

  const response = await fetch(importApiUrl, {
    method: 'POST',
    body: formData,
  });

  const payload = await response.json();

  if (!response.ok) {
    importResult.textContent = JSON.stringify(payload, null, 2);
    return;
  }

  importResult.textContent = JSON.stringify(payload.data, null, 2);
  importForm.reset();
});
