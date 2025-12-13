<?php
/**
 * Template Name: Collaborator Login
 *
 * Custom page template for collaborator login
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

// Redirect if already logged in
if (is_user_logged_in() && Collaborator::is_user_collaborator()) {
  wp_redirect(Collaborator::get_dashboard_url());
  exit;
}

get_header();

// Handle login form submission
$login_errors = [];
$login_message = '';

if (isset($_GET['action'])) {
  if ($_GET['action'] === 'logout') {
    $login_message = 'You have been logged out successfully.';
  } elseif ($_GET['action'] === 'registered') {
    $login_message = 'Registration successful! Please log in.';
  }
}

// Server-side fallback for non-JavaScript users
// This will only run if JavaScript is disabled or AJAX fails
if (isset($_POST['collaborator_login_submit'])) {
  if (!isset($_POST['collaborator_login_nonce']) || !wp_verify_nonce($_POST['collaborator_login_nonce'], 'collaborator_login_action')) {
    $login_errors[] = 'Security check failed. Please try again.';
  } else {
    $username = sanitize_user($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($username)) {
      $login_errors[] = 'Username is required.';
    }
    if (empty($password)) {
      $login_errors[] = 'Password is required.';
    }

    if (empty($login_errors)) {
      $creds = [
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember,
      ];

      $user = wp_signon($creds, false);

      if (is_wp_error($user)) {
        $login_errors[] = $user->get_error_message();
      } else {
        // Check if user has collaborator role
        if (Collaborator::is_user_collaborator($user->ID)) {
          wp_redirect(Collaborator::get_dashboard_url());
          exit;
        } else {
          // Not a collaborator, log them out
          wp_logout();
          $login_errors[] = 'This login page is for collaborators only. Please use the appropriate login page.';
        }
      }
    }
  }
}

// Start Barba.js container
get_template_part('template-parts/barba-container-start');
?>


<div id="collaborator-login" class="flex min-h-[70vh] items-center justify-center bg-background">
  <div class="w-full max-w-md mx-auto">
    <div class="card p-8 shadow-lg">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold mb-2">Collaborator Login</h1>
        <p class="text-muted-foreground">Sign in to access the member intake form</p>
      </div>

      <!-- Messages -->
      <?php if (!empty($login_message)): ?>
        <div class="alert alert-success mb-4">
          <?php echo esc_html($login_message); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($login_errors)): ?>
        <div class="alert alert-danger mb-4">
          <ul class="list-disc list-inside">
            <?php foreach ($login_errors as $error): ?>
              <li><?php echo wp_kses_post($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="post" action="" class="form grid gap-6 collaborator-login-form">
        <?php wp_nonce_field('collaborator_login_action', 'collaborator_login_nonce'); ?>

        <div class="grid gap-2">
          <label for="username" class="label">Username or Email</label>
          <input type="text" id="username" name="username" class="input" autocomplete="username"
            value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>" required>
        </div>

        <div class="grid gap-2">
          <label for="password" class="label">Password</label>
          <input type="password" id="password" name="password" class="input" autocomplete="current-password" required>
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" id="remember" name="remember" class="checkbox" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
          <label for="remember" class="label font-normal">Remember Me</label>
        </div>

        <button type="submit" name="collaborator_login_submit" class="btn w-full">Sign In</button>

        <div class="text-center">
          <a href="<?php echo wp_lostpassword_url(); ?>"
            class="text-sm text-blue-600 hover:underline">Forgot your password?</a>
        </div>
      </form>

    </div>
    <div class="mt-6 text-center text-sm text-muted-foreground">
      <p>
        Need collaborator access? <a href="<?php echo home_url('/contact'); ?>" class="text-blue-600 hover:underline">Contact
          us</a>
      </p>
      <p class="mt-2">
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
