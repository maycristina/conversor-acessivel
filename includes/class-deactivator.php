<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CDA_Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
