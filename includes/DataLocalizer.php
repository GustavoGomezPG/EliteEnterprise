<?php
/**
 * Data Localizer Class
 * Shares data between backend (PHP) and frontend (JavaScript)
 *
 * @package EliteEnterpriseTheme
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

class DataLocalizer
{
  /**
   * Constructor
   */
  public function __construct()
  {
    add_action('wp_enqueue_scripts', array($this, 'localize_data'));
    add_action('wp_head', array($this, 'add_wpdata_early'), 1);
  }

  /**
   * Add wpData to head early for member login/logout functionality
   */
  public function add_wpdata_early()
  {
    $wp_data = array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      // Use user ID -1 for login nonce to make it work for both logged-in and logged-out users
      'loginNonce' => wp_create_nonce('member_login_action_-1'),
      'logoutNonce' => wp_create_nonce('member_logout_action'),
    );
    ?>
    <script>
      window.wpData = <?php echo wp_json_encode($wp_data); ?>;
    </script>
    <?php
  }

  /**
   * Localize data for frontend JavaScript
   */
  public function localize_data()
  {
    // Define the name of the data object in JavaScript
    $script_name = 'backend_data';

    // Prepare data to pass from PHP to JS
    $theme_data = array(
      'site_info' => array(
        'site_url' => get_site_url(),
        'theme_url' => get_template_directory_uri(),
        'ajax_url' => admin_url('admin-ajax.php'),
      ),
      'theme_settings' => array(
        'theme_name' => 'EliteEnterprise',
        'theme_version' => HELLO_ELEMENTOR_VERSION,
      ),
    );

    // Register an empty script to attach our data to
    wp_register_script($script_name, '');
    wp_enqueue_script($script_name);

    // Add inline script that creates window.backend_data object
    wp_add_inline_script(
      $script_name,
      'window.' . $script_name . ' = ' . wp_json_encode($theme_data),
      'before'
    );

    // Create wpData object for member login/logout functionality
    $wp_data = array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      // Use user ID -1 for login nonce to make it work for both logged-in and logged-out users
      'loginNonce' => wp_create_nonce('member_login_action_-1'),
      'logoutNonce' => wp_create_nonce('member_logout_action'),
    );

    // Register and add wpData
    $wpdata_script = 'wpdata';
    wp_register_script($wpdata_script, '');
    wp_enqueue_script($wpdata_script);
    wp_add_inline_script(
      $wpdata_script,
      'window.wpData = ' . wp_json_encode($wp_data),
      'before'
    );
  }
}

// Initialize the DataLocalizer
new DataLocalizer();