<?php
/**
 * Plugin Name:       Conversor de Documentos Acessível
 * Plugin URI:         https://github.com/maycristina/contatos
 * Description:       Converte arquivos PDF, Word e TXT em páginas responsivas e acessíveis (com leitura em voz alta) e permite publicá-las via shortcode.
 * Version:            1.0.0
 * Requires at least:  6.0
 * Requires PHP:       7.4
 * Author:             Mayara Nascimento
 * Author URI:         https://github.com/maycristina
 * License:            GPL v2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        conversor-acessivel
 * Domain Path:        /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acesso direto não permitido.
}

define( 'CDA_VERSION', '1.0.0' );
define( 'CDA_PLUGIN_FILE', __FILE__ );
define( 'CDA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CDA_PLUGIN_SLUG', 'conversor-acessivel' );

/**
 * Autoload das dependências do Composer (smalot/pdfparser, phpoffice/phpword).
 * Necessário rodar `composer install` na pasta do plugin antes de usar.
 */
$cda_autoload = CDA_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $cda_autoload ) ) {
	require_once $cda_autoload;
}

/**
 * Autoload simples das classes internas do plugin (prefixo CDA_).
 */
spl_autoload_register(
	function ( $class_name ) {
		if ( strpos( $class_name, 'CDA_' ) !== 0 ) {
			return;
		}

		$relative = strtolower( str_replace( '_', '-', substr( $class_name, 4 ) ) );
		$paths    = array(
			CDA_PLUGIN_DIR . 'includes/class-' . $relative . '.php',
			CDA_PLUGIN_DIR . 'includes/converters/class-' . $relative . '.php',
			CDA_PLUGIN_DIR . 'admin/class-' . $relative . '.php',
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
);

register_activation_hook( CDA_PLUGIN_FILE, array( 'CDA_Activator', 'activate' ) );
register_deactivation_hook( CDA_PLUGIN_FILE, array( 'CDA_Deactivator', 'deactivate' ) );

/**
 * Inicializa o plugin depois que todos os plugins foram carregados.
 */
function cda_run_plugin() {
	if ( ! file_exists( CDA_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'Conversor de Documentos Acessível: as dependências do Composer não foram instaladas. Rode "composer install" na pasta do plugin.', 'conversor-acessivel' );
				echo '</p></div>';
			}
		);
	}

	CDA_Plugin::get_instance()->init();
}
add_action( 'plugins_loaded', 'cda_run_plugin' );
