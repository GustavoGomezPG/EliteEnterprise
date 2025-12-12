<?php
/**
 * Elementor Widget: Member Login/Logout Button
 *
 * Provides a login/logout button for use in Elementor, following the Member system's routing and logic.
 * Place this file in the theme's includes/ directory and load it from functions.php.
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Member_Login_Logout_Widget extends Widget_Base
{
  public function get_name()
  {
    return 'member-login-logout';
  }

  public function get_title()
  {
    return __('Member Login/Logout', 'elite-enterprise');
  }

  public function get_icon()
  {
    return 'eicon-lock-user';
  }

  public function get_categories()
  {
    return ['member-widgets'];
  }

  protected function register_controls()
  {
    $this->start_controls_section(
      'section_content',
      [
        'label' => __('Content', 'elite-enterprise'),
        'tab' => Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'show_icon',
      [
        'label' => __('Show Icon', 'elite-enterprise'),
        'type' => Controls_Manager::SWITCHER,
        'default' => 'yes',
      ]
    );

    $this->end_controls_section();
  }

  protected function render()
  {
    $is_logged_in = is_user_logged_in();
    $current_user = wp_get_current_user();
    $is_member = Member::is_user_member($current_user->ID);
    $login_url = Member::get_login_url();
    $logout_url = wp_logout_url(home_url());

    // Output widget container with data attributes for JavaScript
    echo '<div class="member-login-logout-widget" data-login-url="' . esc_attr($login_url) . '" data-logout-url="' . esc_attr($logout_url) . '">';

    if ($is_logged_in && $is_member) {
      // Show logout button as button element (no href to prevent navigation)
      echo '<button type="button" class="btn btn-logout" data-logout-url="' . esc_attr($logout_url) . '">Logout</button>';
    } else {
      // Show login button
      echo '<a href="' . esc_url($login_url) . '" class="btn btn-login">Member Login</a>';
    }

    echo '</div>';

    // Add inline script to update button state dynamically
    ?>
    <script>
    (function() {
      function updateLoginLogoutButtons() {
        const widgets = document.querySelectorAll('.member-login-logout-widget');
        if (!widgets.length) return;

        const isLoggedIn = document.body.classList.contains('logged-in');

        widgets.forEach(widget => {
          const loginUrl = widget.dataset.loginUrl;
          const logoutUrl = widget.dataset.logoutUrl;

          if (isLoggedIn) {
            // Show logout button as button element (no href to prevent navigation)
            widget.innerHTML = '<button type="button" class="btn btn-logout" data-logout-url="' + logoutUrl + '">Logout</button>';
          } else {
            // Show login button
            widget.innerHTML = '<a href="' + loginUrl + '" class="btn btn-login">Member Login</a>';
          }
        });

        // Re-initialize logout handlers after updating widget content
        if (window.initMemberLogout) {
          window.initMemberLogout();
        }
      }

      // Make function globally available
      window.updateLoginLogoutWidgets = updateLoginLogoutButtons;

      // Update on page load
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateLoginLogoutButtons);
      } else {
        updateLoginLogoutButtons();
      }

      // Update after Barba.js transitions
      if (window.barba) {
        window.barba.hooks.after(() => {
          updateLoginLogoutButtons();
        });
      }
    })();
    </script>
    <?php
  }
}