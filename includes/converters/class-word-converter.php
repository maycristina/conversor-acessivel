<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpWord\IOFactory;

class CDA_Word_Converter implements CDA_Converter_Interface {

	public function convert( $file_path ) {
		if ( ! class_exists( IOFactory::class ) ) {
			throw new CDA_Converter_Exception(
				__( 'Biblioteca phpoffice/phpword não encontrada. Rode "composer install" na pasta do plugin.', 'conversor-acessivel' )
			);
		}

		try {
			$phpWord = IOFactory::load( $file_path, 'Word2007' );
			$writer  = IOFactory::createWriter( $phpWord, 'HTML' );
		} catch ( \Exception $e ) {
			throw new CDA_Converter_Exception(
				sprintf(
					/* translators: %s: mensagem de erro original */
					__( 'Não foi possível ler o documento Word: %s', 'conversor-acessivel' ),
					$e->getMessage()
				)
			);
		}

		$tmp_html = wp_tempnam( 'cda-word-' );

		try {
			$writer->save( $tmp_html );
			$full_html = file_get_contents( $tmp_html );
		} finally {
			if ( file_exists( $tmp_html ) ) {
				unlink( $tmp_html );
			}
		}

		if ( false === $full_html || '' === trim( (string) $full_html ) ) {
			throw new CDA_Converter_Exception( __( 'O documento Word parece estar vazio.', 'conversor-acessivel' ) );
		}

		return $this->extract_body( $full_html );
	}

	/**
	 * PhpWord gera um documento HTML completo (<html><head>...<body>...).
	 * Aqui extraímos só o conteúdo do <body> para inserir no post_content.
	 */
	private function extract_body( $full_html ) {
		$dom = new \DOMDocument();

		$previous_setting = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $full_html );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_setting );

		$body = $dom->getElementsByTagName( 'body' )->item( 0 );

		if ( null === $body ) {
			return wp_strip_all_tags( $full_html );
		}

		$inner_html = '';
		foreach ( $body->childNodes as $child ) {
			$inner_html .= $dom->saveHTML( $child );
		}

		return $inner_html;
	}
}
