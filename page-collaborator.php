<?php
/**
 * Template Name: Collaborator Dashboard
 *
 * Custom page template for collaborator dashboard
 * This page redirects to the intake form
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

// Redirect to intake form page
wp_redirect(home_url('/collaborator/intake-form'));
exit;
