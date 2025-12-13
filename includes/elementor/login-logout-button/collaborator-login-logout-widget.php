<?php
/**
 * Elementor Widget: Collaborator Login/Logout Button
 *
 * Provides a login/logout button for collaborators in Elementor.
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Collaborator_Login_Logout_Widget extends Widget_Base
{
  public function get_name()
  {
    return 'collaborator-login-logout';
  }

  public function get_title()
  {
    return __('Collaborator Login/Logout', 'elite-enterprise');
  }

  public function get_icon()
  {
    return 'eicon-lock-user';
  }

  public function get_categories()
  {
    return ['collaborator-widgets'];
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
    $is_collaborator = Collaborator::is_user_collaborator($current_user->ID);
    $login_url = Collaborator::get_login_url();
    $logout_url = wp_logout_url(Collaborator::get_login_url());

    // Output widget container with data attributes for JavaScript
    echo '<div class="collaborator-login-logout-widget" data-login-url="' . esc_attr($login_url) . '" data-logout-url="' . esc_attr($logout_url) . '">';

    if ($is_logged_in && $is_collaborator) {
      // Show logout button as button element (no href to prevent navigation)
      echo '<button type="button" class="btn btn-logout" data-logout-url="' . esc_attr($logout_url) . '">Logout</button>';
    } else {
      // Show login button
      echo '<a href="' . esc_url($login_url) . '" class="btn btn-login">Login</a>';
    }

    echo '</div>';

    // Add inline script to update button state dynamically
    ?>
    <script>
      (function () {
        function updateCollaboratorLoginLogoutButtons() {
          const widgets = document.querySelectorAll('.collaborator-login-logout-widget');
          if (!widgets.length) return;

          const isLoggedIn = document.body.classList.contains('logged-in');

          widgets.forEach(widget => {
            const loginUrl = widget.dataset.loginUrl;
            const logoutUrl = widget.dataset.logoutUrl;

            if (isLoggedIn) {
              // Show logout button as button element (no href to prevent navigation)
              widget.innerHTML = '<button type="button" class="btn btn-logout" data-logout-url="' + logoutUrl +
                '">Logout</button>';
            } else {
              // Show login button
              widget.innerHTML = '<a href="' + loginUrl + '" class="btn btn-login">Login</a>';
            }
          });

          // Re-initialize logout handlers after updating widget content
          if (window.initCollaboratorLogout) {
            window.initCollaboratorLogout();
          }
        }

        // Make function globally available
        window.updateCollaboratorLoginLogoutWidgets = updateCollaboratorLoginLogoutButtons;

        // Update on page load
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', updateCollaboratorLoginLogoutButtons);
        } else {
          updateCollaboratorLoginLogoutButtons();
        }

        // Update after Barba.js transitions
        if (window.barba) {
          window.barba.hooks.after(() => {
            updateCollaboratorLoginLogoutButtons();
          });
        }
      })();
    </script>
    <?php
  }
}
