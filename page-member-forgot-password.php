<?php
/**
 * Template Name: Member Forgot Password
 *
 * Custom page template for member password reset request
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

if (isset($_POST['member_forgot_password_submit'])) {
  if (!isset($_POST['member_forgot_password_nonce']) || !wp_verify_nonce($_POST['member_forgot_password_nonce'], 'member_forgot_password_action')) {
    $errors[] = 'Security check failed. Please try again.';
  } else {
    $user_login = sanitize_text_field($_POST['user_login']);

    if (empty($user_login)) {
      $errors[] = 'Username or email address is required.';
    } else {
      // Find user by username or email
      $user = false;
      if (is_email($user_login)) {
        $user = get_user_by('email', $user_login);
      } else {
        $user = get_user_by('login', $user_login);
      }

      if (!$user) {
        // Don't reveal if user exists or not for security
        $success_message = 'If an account exists with that email/username, you will receive a password reset link shortly.';
      } elseif (!Member::is_user_member($user->ID)) {
        // Not a member, but don't reveal this
        $success_message = 'If an account exists with that email/username, you will receive a password reset link shortly.';
      } else {
        // Send password reset email
        $reset_key = get_password_reset_key($user);

        if (is_wp_error($reset_key)) {
          $errors[] = 'Unable to generate reset key. Please try again.';
        } else {
          // Get custom reset URL
          $reset_url = Member::get_password_reset_url($user->user_login, $reset_key);

          // Send email
          $sent = Member::send_password_reset_email($user, $reset_url);

          if ($sent) {
            $success_message = 'Password reset instructions have been sent to your email address.';
          } else {
            $errors[] = 'Unable to send reset email. Please try again.';
          }
        }
      }
    }
  }
}

// Start Barba.js container
get_template_part('template-parts/barba-container-start');
?>


<div id="member-forgot-password" class="flex min-h-[70vh] items-center justify-center bg-background">
  <div class="w-full max-w-md mx-auto">
    <div class="card p-8 shadow-lg">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold mb-2">Forgot Password</h1>
        <p class="text-muted-foreground">Enter your username or email address to receive password reset instructions</p>
      </div>

      <!-- Success Message -->
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success mb-4">
          <?php echo esc_html($success_message); ?>
        </div>
      <?php endif; ?>

      <!-- Error Messages -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
          <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?php echo wp_kses_post($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Forgot Password Form -->
      <form method="post" action="" class="form grid gap-6">
        <?php wp_nonce_field('member_forgot_password_action', 'member_forgot_password_nonce'); ?>

        <div class="grid gap-2">
          <label for="user_login" class="label">Username or Email Address</label>
          <input type="text" id="user_login" name="user_login" class="input" autocomplete="username"
            value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>" required>
        </div>

        <button type="submit" name="member_forgot_password_submit" class="btn w-full">Send Reset Link</button>

        <div class="text-center space-y-2">
          <a href="<?php echo Member::get_login_url(); ?>" class="text-sm text-blue-600 hover:underline block">
            ← Back to Login
          </a>
        </div>
      </form>

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
