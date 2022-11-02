<?php

class Idlshipbydist {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		if ( defined( 'IDLSHIPBYDIST_VERSION' ) ) {
			$this->version = IDLSHIPBYDIST_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'idlshipbydist';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-idlshipbydist-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-idlshipbydist-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-idlshipbydist-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-idlshipbydist-public.php';

		$this->loader = new Idlshipbydist_Loader();

	}

	private function set_locale() {

		$plugin_i18n = new Idlshipbydist_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	private function define_admin_hooks() {

		$plugin_admin = new Idlshipbydist_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	private function define_public_hooks() {

		$plugin_public = new Idlshipbydist_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_shipping_init', $plugin_public, 'show_distance_calc' );
		$this->loader->add_action( 'woocommerce_shipping_methods', $plugin_public, 'idl_shipping', 10 ,1 );
		$this->loader->add_action( 'woocommerce_checkout_update_order_review', $plugin_public, 'clear_wc_shipping_rates_cache', 10);

	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}

}
