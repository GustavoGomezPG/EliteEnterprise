/**
 * Member Logout AJAX Handler
 * Handles logout action via AJAX for Barba.js compatibility
 */

// Use event delegation to handle logout clicks
document.addEventListener('click', async function(e) {
  // Check if clicked element is a logout button or link
  // Also check if the clicked element itself or any parent is a logout link
  let logoutElement = e.target.closest('button.btn-logout, a.btn-logout, a[href*="wp-login.php?action=logout"]');

  // Also check if the element itself is a link with logout in href
  if (!logoutElement && e.target.tagName === 'A' && e.target.href && e.target.href.includes('action=logout')) {
    logoutElement = e.target;
  }

  if (!logoutElement) return;

  e.preventDefault();
  e.stopPropagation();
  e.stopImmediatePropagation(); // Stop ALL other handlers

  // Check if wpData is available
  if (!window.wpData || !window.wpData.ajaxUrl || !window.wpData.logoutNonce) {
    // For buttons, get URL from data attribute; for links, use href
    const fallbackUrl = logoutElement.dataset.logoutUrl || logoutElement.getAttribute('href');
    if (fallbackUrl) {
      window.location.href = fallbackUrl;
    }
    return;
  }

  // Show loading state
  const originalText = logoutElement.textContent;
  logoutElement.textContent = 'Logging out...';
  logoutElement.style.pointerEvents = 'none';
  logoutElement.disabled = true; // For button elements

  try {
    const response = await fetch(window.wpData.ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'member_logout',
        nonce: window.wpData.logoutNonce
      })
    });

    const data = await response.json();

    if (data.success) {
      // Store logout state to persist across page transition
      sessionStorage.setItem('just_logged_out', 'true');

      // Remove logged-in class from body
      document.body.classList.remove('logged-in');

      // Update login/logout widgets immediately
      if (window.updateLoginLogoutWidgets) {
        window.updateLoginLogoutWidgets();
      }

      // Update dynamic menus
      if (window.updateDynamicMenus) {
        await window.updateDynamicMenus();
      }

      // Success! Use Barba.js to navigate to home
      if (window.barba) {
        window.barba.go(data.data.redirect_url);
      } else {
        // Fallback if Barba.js is not available
        window.location.href = data.data.redirect_url;
      }
    } else {
      // Restore button state
      logoutElement.textContent = originalText;
      logoutElement.style.pointerEvents = '';
      logoutElement.disabled = false;
      // Fallback to standard logout
      const fallbackUrl = logoutElement.dataset.logoutUrl || logoutElement.getAttribute('href');
      if (fallbackUrl) {
        window.location.href = fallbackUrl;
      }
    }
  } catch {
    // Restore button state
    logoutElement.textContent = originalText;
    logoutElement.style.pointerEvents = '';
    logoutElement.disabled = false;
    // Fallback to standard logout
    const fallbackUrl = logoutElement.dataset.logoutUrl || logoutElement.getAttribute('href');
    if (fallbackUrl) {
      window.location.href = fallbackUrl;
    }
  }
}, true); // Use capture phase to intercept before other handlers

function initMemberLogout() {
  // Keep this function for compatibility but it's no longer needed
  // Event delegation above handles all logout clicks automatically
  window.initMemberLogout = initMemberLogout;
}

// Initialize on DOMContentLoaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMemberLogout);
} else {
  initMemberLogout();
}

// Re-initialize after Barba.js transitions
if (window.barba) {
  window.barba.hooks.after(() => {
    initMemberLogout();

    // Check if user just logged out
    if (sessionStorage.getItem('just_logged_out') === 'true') {
      sessionStorage.removeItem('just_logged_out');
      // Remove logged-in class that might have been added by server
      document.body.classList.remove('logged-in');
      // Update widgets to reflect logged-out state
      if (window.updateLoginLogoutWidgets) {
        window.updateLoginLogoutWidgets();
      }
      // Update dynamic menus
      if (window.updateDynamicMenus) {
        window.updateDynamicMenus();
      }
    }
  });
}
