<?php
/**
 * Template part for Barba.js container wrapper - Opening
 * 
 * This file contains the opening tag for Barba.js page transitions
 * Include this at the start of any page template after get_header()
 * 
 * @package EliteEnterprise
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

// Generate namespace/slug for the current page
$namespace = 'page';

if (is_page()) {
  $post = get_post();
  $namespace = $post->post_name;
} elseif (is_archive()) {
  $namespace = 'archive';
} elseif (is_search()) {
  $namespace = 'search';
} elseif (is_404()) {
  $namespace = '404';
} elseif (is_singular()) {
  $post = get_post();
  $namespace = $post->post_name;
}
?>
<div data-barba="container" data-barba-namespace="<?php echo esc_attr($namespace); ?>" id="site-main-wrapper">