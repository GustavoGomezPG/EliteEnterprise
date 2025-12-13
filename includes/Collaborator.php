<?php
/**
 * Collaborator Class
 *
 * Extends WordPress user functionality to create and manage Collaborator role.
 * Collaborators are external users with limited access to intake forms for creating new members.
 * They cannot access WordPress admin and only have access to /collaborator pages.
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}


class Collaborator
{
  /**
   * Role name
   *
   * @var string
   */
  private const ROLE_NAME = 'collaborator';

  /**
   * Role display name
   *
   * @var string
   */
  private const ROLE_DISPLAY_NAME = 'Collaborator';

  /**
   * Collaborator dashboard page slug
   *
   * @var string
   */
  private const DASHBOARD_SLUG = 'collaborator';

  /**
   * ACF field group key
   *
   * @var string
   */
  private const ACF_GROUP_KEY = 'group_collaborator_fields';

  /**
   * Initialize the Collaborator class
   */
  public function __construct()
  {
    // Register custom role on theme activation and init
    add_action('after_switch_theme', [$this, 'register_collaborator_role']);
    add_action('init', [$this, 'ensure_collaborator_role_exists']);

    // Block admin access for collaborators
    add_action('admin_init', [$this, 'block_admin_access']);

    // Remove admin bar for collaborators
    add_action('after_setup_theme', [$this, 'remove_admin_bar']);

    // Redirect collaborators after login
    add_filter('login_redirect', [$this, 'redirect_after_login'], 10, 3);

    // Load ACF JSON fields
    add_filter('acf/settings/load_json', [$this, 'load_acf_json']);
    add_filter('acf/settings/save_json', [$this, 'save_acf_json']);

    // Check for required pages and show admin notice
    add_action('admin_notices', [$this, 'check_required_pages']);

    // Handle page creation AJAX
    add_action('wp_ajax_create_collaborator_pages', [$this, 'ajax_create_pages']);

    // Lock collaborator pages from editing
    add_filter('user_has_cap', [$this, 'restrict_collaborator_page_editing'], 10, 3);
    add_action('admin_notices', [$this, 'show_locked_page_notice']);
    add_filter('page_row_actions', [$this, 'remove_collaborator_page_actions'], 10, 2);

    // Redirect logged-out users from protected collaborator pages
    add_action('template_redirect', [$this, 'protect_collaborator_pages']);
  }

  /**
   * Ensure collaborator role exists on every init
   * This checks if the role exists and creates it if missing
   *
   * @return void
   */
  public function ensure_collaborator_role_exists()
  {
    // Check if role exists
    $role = get_role(self::ROLE_NAME);

    // If role doesn't exist, register it
    if (!$role) {
      $this->register_collaborator_role();
    }
  }

  /**
   * Register the Collaborator role with appropriate capabilities
   *
   * @return void
   */
  public function register_collaborator_role()
  {
    // Define collaborator capabilities - very limited
    $capabilities = [
      'read' => true,
      'edit_posts' => false,
      'delete_posts' => false,
      'publish_posts' => false,
      'upload_files' => false,
    ];

    // Check if role already exists
    $role = get_role(self::ROLE_NAME);

    if ($role) {
      // Role exists, update its capabilities
      foreach ($capabilities as $cap => $grant) {
        if ($grant) {
          $role->add_cap($cap);
        } else {
          $role->remove_cap($cap);
        }
      }
    } else {
      // Add the collaborator role
      add_role(
        self::ROLE_NAME,
        __(self::ROLE_DISPLAY_NAME, 'elite-enterprise'),
        $capabilities
      );
    }
  }

  /**
   * Block collaborators from accessing WordPress admin
   *
   * CRITICAL: Redirects collaborators attempting to access admin pages
   *
   * @return void
   */
  public function block_admin_access()
  {
    // Check if current user is a collaborator
    if (!$this->is_collaborator()) {
      return;
    }

    // Allow AJAX requests
    if (defined('DOING_AJAX') && DOING_AJAX) {
      return;
    }

    // Redirect to collaborator dashboard
    wp_redirect(home_url('/' . self::DASHBOARD_SLUG));
    exit;
  }

  /**
   * Remove admin bar for collaborators on front-end
   *
   * CRITICAL: Prevents admin bar from showing for collaborators
   *
   * @return void
   */
  public function remove_admin_bar()
  {
    if ($this->is_collaborator()) {
      show_admin_bar(false);
    }
  }

  /**
   * Redirect collaborators to dashboard after login
   *
   * CRITICAL: Ensures collaborators land on their dashboard, not admin
   *
   * @param string $redirect_to URL to redirect to
   * @param string $request Requested redirect URL
   * @param WP_User|WP_Error $user User object or WP_Error
   * @return string Modified redirect URL
   */
  public function redirect_after_login($redirect_to, $request, $user)
  {
    // Check if user is logged in and is a collaborator
    if (isset($user->roles) && is_array($user->roles)) {
      if (in_array(self::ROLE_NAME, $user->roles)) {
        return home_url('/' . self::DASHBOARD_SLUG . '/intake-form');
      }
    }

    return $redirect_to;
  }

  /**
   * Check if current user is a collaborator
   *
   * @return bool True if current user has collaborator role
   */
  private function is_collaborator()
  {
    if (!is_user_logged_in()) {
      return false;
    }

    $user = wp_get_current_user();
    return in_array(self::ROLE_NAME, (array) $user->roles);
  }

  /**
   * Load ACF JSON fields from custom location
   *
   * @param array $paths Existing ACF JSON paths
   * @return array Modified paths array
   */
  public function load_acf_json($paths)
  {
    // Add our custom ACF JSON directory
    $paths[] = get_template_directory() . '/acf-json';
    return $paths;
  }

  /**
   * Save ACF JSON fields to custom location
   *
   * @param string $path Default save path
   * @return string Modified save path
   */
  public function save_acf_json($path)
  {
    // Save to our custom ACF JSON directory
    return get_template_directory() . '/acf-json';
  }

  /**
   * Get collaborator custom field value
   *
   * @param string $field_name ACF field name
   * @param int $user_id User ID (defaults to current user)
   * @return mixed Field value
   */
  public static function get_field($field_name, $user_id = null)
  {
    if (!$user_id) {
      $user_id = get_current_user_id();
    }

    return get_field($field_name, 'user_' . $user_id);
  }

  /**
   * Update collaborator custom field value
   *
   * @param string $field_name ACF field name
   * @param mixed $value Field value
   * @param int $user_id User ID (defaults to current user)
   * @return bool Success status
   */
  public static function update_field($field_name, $value, $user_id = null)
  {
    if (!$user_id) {
      $user_id = get_current_user_id();
    }

    return update_field($field_name, $value, 'user_' . $user_id);
  }

  /**
   * Get collaborator dashboard URL
   *
   * @return string Dashboard URL
   */
  public static function get_dashboard_url()
  {
    return home_url('/' . self::DASHBOARD_SLUG);
  }

  /**
   * Get collaborator login URL
   *
   * @return string Login URL
   */
  public static function get_login_url()
  {
    return home_url('/' . self::DASHBOARD_SLUG . '/login');
  }

  /**
   * Get collaborator intake form URL
   *
   * @return string Intake form URL
   */
  public static function get_intake_form_url()
  {
    return home_url('/' . self::DASHBOARD_SLUG . '/intake-form');
  }

  /**
   * Check if user has collaborator role
   *
   * @param int $user_id User ID (defaults to current user)
   * @return bool True if user is a collaborator
   */
  public static function is_user_collaborator($user_id = null)
  {
    if (!$user_id) {
      $user_id = get_current_user_id();
    }

    $user = get_userdata($user_id);
    if (!$user) {
      return false;
    }

    return in_array(self::ROLE_NAME, (array) $user->roles);
  }

  /**
   * Get required collaborator pages configuration
   *
   * @return array Array of required pages
   */
  private function get_required_pages()
  {
    return [
      'collaborator' => [
        'title' => 'Collaborator',
        'slug' => 'collaborator',
        'content' => '<!-- wp:paragraph --><p>Collaborator area.</p><!-- /wp:paragraph -->',
        'template' => 'page-collaborator.php',
      ],
      'collaborator-login' => [
        'title' => 'Collaborator Login',
        'slug' => 'login',
        'content' => '<!-- wp:paragraph --><p>Collaborator login page.</p><!-- /wp:paragraph -->',
        'template' => 'page-collaborator-login.php',
        'parent' => 'collaborator',
      ],
      'intake-form' => [
        'title' => 'Intake Form',
        'slug' => 'intake-form',
        'content' => '<!-- wp:paragraph --><p>Member intake form page.</p><!-- /wp:paragraph -->',
        'template' => 'page-collaborator-intake-form.php',
        'parent' => 'collaborator',
      ],
    ];
  }

  /**
   * Check if required pages exist and show admin notice
   *
   * @return void
   */
  public function check_required_pages()
  {
    // Only show to administrators
    if (!current_user_can('manage_options')) {
      return;
    }

    $required_pages = $this->get_required_pages();
    $missing_pages = [];

    foreach ($required_pages as $key => $page_config) {
      $parent = isset($page_config['parent']) ? $page_config['parent'] : '';
      $page = get_page_by_path($parent ? $parent . '/' . $page_config['slug'] : $page_config['slug']);
      if (!$page) {
        $missing_pages[] = $page_config['title'];
      }
    }

    if (!empty($missing_pages)) {
      ?>
<div class="notice notice-warning is-dismissible">
  <p>
    <strong>Collaborator System Setup Required:</strong>
    The following required pages are missing: <strong><?php echo implode(', ', $missing_pages); ?></strong>
  </p>
  <p>
    <button type="button" class="button button-primary" id="create-collaborator-pages">
      Create Missing Pages
    </button>
    <span class="spinner" style="float: none; margin: 0 10px;"></span>
    <span id="collaborator-pages-status"></span>
  </p>
</div>
<script>
jQuery(document).ready(function($) {
  $('#create-collaborator-pages').on('click', function() {
    var $button = $(this);
    var $spinner = $button.next('.spinner');
    var $status = $('#collaborator-pages-status');

    $button.prop('disabled', true);
    $spinner.addClass('is-active');
    $status.text('Creating pages...');

    $.post(ajaxurl, {
      action: 'create_collaborator_pages',
      nonce: '<?php echo wp_create_nonce('create_collaborator_pages'); ?>'
    }, function(response) {
      $spinner.removeClass('is-active');

      if (response.success) {
        $status.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
        setTimeout(function() {
          location.reload();
        }, 1500);
      } else {
        $status.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
        $button.prop('disabled', false);
      }
    });
  });
});
</script>
<?php
    }
  }

  /**
   * AJAX handler to create missing collaborator pages
   *
   * @return void
   */
  public function ajax_create_pages()
  {
    check_ajax_referer('create_collaborator_pages', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Permission denied.']);
    }

    $required_pages = $this->get_required_pages();
    $created = [];
    $parent_ids = [];

    foreach ($required_pages as $key => $page_config) {
      // Check if page already exists
      $full_slug = $page_config['parent'] ? $page_config['parent'] . '/' . $page_config['slug'] : $page_config['slug'];
      $existing_page = get_page_by_path($full_slug);

      if (!$existing_page) {
        // Get parent page ID if this page has a parent
        $parent_id = 0;
        if (!empty($page_config['parent'])) {
          if (isset($parent_ids[$page_config['parent']])) {
            $parent_id = $parent_ids[$page_config['parent']];
          } else {
            $parent_page = get_page_by_path($page_config['parent']);
            if ($parent_page) {
              $parent_id = $parent_page->ID;
            }
          }
        }

        // Create the page
        $page_data = [
          'post_title' => $page_config['title'],
          'post_name' => $page_config['slug'],
          'post_content' => $page_config['content'],
          'post_status' => 'publish',
          'post_type' => 'page',
          'post_parent' => $parent_id,
        ];

        $page_id = wp_insert_post($page_data);

        if ($page_id && !is_wp_error($page_id)) {
          // Store parent ID for child pages
          $parent_ids[$key] = $page_id;

          // Set page template if specified
          if (!empty($page_config['template'])) {
            update_post_meta($page_id, '_wp_page_template', $page_config['template']);
          }

          $created[] = $page_config['title'];
        }
      } else {
        // Store existing parent ID
        if (empty($page_config['parent'])) {
          $parent_ids[$key] = $existing_page->ID;
        }
      }
    }

    if (!empty($created)) {
      wp_send_json_success([
        'message' => 'Successfully created: ' . implode(', ', $created)
      ]);
    } else {
      wp_send_json_success([
        'message' => 'All required pages already exist.'
      ]);
    }
  }

  /**
   * Get array of collaborator page IDs
   *
   * @return array Array of page IDs
   */
  private function get_collaborator_page_ids()
  {
    $page_ids = [];
    $required_pages = $this->get_required_pages();

    foreach ($required_pages as $key => $page_config) {
      $parent = isset($page_config['parent']) ? $page_config['parent'] : '';
      $page = get_page_by_path($parent ? $parent . '/' . $page_config['slug'] : $page_config['slug']);
      if ($page) {
        $page_ids[] = $page->ID;
      }
    }

    return $page_ids;
  }

  /**
   * Check if a page is a collaborator system page
   *
   * @param int $page_id Page ID to check
   * @return bool True if it's a collaborator page
   */
  private function is_collaborator_page($page_id)
  {
    return in_array($page_id, $this->get_collaborator_page_ids());
  }

  /**
   * Restrict editing capabilities for collaborator pages
   *
   * @param array $allcaps All capabilities
   * @param array $caps Required capabilities
   * @param array $args Additional arguments
   * @return array Modified capabilities
   */
  public function restrict_collaborator_page_editing($allcaps, $caps, $args)
  {
    // Only apply to edit_post and delete_post capabilities
    if (!isset($args[0]) || !in_array($args[0], ['edit_post', 'delete_post'])) {
      return $allcaps;
    }

    // Check if there's a post ID
    if (!isset($args[2])) {
      return $allcaps;
    }

    $post_id = $args[2];
    $post = get_post($post_id);

    // Only apply to pages
    if (!$post || $post->post_type !== 'page') {
      return $allcaps;
    }

    // Check if this is a collaborator page
    if ($this->is_collaborator_page($post_id)) {
      // Remove edit and delete capabilities for this specific page
      $allcaps['edit_post'] = false;
      $allcaps['delete_post'] = false;
      $allcaps['edit_page'] = false;
      $allcaps['delete_page'] = false;
    }

    return $allcaps;
  }

  /**
   * Show notice on locked collaborator pages
   *
   * @return void
   */
  public function show_locked_page_notice()
  {
    $screen = get_current_screen();

    // Only show on page edit screen
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'page') {
      return;
    }

    // Get current page ID
    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;

    if ($post_id && $this->is_collaborator_page($post_id)) {
      ?>
<div class="notice notice-info">
  <p>
    <strong>ℹ️ Theme-Controlled Page</strong><br>
    This page is part of the Collaborator System and is controlled by the theme.
    Content and functionality are managed through the page template.
    You cannot edit or delete this page directly.
  </p>
</div>
<style>
/* Hide edit interface for collaborator pages */
#post-body-content,
#edit-slug-box,
#submitdiv #publishing-action,
#submitdiv .misc-pub-post-status,
#submitdiv .misc-pub-visibility {
  opacity: 0.5;
  pointer-events: none;
}

#submitdiv #delete-action {
  display: none;
}
</style>
<?php
    }
  }

  /**
   * Remove action links from collaborator pages in page list
   *
   * @param array $actions Existing actions
   * @param WP_Post $post Post object
   * @return array Modified actions
   */
  public function remove_collaborator_page_actions($actions, $post)
  {
    if ($this->is_collaborator_page($post->ID)) {
      // Remove edit, trash, and quick edit links
      unset($actions['edit']);
      unset($actions['trash']);
      unset($actions['inline hide-if-no-js']);

      // Add a custom indicator
      $actions['theme-controlled'] = '<span style="color: #2271b1;">🔒 Theme Controlled</span>';
    }

    return $actions;
  }

  /**
   * Protect collaborator pages from logged-out users
   * Redirects to login page if user is not logged in
   * Redirects to home if user is logged in but not a collaborator
   *
   * @return void
   */
  public function protect_collaborator_pages()
  {
    // Get current page
    global $post;

    // Check if we're on a page
    if (!is_page() || !$post) {
      return;
    }

    // Get the page slug and check if it's under /collaborator/
    $page_path = get_page_uri($post->ID);

    // Only protect pages under /collaborator/ path
    if (strpos($page_path, 'collaborator') !== 0) {
      return;
    }

    // Allow access to login page for everyone
    if ($page_path === 'collaborator/login') {
      return;
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
      // Redirect to collaborator login page
      wp_redirect(self::get_login_url());
      exit;
    }

    // User is logged in - check if they are a collaborator
    $current_user = wp_get_current_user();

    // Allow admins to view the pages
    if (in_array('administrator', (array) $current_user->roles)) {
      return;
    }

    if (!self::is_user_collaborator($current_user->ID)) {
      // Not a collaborator, redirect to home
      wp_redirect(home_url());
      exit;
    }
  }
}

// Initialize the Collaborator class and store it globally to prevent garbage collection
$GLOBALS['elite_collaborator_instance'] = new Collaborator();
