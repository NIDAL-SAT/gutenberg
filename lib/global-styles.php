<?php
/**
 * Bootstraps Global Styles.
 *
 * @package gutenberg
 */

/**
 * Takes a tree adhering to the theme.json schema and generates
 * the corresponding stylesheet.
 *
 * @param WP_Theme_JSON_Gutenberg $tree  Input tree.
 * @param array                   $types Which styles to load. It accepts 'variables', 'styles', 'presets'. By default, it'll load all.
 *
 * @return string Stylesheet.
 */
function gutenberg_experimental_global_styles_get_stylesheet( $tree, $types = array( 'variables', 'styles', 'presets' ) ) {
	$origins             = array( 'core', 'theme', 'user' );
	$supports_theme_json = WP_Theme_JSON_Resolver_Gutenberg::theme_has_support();
	$supports_link_color = get_theme_support( 'experimental-link-color' );
	if ( ! $supports_theme_json && ! $supports_link_color ) {
		// In this case we only enqueue the core presets (CSS Custom Properties + the classes).
		$origins = array( 'core' );
	} elseif ( ! $supports_theme_json && $supports_link_color ) {
		// For the legacy link color feature to work, the CSS Custom Properties
		// should be in scope (either the core or the theme ones).
		$origins = array( 'core', 'theme' );
	}

	$stylesheet = $tree->get_stylesheet( $types, $origins );

	return $stylesheet;
}

/**
 * Fetches the preferences for each origin (core, theme, user)
 * and enqueues the resulting stylesheet.
 */
function gutenberg_experimental_global_styles_enqueue_assets() {
	$stylesheet = gutenberg_get_global_stylesheet();
	if ( empty( $stylesheet ) ) {
		return;
	}

	if ( isset( wp_styles()->registered['global-styles'] ) ) {
		wp_styles()->registered['global-styles']->extra['after'][0] = $stylesheet;
	} else {
		wp_register_style( 'global-styles', false, array(), true, true );
		wp_add_inline_style( 'global-styles', $stylesheet );
		wp_enqueue_style( 'global-styles' );
	}
}

/**
 * Adds the necessary settings for the Global Styles client UI.
 *
 * @param array $settings Existing block editor settings.
 *
 * @return array New block editor settings.
 */
function gutenberg_experimental_global_styles_settings( $settings ) {
	// Set what is the context for this data request.
	$context = 'other';
	if (
		is_callable( 'get_current_screen' ) &&
		function_exists( 'gutenberg_is_edit_site_page' ) &&
		gutenberg_is_edit_site_page( get_current_screen()->id )
	) {
		$context = 'site-editor';
	}

	if (
		defined( 'REST_REQUEST' ) &&
		REST_REQUEST &&
		isset( $_GET['context'] ) &&
		'mobile' === $_GET['context']
	) {
		$context = 'mobile';
	}

	$consolidated = WP_Theme_JSON_Resolver_Gutenberg::get_merged_data( $settings );

	if ( 'mobile' === $context && WP_Theme_JSON_Resolver_Gutenberg::theme_has_support() ) {
		$settings['__experimentalStyles'] = $consolidated->get_raw_data()['styles'];
	}

	if ( 'site-editor' === $context && gutenberg_experimental_is_site_editor_available() ) {
		$theme = WP_Theme_JSON_Resolver_Gutenberg::get_merged_data( $settings, 'theme' );

		$settings['__experimentalGlobalStylesBaseConfig']['styles']   = $theme->get_raw_data()['styles'];
		$settings['__experimentalGlobalStylesBaseConfig']['settings'] = $theme->get_settings();
	}

	if ( 'other' === $context ) {
		// Make sure the styles array exists.
		// In some contexts, like the navigation editor, it doesn't.
		if ( ! isset( $settings['styles'] ) ) {
			$settings['styles'] = array();
		}

		// Reset existing global styles.
		$styles_without_existing_global_styles = array();
		foreach ( $settings['styles'] as $style ) {
			if ( ! isset( $style['__unstableType'] ) || 'globalStyles' !== $style['__unstableType'] ) {
				$styles_without_existing_global_styles[] = $style;
			}
		}

		$new_global_styles = array();
		$new_presets       = array(
			array(
				'css'                     => 'variables',
				'__unstableType'          => 'presets',
				'__experimentalNoWrapper' => true,
			),
			array(
				'css'            => 'presets',
				'__unstableType' => 'presets',
			),
		);
		foreach ( $new_presets as $new_style ) {
			$style_css = gutenberg_experimental_global_styles_get_stylesheet( $consolidated, array( $new_style['css'] ) );
			if ( '' !== $style_css ) {
				$new_style['css']    = $style_css;
				$new_global_styles[] = $new_style;
			}
		}

		$new_block_classes = array(
			'css'            => 'styles',
			'__unstableType' => 'theme',
		);
		if ( WP_Theme_JSON_Resolver_Gutenberg::theme_has_support() ) {
			$style_css = gutenberg_experimental_global_styles_get_stylesheet( $consolidated, array( $new_block_classes['css'] ) );
			if ( '' !== $style_css ) {
				$new_block_classes['css'] = $style_css;
				$new_global_styles[]      = $new_block_classes;
			}
		}

		$settings['styles'] = array_merge( $styles_without_existing_global_styles, $new_global_styles );
	}

	// Copied from get_block_editor_settings() at wordpress-develop/block-editor.php.
	$settings['__experimentalFeatures'] = $consolidated->get_settings();

	if ( isset( $settings['__experimentalFeatures']['color']['palette'] ) ) {
		$colors_by_origin   = $settings['__experimentalFeatures']['color']['palette'];
		$settings['colors'] = isset( $colors_by_origin['user'] ) ?
			$colors_by_origin['user'] : (
				isset( $colors_by_origin['theme'] ) ?
					$colors_by_origin['theme'] :
					$colors_by_origin['core']
			);
	}

	if ( isset( $settings['__experimentalFeatures']['color']['gradients'] ) ) {
		$gradients_by_origin   = $settings['__experimentalFeatures']['color']['gradients'];
		$settings['gradients'] = isset( $gradients_by_origin['user'] ) ?
			$gradients_by_origin['user'] : (
				isset( $gradients_by_origin['theme'] ) ?
					$gradients_by_origin['theme'] :
					$gradients_by_origin['core']
			);
	}

	if ( isset( $settings['__experimentalFeatures']['typography']['fontSizes'] ) ) {
		$font_sizes_by_origin  = $settings['__experimentalFeatures']['typography']['fontSizes'];
		$settings['fontSizes'] = isset( $font_sizes_by_origin['user'] ) ?
			$font_sizes_by_origin['user'] : (
				isset( $font_sizes_by_origin['theme'] ) ?
					$font_sizes_by_origin['theme'] :
					$font_sizes_by_origin['core']
			);
	}

	if ( isset( $settings['__experimentalFeatures']['color']['custom'] ) ) {
		$settings['disableCustomColors'] = ! $settings['__experimentalFeatures']['color']['custom'];
		unset( $settings['__experimentalFeatures']['color']['custom'] );
	}
	if ( isset( $settings['__experimentalFeatures']['color']['customGradient'] ) ) {
		$settings['disableCustomGradients'] = ! $settings['__experimentalFeatures']['color']['customGradient'];
		unset( $settings['__experimentalFeatures']['color']['customGradient'] );
	}
	if ( isset( $settings['__experimentalFeatures']['typography']['customFontSize'] ) ) {
		$settings['disableCustomFontSizes'] = ! $settings['__experimentalFeatures']['typography']['customFontSize'];
		unset( $settings['__experimentalFeatures']['typography']['customFontSize'] );
	}
	if ( isset( $settings['__experimentalFeatures']['typography']['customLineHeight'] ) ) {
		$settings['enableCustomLineHeight'] = $settings['__experimentalFeatures']['typography']['customLineHeight'];
		unset( $settings['__experimentalFeatures']['typography']['customLineHeight'] );
	}
	if ( isset( $settings['__experimentalFeatures']['spacing']['units'] ) ) {
		$settings['enableCustomUnits'] = $settings['__experimentalFeatures']['spacing']['units'];
	}
	if ( isset( $settings['__experimentalFeatures']['spacing']['customPadding'] ) ) {
		$settings['enableCustomSpacing'] = $settings['__experimentalFeatures']['spacing']['customPadding'];
		unset( $settings['__experimentalFeatures']['spacing']['customPadding'] );
	}

	return $settings;
}

/**
 * Whether or not the Site Editor is available.
 *
 * @return boolean
 */
function gutenberg_experimental_is_site_editor_available() {
	return gutenberg_is_fse_theme();
}

/**
 * Register CPT to store/access user data.
 *
 * @return void
 */
function gutenberg_experimental_global_styles_register_user_cpt() {
	if ( gutenberg_experimental_is_site_editor_available() ) {
		WP_Theme_JSON_Resolver_Gutenberg::register_user_custom_post_type();
	}
}

/**
 * Sanitizes global styles user content removing unsafe rules.
 *
 * @param string $content Post content to filter.
 * @return string Filtered post content with unsafe rules removed.
 */
function gutenberg_global_styles_filter_post( $content ) {
	$decoded_data        = json_decode( wp_unslash( $content ), true );
	$json_decoding_error = json_last_error();
	if (
		JSON_ERROR_NONE === $json_decoding_error &&
		is_array( $decoded_data ) &&
		isset( $decoded_data['isGlobalStylesUserThemeJSON'] ) &&
		$decoded_data['isGlobalStylesUserThemeJSON']
	) {
		unset( $decoded_data['isGlobalStylesUserThemeJSON'] );

		$data_to_encode = WP_Theme_JSON_Gutenberg::remove_insecure_properties( $decoded_data );

		$data_to_encode['isGlobalStylesUserThemeJSON'] = true;
		return wp_slash( wp_json_encode( $data_to_encode ) );
	}
	return $content;
}

/**
 * Adds the filters to filter global styles user theme.json.
 */
function gutenberg_global_styles_kses_init_filters() {
	add_filter( 'content_save_pre', 'gutenberg_global_styles_filter_post' );
	add_filter( 'safe_style_css', 'gutenberg_global_styles_include_support_for_duotone', 10, 2 );
}

/**
 * Removes the filters to filter global styles user theme.json.
 */
function gutenberg_global_styles_kses_remove_filters() {
	remove_filter( 'content_save_pre', 'gutenberg_global_styles_filter_post' );
}

/**
 * Register global styles kses filters if the user does not have unfiltered_html capability.
 *
 * @uses render_block_core_navigation()
 * @throws WP_Error An WP_Error exception parsing the block definition.
 */
function gutenberg_global_styles_kses_init() {
	gutenberg_global_styles_kses_remove_filters();
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		gutenberg_global_styles_kses_init_filters();
	}
}

/**
 * This filter is the last being executed on force_filtered_html_on_import.
 * If the input of the filter is true it means we are in an import situation and should
 * enable kses, independently of the user capabilities.
 * So in that case we call gutenberg_global_styles_kses_init_filters;
 *
 * @param string $arg Input argument of the filter.
 * @return string Exactly what was passed as argument.
 */
function gutenberg_global_styles_force_filtered_html_on_import_filter( $arg ) {
	// force_filtered_html_on_import is true we need to init the global styles kses filters.
	if ( $arg ) {
		gutenberg_global_styles_kses_init_filters();
	}
	return $arg;
}

/**
 * This filter is the last being executed on force_filtered_html_on_import.
 * If the input of the filter is true it means we are in an import situation and should
 * enable kses, independently of the user capabilities.
 * So in that case we call gutenberg_global_styles_kses_init_filters;
 *
 * @param bool $allow_css       Whether the CSS in the test string is considered safe.
 * @param bool $css_test_string The CSS string to test..
 * @return bool If $allow_css is true it returns true.
 * If $allow_css is false and the CSS rule is referencing a WordPress css variable it returns true.
 * Otherwise the function return false.
 */
function gutenberg_global_styles_include_support_for_wp_variables( $allow_css, $css_test_string ) {
	if ( $allow_css ) {
		return $allow_css;
	}
	$allowed_preset_attributes = array(
		'background',
		'background-color',
		'border-color',
		'color',
		'font-family',
		'font-size',
	);
	$parts                     = explode( ':', $css_test_string, 2 );

	if ( ! in_array( trim( $parts[0] ), $allowed_preset_attributes, true ) ) {
		return $allow_css;
	}
	return ! ! preg_match( '/^var\(--wp-[a-zA-Z0-9\-]+\)$/', trim( $parts[1] ) );
}

/**
 * This is for using kses to test user data.
 *
 * @param array $atts Allowed CSS property names, according to kses.
 *
 * @return array The new allowed CSS property names.
 */
function gutenberg_global_styles_include_support_for_duotone( $atts ) {
	$atts[] = 'filter';
	return $atts;
}

// The else clause can be removed when plugin support requires WordPress 5.8.0+.
if ( function_exists( 'get_block_editor_settings' ) ) {
	add_filter( 'block_editor_settings_all', 'gutenberg_experimental_global_styles_settings', PHP_INT_MAX );
} else {
	add_filter( 'block_editor_settings', 'gutenberg_experimental_global_styles_settings', PHP_INT_MAX );
}

add_action( 'init', 'gutenberg_experimental_global_styles_register_user_cpt' );
add_action( 'wp_enqueue_scripts', 'gutenberg_experimental_global_styles_enqueue_assets' );

// kses actions&filters.
add_action( 'init', 'gutenberg_global_styles_kses_init' );
add_action( 'set_current_user', 'gutenberg_global_styles_kses_init' );
add_filter( 'force_filtered_html_on_import', 'gutenberg_global_styles_force_filtered_html_on_import_filter', 999 );
add_filter( 'safecss_filter_attr_allow_css', 'gutenberg_global_styles_include_support_for_wp_variables', 10, 2 );
// This filter needs to be executed last.
