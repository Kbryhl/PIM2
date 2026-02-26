const optionsApiUrl = '/api/options.php';
const root = document.getElementById('optionsAdminRoot');
const resultBox = document.getElementById('optionsAdminResult');

const groups = [
  { key: 'smagsvarianter', label: 'Smagsvarianter' },
  { key: 'form_varianter', label: 'Form varianter' },
  { key: 'folie_varianter', label: 'Folie varianter' },
  { key: 'finish', label: 'Finish' },
];

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

async function callOptionsApi(payload) {
  const response = await fetch(optionsApiUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.error || 'Options API error');
  }

  return data.data || {};
}

function renderGroup(groupKey, label, values) {
  const safeGroup = escapeHtml(groupKey);
  const rows = values.map((value) => {
    const safeValue = escapeHtml(value);
    return `
      <tr>
        <td>${safeValue}</td>
        <td>
          <div class="inline-row">
            <input data-rename-input="${safeGroup}" data-old-value="${safeValue}" type="text" placeholder="New name" />
            <button data-rename-btn="${safeGroup}" data-old-value="${safeValue}" type="button">Rename</button>
            <button data-delete-btn="${safeGroup}" data-value="${safeValue}" type="button">Delete</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  return `
    <section class="card">
      <h3>${escapeHtml(label)}</h3>
      <div class="inline-row" style="margin-bottom:0.6rem;">
        <input data-add-input="${safeGroup}" type="text" placeholder="Add new option" />
        <button data-add-btn="${safeGroup}" type="button">Add</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Option</th><th>Actions</th></tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="2">No options yet.</td></tr>'}</tbody>
        </table>
      </div>
    </section>
  `;
}

async function loadAndRender() {
  const response = await fetch(optionsApiUrl);
  const payload = await response.json();

  if (!response.ok || !payload.data || !payload.data.groups) {
    resultBox.textContent = JSON.stringify(payload, null, 2);
    return;
  }

  const grouped = payload.data.groups;
  root.innerHTML = groups.map((group) => {
    const values = Array.isArray(grouped[group.key]) ? grouped[group.key] : [];
    return renderGroup(group.key, group.label, values);
  }).join('');

  bindActions();
  resultBox.textContent = 'Options loaded.';
}

function bindActions() {
  for (const button of document.querySelectorAll('[data-add-btn]')) {
    button.addEventListener('click', async () => {
      const group = button.getAttribute('data-add-btn') || '';
      const input = document.querySelector(`[data-add-input="${group}"]`);
      const value = (input && 'value' in input) ? String(input.value || '').trim() : '';
      if (!value) return;

      try {
        await callOptionsApi({ action: 'add', group, value });
        await loadAndRender();
      } catch (error) {
        resultBox.textContent = String(error);
      }
    });
  }

  for (const button of document.querySelectorAll('[data-delete-btn]')) {
    button.addEventListener('click', async () => {
      const group = button.getAttribute('data-delete-btn') || '';
      const value = button.getAttribute('data-value') || '';
      if (!window.confirm(`Delete option "${value}" from ${group}?`)) {
        return;
      }

      try {
        await callOptionsApi({ action: 'delete', group, value });
        await loadAndRender();
      } catch (error) {
        resultBox.textContent = String(error);
      }
    });
  }

  for (const button of document.querySelectorAll('[data-rename-btn]')) {
    button.addEventListener('click', async () => {
      const group = button.getAttribute('data-rename-btn') || '';
      const oldValue = button.getAttribute('data-old-value') || '';
      const input = document.querySelector(`[data-rename-input="${group}"][data-old-value="${oldValue}"]`);
      const newValue = (input && 'value' in input) ? String(input.value || '').trim() : '';
      if (!newValue) return;

      try {
        await callOptionsApi({ action: 'rename', group, old_value: oldValue, new_value: newValue });
        await loadAndRender();
      } catch (error) {
        resultBox.textContent = String(error);
      }
    });
  }
}

loadAndRender();
