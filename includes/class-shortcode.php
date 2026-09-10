<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode [documento_acessivel id="123"] — exibe a página responsiva e
 * acessível (WCAG 2.1 AA) com leitor de voz via Web Speech API.
 */
class CDA_Shortcode {

	const TAG = 'documento_acessivel';

	private static $instance = null;
	private $rendered_on_page = false;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			self::TAG
		);

		$post_id = absint( $atts['id'] );
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || CDA_Post_Type::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return '<p>' . esc_html__( 'Conversor de Documentos Acessível: documento não encontrado.', 'conversor-acessivel' ) . '</p>';
			}
			return '';
		}

		$this->enqueue_assets();

		$settings = CDA_Admin::get_settings();
		// Não usamos apply_filters( 'the_content', ... ) de propósito: isso rodaria
		// do_shortcode() sobre texto extraído de um arquivo enviado, permitindo que
		// um documento com algo como "[algum-shortcode]" execute shortcodes do site
		// sem intenção. O HTML já vem pronto (com <p>) dos conversores e sanitizado
		// com wp_kses_post() no momento da conversão.
		$content    = $post->post_content;
		$word_count = (int) get_post_meta( $post->ID, '_cda_word_count', true );
		$uid        = 'cda-' . $post->ID . '-' . wp_unique_id();

		ob_start();
		include CDA_PLUGIN_DIR . 'templates/document-viewer.php';
		return ob_get_clean();
	}

	private function enqueue_assets() {
		if ( $this->rendered_on_page ) {
			return;
		}
		$this->rendered_on_page = true;

		wp_enqueue_style( 'cda-frontend', CDA_PLUGIN_URL . 'assets/css/frontend.css', array(), CDA_VERSION );
		wp_enqueue_script( 'cda-frontend', CDA_PLUGIN_URL . 'assets/js/frontend.js', array(), CDA_VERSION, true );

		wp_localize_script(
			'cda-frontend',
			'cdaFrontendI18n',
			array(
				'play'          => __( 'Ouvir', 'conversor-acessivel' ),
				'pause'         => __( 'Pausar', 'conversor-acessivel' ),
				'resume'        => __( 'Continuar', 'conversor-acessivel' ),
				'stop'          => __( 'Parar', 'conversor-acessivel' ),
				'statusReading' => __( 'Lendo em voz alta.', 'conversor-acessivel' ),
				'statusPaused'  => __( 'Leitura pausada.', 'conversor-acessivel' ),
				'statusStopped' => __( 'Leitura interrompida.', 'conversor-acessivel' ),
				'statusDone'    => __( 'Leitura concluída.', 'conversor-acessivel' ),
				'unsupported'   => __( 'Este navegador não tem suporte à leitura em voz alta.', 'conversor-acessivel' ),
				'increaseFont'  => __( 'Aumentar tamanho do texto', 'conversor-acessivel' ),
				'decreaseFont'  => __( 'Diminuir tamanho do texto', 'conversor-acessivel' ),
				'resetFont'     => __( 'Tamanho de texto padrão', 'conversor-acessivel' ),
				'contrastOn'    => __( 'Ativar alto contraste', 'conversor-acessivel' ),
				'contrastOff'   => __( 'Desativar alto contraste', 'conversor-acessivel' ),
				'voiceLabel'    => __( 'Voz', 'conversor-acessivel' ),
				'rateLabel'     => __( 'Velocidade da leitura', 'conversor-acessivel' ),
			)
		);
	}
}
