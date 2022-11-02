<?php

class Idlshipbydist_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/idlshipbydist-public.css', array(), $this->version, 'all' );

	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/idlshipbydist-public.js', array( 'jquery' ), $this->version, false );

	}

	public function show_distance_calc() {
		if ( ! class_exists( 'IDL_SHIPPING' ) ) {
			include_once 'shippingclass.php';
		}
	}

	public function idl_shipping($methods) {
		$methods['idl_distance_shipping'] = 'IDL_SHIPPING';
		return $methods;
	}

}
