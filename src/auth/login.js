/*
  Requirement: Add client-side validation to the login form.
*/

// --- Element Selections ---

// Select the login form (needs id="login-form" in HTML)
const loginForm = document.querySelector('#login-form');

// Select the email input element
const emailInput = document.querySelector('#email');

// Select the password input element
const passwordInput = document.querySelector('#password');

// Select the message container
const messageContainer = document.querySelector('#message-container');

// --- Functions ---

/**
 * Display a message in the message container.
 * type: "success" or "error"
 */
function displayMessage(message, type) {
  if (!messageContainer) return;

  messageContainer.textContent = message;
  // className مباشرة عشان CSS يقدر يميز بينهم
  messageContainer.className = type;
}

/**
 * Validate email format with a simple regex.
 */
function isValidEmail(email) {
  const regex = /\S+@\S+\.\S+/;
  return regex.test(email);
}

/**
 * Validate password length (at least 8 characters).
 */
function isValidPassword(password) {
  return password.length >= 8;
}

/**
 * Handle login form submit.
 */
function handleLogin(event) {
  event.preventDefault();

  const email = emailInput ? emailInput.value.trim() : '';
  const password = passwordInput ? passwordInput.value.trim() : '';

  // Validate email
  if (!isValidEmail(email)) {
    displayMessage('Invalid email format.', 'error');
    return;
  }

  // Validate password
  if (!isValidPassword(password)) {
    displayMessage('Password must be at least 8 characters.', 'error');
    return;
  }

  // If both valid
  displayMessage('Login successful!', 'success');

  // (Optional) clear inputs
  if (emailInput) emailInput.value = '';
  if (passwordInput) passwordInput.value = '';
}

/**
 * Attach event listener to the form.
 */
function setupLoginForm() {
  if (!loginForm) return;
  loginForm.addEventListener('submit', handleLogin);
}

// --- Initial Page Load ---
setupLoginForm();
