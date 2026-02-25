const importForm = document.getElementById('importForm');
const importResult = document.getElementById('importResult');
const basePath = window.location.pathname.split('/public/')[0];
const importApiUrl = `${basePath}/public/api/import.php`;

function setBusyState(isBusy) {
  const submitButton = importForm.querySelector('button[type="submit"]');
  if (!submitButton) return;
  submitButton.disabled = isBusy;
  submitButton.textContent = isBusy ? 'Importing...' : 'Import File';
}

async function processChunks(jobId, chunkSize = 250) {
  while (true) {
    const processForm = new FormData();
    processForm.set('action', 'process');
    processForm.set('job_id', jobId);
    processForm.set('chunk_size', String(chunkSize));

    const response = await fetch(importApiUrl, {
      method: 'POST',
      body: processForm,
    });

    const payload = await response.json();

    if (!response.ok || !payload.data) {
      importResult.textContent = JSON.stringify(payload, null, 2);
      return;
    }

    const progress = payload.data;
    importResult.textContent = JSON.stringify({
      status: progress.status,
      progressPercent: progress.progressPercent,
      processedRows: progress.processedRows,
      totalRows: progress.totalRows,
      rowsImported: progress.rowsImported,
      rowsSkipped: progress.rowsSkipped,
      message: progress.message,
    }, null, 2);

    if (progress.isComplete) {
      return;
    }
  }
}

importForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  setBusyState(true);

  importResult.textContent = 'Uploading file and preparing chunk import...';

  const formData = new FormData(importForm);
  formData.set('action', 'start');

  try {
    const response = await fetch(importApiUrl, {
      method: 'POST',
      body: formData,
    });

    const payload = await response.json();

    if (!response.ok || !payload.data) {
      importResult.textContent = JSON.stringify(payload, null, 2);
      return;
    }

    const startData = payload.data;
    importResult.textContent = JSON.stringify({
      message: startData.message,
      totalRows: startData.totalRows,
      progressPercent: 0,
      processedRows: 0,
    }, null, 2);

    await processChunks(startData.jobId, 250);
    importForm.reset();
  } catch (error) {
    importResult.textContent = JSON.stringify({
      error: 'Import request failed.',
      details: String(error),
    }, null, 2);
  } finally {
    setBusyState(false);
  }
});
