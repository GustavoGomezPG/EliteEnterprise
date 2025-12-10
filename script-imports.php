<?php
/**
 * Development Scripts Loader
 * Loads scripts from Vite dev server with HMR support
 *
 * @package EliteEnterpriseTheme
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

class ThemeScripts
{
  private $vite_dev_server_url = 'http://localhost:3000';

  function __construct()
  {
    // Add Vite dev server script tags to wp_head
    add_action('wp_head', array($this, 'add_vite_dev_server_tags'), 1);
  }

  /**
   * Add Vite dev server script tags
   * Vite handles all imports, CSS, and HMR automatically through main.js
   */
  public function add_vite_dev_server_tags()
  {
    // Vite client for HMR
    echo '<script type="module" src="' . esc_url($this->vite_dev_server_url . '/@vite/client') . '"></script>' . "\n";

    // Main entry point - Vite will handle all imports from here
    echo '<script type="module" src="' . esc_url($this->vite_dev_server_url . '/assets/js/main.js') . '"></script>' . "\n";
  }
}

new ThemeScripts();