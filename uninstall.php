<?php
require_once( 'query-posts-controller.php' );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

/**
 * プラグインで保持しているオプション値の削除
 */
function qpc_delete_plugin() {
	$args = array(
					'public' => true,
					'_builtin' => false
				);
	$post_types = get_post_types( $args );
	
	if( count( $post_types ) != 0 ) {
		foreach ( $post_types as $post_type ) {
			delete_option( QueryPostsController::PREFIX . $post_type );
		}
	}
}

qpc_delete_plugin();