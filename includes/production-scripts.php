<?php
/**
 * Production Scripts Loader
 * Loads bundled and optimized assets from the dist folder
 *
 * @package EliteEnterpriseTheme
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

class ThemeProductionScripts
{
  private $manifest;

  function __construct()
  {
    add_action('elementor/frontend/after_register_scripts', array($this, 'register_theme_scripts'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_wp_i18n'));
    add_filter('script_loader_tag', array($this, 'add_module_type_attribute'), 10, 3);
    $this->load_manifest();
  }

  /**
   * Enqueue WordPress i18n script for sprintf and other functions
   */
  public function enqueue_wp_i18n()
  {
    wp_enqueue_script('wp-i18n');
  }

  /**
   * Load the Vite manifest file
   */
  private function load_manifest()
  {
    $manifest_path = get_template_directory() . '/dist/.vite/manifest.json';
    if (file_exists($manifest_path)) {
      $manifest_content = file_get_contents($manifest_path);
      $this->manifest = json_decode($manifest_content, true);
    }
  }

  /**
   * Get asset path from manifest
   */
  private function get_asset_path($entry)
  {
    if (isset($this->manifest[$entry]['file'])) {
      return get_template_directory_uri() . '/dist/' . $this->manifest[$entry]['file'];
    }
    return null;
  }

  /**
   * Add type="module" attribute to script tags for ES modules
   * Also preload imported chunks for better performance
   */
  public function add_module_type_attribute($tag, $handle, $src)
  {
    // Add type="module" to our main script
    if ($handle === 'elite-theme-main') {
      // Get chunk imports from manifest
      $modulepreload_links = '';
      if ($this->manifest && isset($this->manifest['assets/js/main.js']['imports'])) {
        foreach ($this->manifest['assets/js/main.js']['imports'] as $import) {
          if (isset($this->manifest[$import]['file'])) {
            $chunk_url = get_template_directory_uri() . '/dist/' . $this->manifest[$import]['file'];
            $modulepreload_links .= '<link rel="modulepreload" href="' . esc_url($chunk_url) . '">' . "\n";
          }
        }
      }

      // Return modulepreload links + module script tag
      return $modulepreload_links . '<script type="module" src="' . esc_url($src) . '"></script>';
    }

    return $tag;
  }

  /**
   * Enqueue bundled JavaScript
   */
  public function enqueue_main_js()
  {
    // Get the main JS file from the manifest
    $main_js = $this->get_asset_path('assets/js/main.js');

    if ($main_js) {
      wp_enqueue_script(
        'elite-theme-main',
        $main_js,
        array('jquery', 'wp-i18n'),
        HELLO_ELEMENTOR_VERSION,
        true
      );
    } else {
      // Fallback to direct path if manifest not available
      wp_enqueue_script(
        'elite-theme-main',
        get_template_directory_uri() . '/dist/js/main.min.js',
        array('jquery', 'wp-i18n'),
        HELLO_ELEMENTOR_VERSION,
        true
      );
    }
  }

  /**
   * Enqueue production CSS
   */
  public function enqueue_main_css()
  {
    // Check if CSS was extracted from main.js by Vite
    if ($this->manifest && isset($this->manifest['assets/js/main.js']['css'])) {
      foreach ($this->manifest['assets/js/main.js']['css'] as $css_file) {
        wp_enqueue_style(
          'elite-theme-main-css',
          get_template_directory_uri() . '/dist/' . $css_file,
          array(),
          HELLO_ELEMENTOR_VERSION,
          'all'
        );
      }
    }
  }

  /**
   * Register all theme scripts
   */
  public function register_theme_scripts()
  {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_main_js'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_main_css'));
  }
}

new ThemeProductionScripts();
