<?php
/**
 * Template Name: Members
 *
 * Landing page for /members/ - redirects logged-in users to dashboard
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

// Redirect logged-in users to dashboard
if (is_user_logged_in()) {
  wp_redirect(Member::get_dashboard_url());
  exit;
}

// For logged-out users, show the default page content or redirect to login
get_header();

// Start Barba.js container
get_template_part('template-parts/barba-container-start');
?>

<div id="members-landing" class="flex min-h-[70vh] items-center justify-center bg-background">
  <div class="w-full max-w-2xl mx-auto text-center px-6">
    <div class="card p-12 shadow-lg">
      <h1 class="text-4xl font-bold mb-4">Members Area</h1>
      <p class="text-xl text-muted-foreground mb-8">
        Access your member dashboard, resources, and exclusive content.
      </p>

      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?php echo Member::get_login_url(); ?>" class="btn btn-primary">
          Member Login
        </a>
        <a href="<?php echo home_url('/contact'); ?>" class="btn btn-secondary">
          Become a Member
        </a>
      </div>

      <div class="mt-8 text-sm text-muted-foreground">
        <p>Already a member? <a href="<?php echo Member::get_login_url(); ?>" class="text-blue-600 hover:underline">Sign in here</a></p>
      </div>
    </div>
  </div>
</div>

<?php
// End Barba.js container
get_template_part('template-parts/barba-container-end');

get_footer();
?>
