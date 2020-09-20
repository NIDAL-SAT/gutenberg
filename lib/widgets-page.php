<?php
/**
 * Bootstraping the Gutenberg widgets page.
 *
 * @package gutenberg
 */

/**
 * The main entry point for the Gutenberg widgets page.
 *
 * @since 5.2.0
 *
 * @param string $page      The page name the function is being called for, `'gutenberg_customizer'` for the Customizer.
 * @param string $widget_id The widget ID the function is rendering, used in customizer.
 */
function the_gutenberg_widgets( $page = 'appearance_page_gutenberg-widgets', $widget_id = '' ) {
	?>
	<div
		class="blocks-widgets-container widgets-editor
		<?php
		echo 'gutenberg_customizer' === $page
			? ' is-in-customizer'
			: '';
		?>
		"
		<?php echo $widget_id ? 'data-widget-id="' . $widget_id . '"' : ''; ?>
	>
	</div>
	<?php
}

/**
 * Initialize the Gutenberg widgets page.
 *
 * @since 5.2.0
 *
 * @param string $hook      Page.
 * @param string $widget_id The widget ID.
 */
function gutenberg_widgets_init( $hook, $widget_id = '' ) {
	if ( 'widgets.php' === $hook ) {
		wp_enqueue_style( 'wp-block-library' );
		wp_enqueue_style( 'wp-block-library-theme' );
		wp_add_inline_style(
			'wp-block-library-theme',
			'.block-widget-form .widget-control-save { display: none; }'
		);
		return;
	}
	if ( ! in_array( $hook, array( 'appearance_page_gutenberg-widgets', 'gutenberg_customizer' ), true ) ) {
		return;
	}

	$initializer_name = 'appearance_page_gutenberg-widgets' === $hook
		? 'initialize'
		: 'customizerInitialize';

	// Media settings.
	$max_upload_size = wp_max_upload_size();
	if ( ! $max_upload_size ) {
		$max_upload_size = 0;
	}

	/** This filter is documented in wp-admin/includes/media.php */
	$image_size_names = apply_filters(
		'image_size_names_choose',
		array(
			'thumbnail' => __( 'Thumbnail', 'gutenberg' ),
			'medium'    => __( 'Medium', 'gutenberg' ),
			'large'     => __( 'Large', 'gutenberg' ),
			'full'      => __( 'Full Size', 'gutenberg' ),
		)
	);

	$available_image_sizes = array();
	foreach ( $image_size_names as $image_size_slug => $image_size_name ) {
		$available_image_sizes[] = array(
			'slug' => $image_size_slug,
			'name' => $image_size_name,
		);
	}

	$settings = array_merge(
		array(
			'imageSizes'        => $available_image_sizes,
			'isRTL'             => is_rtl(),
			'maxUploadFileSize' => $max_upload_size,
		),
		gutenberg_get_legacy_widget_settings()
	);

	list( $font_sizes, ) = (array) get_theme_support( 'editor-font-sizes' );

	if ( false !== $font_sizes ) {
		$settings['fontSizes'] = $font_sizes;
	}
	$settings = gutenberg_experimental_global_styles_settings( $settings );

	wp_add_inline_script(
		'wp-edit-widgets',
		sprintf(
			'wp.domReady( function() {
				wp.editWidgets.%s( "widgets-editor", %s, %s );
			} );',
			$initializer_name,
			wp_json_encode( gutenberg_experiments_editor_settings( $settings ) ),
			wp_json_encode( $widget_id )
		)
	);

	// Preload server-registered block schemas.
	wp_add_inline_script(
		'wp-blocks',
		'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
	);

	wp_enqueue_script( 'wp-edit-widgets' );
	wp_enqueue_script( 'admin-widgets' );
	wp_enqueue_script( 'wp-format-library' );
	wp_enqueue_style( 'wp-edit-widgets' );
	wp_enqueue_style( 'wp-format-library' );
}
add_action( 'admin_enqueue_scripts', 'gutenberg_widgets_init' );
