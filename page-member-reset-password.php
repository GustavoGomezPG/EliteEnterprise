<?php
/**
 * Template Name: Member Reset Password
 *
 * Custom page template for member password reset
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

// Redirect if already logged in
if (is_user_logged_in()) {
  wp_redirect(Member::get_dashboard_url());
  exit;
}

get_header();

// Handle password reset form submission
$errors = [];
$success_message = '';
$show_form = true;

// Get reset key and login from URL
$reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$user_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';

// Validate reset key
$user = false;
if (!empty($reset_key) && !empty($user_login)) {
  $user = check_password_reset_key($reset_key, $user_login);

  if (is_wp_error($user)) {
    if ($user->get_error_code() === 'expired_key') {
      $errors[] = 'This password reset link has expired. Please request a new one.';
    } else {
      $errors[] = 'This password reset link is invalid. Please request a new one.';
    }
    $show_form = false;
  } elseif (!Member::is_user_member($user->ID)) {
    $errors[] = 'This reset link is not valid for member accounts.';
    $show_form = false;
  }
} else {
  $errors[] = 'Invalid password reset link.';
  $show_form = false;
}

// Process password reset
if ($show_form && isset($_POST['member_reset_password_submit'])) {
  if (!isset($_POST['member_reset_password_nonce']) || !wp_verify_nonce($_POST['member_reset_password_nonce'], 'member_reset_password_action')) {
    $errors[] = 'Security check failed. Please try again.';
  } else {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password
    if (empty($new_password)) {
      $errors[] = 'Password is required.';
    } elseif (strlen($new_password) < 8) {
      $errors[] = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
      $errors[] = 'Passwords do not match.';
    }

    if (empty($errors) && $user) {
      // Reset the password
      reset_password($user, $new_password);

      // Log the user in automatically
      wp_set_auth_cookie($user->ID);
      wp_set_current_user($user->ID);
      do_action('wp_login', $user->user_login, $user);

      // Redirect to dashboard
      wp_redirect(Member::get_dashboard_url());
      exit;
    }
  }
}

// Start Barba.js container
get_template_part('template-parts/barba-container-start');
?>


<div id="member-reset-password" class="flex min-h-[70vh] items-center justify-center bg-background">
  <div class="w-full max-w-md mx-auto">
    <div class="card p-8 shadow-lg">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold mb-2">Reset Password</h1>
        <?php if ($show_form): ?>
          <p class="text-muted-foreground">Enter your new password below</p>
        <?php endif; ?>
      </div>

      <!-- Error Messages -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
          <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?php echo wp_kses_post($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <?php if (!$show_form): ?>
          <div class="text-center space-y-4">
            <a href="<?php echo Member::get_forgot_password_url(); ?>" class="btn w-full">
              Request New Reset Link
            </a>
            <a href="<?php echo Member::get_login_url(); ?>" class="text-sm text-blue-600 hover:underline block">
              ← Back to Login
            </a>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Reset Password Form -->
      <?php if ($show_form): ?>
        <form method="post" action="" class="form grid gap-6">
          <?php wp_nonce_field('member_reset_password_action', 'member_reset_password_nonce'); ?>

          <div class="grid gap-2">
            <label for="new_password" class="label">New Password</label>
            <input type="password" id="new_password" name="new_password" class="input"
              autocomplete="new-password" required minlength="8">
            <p class="text-xs text-muted-foreground">Password must be at least 8 characters long</p>
          </div>

          <div class="grid gap-2">
            <label for="confirm_password" class="label">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="input"
              autocomplete="new-password" required minlength="8">
          </div>

          <button type="submit" name="member_reset_password_submit" class="btn w-full">Reset Password</button>

          <div class="text-center">
            <a href="<?php echo Member::get_login_url(); ?>" class="text-sm text-blue-600 hover:underline">
              ← Back to Login
            </a>
          </div>
        </form>
      <?php endif; ?>

    </div>
    <div class="mt-6 text-center text-sm text-muted-foreground">
      <p>
        <a href="<?php echo home_url(); ?>" class="text-blue-600 hover:underline">← Back to Home</a>
      </p>
    </div>
  </div>
</div>

<?php
// End Barba.js container
get_template_part('template-parts/barba-container-end');

get_footer();
?>
