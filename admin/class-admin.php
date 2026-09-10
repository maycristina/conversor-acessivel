<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Telas de administração: upload de documentos e configurações do plugin.
 */
class CDA_Admin {

	const CAPABILITY_UPLOAD  = 'upload_files';
	const CAPABILITY_MANAGE  = 'manage_options';
	const MENU_SLUG          = 'cda-conversor';
	const SETTINGS_OPTION    = 'cda_settings';

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_cda_upload_document', array( $this, 'handle_upload' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Conversor Acessível', 'conversor-acessivel' ),
			__( 'Conversor Acessível', 'conversor-acessivel' ),
			self::CAPABILITY_UPLOAD,
			self::MENU_SLUG,
			array( $this, 'render_upload_page' ),
			'dashicons-media-document',
			26
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Novo Documento', 'conversor-acessivel' ),
			__( 'Novo Documento', 'conversor-acessivel' ),
			self::CAPABILITY_UPLOAD,
			self::MENU_SLUG,
			array( $this, 'render_upload_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Configurações', 'conversor-acessivel' ),
			__( 'Configurações', 'conversor-acessivel' ),
			self::CAPABILITY_MANAGE,
			self::MENU_SLUG . '-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public static function get_settings() {
		$settings = get_option( self::SETTINGS_OPTION, array() );
		return wp_parse_args( $settings, CDA_Activator::default_settings() );
	}

	/* ---------------------------------------------------------------
	 * Upload
	 * ------------------------------------------------------------- */

	public function render_upload_page() {
		if ( ! current_user_can( self::CAPABILITY_UPLOAD ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'conversor-acessivel' ) );
		}

		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Conversor de Documentos Acessível', 'conversor-acessivel' ); ?></h1>
			<p><?php esc_html_e( 'Envie um arquivo PDF, DOCX ou TXT para gerar uma página acessível (com leitura em voz alta) e um shortcode para publicá-la.', 'conversor-acessivel' ); ?></p>

			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cda_upload_document', 'cda_upload_nonce' ); ?>
				<input type="hidden" name="action" value="cda_upload_document">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cda_title"><?php esc_html_e( 'Título do documento', 'conversor-acessivel' ); ?></label></th>
						<td><input type="text" id="cda_title" name="cda_title" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="cda_file"><?php esc_html_e( 'Arquivo', 'conversor-acessivel' ); ?></label></th>
						<td>
							<input type="file" id="cda_file" name="cda_file" accept=".pdf,.docx,.txt" required>
							<p class="description">
								<?php
								printf(
									/* translators: 1: tipos permitidos, 2: tamanho máximo em MB */
									esc_html__( 'Tipos aceitos: %1$s. Tamanho máximo: %2$s MB.', 'conversor-acessivel' ),
									esc_html( strtoupper( implode( ', ', $settings['allowed_types'] ) ) ),
									esc_html( $settings['max_file_size_mb'] )
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Converter documento', 'conversor-acessivel' ) ); ?>
			</form>

			<hr>
			<p>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . CDA_Post_Type::POST_TYPE ) ); ?>">
					<?php esc_html_e( 'Ver todos os documentos convertidos e seus shortcodes →', 'conversor-acessivel' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function handle_upload() {
		if ( ! current_user_can( self::CAPABILITY_UPLOAD ) ) {
			wp_die( esc_html__( 'Você não tem permissão para enviar documentos.', 'conversor-acessivel' ) );
		}

		check_admin_referer( 'cda_upload_document', 'cda_upload_nonce' );

		$redirect = admin_url( 'admin.php?page=' . self::MENU_SLUG );

		try {
			$post_id = $this->process_upload();
			set_transient( 'cda_notice_' . get_current_user_id(), array(
				'type'    => 'success',
				'message' => sprintf(
					/* translators: %s: link para editar o documento */
					__( 'Documento convertido com sucesso! %s', 'conversor-acessivel' ),
					'<a href="' . esc_url( get_edit_post_link( $post_id, '' ) ) . '">' . esc_html__( 'Ver documento e shortcode', 'conversor-acessivel' ) . '</a>'
				),
			), 60 );
		} catch ( CDA_Converter_Exception $e ) {
			set_transient( 'cda_notice_' . get_current_user_id(), array(
				'type'    => 'error',
				'message' => esc_html( $e->getMessage() ),
			), 60 );
		} catch ( \Exception $e ) {
			set_transient( 'cda_notice_' . get_current_user_id(), array(
				'type'    => 'error',
				'message' => esc_html__( 'Ocorreu um erro inesperado ao processar o arquivo.', 'conversor-acessivel' ),
			), 60 );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * @return int ID do post criado.
	 * @throws CDA_Converter_Exception
	 */
	private function process_upload() {
		if ( empty( $_FILES['cda_file'] ) || ! empty( $_FILES['cda_file']['error'] ) ) {
			throw new CDA_Converter_Exception( __( 'Nenhum arquivo válido foi enviado.', 'conversor-acessivel' ) );
		}

		$file      = $_FILES['cda_file'];
		$settings  = self::get_settings();
		$max_bytes = (int) $settings['max_file_size_mb'] * MB_IN_BYTES;

		if ( $file['size'] > $max_bytes ) {
			throw new CDA_Converter_Exception(
				sprintf(
					/* translators: %d: tamanho máximo em MB */
					__( 'Arquivo maior que o limite de %d MB.', 'conversor-acessivel' ),
					$settings['max_file_size_mb']
				)
			);
		}

		$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

		if ( empty( $filetype['ext'] ) || ! in_array( $filetype['ext'], $settings['allowed_types'], true ) ) {
			throw new CDA_Converter_Exception( __( 'Tipo de arquivo não permitido.', 'conversor-acessivel' ) );
		}

		add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		$moved = wp_handle_upload( $file, array( 'test_form' => false ) );
		remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );

		if ( isset( $moved['error'] ) ) {
			throw new CDA_Converter_Exception( $moved['error'] );
		}

		$this->protect_upload_dir( dirname( $moved['file'] ) );

		$extension = $filetype['ext'];
		$html      = '';

		try {
			$html = CDA_Converter::convert( $moved['file'], $extension );
		} finally {
			if ( ! empty( $settings['delete_original_after'] ) && file_exists( $moved['file'] ) ) {
				unlink( $moved['file'] );
			}
		}

		$title = isset( $_POST['cda_title'] ) ? sanitize_text_field( wp_unslash( $_POST['cda_title'] ) ) : '';
		if ( '' === $title ) {
			$title = sanitize_text_field( $file['name'] );
		}

		preg_match_all( '/\p{L}+/u', wp_strip_all_tags( $html ), $matches );
		$word_count = count( $matches[0] );

		$post_id = wp_insert_post(
			array(
				'post_type'    => CDA_Post_Type::POST_TYPE,
				'post_title'   => $title,
				'post_content' => $html,
				'post_status'  => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			throw new CDA_Converter_Exception( $post_id->get_error_message() );
		}

		update_post_meta( $post_id, '_cda_original_filename', sanitize_file_name( $file['name'] ) );
		update_post_meta( $post_id, '_cda_original_type', $extension );
		update_post_meta( $post_id, '_cda_word_count', $word_count );
		update_post_meta( $post_id, '_cda_converted_at', current_time( 'mysql' ) );

		if ( empty( $settings['delete_original_after'] ) ) {
			update_post_meta( $post_id, '_cda_original_url', esc_url_raw( $moved['url'] ) );
		}

		return $post_id;
	}

	/**
	 * Defesa em profundidade: só PDF/DOCX/TXT chegam a essa pasta (já
	 * validados antes do upload), mas escrevemos um .htaccess que nega
	 * a EXECUÇÃO de qualquer script (não o acesso a arquivos em geral —
	 * o PDF/DOCX/TXT original precisa continuar acessível quando a opção
	 * "apagar original" está desligada). Cobre o caso de outro
	 * plugin/config permitir no futuro um upload que não devia. Efeito
	 * real depende do servidor honrar .htaccess (Apache/LiteSpeed; não
	 * se aplica a Nginx). index.php evita listagem do diretório.
	 */
	private function protect_upload_dir( $dir ) {
		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents(
				$htaccess,
				"<IfModule mod_authz_core.c>\n" .
				"    <FilesMatch \"\\.(php\\d?|phtml|pl|py|cgi|sh|asp|aspx)$\">\n" .
				"        Require all denied\n" .
				"    </FilesMatch>\n" .
				"</IfModule>\n" .
				"<IfModule !mod_authz_core.c>\n" .
				"    <FilesMatch \"\\.(php\\d?|phtml|pl|py|cgi|sh|asp|aspx)$\">\n" .
				"        Order allow,deny\n" .
				"        Deny from all\n" .
				"    </FilesMatch>\n" .
				"</IfModule>\n" .
				"Options -Indexes -ExecCGI\n"
			);
		}

		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	public function filter_upload_dir( $dirs ) {
		$dirs['subdir'] = '/cda-documentos' . $dirs['subdir'];
		$dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
		$dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
		return $dirs;
	}

	public function render_admin_notices() {
		$key    = 'cda_notice_' . get_current_user_id();
		$notice = get_transient( $key );

		if ( ! $notice ) {
			return;
		}

		delete_transient( $key );

		$class = ( 'error' === $notice['type'] ) ? 'notice-error' : 'notice-success';
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . wp_kses_post( $notice['message'] ) . '</p></div>';
	}

	/* ---------------------------------------------------------------
	 * Configurações
	 * ------------------------------------------------------------- */

	public function register_settings() {
		register_setting( 'cda_settings_group', self::SETTINGS_OPTION, array( $this, 'sanitize_settings' ) );
	}

	public function sanitize_settings( $input ) {
		$defaults = CDA_Activator::default_settings();
		$output   = array();

		$allowed_types_input     = isset( $input['allowed_types'] ) ? (array) $input['allowed_types'] : array();
		$output['allowed_types'] = array_values( array_intersect( array( 'pdf', 'docx', 'txt' ), $allowed_types_input ) );
		if ( empty( $output['allowed_types'] ) ) {
			$output['allowed_types'] = $defaults['allowed_types'];
		}

		$output['max_file_size_mb']      = max( 1, min( 100, (int) ( $input['max_file_size_mb'] ?? $defaults['max_file_size_mb'] ) ) );
		$output['delete_original_after'] = ! empty( $input['delete_original_after'] );
		$output['wporg_slug']            = isset( $input['wporg_slug'] ) ? sanitize_title( $input['wporg_slug'] ) : $defaults['wporg_slug'];

		$rate                       = isset( $input['tts_default_rate'] ) ? (float) $input['tts_default_rate'] : $defaults['tts_default_rate'];
		$output['tts_default_rate'] = max( 0.5, min( 2, $rate ) );

		return $output;
	}

	public function render_settings_page() {
		if ( ! current_user_can( self::CAPABILITY_MANAGE ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'conversor-acessivel' ) );
		}

		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Configurações — Conversor Acessível', 'conversor-acessivel' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'cda_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Tipos de arquivo permitidos', 'conversor-acessivel' ); ?></th>
						<td>
							<?php foreach ( array( 'pdf', 'docx', 'txt' ) as $type ) : ?>
								<label style="margin-right:1em;">
									<input type="checkbox" name="cda_settings[allowed_types][]" value="<?php echo esc_attr( $type ); ?>"
										<?php checked( in_array( $type, $settings['allowed_types'], true ) ); ?>>
									<?php echo esc_html( strtoupper( $type ) ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cda_max_size"><?php esc_html_e( 'Tamanho máximo (MB)', 'conversor-acessivel' ); ?></label></th>
						<td><input type="number" id="cda_max_size" name="cda_settings[max_file_size_mb]" min="1" max="100" value="<?php echo esc_attr( $settings['max_file_size_mb'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Apagar arquivo original após converter', 'conversor-acessivel' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="cda_settings[delete_original_after]" value="1" <?php checked( ! empty( $settings['delete_original_after'] ) ); ?>>
								<?php esc_html_e( 'Recomendado: mantém apenas o HTML convertido, sem guardar o arquivo enviado.', 'conversor-acessivel' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cda_wporg_slug"><?php esc_html_e( 'Slug do plugin no WordPress.org', 'conversor-acessivel' ); ?></label></th>
						<td>
							<input type="text" id="cda_wporg_slug" name="cda_settings[wporg_slug]" class="regular-text" value="<?php echo esc_attr( $settings['wporg_slug'] ); ?>">
							<p class="description"><?php esc_html_e( 'Usado pelo shortcode [cda_instalacoes] para buscar o número de instalações ativas na API do WordPress.org (só funciona depois que o plugin for publicado lá).', 'conversor-acessivel' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cda_tts_rate"><?php esc_html_e( 'Velocidade padrão da leitura em voz alta', 'conversor-acessivel' ); ?></label></th>
						<td><input type="number" id="cda_tts_rate" name="cda_settings[tts_default_rate]" min="0.5" max="2" step="0.1" value="<?php echo esc_attr( $settings['tts_default_rate'] ); ?>"></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
