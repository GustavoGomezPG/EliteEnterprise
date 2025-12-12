<?php
/**
 * Elementor Widget: Dynamic Menu
 *
 * Displays different WordPress menus based on user login status and role.
 * Allows configuration of menus for logged-out users, logged-in users by role.
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Dynamic_Menu_Widget extends Widget_Base
{
  public function get_name()
  {
    return 'dynamic-menu';
  }

  public function get_title()
  {
    return __('Dynamic Menu', 'elite-enterprise');
  }

  public function get_icon()
  {
    return 'eicon-nav-menu';
  }

  public function get_categories()
  {
    return ['member-widgets'];
  }

  /**
   * Get all registered WordPress menus
   *
   * @return array Menu options for select control
   */
  private function get_available_menus()
  {
    $menus = wp_get_nav_menus();
    $options = ['' => __('— Select Menu —', 'elite-enterprise')];

    foreach ($menus as $menu) {
      $options[$menu->term_id] = $menu->name;
    }

    return $options;
  }

  /**
   * Get all WordPress user roles
   *
   * @return array Role options for select control
   */
  private function get_user_roles()
  {
    global $wp_roles;
    $roles = $wp_roles->roles;
    $options = [];

    foreach ($roles as $role_key => $role) {
      $options[$role_key] = $role['name'];
    }

    return $options;
  }

  protected function register_controls()
  {
    // Logged Out Users Section
    $this->start_controls_section(
      'section_logged_out',
      [
        'label' => __('Logged Out Users', 'elite-enterprise'),
        'tab' => Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'logged_out_menu',
      [
        'label' => __('Menu for Logged Out Users', 'elite-enterprise'),
        'type' => Controls_Manager::SELECT,
        'options' => $this->get_available_menus(),
        'default' => '',
        'description' => __('Select the menu to display for logged-out users', 'elite-enterprise'),
      ]
    );

    $this->end_controls_section();

    // Logged In Users Section
    $this->start_controls_section(
      'section_logged_in',
      [
        'label' => __('Logged In Users', 'elite-enterprise'),
        'tab' => Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'logged_in_menu',
      [
        'label' => __('Default Menu for Logged In Users', 'elite-enterprise'),
        'type' => Controls_Manager::SELECT,
        'options' => $this->get_available_menus(),
        'default' => '',
        'description' => __('Default menu for logged-in users (fallback if no role-specific menu is set)', 'elite-enterprise'),
      ]
    );

    $this->end_controls_section();

    // Role-Specific Menus Section
    $this->start_controls_section(
      'section_role_menus',
      [
        'label' => __('Role-Specific Menus', 'elite-enterprise'),
        'tab' => Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'enable_role_menus',
      [
        'label' => __('Enable Role-Specific Menus', 'elite-enterprise'),
        'type' => Controls_Manager::SWITCHER,
        'default' => 'no',
        'description' => __('Enable different menus for different user roles', 'elite-enterprise'),
      ]
    );

    // Add controls for each role
    $roles = $this->get_user_roles();
    foreach ($roles as $role_key => $role_name) {
      $this->add_control(
        'menu_' . $role_key,
        [
          'label' => sprintf(__('Menu for %s', 'elite-enterprise'), $role_name),
          'type' => Controls_Manager::SELECT,
          'options' => $this->get_available_menus(),
          'default' => '',
          'condition' => [
            'enable_role_menus' => 'yes',
          ],
        ]
      );
    }

    $this->end_controls_section();

    // Menu Display Settings
    $this->start_controls_section(
      'section_menu_settings',
      [
        'label' => __('Menu Settings', 'elite-enterprise'),
        'tab' => Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'menu_container',
      [
        'label' => __('Menu Container', 'elite-enterprise'),
        'type' => Controls_Manager::SELECT,
        'options' => [
          'div' => 'DIV',
          'nav' => 'NAV',
          'ul' => 'UL',
          '' => __('None', 'elite-enterprise'),
        ],
        'default' => 'nav',
        'description' => __('HTML element to wrap the menu', 'elite-enterprise'),
      ]
    );

    $this->add_control(
      'menu_class',
      [
        'label' => __('Menu CSS Class', 'elite-enterprise'),
        'type' => Controls_Manager::TEXT,
        'default' => 'dynamic-menu',
        'description' => __('Additional CSS classes for the menu', 'elite-enterprise'),
      ]
    );

    $this->end_controls_section();
  }

  /**
   * Get the menu ID to display based on user status and role
   *
   * @return int|null Menu ID or null if no menu should be displayed
   */
  private function get_menu_id_for_user()
  {
    $settings = $this->get_settings_for_display();

    // Check if user is logged in
    if (!is_user_logged_in()) {
      return !empty($settings['logged_out_menu']) ? (int) $settings['logged_out_menu'] : null;
    }

    // User is logged in - check for role-specific menus if enabled
    if ($settings['enable_role_menus'] === 'yes') {
      $current_user = wp_get_current_user();

      // Check each role the user has
      foreach ($current_user->roles as $role) {
        $menu_key = 'menu_' . $role;
        if (!empty($settings[$menu_key])) {
          return (int) $settings[$menu_key];
        }
      }
    }

    // Fallback to default logged-in menu
    return !empty($settings['logged_in_menu']) ? (int) $settings['logged_in_menu'] : null;
  }

  protected function render()
  {
    $settings = $this->get_settings_for_display();
    $menu_id = $this->get_menu_id_for_user();

    if (!$menu_id) {
      if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
        echo '<div class="elementor-alert elementor-alert-warning">';
        echo __('Please select a menu in the widget settings.', 'elite-enterprise');
        echo '</div>';
      }
      return;
    }

    // Prepare menu arguments
    $menu_args = [
      'menu' => $menu_id,
      'container' => $settings['menu_container'],
      'menu_class' => $settings['menu_class'],
      'echo' => false,
      'fallback_cb' => false,
    ];

    // Get the menu HTML
    $menu_html = wp_nav_menu($menu_args);

    // Output the menu with data attributes for JavaScript updates
    $is_logged_in = is_user_logged_in();
    $current_user = wp_get_current_user();
    $user_roles = $is_logged_in ? implode(',', $current_user->roles) : '';

    echo '<div class="dynamic-menu-widget" data-widget-settings="' . esc_attr(wp_json_encode($settings)) . '" data-is-logged-in="' . ($is_logged_in ? 'true' : 'false') . '" data-user-roles="' . esc_attr($user_roles) . '">';
    echo $menu_html;
    echo '</div>';

    // Add inline script to update menu dynamically after Barba.js transitions
    ?>
<script>
(function() {
  async function updateDynamicMenus() {
    const menuWidgets = document.querySelectorAll('.dynamic-menu-widget');
    if (!menuWidgets.length) return;

    const isLoggedIn = document.body.classList.contains('logged-in');

    // Process each widget
    for (const widget of menuWidgets) {
      const currentIsLoggedIn = widget.dataset.isLoggedIn === 'true';

      // Only update if login status has changed
      if (currentIsLoggedIn !== isLoggedIn) {
        try {
          const settings = JSON.parse(widget.dataset.widgetSettings);

          // Check if wpData is available
          if (!window.wpData || !window.wpData.ajaxUrl) {
            console.error('wpData not available for dynamic menu update');
            continue;
          }

          const response = await fetch(window.wpData.ajaxUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
              action: 'get_dynamic_menu',
              settings: JSON.stringify(settings)
            })
          });

          const data = await response.json();

          if (data.success && data.data.menu_html) {
            // Update the widget content with new menu
            widget.innerHTML = data.data.menu_html;

            // Update the logged-in status
            widget.dataset.isLoggedIn = isLoggedIn ? 'true' : 'false';

            // Update user roles if logged in
            if (isLoggedIn) {
              // Try to get user roles from body classes
              const bodyClasses = document.body.className.split(' ');
              const roleClasses = bodyClasses.filter(cls => cls.startsWith('role-'));
              const roles = roleClasses.map(cls => cls.replace('role-', ''));
              widget.dataset.userRoles = roles.join(',');
            } else {
              widget.dataset.userRoles = '';
            }
          } else {
            console.error('Failed to fetch dynamic menu:', data.data ? data.data.message : 'Unknown error');
          }
        } catch (error) {
          console.error('Error updating dynamic menu:', error);
        }
      }
    }
  }

  // Make function globally available
  window.updateDynamicMenus = updateDynamicMenus;

  // Update on page load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateDynamicMenus);
  } else {
    updateDynamicMenus();
  }

  // Update after Barba.js transitions
  if (window.barba) {
    window.barba.hooks.after(() => {
      updateDynamicMenus();
    });
  }
})();
</script>
<?php
  }

  protected function content_template()
  {
    ?>
<# if (!settings.logged_out_menu && !settings.logged_in_menu) { #>
  <div class="elementor-alert elementor-alert-warning">
    <?php echo __('Please select at least one menu in the widget settings.', 'elite-enterprise'); ?>
  </div>
  <# } else { #>
    <div class="dynamic-menu-widget">
      <p><?php echo __('Menu will be displayed based on user login status and role.', 'elite-enterprise'); ?></p>
      <p><strong><?php echo __('Logged Out Menu:', 'elite-enterprise'); ?></strong> {{ settings.logged_out_menu ?
            'Selected' : 'Not set' }}</p>
      <p><strong><?php echo __('Logged In Menu:', 'elite-enterprise'); ?></strong> {{ settings.logged_in_menu ?
            'Selected' : 'Not set' }}</p>
      <# if (settings.enable_role_menus==='yes' ) { #>
        <p><strong><?php echo __('Role-specific menus:', 'elite-enterprise'); ?></strong> Enabled</p>
        <# } #>
    </div>
    <# } #>
      <?php
  }
}