/**
 * Member Login AJAX Handler
 * Handles login form submission via AJAX for Barba.js compatibility
 */

// Auto-fill username from localStorage if "Remember Me" was checked
function initRememberMe() {
  const loginForm = document.querySelector('form.member-login-form');

  if (!loginForm) return;

  const usernameInput = loginForm.querySelector('input[name="username"]');
  const rememberCheckbox = loginForm.querySelector('input[name="remember"]');

  // Load saved username from localStorage
  const savedUsername = localStorage.getItem('member_login_username');

  if (savedUsername && usernameInput) {
    usernameInput.value = savedUsername;
    if (rememberCheckbox) {
      rememberCheckbox.checked = true;
    }
  }
}

// Use event delegation to handle login form submission
document.addEventListener('submit', async function(e) {
  // Check if the submitted form is the member login form
  if (!e.target.matches('form.member-login-form')) {
    return;
  }

  e.preventDefault();
  e.stopPropagation();

  const loginForm = e.target;
  const formData = new FormData(loginForm);
  const submitButton = loginForm.querySelector('button[type="submit"]');
  const originalButtonText = submitButton.textContent;

  // Get form values
  const username = formData.get('username');
  const password = formData.get('password');
  const remember = formData.get('remember') ? 'true' : 'false';
  const rememberChecked = formData.get('remember') !== null;
  const nonce = formData.get('member_login_nonce');

  // Check for redirect_to parameter in URL
  const urlParams = new URLSearchParams(window.location.search);
  const redirectTo = urlParams.get('redirect_to') || '';

  // Disable button and show loading state
  submitButton.disabled = true;
  submitButton.textContent = 'Signing in...';

  // Remove any existing error messages
  const existingErrors = document.querySelector('.alert-danger');
  if (existingErrors) {
    existingErrors.remove();
  }

  try {
    const requestBody = {
      action: 'member_login',
      username: username,
      password: password,
      remember: remember,
      nonce: nonce
    };

    // Add redirect_to if it exists
    if (redirectTo) {
      requestBody.redirect_to = redirectTo;
    }

    const response = await fetch(window.wpData.ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(requestBody)
    });

    const data = await response.json();

    if (data.success) {
      // Handle "Remember Me" - save or clear username from localStorage
      if (rememberChecked) {
        localStorage.setItem('member_login_username', username);
      } else {
        localStorage.removeItem('member_login_username');
      }

      // Add logged-in class to body for widget detection
      document.body.classList.add('logged-in');

      // Update login/logout widgets immediately
      if (window.updateLoginLogoutWidgets) {
        window.updateLoginLogoutWidgets();
      }

      // Update dynamic menus
      if (window.updateDynamicMenus) {
        await window.updateDynamicMenus();
      }

      // Success! Use Barba.js to navigate to dashboard
      if (window.barba) {
        window.barba.go(data.data.redirect_url);
      } else {
        // Fallback if Barba.js is not available
        window.location.href = data.data.redirect_url;
      }
    } else {
      // Show error message
      const errorMessage = data.data?.message || 'Login failed. Please try again.';
      showError(errorMessage, loginForm);
      submitButton.disabled = false;
      submitButton.textContent = originalButtonText;
    }
  } catch {
    showError('An error occurred during login. Please try again.', loginForm);
    submitButton.disabled = false;
    submitButton.textContent = originalButtonText;
  }
}, true); // Use capture phase to intercept before other handlers

function showError(message, form) {
  const errorDiv = document.createElement('div');
  errorDiv.className = 'alert alert-danger mb-4';
  errorDiv.innerHTML = `
    <ul class="list-disc list-inside">
      <li>${message}</li>
    </ul>
  `;

  // Insert error before the form
  form.parentNode.insertBefore(errorDiv, form);
}

function initMemberLogin() {
  // Initialize Remember Me auto-fill
  initRememberMe();
}

// Initialize on DOMContentLoaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMemberLogin);
} else {
  initMemberLogin();
}

// Re-initialize after Barba.js transitions
if (window.barba) {
  window.barba.hooks.after(() => {
    // Re-run initRememberMe after page transition to login page
    initMemberLogin();
  });
}

// Also make initRememberMe globally available so it can be called by executeInlineScripts
window.initRememberMe = initRememberMe;
