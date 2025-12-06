/*
  Requirement: Make the "Manage Resources" page interactive.
*/

// --- Global Data Store ---
let resources = [];
let editingId = null;

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
  const addBtn = document.querySelector('#add-resource');

  const title = titleInput ? titleInput.value.trim() : '';
  const description = descInput ? descInput.value.trim() : '';
  const link = linkInput ? linkInput.value.trim() : '';

  if (!title || !link) {
    alert('Please fill in at least the title and the link.');
    return;
  }

  // If we are editing an existing resource
  if (editingId) {
    const index = resources.findIndex((res) => res.id === editingId);
    if (index !== -1) {
      resources[index] = {
        ...resources[index],
        title,
        description,
        link
      };
    }

    editingId = null;
    renderTable();

    if (resourceForm) resourceForm.reset();
    if (addBtn) addBtn.textContent = 'Add Resource';

    return;
  }

  // Otherwise, add a new resource
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
 * Handle clicks inside the table body (edit/delete via delegation).
 */
function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  // Delete
  if (target.classList.contains('delete-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    resources = resources.filter((res) => res.id !== id);
    renderTable();
    return;
  }

  // Edit
  if (target.classList.contains('edit-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    const resource = resources.find((res) => res.id === id);
    if (!resource) return;

    const titleInput = document.querySelector('#resource-title');
    const descInput = document.querySelector('#resource-description');
    const linkInput = document.querySelector('#resource-link');
    const addBtn = document.querySelector('#add-resource');

    if (titleInput) titleInput.value = resource.title;
    if (descInput) descInput.value = resource.description || '';
    if (linkInput) linkInput.value = resource.link || '';

    editingId = id; // remember which one we're editing

    if (addBtn) addBtn.textContent = 'Save Changes';
  }
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

  // Initial render
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
