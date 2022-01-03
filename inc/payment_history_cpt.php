<?php 
if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
class Mep_Payment_History_Cpt{
	
	public function __construct(){
		add_action( 'init', array($this, 'register_cpt' ));
	}


	public function register_cpt(){
		$labels = array(
			'name'                  => _x( 'Payment History', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
			'singular_name'         => _x( 'Payment History', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
		);
	
	    $args = array(
	        'public'                => false,
	        'labels'                => $labels,
	        'menu_icon'             => 'dashicons-slides',
	        'supports'              => array('title'),
			'rewrite'               => array('slug' => 'mep_pp_history'),
			'show_ui'          		=> false,
			// 'show_in_menu'          => 'edit.php?post_type=mep_events',
	    );

	   	register_post_type( 'mep_pp_history', $args );

	}

}
new Mep_Payment_History_Cpt();