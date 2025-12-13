<?php
/**
 * Required Plugin Registration for Elite Enterprise Theme
 *
 * Registers and enforces required plugins for the Elite Enterprise theme.
 * These plugins are essential for theme functionality and cannot be deactivated
 * while the theme is active.
 *
 * @package    EliteEnterprise
 * @since      1.0.0
 */

/**
 * Include the TGM_Plugin_Activation class
 */
require_once dirname(__FILE__) . '/class-tgm-plugin-activation.php';

add_action('tgmpa_register', 'elite_enterprise_register_required_plugins');

/**
 * Register the required plugins for Elite Enterprise theme.
 *
 * This function registers the following required plugins:
 * - Elementor: Page builder (free version from WordPress.org)
 * - Elementor Pro: Premium page builder features
 * - Advanced Custom Fields (ACF): Custom fields management
 * - Gravity Forms: Form builder and submission handler
 *
 * All plugins are set to force_activation, meaning they cannot be deactivated
 * while this theme is active, ensuring core theme functionality remains intact.
 *
 * @since 1.0.0
 */
function elite_enterprise_register_required_plugins()
{
	$plugins = array(
		// Elementor (Free version from WordPress.org)
		array(
			'name'               => 'Elementor',
			'slug'               => 'elementor',
			'required'           => true,
			'force_activation'   => true,
			'force_deactivation' => false,
		),

		// Elementor Pro (Premium - must be installed manually or via license)
		array(
			'name'               => 'Elementor Pro',
			'slug'               => 'elementor-pro',
			'required'           => true,
			'force_activation'   => true,
			'force_deactivation' => false,
			'is_callable'        => array('ElementorPro\Plugin', 'instance'),
		),

		// Advanced Custom Fields Pro
		array(
			'name'               => 'Advanced Custom Fields PRO',
			'slug'               => 'advanced-custom-fields-pro',
			'required'           => true,
			'force_activation'   => true,
			'force_deactivation' => false,
			'is_callable'        => 'acf',
		),

		// Gravity Forms (Premium plugin)
		array(
			'name'               => 'Gravity Forms',
			'slug'               => 'gravityforms',
			'required'           => true,
			'force_activation'   => true,
			'force_deactivation' => false,
			'is_callable'        => array('GFForms', 'setup'),
		),
	);

	$config = array(
		'id'           => 'elite-enterprise',
		'default_path' => '',
		'menu'         => 'tgmpa-install-plugins',
		'parent_slug'  => 'themes.php',
		'capability'   => 'edit_theme_options',
		'has_notices'  => true,
		'dismissable'  => false,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '<strong>Elite Enterprise Theme:</strong> This theme requires the following plugins to function properly. These plugins cannot be deactivated while the theme is active.',
		'strings'      => array(
			'page_title'                     => __('Install Required Plugins', 'elite-enterprise'),
			'menu_title'                     => __('Install Plugins', 'elite-enterprise'),
			'installing'                     => __('Installing Plugin: %s', 'elite-enterprise'),
			'updating'                       => __('Updating Plugin: %s', 'elite-enterprise'),
			'oops'                           => __('Something went wrong with the plugin API.', 'elite-enterprise'),
			'notice_can_install_required'    => _n_noop(
				'This theme requires the following plugin: %1$s.',
				'This theme requires the following plugins: %1$s.',
				'elite-enterprise'
			),
			'notice_can_activate_required'   => _n_noop(
				'The following required plugin is currently inactive: %1$s.',
				'The following required plugins are currently inactive: %1$s.',
				'elite-enterprise'
			),
			'notice_ask_to_update'           => _n_noop(
				'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
				'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
				'elite-enterprise'
			),
			'notice_cannot_deactivate'       => __('These required plugins cannot be deactivated while the Elite Enterprise theme is active.', 'elite-enterprise'),
			'install_link'                   => _n_noop(
				'Begin installing plugin',
				'Begin installing plugins',
				'elite-enterprise'
			),
			'update_link'                    => _n_noop(
				'Begin updating plugin',
				'Begin updating plugins',
				'elite-enterprise'
			),
			'activate_link'                  => _n_noop(
				'Begin activating plugin',
				'Begin activating plugins',
				'elite-enterprise'
			),
			'return'                         => __('Return to Required Plugins Installer', 'elite-enterprise'),
			'plugin_activated'               => __('Plugin activated successfully.', 'elite-enterprise'),
			'activated_successfully'         => __('The following plugin was activated successfully:', 'elite-enterprise'),
			'plugin_already_active'          => __('No action taken. Plugin %1$s was already active.', 'elite-enterprise'),
			'plugin_needs_higher_version'    => __('Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'elite-enterprise'),
			'complete'                       => __('All plugins installed and activated successfully. %1$s', 'elite-enterprise'),
			'dismiss'                        => __('Dismiss this notice', 'elite-enterprise'),
			'notice_cannot_install_activate' => __('There are one or more required plugins to install, update or activate.', 'elite-enterprise'),
			'contact_admin'                  => __('Please contact the administrator of this site for help.', 'elite-enterprise'),
			'nag_type'                       => 'error',
		),
	);

	tgmpa($plugins, $config);
}
