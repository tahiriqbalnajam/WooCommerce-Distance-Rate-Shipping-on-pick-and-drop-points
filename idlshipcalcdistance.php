<?php
use \Datetime;
/*
Plugin Name: Woo Distance Calc Shipping
Plugin URI: https://tahir.codes/
Description: This plugin is to calculate shipping by distance
Version: 1.0.1
Author: Tahir Iqbal
Author URI: https://tahir.codes/
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    function lalamoveidl_shipping_method() {
        if (!class_exists('Lalamoveidl_Shipping_Method')) {

            class Lalamoveidl_Shipping_Method extends WC_Shipping_Method {
                public $newrate;
                public  $lalamove;
                public $text_domain = 'lalamoveshipping';
                public $lalamove_apikey;
                public $lalamove_secret;
                public $storename;
                public $storephone;
                public $lalmove_mode;
                public function __construct( $instance_id = 0 ) {
                  require dirname(__FILE__).'/class_lalamove_main.php';
                  $this->lalamove = new lalamove_main();
                  $this->id = 'lalamoveidl_shipping';
                  $this->instance_id          = absint( $instance_id );
                  $this->method_title         = __('Distance Shipping', $this->text_domain);
                  $this->method_description   = __('Plugin to calculate distance shipping', $this->text_domain);
                  $this->last_response = '';
                  $this->supports             = array(
                      'shipping-zones',
                      'instance-settings',
                      'instance-settings-modal',
                      'settings'
                  );
                  $this->init();
                  
                }

                /**
                 * Initialize Launch Simple Shipping.
                 */
                public function init() {
                    // Load the settings.
                    $this->init_form_fields();
		                $this->init_settings();
                    // Define user set variables.
                    $this->title      = $this->get_option( 'title' );
                    $this->google_distance_api = $this->get_option( 'distance_matrix_api');
                    $this->google_places_api = $this->get_option( 'google_places_api');
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                    //add_action( 'woocommerce_after_shipping_rate', array($this, 'action_after_shipping_rate'), 10, 2);
                    //add_action( 'woocommerce_proceed_to_checkout', array( $this, 'action_add_text_before_proceed_to_checkout' ));
                    //add_action( 'woocommerce_proceed_to_checkout', array( $this, 'maybe_clear_wc_shipping_rates_cache' ));
                    //add_action('woocommerce_thankyou', array($this,'lalamove_send_order')); 
                }

                public function action_after_shipping_rate($rate, $index) {
                    $rate_id = $rate->id;
                    $rates = $this->last_response['rates'];
                    foreach( $rates as $r ) {
                        if ( $rate_id == $r['id'] ) { // This rate ID belongs to this instance
                            echo "<div class='shipping_rate_description'>" . $r['description'] . "</div>";
                        }
                    }
                }

                public function maybe_clear_wc_shipping_rates_cache() {
                    $packages = WC()->cart->get_shipping_packages();
                    foreach ($packages as $key => $value) {
                        $shipping_session = "shipping_for_package_$key";
                        unset(WC()->session->$shipping_session);
                    }
                }

                public function action_add_text_before_proceed_to_checkout() {
                    //echo $this->last_response;
                    //echo 'Tahir is here.';
                }

                /**
                 * Init form fields.
                 */
                public function init_form_fields() {
                    $this->instance_form_fields = array(
                        'title'      => array(
                            'title'         => __( 'Title', $this->text_domain ),
                            'type'          => 'text',
                            'description'   => __( 'This controls the title which the user sees during checkout.', $this->text_domain ),
                            'default'       => $this->method_title,
                            'desc_tip'      => true,
                        ),
                        'distance_matrix_api'      => array(
                            'title'         => __( 'Distance Matrix API', $this->text_domain ),
                            'type'          => 'text',
                            'description'   => __( 'Google Distance Matrix API Key.', $this->text_domain ),
                            'default'       => '',
                            'desc_tip'      => true,
                        ),
                        'google_places_api'      => array(
                            'title'         => __( 'Places API', $this->text_domain ),
                            'type'          => 'text',
                            'description'   => __( 'Google Places API.', $this->text_domain ),
                            'default'       => '',
                            'desc_tip'      => true,
                        ),
                    );
                }

                /**
                 * Get setting form fields for instances of this shipping method within zones.
                 *o=-/
                 * @return array
                 */
                public function get_instance_form_fields() {
                    return parent::get_instance_form_fields();
                }

                /**
                 * Always return shipping method is available
                 *
                 * @param array $package Shipping package.
                 * @return bool
                 */
                public function is_available( $package ) {
                    $is_available = true;
                    return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
                }

                /**
                 * Free shipping rate applied for this method.
                 *
                 * @uses WC_Shipping_Method::add_rate()
                 *
                 * @param array $package Shipping package.
                 */
                public function calculate_shipping( $package = array() ) { 
                  try{
                        global $woocommerce;
                        $origin = str_replace(' ', '+', WC()->session->get('start_distance'));
                        $destination = str_replace(' ', '+', WC()->session->get('end_distance'));
                        if(empty($origin) || empty($destination)){
                            $this->add_rate(
                                array(
                                    'label'   => $this->title,
                                    'cost'    => 0,
                                    'taxes'   => false,
                                    'package' => $package,
                                )
                            );
                            return ;
                        }

                        $api = $this->google_distance_api;

                        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origin&destinations=$destination&key=$api";

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        $response = curl_exec($ch);
                        curl_close($ch);
                        $response_a = json_decode($response, true);
                        
                        $distance = $response_a['rows'][0]['elements'][0]['distance']['value'];
                        //$this->showoutput($url);
                        if(empty($distance)) {
                            wc_add_notice("Please enter pickup and delivery points", 'notice' );
                        } else {
                            $kms = $distance/1000;
                            $shipping_cost = 0;
                            foreach ( $package['contents'] as $item_id => $values ) {
                                $_product = $values['data'];
                                $_price_price_km = get_post_meta($_product->id,'_price_price_km', true); 
                                $shipping_cost =  $shipping_cost+($_price_price_km*$kms);                   
                            }
                            $this->add_rate(
                                array(
                                    'label'   => $this->title,
                                    'cost'    => $shipping_cost,
                                    'taxes'   => false,
                                    'package' => $package,
                                )
                            );
                        }
                  }
                  catch(Exception $e) {
                      wc_add_notice($e->getMessage(), 'errorrrr');
                  }      
                }

                function showoutput($data) {
                    ob_start();
                    echo '<pre>';
                    if(is_array($data))
                        print_r($data);
                    else
                        echo $data;

                    echo '</pre>';
                    $output = ob_get_contents();
                    ob_end_clean();
                    wc_add_notice($output , 'notice' );
                }

              
            }
        }
    }
    add_action('woocommerce_shipping_init', 'Lalamoveidl_Shipping_Method');

    function add_lalamoveidl_shipping_method($methods) {
        $methods['lalamoveidl_shipping'] = 'lalamoveidl_shipping_method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_lalamoveidl_shipping_method');
    
    function action_woocommerce_review_order_after_submit($order, $data) {
        global $woocommerce;
        $packages = WC()->shipping->get_packages();
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
        foreach ( $chosen_methods as $chosen_method ) {
            $chosen_method = explode( ':', $chosen_method );
            $method_ids[]  = current( $chosen_method );
        }        
        if( is_array( $chosen_methods ) && in_array( 'lalamoveidl_shipping', $method_ids ) ) {         
            
            foreach ( $packages as $i => $package ) {
             if ( $method_ids[ $i ] != "lalamoveidl_shipping" ) {                           
                    continue;                         
                }

                $lalamove_shipping = new Lalamoveidl_Shipping_Method();
                $lalamove_stoername = $lalamove_shipping->settings['lalamove_stoername'];
                $lalamove_phone = $lalamove_shipping->settings['lalamove_phone'];
                $lalamove_apikey = $lalamove_shipping->settings['lalamove_apikey'];
                $lalamove_secret = $lalamove_shipping->settings['lalamove_secret'];
                $lalmove_mode = $lalamove_shipping->settings['lalmove_mode'];
                $date = new DateTime();
                $scheduleAt =  $date->format('Y-m-d\TH:i:s.00\Z');
                $response = $lalamove_shipping->lalamove->lalamove_post_order($lalamove_apikey,  $lalamove_secret, $lalmove_mode, $lalamove_stoername, $lalamove_phone, $scheduleAt, $package);
            
            }       
        } 
    }
    add_action( 'woocommerce_checkout_create_order', 'action_woocommerce_review_order_after_submit', 10, 2 );

    add_action( 'woocommerce_before_checkout_billing_form', 'checkoutFields');

    function checkoutFields( $checkout ) {
        ?>
        <script async
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBXVw150XsjUg2-F0Hnwr0OMUhTw89SOPA&libraries=places&callback=initMap">
        </script>
        <script>
            function initMap() {
                const center = { lat: 50.064192, lng: -130.605469 };
                // Create a bounding box with sides ~10km away from the center point
                const defaultBounds = {
                north: center.lat + 0.1,
                south: center.lat - 0.1,
                east: center.lng + 0.1,
                west: center.lng - 0.1,
                };
                const start_distance = document.getElementById("start_distance");
                const end_distance = document.getElementById("end_distance");
                //const end_distance = document.querySelector(".end_distance input");
                const options = {
                bounds: defaultBounds,
                componentRestrictions: {},
                fields: ["address_components", "geometry", "icon", "name"],
                strictBounds: false,
                types: ["establishment"],
                };
                const autocomplete = new google.maps.places.Autocomplete(start_distance, options);
                const autoend_distance = new google.maps.places.Autocomplete(end_distance, options);
                google.maps.event.addListener(autocomplete, 'place_changed', function() {
                    console.log('start change');
                    submitdistance();
                });
                google.maps.event.addListener(autoend_distance, 'place_changed', function() {
                    console.log('end change');
                    submitdistance();
                });
                function submitdistance() {
                    jQuery.ajax({
                    type: 'POST',
                    url: wc_checkout_params.ajax_url,
                    data: {
                        'action': 'idl_points',
                        'start_distance': jQuery("#start_distance").val(),
                        'end_distance' : jQuery("#end_distance").val()
                    },
                    success: function (result) {
                        jQuery(document.body).trigger('update_checkout'); // Update checkout processes
                        console.log( result ); // For testing (output data sent)
                    }
                 });
                }
            }
        </script>
        <?php
        echo '<div class="distance_fields">';
        woocommerce_form_field( 'start_distance', array(
            'type'          => 'text',
            'id'          => 'start_distance',
            'class'         => array('places_find form-row-first','update_totals_on_change'),
            'label'         => __('Pickup Point'),
            'placeholder'   => __(''),
            'required'      => true,
            ), $checkout->get_value( 'start_distance' ));
    
        woocommerce_form_field( 'end_distance', array(
            'type'          => 'text',
            'id'          => 'end_distance',
            'class'         => array('places_find form-row-last','update_totals_on_change'),
            'label'         => __('Devliery Point'),
            'placeholder'   => __(' '),
            'required'      => true,
            ), $checkout->get_value( 'end_distance' ));
    
        echo '</div>';
    
    }

    add_action('woocommerce_checkout_process', 'idl_custom_checkout_field_process');

    function idl_custom_checkout_field_process() {
        // Check if set, if its not set add an error.
        if ( ! $_POST['start_distance'] )
            wc_add_notice( __( 'Please enter pickup point.' ), 'error' );
        if ( ! $_POST['end_distance'] )
            wc_add_notice( __( 'Please enter delivery point.' ), 'error' );
    }

    add_action( 'woocommerce_checkout_update_order_meta', 'idl_custom_checkout_field_update_order_meta' );

    function my_custom_checkout_field_update_order_meta( $order_id ) {
        if ( ! empty( $_POST['start_distance'] ) ) {
            update_post_meta( $order_id, 'Pickup Point', sanitize_text_field( $_POST['start_distance'] ) );
        }
        if ( ! empty( $_POST['end_distance'] ) ) {
            update_post_meta( $order_id, 'Delivery Point', sanitize_text_field( $_POST['end_distance'] ) );
        }
    }

    add_action( 'wp_footer', 'checkout_send_fias_code_via_ajax_js' );
    function checkout_send_fias_code_via_ajax_js() {
        if ( is_checkout() && ! is_wc_endpoint_url() ) :
        ?><script type="text/javascript">
            jQuery(function() {
                jQuery("#start_distance").blur(function(){
                    sendAjaxRequest();
                }); 
                jQuery("#end_distance").blur(function(){
                    sendAjaxRequest();
                }); 
            })
        </script>
        <?php
        endif;
    }

    add_action( 'wp_ajax_idl_points', 'set_idl_points_to_wc_session' );
    add_action( 'wp_ajax_nopriv_idl_points', 'set_idl_points_to_wc_session' );
    function set_idl_points_to_wc_session() {
        $field_key = 'start_distance';
        if ( isset($_POST['start_distance']) && isset($_POST['end_distance']) ){
            // Get data from custom session variable
            $start_distance = sanitize_text_field($_POST['start_distance']);
            $end_distance = sanitize_text_field($_POST['end_distance']);

            WC()->session->set('start_distance', wc_clean($start_distance));
            WC()->session->set('end_distance', wc_clean($end_distance));

            // Send back to javascript the data received as an array (json encoded)
            echo json_encode(array($start_distance,$end_distance));
            wp_die(); // always use die() or wp_die() at the end to avoird errors
        }
    }

    add_action('woocommerce_product_options_general_product_data', function() {
    	woocommerce_wp_text_input([
            	'id' => '_price_price_km',
    	        'label' => __('Price per km', 'txtdomain'),
    	]);
    });

    add_action('woocommerce_process_product_meta', function($post_id) {
    	$product = wc_get_product($post_id);
    	$_price_price_km = isset($_POST['_price_price_km']) ? $_POST['_price_price_km'] : '';
    	$product->update_meta_data('_price_price_km', sanitize_text_field($_price_price_km));
    	$product->save();
    });

    add_filter('woocommerce_checkout_update_order_review', function() {
    	$packages = WC()->cart->get_shipping_packages();
        foreach ($packages as $key => $value) {
            $shipping_session = "shipping_for_package_$key";
            unset(WC()->session->$shipping_session);
        }
    });

}