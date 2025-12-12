<?php
/**
 * Member Class
 * 
 * Extends WordPress user functionality to create and manage Member role.
 * Handles custom fields registration and member-specific access controls.
 * 
 * @package EliteEnterprise
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}


class Member
{

  /**
   * Get member login URL
   *
   * @return string Login URL
   */
  public static function get_login_url()
  {
    // Find the login page (members/login) or fallback to wp_login_url
    $login_page = get_page_by_path('members/login');
    if ($login_page) {
      return get_permalink($login_page->ID);
    }
    return wp_login_url();
  }

  /**
   * Get member forgot password URL
   *
   * @return string Forgot password URL
   */
  public static function get_forgot_password_url()
  {
    // Find the forgot password page (members/forgot-password)
    $forgot_page = get_page_by_path('members/forgot-password');
    if ($forgot_page) {
      return get_permalink($forgot_page->ID);
    }
    return wp_lostpassword_url();
  }

  /**
   * Get member password reset URL
   *
   * @param string $user_login Username
   * @param string $reset_key Reset key
   * @return string Password reset URL
   */
  public static function get_password_reset_url($user_login, $reset_key)
  {
    // Find the password reset page (members/reset-password)
    $reset_page = get_page_by_path('members/reset-password');
    if ($reset_page) {
      return add_query_arg([
        'key' => $reset_key,
        'login' => rawurlencode($user_login)
      ], get_permalink($reset_page->ID));
    }
    return network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user_login), 'login');
  }

  /**
   * Send password reset email to member
   *
   * @param WP_User $user User object
   * @param string $reset_url Reset URL with key
   * @return bool True if email sent successfully
   */
  public static function send_password_reset_email($user, $reset_url)
  {
    $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
    $message .= sprintf(__('Site Name: %s'), $site_name) . "\r\n\r\n";
    $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
    $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
    $message .= $reset_url . "\r\n\r\n";
    $message .= __('This link will expire in 24 hours.') . "\r\n";

    $subject = sprintf(__('[%s] Password Reset Request'), $site_name);

    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    return wp_mail($user->user_email, $subject, $message, $headers);
  }

  /**
   * Log out the current user and redirect to home or custom URL
   *
   * @param string $redirect Optional. URL to redirect to after logout.
   * @return void
   */
  public static function logout_member($redirect = '')
  {
    wp_logout();
    $redirect_url = $redirect ? $redirect : home_url();
    wp_safe_redirect($redirect_url);
    exit;
  }

  /**
   * Role name
   * 
   * @var string
   */
  private const ROLE_NAME = 'member';

  /**
   * Role display name
   * 
   * @var string
   */
  private const ROLE_DISPLAY_NAME = 'Member';

  /**
   * Member dashboard page slug
   * 
   * @var string
   */
  private const DASHBOARD_SLUG = 'members/dashboard';

  /**
   * ACF field group key
   * 
   * @var string
   */
  private const ACF_GROUP_KEY = 'group_member_fields';

  /**
   * Initialize the Member class
   */
  public function __construct()
  {
    // Register custom role on theme activation and init
    add_action('after_switch_theme', [$this, 'register_member_role']);
    add_action('init', [$this, 'ensure_member_role_exists']);

    // Block admin access for members
    add_action('admin_init', [$this, 'block_admin_access']);

    // Remove admin bar for members
    add_action('after_setup_theme', [$this, 'remove_admin_bar']);

    // Redirect members after login
    add_filter('login_redirect', [$this, 'redirect_after_login'], 10, 3);

    // Load ACF JSON fields
    add_filter('acf/settings/load_json', [$this, 'load_acf_json']);
    add_filter('acf/settings/save_json', [$this, 'save_acf_json']);

    // Check for required pages and show admin notice
    add_action('admin_notices', [$this, 'check_required_pages']);

    // Handle page creation AJAX
    add_action('wp_ajax_create_member_pages', [$this, 'ajax_create_pages']);

    // Handle member login AJAX - register for BOTH logged-in and non-logged-in users
    // This is needed because admin users might have a WP session but still need to log in as members
    add_action('wp_ajax_member_login', [$this, 'ajax_member_login']);
    add_action('wp_ajax_nopriv_member_login', [$this, 'ajax_member_login']);

    // Handle member logout AJAX
    add_action('wp_ajax_member_logout', [$this, 'ajax_member_logout']);

    // Handle dynamic menu AJAX
    add_action('wp_ajax_get_dynamic_menu', [$this, 'ajax_get_dynamic_menu']);
    add_action('wp_ajax_nopriv_get_dynamic_menu', [$this, 'ajax_get_dynamic_menu']);

    // Lock member pages from editing
    add_filter('user_has_cap', [$this, 'restrict_member_page_editing'], 10, 3);
    add_action('admin_notices', [$this, 'show_locked_page_notice']);
    add_filter('page_row_actions', [$this, 'remove_member_page_actions'], 10, 2);

    // Redirect logged-out users from protected member pages
    add_action('template_redirect', [$this, 'protect_member_pages']);
  }

  /**
   * Ensure member role exists on every init
   * This checks if the role exists and creates it if missing
   * 
   * @return void
   */
  public function ensure_member_role_exists()
  {
    // Check if role exists
    $role = get_role(self::ROLE_NAME);

    // If role doesn't exist, register it
    if (!$role) {
      $this->register_member_role();
    }
  }

  /**
   * Register the Member role with appropriate capabilities
   * 
   * @return void
   */
  public function register_member_role()
  {
    // Define member capabilities
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
      // Add the member role
      add_role(
        self::ROLE_NAME,
        __(self::ROLE_DISPLAY_NAME, 'elite-enterprise'),
        $capabilities
      );
    }
  }

  /**
   * Block members from accessing WordPress admin
   * 
   * CRITICAL: Redirects members attempting to access admin pages
   * 
   * @return void
   */
  public function block_admin_access()
  {
    // Check if current user is a member
    if (!$this->is_member()) {
      return;
    }

    // Allow AJAX requests
    if (defined('DOING_AJAX') && DOING_AJAX) {
      return;
    }

    // Redirect to member dashboard
    wp_redirect(home_url('/' . self::DASHBOARD_SLUG));
    exit;
  }

  /**
   * Remove admin bar for members on front-end
   * 
   * CRITICAL: Prevents admin bar from showing for members
   * 
   * @return void
   */
  public function remove_admin_bar()
  {
    if ($this->is_member()) {
      show_admin_bar(false);
    }
  }

  /**
   * Redirect members to dashboard after login
   * 
   * CRITICAL: Ensures members land on their dashboard, not admin
   * 
   * @param string $redirect_to URL to redirect to
   * @param string $request Requested redirect URL
   * @param WP_User|WP_Error $user User object or WP_Error
   * @return string Modified redirect URL
   */
  public function redirect_after_login($redirect_to, $request, $user)
  {
    // Check if user is logged in and is a member
    if (isset($user->roles) && is_array($user->roles)) {
      if (in_array(self::ROLE_NAME, $user->roles)) {
        return home_url('/' . self::DASHBOARD_SLUG);
      }
    }

    return $redirect_to;
  }

  /**
   * Check if current user is a member
   * 
   * @return bool True if current user has member role
   */
  private function is_member()
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
   * Programmatically add custom fields to member users
   * 
   * This method allows declarative addition of ACF fields through JSON.
   * Fields are automatically loaded from the acf-json directory.
   * 
   * @return void
   */
  public function register_custom_fields()
  {
    // Fields are automatically registered via ACF JSON
    // See: /acf-json/group_member_fields.json

    // This method can be extended to programmatically add fields if needed
    do_action('member_register_custom_fields');
  }

  /**
   * Get member custom field value
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
   * Update member custom field value
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
   * Get member dashboard URL
   * 
   * @return string Dashboard URL
   */
  public static function get_dashboard_url()
  {
    return home_url('/' . self::DASHBOARD_SLUG);
  }

  /**
   * Check if user has member role
   * 
   * @param int $user_id User ID (defaults to current user)
   * @return bool True if user is a member
   */
  public static function is_user_member($user_id = null)
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
   * Get required member pages configuration
   * 
   * @return array Array of required pages
   */
  private function get_required_pages()
  {
    return [
      'members' => [
        'title' => 'Members',
        'slug' => 'members',
        'content' => '<!-- wp:paragraph --><p>Members area.</p><!-- /wp:paragraph -->',
        'template' => '',
      ],
      'dashboard' => [
        'title' => 'Member Dashboard',
        'slug' => 'dashboard',
        'content' => '<!-- wp:paragraph --><p>Member dashboard page.</p><!-- /wp:paragraph -->',
        'template' => 'page-member-dashboard.php',
        'parent' => 'members',
      ],
      'login' => [
        'title' => 'Member Login',
        'slug' => 'login',
        'content' => '<!-- wp:paragraph --><p>Member login page.</p><!-- /wp:paragraph -->',
        'template' => 'page-member-login.php',
        'parent' => 'members',
      ],
      'forgot-password' => [
        'title' => 'Forgot Password',
        'slug' => 'forgot-password',
        'content' => '<!-- wp:paragraph --><p>Password reset request page.</p><!-- /wp:paragraph -->',
        'template' => 'page-member-forgot-password.php',
        'parent' => 'members',
      ],
      'reset-password' => [
        'title' => 'Reset Password',
        'slug' => 'reset-password',
        'content' => '<!-- wp:paragraph --><p>Password reset page.</p><!-- /wp:paragraph -->',
        'template' => 'page-member-reset-password.php',
        'parent' => 'members',
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
    <strong>Member System Setup Required:</strong>
    The following required pages are missing: <strong><?php echo implode(', ', $missing_pages); ?></strong>
  </p>
  <p>
    <button type="button" class="button button-primary" id="create-member-pages">
      Create Missing Pages
    </button>
    <span class="spinner" style="float: none; margin: 0 10px;"></span>
    <span id="member-pages-status"></span>
  </p>
</div>
<script>
jQuery(document).ready(function($) {
  $('#create-member-pages').on('click', function() {
    var $button = $(this);
    var $spinner = $button.next('.spinner');
    var $status = $('#member-pages-status');

    $button.prop('disabled', true);
    $spinner.addClass('is-active');
    $status.text('Creating pages...');

    $.post(ajaxurl, {
      action: 'create_member_pages',
      nonce: '<?php echo wp_create_nonce('create_member_pages'); ?>'
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
   * AJAX handler to create missing member pages
   * 
   * @return void
   */
  public function ajax_create_pages()
  {
    check_ajax_referer('create_member_pages', 'nonce');

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
   * Get array of member page IDs
   * 
   * @return array Array of page IDs
   */
  private function get_member_page_ids()
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
   * Check if a page is a member system page
   * 
   * @param int $page_id Page ID to check
   * @return bool True if it's a member page
   */
  private function is_member_page($page_id)
  {
    return in_array($page_id, $this->get_member_page_ids());
  }

  /**
   * Restrict editing capabilities for member pages
   * 
   * @param array $allcaps All capabilities
   * @param array $caps Required capabilities
   * @param array $args Additional arguments
   * @return array Modified capabilities
   */
  public function restrict_member_page_editing($allcaps, $caps, $args)
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

    // Check if this is a member page
    if ($this->is_member_page($post_id)) {
      // Remove edit and delete capabilities for this specific page
      $allcaps['edit_post'] = false;
      $allcaps['delete_post'] = false;
      $allcaps['edit_page'] = false;
      $allcaps['delete_page'] = false;
    }

    return $allcaps;
  }

  /**
   * Show notice on locked member pages
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

    if ($post_id && $this->is_member_page($post_id)) {
      ?>
<div class="notice notice-info">
  <p>
    <strong>ℹ️ Theme-Controlled Page</strong><br>
    This page is part of the Member System and is controlled by the theme.
    Content and functionality are managed through the page template.
    You cannot edit or delete this page directly.
  </p>
</div>
<style>
/* Hide edit interface for member pages */
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
   * Remove action links from member pages in page list
   * 
   * @param array $actions Existing actions
   * @param WP_Post $post Post object
   * @return array Modified actions
   */
  public function remove_member_page_actions($actions, $post)
  {
    if ($this->is_member_page($post->ID)) {
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
   * Enqueue member-related scripts
   *
   * @return void
   */
  public function enqueue_member_scripts()
  {
    // Only enqueue on member login page
    if (is_page_template('page-member-login.php')) {
      wp_enqueue_script(
        'member-login',
        get_template_directory_uri() . '/assets/js/member-login.js',
        [],
        '1.0.0',
        true
      );

      // Localize script with AJAX URL
      wp_localize_script('member-login', 'wpData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('member_login_action_-1')
      ]);
    }
  }

  /**
   * AJAX handler for member login
   *
   * @return void
   */
  public function ajax_member_login()
  {
    // No nonce verification needed for login - credentials are the security mechanism
    // This follows WordPress core's approach (wp-login.php doesn't use nonces for login)

    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : '';

    // Validate inputs
    if (empty($username)) {
      wp_send_json_error(['message' => 'Username is required.']);
    }
    if (empty($password)) {
      wp_send_json_error(['message' => 'Password is required.']);
    }

    // Attempt login
    $creds = [
      'user_login' => $username,
      'user_password' => $password,
      'remember' => $remember,
    ];

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
      wp_send_json_error(['message' => $user->get_error_message()]);
    }

    // Check if user has member role
    if (!self::is_user_member($user->ID)) {
      wp_logout();
      wp_send_json_error(['message' => 'This login page is for members only. Please use the standard WordPress login.']);
    }

    // Check membership status using ACF field
    $member_status = get_field('member_status', 'user_' . $user->ID);

    // If no status is set, default to 'active' for backwards compatibility
    if (empty($member_status)) {
      $member_status = 'active';
    }

    // Check if membership is active
    if ($member_status !== 'active') {
      wp_logout();

      // Provide specific error message based on status
      $status_messages = [
        'pending' => 'Your membership is pending approval. Please contact us for assistance.',
        'suspended' => 'Your membership has been suspended. Please contact us to resolve this issue.',
        'expired' => 'Your membership has expired. Please contact us to renew your membership.',
      ];

      $error_message = isset($status_messages[$member_status])
        ? $status_messages[$member_status]
        : 'Your membership is not active. Please contact us for assistance.';

      wp_send_json_error(['message' => $error_message]);
    }

    // Determine redirect URL - use redirect_to if provided, otherwise dashboard
    $redirect_url = !empty($redirect_to) ? $redirect_to : self::get_dashboard_url();

    // Success - return redirect URL for Barba.js transition
    wp_send_json_success([
      'message' => 'Login successful!',
      'redirect_url' => $redirect_url
    ]);
  }

  /**
   * AJAX handler for member logout
   *
   * @return void
   */
  public function ajax_member_logout()
  {
    // Verify user is logged in - this is sufficient security for logout
    // No nonce needed: logout is a safe operation, and being logged in provides security
    if (!is_user_logged_in()) {
      wp_send_json_error(['message' => 'You are not logged in.']);
    }

    // Log out the user
    wp_logout();

    // Success - return home URL for Barba.js transition
    wp_send_json_success([
      'message' => 'Logout successful!',
      'redirect_url' => home_url()
    ]);
  }

  /**
   * Protect member pages from logged-out users and inactive members
   * Redirects to login page if user is not logged in or membership is not active
   *
   * @return void
   */
  public function protect_member_pages()
  {
    // Get current page
    global $post;

    // Check if we're on a page
    if (!is_page() || !$post) {
      return;
    }

    // Get the page slug and check if it's under /members/
    $page_path = get_page_uri($post->ID);

    // Only protect pages under /members/ path
    if (strpos($page_path, 'members/') !== 0) {
      return;
    }

    // Allow access to login and password reset pages
    $allowed_pages = [
      'members/login',
      'members/forgot-password',
      'members/reset-password'
    ];

    if (in_array($page_path, $allowed_pages)) {
      return;
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
      // Redirect to login page with return URL
      $login_url = self::get_login_url();
      $return_url = get_permalink($post->ID);
      $redirect_url = add_query_arg('redirect_to', urlencode($return_url), $login_url);

      wp_redirect($redirect_url);
      exit;
    }

    // User is logged in - check if they are a member
    $current_user = wp_get_current_user();

    if (!self::is_user_member($current_user->ID)) {
      // Not a member, allow access (could be admin viewing the page)
      return;
    }

    // Check membership status using ACF field
    $member_status = get_field('member_status', 'user_' . $current_user->ID);

    // If no status is set, default to 'active' for backwards compatibility
    if (empty($member_status)) {
      $member_status = 'active';
    }

    // Block access if membership is not active
    if ($member_status !== 'active') {
      // Redirect to login page with error message
      $login_url = self::get_login_url();

      // Add status-specific message as URL parameter
      $status_param = 'status_' . $member_status;
      $redirect_url = add_query_arg($status_param, '1', $login_url);

      // Log them out first
      wp_logout();

      wp_redirect($redirect_url);
      exit;
    }
  }

  /**
   * AJAX handler for getting dynamic menu based on user status
   *
   * @return void
   */
  public function ajax_get_dynamic_menu()
  {
    // Get widget settings from POST
    if (!isset($_POST['settings'])) {
      wp_send_json_error(['message' => 'Widget settings not provided.']);
    }

    $settings = json_decode(stripslashes($_POST['settings']), true);
    if (!$settings) {
      wp_send_json_error(['message' => 'Invalid widget settings.']);
    }

    // Determine which menu to display
    $menu_id = null;

    // Check if user is logged in
    if (!is_user_logged_in()) {
      $menu_id = !empty($settings['logged_out_menu']) ? (int) $settings['logged_out_menu'] : null;
    } else {
      // User is logged in - check for role-specific menus if enabled
      if (!empty($settings['enable_role_menus']) && $settings['enable_role_menus'] === 'yes') {
        $current_user = wp_get_current_user();

        // Check each role the user has
        foreach ($current_user->roles as $role) {
          $menu_key = 'menu_' . $role;
          if (!empty($settings[$menu_key])) {
            $menu_id = (int) $settings[$menu_key];
            break;
          }
        }
      }

      // Fallback to default logged-in menu
      if (!$menu_id && !empty($settings['logged_in_menu'])) {
        $menu_id = (int) $settings['logged_in_menu'];
      }
    }

    if (!$menu_id) {
      wp_send_json_error(['message' => 'No menu configured for current user status.']);
    }

    // Prepare menu arguments
    $menu_args = [
      'menu' => $menu_id,
      'container' => !empty($settings['menu_container']) ? $settings['menu_container'] : 'nav',
      'menu_class' => !empty($settings['menu_class']) ? $settings['menu_class'] : 'dynamic-menu',
      'echo' => false,
      'fallback_cb' => false,
    ];

    // Get the menu HTML
    $menu_html = wp_nav_menu($menu_args);

    if (!$menu_html) {
      wp_send_json_error(['message' => 'Menu not found.']);
    }

    wp_send_json_success([
      'menu_html' => $menu_html,
      'menu_id' => $menu_id
    ]);
  }
}

// Initialize the Member class and store it globally to prevent garbage collection
$GLOBALS['elite_member_instance'] = new Member();