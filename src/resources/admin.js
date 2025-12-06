/*
  Requirement: Make the "Manage Resources" page interactive.
*/

// --- Global Data Store ---
let resources = [];

// --- Element Selections ---
const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');

// --- Functions ---

/**
 * Create one <tr> row for a resource.
 * resource = { id, title, description, link? }
 */
function createResourceRow(resource) {
  const tr = document.createElement('tr');

  // Title cell
  const titleTd = document.createElement('td');
  titleTd.textContent = resource.title;
  tr.appendChild(titleTd);

  // Description cell
  const descTd = document.createElement('td');
  descTd.textContent = resource.description || '';
  tr.appendChild(descTd);

  // Actions cell
  const actionsTd = document.createElement('td');
  actionsTd.classList.add('actions-cell');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = resource.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = resource.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);
  tr.appendChild(actionsTd);

  return tr;
}

/**
 * Render the whole table from `resources` array.
 */
function renderTable() {
  if (!resourcesTableBody) return;

  resourcesTableBody.innerHTML = '';

  resources.forEach((resource) => {
    const row = createResourceRow(resource);
    resourcesTableBody.appendChild(row);
  });
}

/**
 * Handle adding a new resource (form submit).
 */
function handleAddResource(event) {
  event.preventDefault();

  const titleInput = document.querySelector('#resource-title');
  const descInput = document.querySelector('#resource-description');
  const linkInput = document.querySelector('#resource-link');

  const title = titleInput ? titleInput.value.trim() : '';
  const description = descInput ? descInput.value.trim() : '';
  const link = linkInput ? linkInput.value.trim() : '';

  if (!title || !link) {
    alert('Please fill in at least the title and the link.');
    return;
  }

  const newResource = {
    id: `res_${Date.now()}`,
    title,
    description,
    link
  };

  resources.push(newResource);
  renderTable();

  if (resourceForm) {
    resourceForm.reset();
  }
}

/**
 * Handle clicks inside the table body (delete via delegation).
 */
function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  if (target.classList.contains('delete-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    resources = resources.filter((res) => res.id !== id);
    renderTable();
  }

  // (Optional) edit-btn logic ممكن تضيفه لاحقاً لو طلبت منكم الدكتورة
}

/**
 * Load initial data and set up listeners.
 */
async function loadAndInitialize() {
  try {
    const response = await fetch('resources.json');
    if (!response.ok) {
      console.error('Failed to load resources.json:', response.status);
      resources = [];
    } else {
      const data = await response.json();
      resources = Array.isArray(data) ? data : [];
    }
  } catch (error) {
    console.error('Error fetching resources.json:', error);
    resources = [];
  }

  // أول رسم
  renderTable();

  // Listeners
  if (resourceForm) {
    resourceForm.addEventListener('submit', handleAddResource);
  }

  if (resourcesTableBody) {
    resourcesTableBody.addEventListener('click', handleTableClick);
  }
}

// --- Initial Page Load ---
loadAndInitialize();
