<?php
/**
 * Gravity Form Manager Class
 *
 * Manages Gravity Forms programmatically, including creating forms and handling submissions.
 * This class is specifically designed for the member intake form used by collaborators.
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

class GravityFormManager
{
  /**
   * Form title
   *
   * @var string
   */
  private const FORM_TITLE = 'Member Intake Form';

  /**
   * Initialize the GravityFormManager class
   */
  public function __construct()
  {
    // Check for Gravity Forms and show admin notice
    add_action('admin_notices', [$this, 'check_gravity_forms']);

    // Handle form creation AJAX
    add_action('wp_ajax_create_intake_form', [$this, 'ajax_create_intake_form']);

    // Handle form submission - dynamically hook based on form ID
    add_action('init', [$this, 'register_form_hooks']);

    // Lock intake form from editing
    add_action('admin_enqueue_scripts', [$this, 'lock_intake_form']);
    add_action('admin_notices', [$this, 'show_locked_form_notice']);
    add_filter('gform_form_actions', [$this, 'remove_form_actions'], 10, 2);
    add_action('admin_head', [$this, 'add_form_list_styles']);

    // Prevent form updates and deletion
    add_filter('gform_pre_form_settings_save', [$this, 'prevent_form_save'], 10, 2);
    add_action('gform_before_delete_form', [$this, 'prevent_form_delete'], 10, 1);
    add_filter('gform_form_update_meta', [$this, 'prevent_form_meta_update'], 10, 3);

    // Disable form editor capabilities for the intake form
    add_filter('user_has_cap', [$this, 'restrict_form_editing_capability'], 10, 4);
  }

  /**
   * Register form hooks after WordPress initialization
   *
   * @return void
   */
  public function register_form_hooks()
  {
    if (!self::is_gravity_forms_active()) {
      return;
    }

    $form_id = self::get_intake_form_id();
    if ($form_id) {
      add_action('gform_after_submission_' . $form_id, [$this, 'handle_intake_submission'], 10, 2);
    }
  }

  /**
   * Check if Gravity Forms is active
   *
   * @return bool
   */
  public static function is_gravity_forms_active()
  {
    return class_exists('GFForms');
  }

  /**
   * Check if the intake form exists
   *
   * @return bool
   */
  public static function intake_form_exists()
  {
    return self::get_intake_form_id() !== null;
  }

  /**
   * Get the intake form ID by its title
   *
   * @return int|null Form ID if found, null otherwise
   */
  public static function get_intake_form_id()
  {
    if (!self::is_gravity_forms_active() || !class_exists('RGFormsModel')) {
      return null;
    }

    $form_id = RGFormsModel::get_form_id(self::FORM_TITLE);
    return $form_id ? (int) $form_id : null;
  }

  /**
   * Check Gravity Forms installation and show admin notice
   *
   * @return void
   */
  public function check_gravity_forms()
  {
    // Only show to administrators
    if (!current_user_can('manage_options')) {
      return;
    }

    // Check if Gravity Forms is active
    if (!self::is_gravity_forms_active()) {
      ?>
      <div class="notice notice-error">
        <p>
          <strong>Gravity Forms Required:</strong>
          The Member Intake Form requires Gravity Forms to be installed and activated.
          Please install and activate Gravity Forms to use this feature.
        </p>
      </div>
      <?php
      return;
    }

    // Check if intake form exists
    if (!self::intake_form_exists()) {
      ?>
      <div class="notice notice-warning is-dismissible">
        <p>
          <strong>Member Intake Form Setup Required:</strong>
          The Member Intake Form does not exist and needs to be created.
        </p>
        <p>
          <button type="button" class="button button-primary" id="create-intake-form">
            Create Member Intake Form
          </button>
          <span class="spinner" style="float: none; margin: 0 10px;"></span>
          <span id="intake-form-status"></span>
        </p>
      </div>
      <script>
        jQuery(document).ready(function ($) {
          $('#create-intake-form').on('click', function () {
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var $status = $('#intake-form-status');

            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $status.text('Creating form...');

            $.post(ajaxurl, {
              action: 'create_intake_form',
              nonce: '<?php echo wp_create_nonce('create_intake_form'); ?>'
            }, function (response) {
              $spinner.removeClass('is-active');

              if (response.success) {
                $status.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                setTimeout(function () {
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
   * AJAX handler for creating the intake form
   *
   * @return void
   */
  public function ajax_create_intake_form()
  {
    // Verify nonce
    check_ajax_referer('create_intake_form', 'nonce');

    // Check permissions
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
    }

    // Create the form
    $result = $this->create_intake_form();

    if (is_wp_error($result)) {
      wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => 'Member Intake Form created successfully!']);
  }

  /**
   * Create the member intake form programmatically
   *
   * @return int|WP_Error Form ID on success, WP_Error on failure
   */
  private function create_intake_form()
  {
    // Check if form already exists
    if (self::intake_form_exists()) {
      return new WP_Error('form_exists', 'The intake form already exists.');
    }

    // Define the form structure using associative arrays
    $form = [
      'title' => self::FORM_TITLE,
      'description' => 'Fill out this form to create a new member account. All new members will be created with a pending status.',
      'labelPlacement' => 'top_label',
      'descriptionPlacement' => 'below',
      'button' => [
        'type' => 'text',
        'text' => 'Submit Member Information'
      ],
      'fields' => [
        // Field 1: Full Name
        [
          'type' => 'name',
          'id' => 1,
          'label' => 'Full Name',
          'isRequired' => true,
          'nameFormat' => 'simple',
          'inputs' => null
        ],
        // Field 2: Email
        [
          'type' => 'email',
          'id' => 2,
          'label' => 'Email Address',
          'isRequired' => true
        ],
        // Field 3: Username
        [
          'type' => 'text',
          'id' => 3,
          'label' => 'Username',
          'isRequired' => true,
          'description' => 'Choose a unique username for this member',
          'descriptionPlacement' => 'below'
        ],
        // Field 4: Phone
        [
          'type' => 'phone',
          'id' => 4,
          'label' => 'Phone Number',
          'isRequired' => false,
          'phoneFormat' => 'standard'
        ],
        // Field 5: Company
        [
          'type' => 'text',
          'id' => 5,
          'label' => 'Company',
          'isRequired' => false
        ],
        // Field 6: Job Title
        [
          'type' => 'text',
          'id' => 6,
          'label' => 'Job Title',
          'isRequired' => false
        ],
        // Field 7: Address
        [
          'type' => 'address',
          'id' => 7,
          'label' => 'Address',
          'isRequired' => false,
          'addressType' => 'international',
          'inputs' => [
            ['id' => '7.1', 'label' => 'Street Address', 'name' => ''],
            ['id' => '7.2', 'label' => 'Address Line 2', 'name' => ''],
            ['id' => '7.3', 'label' => 'City', 'name' => ''],
            ['id' => '7.4', 'label' => 'State / Province', 'name' => ''],
            ['id' => '7.5', 'label' => 'ZIP / Postal Code', 'name' => ''],
            ['id' => '7.6', 'label' => 'Country', 'name' => '']
          ]
        ],
        // Field 8: Date of Birth
        [
          'type' => 'date',
          'id' => 8,
          'label' => 'Date of Birth',
          'isRequired' => false,
          'dateFormat' => 'mdy',
          'calendarIconType' => 'calendar'
        ],
        // Field 9: Biography
        [
          'type' => 'textarea',
          'id' => 9,
          'label' => 'Biography',
          'isRequired' => false,
          'description' => 'Brief biography or additional information about this member',
          'descriptionPlacement' => 'below'
        ]
      ],
      'confirmations' => [
        uniqid() => [
          'id' => uniqid(),
          'name' => 'Default Confirmation',
          'isDefault' => true,
          'type' => 'message',
          'message' => '<p><strong>Thank you!</strong></p><p>The member account has been created successfully with a pending status. An administrator will review and approve the account shortly.</p>',
          'url' => '',
          'pageId' => '',
          'queryString' => ''
        ]
      ],
      'notifications' => [
        uniqid() => [
          'id' => uniqid(),
          'name' => 'Admin Notification',
          'isActive' => true,
          'to' => '{admin_email}',
          'subject' => 'New Member Intake Submission',
          'message' => '{all_fields}',
          'from' => '{admin_email}',
          'fromName' => get_bloginfo('name'),
          'replyTo' => '{Email Address:2}',
          'event' => 'form_submission'
        ]
      ]
    ];

    // Create the form
    $form_id = GFAPI::add_form($form);

    if (is_wp_error($form_id)) {
      error_log('GravityFormManager - Failed to create form: ' . $form_id->get_error_message());
      return $form_id;
    }

    error_log(sprintf('GravityFormManager - Successfully created intake form "%s" with ID: %d', self::FORM_TITLE, $form_id));
    return $form_id;
  }

  /**
   * Handle intake form submission - create member user with pending status
   *
   * @param array $entry The entry that was just created
   * @param array $form The current form
   * @return void
   */
  public function handle_intake_submission($entry, $form)
  {
    // Get form field values
    $full_name = rgar($entry, '1');
    $email = rgar($entry, '2');
    $username = rgar($entry, '3');
    $phone = rgar($entry, '4');
    $company = rgar($entry, '5');
    $job_title = rgar($entry, '6');
    $address_street = rgar($entry, '7.1');
    $address_line2 = rgar($entry, '7.2');
    $address_city = rgar($entry, '7.3');
    $address_state = rgar($entry, '7.4');
    $address_zip = rgar($entry, '7.5');
    $address_country = rgar($entry, '7.6');
    $date_of_birth = rgar($entry, '8');
    $bio = rgar($entry, '9');

    // Split full name into first and last
    $name_parts = explode(' ', $full_name, 2);
    $first_name = isset($name_parts[0]) ? $name_parts[0] : '';
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

    // Generate a random password
    $password = wp_generate_password(12, true, true);

    // Create the user
    $user_data = [
      'user_login' => sanitize_user($username),
      'user_email' => sanitize_email($email),
      'user_pass' => $password,
      'first_name' => sanitize_text_field($first_name),
      'last_name' => sanitize_text_field($last_name),
      'display_name' => sanitize_text_field($first_name . ' ' . $last_name),
      'role' => 'member'
    ];

    $user_id = wp_insert_user($user_data);

    // Check for errors
    if (is_wp_error($user_id)) {
      error_log('Member Intake Form - Failed to create user: ' . $user_id->get_error_message());
      return;
    }

    // Set member status to pending
    update_field('member_status', 'pending', 'user_' . $user_id);

    // Set member custom fields
    if ($phone) {
      update_field('member_phone', sanitize_text_field($phone), 'user_' . $user_id);
    }

    if ($company) {
      update_field('member_company', sanitize_text_field($company), 'user_' . $user_id);
    }

    if ($job_title) {
      update_field('member_job_title', sanitize_text_field($job_title), 'user_' . $user_id);
    }

    // Build full address
    $full_address = '';
    if ($address_street) {
      $full_address .= $address_street;
    }
    if ($address_line2) {
      $full_address .= "\n" . $address_line2;
    }
    if ($address_city || $address_state || $address_zip) {
      $full_address .= "\n" . trim($address_city . ', ' . $address_state . ' ' . $address_zip);
    }
    if ($address_country) {
      $full_address .= "\n" . $address_country;
    }

    if ($full_address) {
      update_field('member_address', sanitize_textarea_field($full_address), 'user_' . $user_id);
    }

    if ($date_of_birth) {
      update_field('member_date_of_birth', sanitize_text_field($date_of_birth), 'user_' . $user_id);
    }

    if ($bio) {
      update_field('member_bio', wp_kses_post($bio), 'user_' . $user_id);
    }

    // Set join date to today
    update_field('member_join_date', date('Y-m-d'), 'user_' . $user_id);

    // Add a note about who created this member
    $collaborator_id = get_current_user_id();
    $collaborator = get_userdata($collaborator_id);
    $collaborator_name = $collaborator ? $collaborator->display_name : 'Unknown';

    $note = sprintf(
      'Member created via intake form by collaborator: %s (ID: %d) on %s',
      $collaborator_name,
      $collaborator_id,
      date('Y-m-d H:i:s')
    );
    update_field('member_notes', $note, 'user_' . $user_id);

    // Log success
    error_log(sprintf(
      'Member Intake Form - Successfully created user ID %d (%s) with pending status',
      $user_id,
      $username
    ));

    // Send welcome email to new member (password will be sent)
    wp_new_user_notification($user_id, null, 'both');
  }

  /**
   * Lock the intake form from editing in admin
   *
   * @return void
   */
  public function lock_intake_form($hook)
  {
    if (!self::is_gravity_forms_active()) {
      return;
    }

    // Get the intake form ID
    $form_id = self::get_intake_form_id();
    if (!$form_id) {
      return;
    }

    // Check if we're on the form editor page
    if (!isset($_GET['page']) || $_GET['page'] !== 'gf_edit_forms') {
      return;
    }

    // Check if we're editing the intake form
    if (isset($_GET['id']) && intval($_GET['id']) === $form_id) {
      // Enqueue custom script to lock the form
      wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
          // Disable all form settings inputs
          setTimeout(function() {
            // Disable all inputs, selects, textareas in settings
            $(".gform-settings-panel, #gform-settings").find("input, select, textarea, button").not(".gform-button--white").prop("disabled", true).css("pointer-events", "none");

            // Disable field settings
            $("#field_settings").find("input, select, textarea, button").prop("disabled", true).css("pointer-events", "none");

            // Make form fields non-draggable and non-editable
            $("#gform_fields").sortable("disable");
            $(".gfield").css({"opacity": "0.6", "pointer-events": "none"});

            // Hide add field buttons
            $(".gform-field-type").css({"opacity": "0.5", "pointer-events": "none", "cursor": "not-allowed"});

            // Disable save button
            $("#gform_save_settings").prop("disabled", true).css("pointer-events", "none");

            // Prevent field clicks
            $(".gfield").off("click").on("click", function(e) {
              e.preventDefault();
              e.stopPropagation();
              alert("This form is theme-controlled and cannot be edited.");
              return false;
            });

            // Prevent adding fields
            $(".gform-field-type").off("click").on("click", function(e) {
              e.preventDefault();
              e.stopPropagation();
              alert("This form is theme-controlled and cannot be edited.");
              return false;
            });
          }, 500);
        });
      ');
    }
  }

  /**
   * Show notice on locked intake form
   *
   * @return void
   */
  public function show_locked_form_notice()
  {
    if (!self::is_gravity_forms_active()) {
      return;
    }

    $form_id = self::get_intake_form_id();
    if (!$form_id) {
      return;
    }

    // Check if we're on the form editor or form list page
    if (!isset($_GET['page']) || !in_array($_GET['page'], ['gf_edit_forms', 'gf_entries'])) {
      return;
    }

    // Check if we're viewing the intake form
    if (isset($_GET['id']) && intval($_GET['id']) === $form_id) {
      ?>
      <div class="notice notice-info">
        <p>
          <strong>ℹ️ Theme-Controlled Form</strong><br>
          This form is part of the Collaborator System and is controlled by the theme.
          The form structure and fields are managed programmatically and cannot be edited directly.
          Form entries and settings can be viewed, but the form itself is locked to prevent accidental modifications.
        </p>
      </div>
      <?php
    }
  }

  /**
   * Remove edit and delete actions from intake form in forms list
   *
   * @param array $actions Form actions
   * @param int $form_id Form ID
   * @return array Modified actions
   */
  public function remove_form_actions($actions, $form_id)
  {
    $intake_form_id = self::get_intake_form_id();

    // Debug logging
    error_log('GravityFormManager - remove_form_actions called. Intake ID: ' . $intake_form_id . ', Current ID: ' . $form_id);
    error_log('GravityFormManager - Actions before: ' . print_r(array_keys($actions), true));

    if ($intake_form_id && $form_id === $intake_form_id) {
      // Remove edit, duplicate, and delete actions
      unset($actions['edit']);
      unset($actions['duplicate']);
      unset($actions['trash']);

      // Add a custom indicator
      $actions['theme-controlled'] = '<span style="color: #2271b1; font-weight: bold;">🔒 Theme Controlled - Read Only</span>';

      error_log('GravityFormManager - Actions after: ' . print_r(array_keys($actions), true));
    }

    return $actions;
  }

  /**
   * Prevent saving changes to the intake form
   *
   * @param array $form Form array
   * @param array $form_meta Form metadata
   * @return array Unmodified form (prevents saving)
   */
  public function prevent_form_save($form, $form_meta)
  {
    $intake_form_id = self::get_intake_form_id();

    if ($intake_form_id && isset($form['id']) && intval($form['id']) === $intake_form_id) {
      // Get the original form from database
      $original_form = GFAPI::get_form($intake_form_id);

      // Show error notice
      add_action('admin_notices', function () {
        ?>
        <div class="notice notice-error is-dismissible">
          <p>
            <strong>Error:</strong> The Member Intake Form is theme-controlled and cannot be modified.
            Any changes have been discarded.
          </p>
        </div>
        <?php
      });

      // Return original form to prevent changes
      return $original_form;
    }

    return $form;
  }

  /**
   * Prevent deleting the intake form
   *
   * @param int $form_id Form ID being deleted
   * @return void
   */
  public function prevent_form_delete($form_id)
  {
    $intake_form_id = self::get_intake_form_id();

    if ($intake_form_id && $form_id === $intake_form_id) {
      wp_die(
        '<h1>Error: Cannot Delete Theme-Controlled Form</h1>' .
        '<p>The Member Intake Form is controlled by the theme and cannot be deleted.</p>' .
        '<p>This form is essential for the Collaborator System to function properly.</p>' .
        '<p><a href="' . admin_url('admin.php?page=gf_edit_forms') . '">Return to Forms</a></p>',
        'Form Deletion Prevented',
        ['response' => 403]
      );
    }
  }

  /**
   * Prevent form metadata from being updated
   *
   * @param array $form_meta Form metadata
   * @param int $form_id Form ID
   * @param array $form Form array
   * @return array Unmodified form metadata
   */
  public function prevent_form_meta_update($form_meta, $form_id, $form)
  {
    $intake_form_id = self::get_intake_form_id();

    if ($intake_form_id && $form_id === $intake_form_id) {
      // Return the original metadata to prevent updates
      $original_form = GFAPI::get_form($intake_form_id);
      return $original_form;
    }

    return $form_meta;
  }

  /**
   * Restrict form editing capabilities for non-admins
   *
   * @param array $allcaps All capabilities
   * @param array $caps Required capabilities
   * @param array $args Capability arguments
   * @param WP_User $user User object
   * @return array Modified capabilities
   */
  public function restrict_form_editing_capability($allcaps, $caps, $args, $user)
  {
    // Only restrict on Gravity Forms pages
    if (!isset($_GET['page']) || strpos($_GET['page'], 'gf_') !== 0) {
      return $allcaps;
    }

    $intake_form_id = self::get_intake_form_id();
    if (!$intake_form_id) {
      return $allcaps;
    }

    // Check if we're trying to edit or delete the intake form
    if (isset($_GET['id']) && intval($_GET['id']) === $intake_form_id) {
      // Remove gravityforms_edit_forms capability for this specific form
      if (isset($allcaps['gravityforms_edit_forms'])) {
        $allcaps['gravityforms_edit_forms'] = false;
      }
    }

    return $allcaps;
  }

  /**
   * Add custom styles to hide action links for the intake form
   *
   * @return void
   */
  public function add_form_list_styles()
  {
    // Only on forms list page - allow both old and new GF admin pages
    if (!isset($_GET['page']) || !in_array($_GET['page'], ['gf_edit_forms', 'gf_entries'])) {
      return;
    }

    $form_id = self::get_intake_form_id();
    if (!$form_id) {
      return;
    }

    ?>
    <style>
      /* Hide action links for the intake form - target span elements within row-actions */
      a[href*="page=gf_edit_forms&id=<?php echo $form_id; ?>"][aria-label*="Edit"] {
        pointer-events: none;
      }

      /* Hide all action spans for this form */
      td.column-title a[href*="id=<?php echo $form_id; ?>"]~div.row-actions span.edit,
      td.column-title a[href*="id=<?php echo $form_id; ?>"]~div.row-actions span.duplicate,
      td.column-title a[href*="id=<?php echo $form_id; ?>"]~div.row-actions span.trash {
        display: none !important;
      }

      /* Add visual indicator to the form row - target by checking if title link contains form ID */
      td.column-title:has(a[href*="id=<?php echo $form_id; ?>"]) {
        position: relative;
      }

      td.column-title a[href*="id=<?php echo $form_id; ?>"]::before {
        content: "🔒 ";
        color: #2271b1;
        font-weight: bold;
      }

      /* Highlight the entire row */
      tr:has(a[href*="page=gf_edit_forms&id=<?php echo $form_id; ?>"]) {
        background-color: #f0f6fc !important;
        border-left: 4px solid #2271b1 !important;
      }
    </style>
    <script>
      jQuery(document).ready(function ($) {
        // Remove action links via JavaScript
        setTimeout(function () {
          // Find the form title link that contains the form ID
          var formLink = $('a[href*="page=gf_edit_forms"][href*="id=<?php echo $form_id; ?>"]').first();

          if (formLink.length) {
            var formRow = formLink.closest('tr');

            // Remove specific action span elements
            formRow.find('.row-actions span.edit').remove();
            formRow.find('.row-actions span.duplicate').remove();
            formRow.find('.row-actions span.trash').remove();

            // Remove the separator pipes after removed items
            var rowActions = formRow.find('.row-actions');
            var html = rowActions.html();
            if (html) {
              // Clean up multiple pipes and spaces
              html = html.replace(/\|\s*\|/g, '|');
              html = html.replace(/^\s*\|\s*/g, '');
              html = html.replace(/\s*\|\s*$/g, '');
              rowActions.html(html);
            }

            // Add theme-controlled indicator at the beginning
            if (rowActions.length && !rowActions.find('.theme-controlled').length) {
              rowActions.prepend(
                '<span class="theme-controlled" style="color: #2271b1; font-weight: bold;">🔒 Theme Controlled</span><span class="sep"> | </span>'
              );
            }

            console.log('GravityFormManager: Successfully locked intake form (ID: <?php echo $form_id; ?>)');
          } else {
            console.log('GravityFormManager: Could not find form row for ID <?php echo $form_id; ?>');
          }
        }, 300);
      });
    </script>
    <?php
  }
}

// Initialize the GravityFormManager class and store it globally to prevent garbage collection
$GLOBALS['elite_gravity_form_manager_instance'] = new GravityFormManager();