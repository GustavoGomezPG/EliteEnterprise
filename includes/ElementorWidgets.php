<?php
/**
 * ElementorWidgets Loader
 *
 * Scans all folders in includes/elementor/ and loads any Elementor widget PHP files found.
 * Provides ElementorWidgets::enqueue() to register all widgets with Elementor.
 *
 * @package EliteEnterprise
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class ElementorWidgets
{
  /**
   * Enqueue all Elementor widgets found in includes/elementor/*
   */
  public static function enqueue()
  {
    // Register the custom category
    add_action('elementor/elements/categories_registered', [__CLASS__, 'register_member_category']);
    // Register widgets
    add_action('elementor/widgets/register', [__CLASS__, 'register_widgets']);
  }

  /**
   * Register custom Elementor widget category for Member widgets
   */
  public static function register_member_category($elements_manager) {
    $elements_manager->add_category(
      'member-widgets',
      [
        'title' => __('Member widgets', 'elite-enterprise'),
        'icon' => 'eicon-lock-user',
      ]
    );
  }

  /**
   * Register all widgets in includes/elementor/*
   * @param $widgets_manager
   */
  public static function register_widgets($widgets_manager)
  {
    // Load Member class if it exists (needed for member widgets)
    $member_file = get_template_directory() . '/includes/Member.php';
    if (file_exists($member_file)) {
      require_once $member_file;
    }

    $widgets_dir = get_template_directory() . '/includes/elementor/';
    if (!is_dir($widgets_dir)) {
      return;
    }
    $folders = glob($widgets_dir . '*', GLOB_ONLYDIR);
    foreach ($folders as $folder) {
      $widget_files = glob($folder . '/*.php');
      foreach ($widget_files as $file) {
        require_once $file;
        // Find the widget class in the file (assume 1 class per file, class name = file name StudlyCase)
        $class_name = self::get_widget_class_from_file($file);
        if ($class_name && class_exists($class_name)) {
          $widget_instance = new $class_name();
          // If the widget has a set_categories method, set it to 'member-widgets'
          if (method_exists($widget_instance, 'set_categories')) {
            $widget_instance->set_categories(['member-widgets']);
          }
          $widgets_manager->register($widget_instance);
        }
      }
    }
  }

  /**
   * Guess the widget class name from the file name (e.g. my-widget.php => My_Widget)
   * @param string $file
   * @return string|null
   */
  private static function get_widget_class_from_file($file)
  {
    $base = basename($file, '.php');
    $parts = explode('-', $base);
    $class = array_map('ucfirst', $parts);
    $class_name = implode('_', $class);
    // Elementor widgets usually extend Widget_Base
    if (class_exists($class_name)) {
      return $class_name;
    }
    // Try with Widget suffix
    if (class_exists($class_name . '_Widget')) {
      return $class_name . '_Widget';
    }
    return null;
  }
}
