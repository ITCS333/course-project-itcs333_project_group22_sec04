/*
  Requirement: Add interactivity and data management to the Admin Portal.

  NOTE: This is purely front-end logic for Task 1.
  All operations happen in memory using the `students` array.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

// --- Element Selections ---

// tbody inside the students table
const studentTableBody = document.querySelector('#student-table-body');

// Add-student form (needs id="add-student-form" in HTML)
const addStudentForm = document.querySelector('#add-student-form');

// Change-password form (needs id="password-form" in HTML)
const changePasswordForm = document.querySelector('#password-form');

// Search input (needs id="search-input" in HTML)
const searchInput = document.querySelector('#search-input');

// All header cells in thead (for sorting)
const tableHeaders = document.querySelectorAll('thead th');

// --- Functions ---

/**
 * Create a <tr> element for a given student.
 * student = { name, id, email }
 */
function createStudentRow(student) {
  const tr = document.createElement('tr');

  // Name cell
  const nameTd = document.createElement('td');
  nameTd.textContent = student.name;
  tr.appendChild(nameTd);

  // ID cell
  const idTd = document.createElement('td');
  idTd.textContent = student.id;
  tr.appendChild(idTd);

  // Email cell
  const emailTd = document.createElement('td');
  emailTd.textContent = student.email;
  tr.appendChild(emailTd);

  // Actions cell
  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = student.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = student.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

/**
 * Render table body based on a given array of students.
 */
function renderTable(studentArray) {
  if (!studentTableBody) return;

  // Clear old rows
  studentTableBody.innerHTML = '';

  // Add new rows
  studentArray.forEach((stu) => {
    const row = createStudentRow(stu);
    studentTableBody.appendChild(row);
  });
}

/**
 * Handle password form submit.
 */
function handleChangePassword(event) {
  event.preventDefault();

  const currentInput = document.querySelector('#current-password');
  const newInput = document.querySelector('#new-password');
  const confirmInput = document.querySelector('#confirm-password');

  const currentPassword = currentInput?.value || '';
  const newPassword = newInput?.value || '';
  const confirmPassword = confirmInput?.value || '';

  // basic validation (we're not actually checking "current" here – no backend)
  if (newPassword !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }

  if (newPassword.length < 8) {
    alert('Password must be at least 8 characters.');
    return;
  }

  // In a real project we would send this to the server.
  alert('Password updated successfully!');

  // Clear fields
  if (currentInput) currentInput.value = '';
  if (newInput) newInput.value = '';
  if (confirmInput) confirmInput.value = '';
}

/**
 * Handle add-student form submit.
 */
function handleAddStudent(event) {
  event.preventDefault();

  const nameInput = document.querySelector('#student-name');
  const idInput = document.querySelector('#student-id');
  const emailInput = document.querySelector('#student-email');
  const defaultPasswordInput = document.querySelector('#default-password');

  const name = nameInput?.value.trim() || '';
  const id = idInput?.value.trim() || '';
  const email = emailInput?.value.trim() || '';

  if (!name || !id || !email) {
    alert('Please fill out all required fields.');
    return;
  }

  // optional: prevent duplicate IDs
  const exists = students.some((s) => s.id === id);
  if (exists) {
    alert('A student with this ID already exists.');
    return;
  }

  const newStudent = { name, id, email };
  students.push(newStudent);

  renderTable(students);

  // clear form fields
  if (nameInput) nameInput.value = '';
  if (idInput) idInput.value = '';
  if (emailInput) emailInput.value = '';
  if (defaultPasswordInput) defaultPasswordInput.value = '';
}

/**
 * Handle clicks on the table body (event delegation).
 */
function handleTableClick(event) {
  const target = event.target;

  if (!(target instanceof HTMLElement)) return;

  // Delete
  if (target.classList.contains('delete-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    students = students.filter((s) => s.id !== id);
    renderTable(students);
  }

  // Optional: simple edit (prompt-based)
  if (target.classList.contains('edit-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    const student = students.find((s) => s.id === id);
    if (!student) return;

    const newName = prompt('Edit name:', student.name);
    const newEmail = prompt('Edit email:', student.email);

    if (newName && newEmail) {
      student.name = newName.trim();
      student.email = newEmail.trim();
      renderTable(students);
    }
  }
}

/**
 * Handle live search by name.
 */
function handleSearch(event) {
  const value = event.target.value.toLowerCase().trim();

  if (!value) {
    renderTable(students);
    return;
  }

  const filtered = students.filter((s) =>
    s.name.toLowerCase().includes(value)
  );

  renderTable(filtered);
}

/**
 * Handle sorting when clicking on table headers.
 */
function handleSort(event) {
  const th = event.currentTarget;
  if (!(th instanceof HTMLElement)) return;

  const index = th.cellIndex;

  let key;
  if (index === 0) key = 'name';
  else if (index === 1) key = 'id';
  else if (index === 2) key = 'email';
  else return; // ignore "Actions" column

  // Determine current & next sort direction
  const currentDir = th.dataset.sortDir === 'desc' ? 'desc' : 'asc';
  const nextDir = currentDir === 'asc' ? 'desc' : 'asc';
  th.dataset.sortDir = nextDir;

  // Sort in place
  students.sort((a, b) => {
    let aVal = a[key];
    let bVal = b[key];

    if (key === 'id') {
      // numeric-ish sort for IDs
      const aNum = Number(aVal);
      const bNum = Number(bVal);
      if (!Number.isNaN(aNum) && !Number.isNaN(bNum)) {
        return nextDir === 'asc' ? aNum - bNum : bNum - aNum;
      }
    }

    // default string compare
    const cmp = String(aVal).localeCompare(String(bVal));
    return nextDir === 'asc' ? cmp : -cmp;
  });

  renderTable(students);
}

/**
 * Load data from students.json and set up event listeners.
 */
async function loadStudentsAndInitialize() {
  try {
    const response = await fetch('students.json');
    if (!response.ok) {
      console.error('Failed to load students.json:', response.status);
      students = []; // fallback
    } else {
      const data = await response.json();
      // Expecting an array of objects like: { name, id, email }
      students = Array.isArray(data) ? data : [];
    }
  } catch (err) {
    console.error('Error fetching students.json:', err);
    students = [];
  }

  // initial render
  renderTable(students);

  // Event listeners
  if (changePasswordForm) {
    changePasswordForm.addEventListener('submit', handleChangePassword);
  }

  if (addStudentForm) {
    addStudentForm.addEventListener('submit', handleAddStudent);
  }

  if (studentTableBody) {
    studentTableBody.addEventListener('click', handleTableClick);
  }

  if (searchInput) {
    searchInput.addEventListener('input', handleSearch);
  }

  tableHeaders.forEach((th) => {
    th.addEventListener('click', handleSort);
  });
}

// --- Initial Page Load ---
loadStudentsAndInitialize();
