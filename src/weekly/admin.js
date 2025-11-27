/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element
     inside your `weeks-table`.
*/

// --- Global Data Store ---
let weeks = [];
let editingWeekId = null; // null = adding, not editing

// --- Element Selections ---
const weekForm = document.getElementById("week-form");
const weeksTableBody = document.getElementById("weeks-tbody");

// Cache form inputs
const titleInput = document.getElementById("week-title");
const dateInput = document.getElementById("week-start-date");
const descInput = document.getElementById("week-description");
const linksInput = document.getElementById("week-links");
const submitButton = document.getElementById("add-week");

// --- Functions ---

// Create one table row for a week
function createWeekRow(week) {
  const tr = document.createElement("tr");

  const titleTd = document.createElement("td");
  titleTd.textContent = week.title;

  const descTd = document.createElement("td");
  descTd.textContent = week.description;

  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.setAttribute("data-id", week.id);

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.setAttribute("data-id", week.id);

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(titleTd);
  tr.appendChild(descTd);
  tr.appendChild(actionsTd);

  return tr;
}

// Render all weeks into the table body
function renderTable() {
  weeksTableBody.innerHTML = "";
  weeks.forEach((week) => {
    const row = createWeekRow(week);
    weeksTableBody.appendChild(row);
  });
}

// Handle Add / Save form submit
function handleAddWeek(event) {
  event.preventDefault();

  const title = titleInput.value.trim();
  const startDate = dateInput.value.trim();
  const description = descInput.value.trim();

  const linksRaw = linksInput.value.trim();
  const links = linksRaw
    ? linksRaw
        .split("\n")
        .map((link) => link.trim())
        .filter((link) => link)
    : [];

  if (!title) {
    return; // simple guard
  }

  if (editingWeekId) {
    // --- Update existing week (Edit mode) ---
    const index = weeks.findIndex((w) => w.id === editingWeekId);
    if (index !== -1) {
      weeks[index] = {
        ...weeks[index],
        title,
        startDate,
        description,
        links,
      };
    }

    editingWeekId = null;
    submitButton.textContent = "Add Week";
  } else {
    // --- Create new week ---
    const newWeek = {
      id: `week_${Date.now()}`,
      title,
      startDate,
      description,
      links,
    };
    weeks.push(newWeek);
  }

  renderTable();
  event.target.reset();
}

// Handle clicks on Edit/Delete buttons (event delegation)
function handleTableClick(event) {
  const target = event.target;

  // Delete
  if (target.classList.contains("delete-btn")) {
    const idToDelete = target.getAttribute("data-id");
    weeks = weeks.filter((week) => week.id !== idToDelete);
    renderTable();
    return;
  }

  // Edit
  if (target.classList.contains("edit-btn")) {
    const idToEdit = target.getAttribute("data-id");
    const weekToEdit = weeks.find((week) => week.id === idToEdit);
    if (!weekToEdit) return;

    // Fill the form with existing values
    titleInput.value = weekToEdit.title || "";
    dateInput.value = weekToEdit.startDate || "";
    descInput.value = weekToEdit.description || "";
    linksInput.value = (weekToEdit.links || []).join("\n");

    editingWeekId = idToEdit;
    submitButton.textContent = "Save Changes";

    // Optional: scroll to form
    window.scrollTo({ top: 0, behavior: "smooth" });
  }
}

// Load initial data and set up listeners
async function loadAndInitialize() {
  try {
    const response = await fetch("weeks.json");
    const data = await response.json();
    weeks = data;
  } catch (error) {
    console.error("Failed to load weeks.json:", error);
    weeks = []; // fall back to empty
  }

  renderTable();

  weekForm.addEventListener("submit", handleAddWeek);
  weeksTableBody.addEventListener("click", handleTableClick);
}

// Initial load
loadAndInitialize();


