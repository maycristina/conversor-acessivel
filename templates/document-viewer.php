<?php
/**
 * Template do visualizador de documento acessível.
 *
 * Variáveis disponíveis (definidas em CDA_Shortcode::render):
 * @var WP_Post $post
 * @var string  $content
 * @var int     $word_count
 * @var string  $uid
 * @var array   $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="cda-viewer" id="<?php echo esc_attr( $uid ); ?>" data-cda-viewer aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">

	<a class="cda-skip-link cda-visually-hidden" href="#<?php echo esc_attr( $uid ); ?>-content">
		<?php esc_html_e( 'Pular para o conteúdo do documento', 'conversor-acessivel' ); ?>
	</a>

	<h2 id="<?php echo esc_attr( $uid ); ?>-title" class="cda-title"><?php echo esc_html( get_the_title( $post ) ); ?></h2>

	<p class="cda-meta">
		<?php
		printf(
			/* translators: %s: número aproximado de palavras */
			esc_html__( 'Aproximadamente %s palavras.', 'conversor-acessivel' ),
			esc_html( number_format_i18n( $word_count ) )
		);
		?>
	</p>

	<div class="cda-toolbar" role="group" aria-label="<?php esc_attr_e( 'Ajustes de leitura', 'conversor-acessivel' ); ?>" data-cda-toolbar hidden>
		<div class="cda-toolbar-group">
			<span class="cda-toolbar-label" id="<?php echo esc_attr( $uid ); ?>-font-label"><?php esc_html_e( 'Tamanho do texto', 'conversor-acessivel' ); ?></span>
			<span role="group" aria-labelledby="<?php echo esc_attr( $uid ); ?>-font-label">
				<button type="button" class="cda-btn" data-cda-font="decrease" aria-label="<?php esc_attr_e( 'Diminuir tamanho do texto', 'conversor-acessivel' ); ?>">A-</button>
				<button type="button" class="cda-btn" data-cda-font="reset" aria-label="<?php esc_attr_e( 'Tamanho de texto padrão', 'conversor-acessivel' ); ?>">A</button>
				<button type="button" class="cda-btn" data-cda-font="increase" aria-label="<?php esc_attr_e( 'Aumentar tamanho do texto', 'conversor-acessivel' ); ?>">A+</button>
			</span>
		</div>

		<div class="cda-toolbar-group">
			<button type="button" class="cda-btn" data-cda-contrast-toggle aria-pressed="false">
				<?php esc_html_e( 'Alto contraste', 'conversor-acessivel' ); ?>
			</button>
		</div>
	</div>

	<div class="cda-audio-controls" data-cda-audio hidden>
		<button type="button" class="cda-btn cda-btn-primary" data-cda-play aria-pressed="false">
			<span data-cda-play-label><?php esc_html_e( 'Ouvir', 'conversor-acessivel' ); ?></span>
		</button>
		<button type="button" class="cda-btn" data-cda-stop disabled>
			<?php esc_html_e( 'Parar', 'conversor-acessivel' ); ?>
		</button>

		<label class="cda-field" for="<?php echo esc_attr( $uid ); ?>-voice">
			<?php esc_html_e( 'Voz', 'conversor-acessivel' ); ?>
			<select id="<?php echo esc_attr( $uid ); ?>-voice" data-cda-voice></select>
		</label>

		<label class="cda-field" for="<?php echo esc_attr( $uid ); ?>-rate">
			<?php esc_html_e( 'Velocidade', 'conversor-acessivel' ); ?>
			<input
				type="range"
				id="<?php echo esc_attr( $uid ); ?>-rate"
				data-cda-rate
				min="0.5"
				max="2"
				step="0.1"
				value="<?php echo esc_attr( $settings['tts_default_rate'] ); ?>"
			>
		</label>

		<p class="cda-status" role="status" aria-live="polite" data-cda-status></p>
	</div>

	<div id="<?php echo esc_attr( $uid ); ?>-content" class="cda-content" tabindex="-1" data-cda-content>
		<?php echo wp_kses_post( $content ); ?>
	</div>
</section>
