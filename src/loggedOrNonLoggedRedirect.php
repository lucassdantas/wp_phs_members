<?php 
defined('ABSPATH') or die();
if(!function_exists('add_action')){
    die;
}

add_action( 'wp', 'redirect_non_logged_users_to_specific_page' );
function redirect_non_logged_users_to_specific_page() {
	if ( !is_user_logged_in() && !is_page('login') && $_SERVER['PHP_SELF'] != '/wp-admin/admin-ajax.php' ) {
		wp_redirect( 'https://cursophs.natcerqueira.com.br/login' ); 
    	exit;
   }elseif( is_user_logged_in() && is_page('login') && $_SERVER['PHP_SELF'] != '/wp-admin/admin-ajax.php' ) {
		wp_redirect( 'https://cursophs.natcerqueira.com.br/aulas/' ); 
    	exit;
   }
}