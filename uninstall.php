<?php
/**
 * Executado quando o plugin é removido (não apenas desativado) pelo admin do WordPress.
 * Remove os documentos convertidos, seus metadados e as opções do plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$post_type = 'cda_documento';

$post_ids = get_posts(
	array(
		'post_type'      => $post_type,
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
	)
);

foreach ( $post_ids as $post_id ) {
	wp_delete_post( $post_id, true );
}

delete_option( 'cda_settings' );

global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_cda_active_installs_' ) . '%'
	)
);
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_cda_active_installs_' ) . '%'
	)
);
