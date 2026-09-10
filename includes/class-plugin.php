<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe principal: orquestra o carregamento dos demais componentes.
 */
class CDA_Plugin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init() {
		load_plugin_textdomain( 'conversor-acessivel', false, dirname( plugin_basename( CDA_PLUGIN_FILE ) ) . '/languages' );

		CDA_Post_Type::get_instance()->init();
		CDA_Shortcode::get_instance()->init();
		CDA_Install_Badge::get_instance()->init();

		if ( is_admin() ) {
			CDA_Admin::get_instance()->init();
		}
	}
}
