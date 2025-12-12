<?php
/**
 * Template Name: Member Dashboard
 * 
 * Custom page template for member dashboard
 * Members are redirected here after login
 * 
 * @package EliteEnterprise
 * @since 1.0.0
 */

// Check if user is logged in and has member role
if (!is_user_logged_in()) {
  wp_redirect(wp_login_url(get_permalink()));
  exit;
}

if (!Member::is_user_member()) {
  wp_redirect(home_url());
  exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// Start Barba.js container
get_template_part('template-parts/barba-container-start');
?>

<div id="member-dashboard" class="member-dashboard-wrapper">
  <div class="container mx-auto px-4 py-8">

    <!-- Dashboard Header -->
    <div class="dashboard-header mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-4xl font-bold mb-2">Welcome back, <?php echo esc_html($current_user->display_name); ?></h1>
          <p class="text-gray-600">Manage your member profile and account settings</p>
        </div>
      </div>
    </div>

    <div class="dashboard-content grid grid-cols-1 lg:grid-cols-3 gap-8">

      <!-- Sidebar -->
      <div class="dashboard-sidebar lg:col-span-1">
        <div class="bg-white rounded-lg shadow-lg p-6">

          <!-- Profile Image -->
          <div class="text-center mb-6">
            <?php
            $profile_image = Member::get_field('member_profile_image', $user_id);
            if ($profile_image && isset($profile_image['url'])) {
              echo '<img src="' . esc_url($profile_image['url']) . '" alt="' . esc_attr($current_user->display_name) . '" class="w-32 h-32 rounded-full mx-auto object-cover">';
            } else {
              echo '<div class="w-32 h-32 rounded-full mx-auto bg-gray-300 flex items-center justify-center">';
              echo '<span class="text-4xl text-white">' . esc_html(strtoupper(substr($current_user->display_name, 0, 1))) . '</span>';
              echo '</div>';
            }
            ?>
            <h3 class="text-xl font-semibold mt-4"><?php echo esc_html($current_user->display_name); ?></h3>
            <p class="text-gray-600 text-sm"><?php echo esc_html($current_user->user_email); ?></p>

            <?php
            $member_status = Member::get_field('member_status', $user_id);
            if ($member_status) {
              $status_colors = [
                'active' => 'bg-green-100 text-green-800',
                'pending' => 'bg-yellow-100 text-yellow-800',
                'suspended' => 'bg-red-100 text-red-800',
                'expired' => 'bg-gray-100 text-gray-800'
              ];
              $color_class = $status_colors[$member_status] ?? 'bg-gray-100 text-gray-800';
              echo '<span class="inline-block mt-2 px-3 py-1 text-xs font-semibold rounded-full ' . $color_class . '">' . esc_html(ucfirst($member_status)) . '</span>';
            }
            ?>
          </div>

          <!-- Navigation Menu -->
          <nav class="dashboard-nav">
            <ul class="space-y-2">
              <li>
                <a href="#profile" class="block px-4 py-2 rounded hover:bg-gray-100 transition nav-link active"
                  data-tab="profile">
                  <span class="font-medium">Profile</span>
                </a>
              </li>
              <li>
                <a href="#account" class="block px-4 py-2 rounded hover:bg-gray-100 transition nav-link"
                  data-tab="account">
                  <span class="font-medium">Account Settings</span>
                </a>
              </li>
              <li>
                <a href="#activity" class="block px-4 py-2 rounded hover:bg-gray-100 transition nav-link"
                  data-tab="activity">
                  <span class="font-medium">Activity</span>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <!-- Main Content -->
      <div class="dashboard-main lg:col-span-2">

        <!-- Profile Tab -->
        <div class="dashboard-tab active" id="tab-profile">
          <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Profile Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Phone -->
              <div class="profile-field">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                <p class="text-gray-900">
                  <?php echo esc_html(Member::get_field('member_phone', $user_id) ?: 'Not provided'); ?>
                </p>
              </div>

              <!-- Date of Birth -->
              <div class="profile-field">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Date of Birth</label>
                <p class="text-gray-900">
                  <?php echo esc_html(Member::get_field('member_date_of_birth', $user_id) ?: 'Not provided'); ?>
                </p>
              </div>

              <!-- Company -->
              <div class="profile-field">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Company</label>
                <p class="text-gray-900">
                  <?php echo esc_html(Member::get_field('member_company', $user_id) ?: 'Not provided'); ?>
                </p>
              </div>

              <!-- Job Title -->
              <div class="profile-field">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Job Title</label>
                <p class="text-gray-900">
                  <?php echo esc_html(Member::get_field('member_job_title', $user_id) ?: 'Not provided'); ?>
                </p>
              </div>

              <!-- Join Date -->
              <div class="profile-field">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Member Since</label>
                <p class="text-gray-900">
                  <?php echo esc_html(Member::get_field('member_join_date', $user_id) ?: date('Y-m-d')); ?>
                </p>
              </div>
            </div>

            <!-- Address -->
            <div class="profile-field mt-6">
              <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
              <p class="text-gray-900">
                <?php echo nl2br(esc_html(Member::get_field('member_address', $user_id) ?: 'Not provided')); ?>
              </p>
            </div>

            <!-- Biography -->
            <div class="profile-field mt-6">
              <label class="block text-sm font-semibold text-gray-700 mb-2">Biography</label>
              <div class="text-gray-900 prose">
                <?php
                $bio = Member::get_field('member_bio', $user_id);
                echo $bio ? wp_kses_post($bio) : 'No biography provided';
                ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Settings Tab -->
        <div class="dashboard-tab" id="tab-account" style="display: none;">
          <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Account Settings</h2>

            <div class="space-y-6">
              <!-- Username -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                <p class="text-gray-900"><?php echo esc_html($current_user->user_login); ?></p>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                <p class="text-gray-900"><?php echo esc_html($current_user->user_email); ?></p>
              </div>

              <!-- Member Since -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Account Created</label>
                <p class="text-gray-900"><?php echo date('F j, Y', strtotime($current_user->user_registered)); ?></p>
              </div>
            </div>

            <div class="mt-8 pt-6 border-t">
              <h3 class="text-lg font-semibold mb-4">Change Password</h3>
              <a href="<?php echo wp_lostpassword_url(get_permalink()); ?>" class="btn btn-secondary">
                Reset Password
              </a>
            </div>
          </div>
        </div>

        <!-- Activity Tab -->
        <div class="dashboard-tab" id="tab-activity" style="display: none;">
          <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Recent Activity</h2>
            <p class="text-gray-600">No recent activity to display.</p>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const navLinks = document.querySelectorAll('.nav-link');
  const tabs = document.querySelectorAll('.dashboard-tab');

  navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();

      const tabName = this.getAttribute('data-tab');

      // Remove active class from all links and tabs
      navLinks.forEach(l => l.classList.remove('active', 'bg-blue-50'));
      tabs.forEach(t => {
        t.classList.remove('active');
        t.style.display = 'none';
      });

      // Add active class to clicked link and corresponding tab
      this.classList.add('active', 'bg-blue-50');
      const activeTab = document.getElementById('tab-' + tabName);
      if (activeTab) {
        activeTab.classList.add('active');
        activeTab.style.display = 'block';
      }
    });
  });
});
</script>

<?php
// End Barba.js container
get_template_part('template-parts/barba-container-end');

get_footer();
?>