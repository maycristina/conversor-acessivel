<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CDA_Activator {

	public static function activate() {
		if ( class_exists( 'CDA_Post_Type' ) ) {
			CDA_Post_Type::get_instance()->register_post_type();
		}

		if ( false === get_option( 'cda_settings' ) ) {
			add_option( 'cda_settings', self::default_settings() );
		}

		flush_rewrite_rules();
	}

	public static function default_settings() {
		return array(
			'allowed_types'          => array( 'pdf', 'docx', 'txt' ),
			'max_file_size_mb'       => 10,
			'delete_original_after'  => true,
			'wporg_slug'             => CDA_PLUGIN_SLUG,
			'tts_default_rate'       => 1,
		);
	}
}
