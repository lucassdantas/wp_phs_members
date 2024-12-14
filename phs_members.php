<?php 
/**
* Plugin Name: Membros PHS
* Plugin URI: https://github.com/lucassdantas/wp_phs_members.git
* Update URI: https://github.com/lucassdantas/wp_phs_members.git
* Description: Members PHS
* Version: 1.0.0
* Author: Lucas Dantas
* Author URI: https://www.linkedin.com/in/lucas-de-sousa-dantas/
**/

defined('ABSPATH') or die();
if(!function_exists('add_action')){
    die;
}
add_filter ('woocommerce_add_to_cart_redirect', function( $url, $adding_to_cart ) {
  return wc_get_checkout_url();
}, 10, 2 );