<?php
/**
 * Template Name: Collaborator Intake Form
 *
 * Custom page template for collaborator intake form
 * Displays the Gravity Form for creating new members
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

// Check if user is logged in and has collaborator role
if (!is_user_logged_in()) {
  wp_redirect(home_url());
  exit;
}

if (!Collaborator::is_user_collaborator()) {
  // Allow admins to view
  $current_user = wp_get_current_user();
  if (!in_array('administrator', (array) $current_user->roles)) {
    wp_redirect(home_url());
    exit;
  }
}

get_header();

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// Start Barba.js container
get_template_part('template-parts/barba-container-start');
?>

<div id="collaborator-intake" class="collaborator-intake-wrapper">
  <div class="container mx-auto px-4 py-8">

    <!-- Page Header -->
    <div class="page-header mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-4xl font-bold mb-2">Member Intake Form</h1>
          <p class="text-gray-600">Create a new member account</p>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-600">Logged in as:</p>
          <p class="text-lg font-semibold"><?php echo esc_html($current_user->display_name); ?></p>
        </div>
      </div>
    </div>

    <!-- Collaborator Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
      <h3 class="text-lg font-semibold mb-3 text-blue-900">Collaborator Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <?php
        $company_name = Collaborator::get_field('collaborator_company_name', $user_id);
        $company_email = Collaborator::get_field('collaborator_company_email', $user_id);
        $company_phone = Collaborator::get_field('collaborator_company_phone', $user_id);
        ?>
        <div>
          <span class="font-semibold text-blue-900">Company:</span>
          <span class="text-blue-800"><?php echo esc_html($company_name ?: 'Not provided'); ?></span>
        </div>
        <div>
          <span class="font-semibold text-blue-900">Contact:</span>
          <span class="text-blue-800"><?php echo esc_html($current_user->user_email); ?></span>
        </div>
        <?php if ($company_phone): ?>
        <div>
          <span class="font-semibold text-blue-900">Phone:</span>
          <span class="text-blue-800"><?php echo esc_html($company_phone); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Form Content -->
    <div class="form-content bg-white rounded-lg shadow-lg p-8">

      <?php if (GravityFormManager::is_gravity_forms_active()): ?>

        <?php if (GravityFormManager::intake_form_exists()): ?>

          <div class="mb-6">
            <h2 class="text-2xl font-bold mb-2">New Member Information</h2>
            <p class="text-gray-600">
              Fill out the form below to create a new member account. The member will be created with a
              <strong>Pending</strong> status
              and will need to be activated by an administrator before they can log in.
            </p>
          </div>

          <?php
          // Display the Gravity Form
          gravity_form(
            GravityFormManager::get_intake_form_id(),
            false, // display_title
            false, // display_description
            false, // display_inactive
            null, // field_values
            true, // ajax
            0, // tabindex
            true  // echo
          );
          ?>

        <?php else: ?>

          <div class="text-center py-12">
            <div class="mb-4">
              <svg class="w-16 h-16 mx-auto text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                </path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Form Not Found</h3>
            <p class="text-gray-600 mb-6">
              The Member Intake Form has not been created yet. Please contact an administrator to set up the form.
            </p>
          </div>

        <?php endif; ?>

      <?php else: ?>

        <div class="text-center py-12">
          <div class="mb-4">
            <svg class="w-16 h-16 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-2">Gravity Forms Required</h3>
          <p class="text-gray-600 mb-6">
            This page requires Gravity Forms to be installed and activated. Please contact an administrator.
          </p>
        </div>

      <?php endif; ?>

    </div>

    <!-- Help Section -->
    <div class="mt-8 p-6 bg-gray-50 rounded-lg">
      <h3 class="text-lg font-semibold mb-3">Need Help?</h3>
      <p class="text-gray-700 mb-2">
        If you have any questions about the intake process or need assistance, please contact the administrator.
      </p>
      <p class="text-gray-600 text-sm">
        <strong>Note:</strong> All new members will be created with a "Pending" status and must be approved by an
        administrator before they can access their account.
      </p>
    </div>

  </div>
</div>

<?php
// End Barba.js container
get_template_part('template-parts/barba-container-end');

get_footer();
?>
