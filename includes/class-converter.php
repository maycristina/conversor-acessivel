<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escolhe o conversor certo com base na extensão do arquivo e devolve
 * HTML sanitizado pronto para ser salvo em post_content.
 */
class CDA_Converter {

	/**
	 * @param string $file_path Caminho absoluto do arquivo temporário.
	 * @param string $extension Extensão em minúsculas (pdf|docx|txt).
	 * @return string HTML sanitizado.
	 *
	 * @throws CDA_Converter_Exception
	 */
	public static function convert( $file_path, $extension ) {
		$extension = strtolower( $extension );

		switch ( $extension ) {
			case 'pdf':
				$converter = new CDA_Pdf_Converter();
				break;
			case 'docx':
				$converter = new CDA_Word_Converter();
				break;
			case 'txt':
				$converter = new CDA_Txt_Converter();
				break;
			case 'doc':
				throw new CDA_Converter_Exception(
					__( 'Arquivos .doc (Word 97-2003) não são suportados. Salve o documento como .docx e envie novamente.', 'conversor-acessivel' )
				);
			default:
				throw new CDA_Converter_Exception(
					sprintf(
						/* translators: %s: extensão do arquivo */
						__( 'Tipo de arquivo não suportado: %s', 'conversor-acessivel' ),
						$extension
					)
				);
		}

		$html = $converter->convert( $file_path );

		return wp_kses_post( $html );
	}
}
