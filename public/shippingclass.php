<?php
class IDL_SHIPPING extends WC_Shipping_Method {
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {
					$this->id                 = 'idl_distance_shipping'; // Id for your shipping method. Should be uunique.
					$this->method_title       = __( 'Distance Calc Shipping' );  // Title shown in admin
					$this->method_description = __( 'Plugin to calculate distance between 2 points and to find total shipping' ); // Description shown in admin
					$this->domain = 'idldistship';

					$this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
					$this->title              = "Distance Calc Shipping"; // This can be added as an setting but for this example its forced.
					$this->supports           = array(
						'shipping-zones',
						'instance-settings',
						'instance-settings-modal',
						'settings'
					);

					$this->init();
				}

				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				function init_form_fields() {
					$this->instance_form_fields = array(
							'title' => array(
									'type'          => 'text',
									'title'         => __('Title', $this->domain),
									'description'   => __( 'Title to be displayed on site.', $this->domain ),
									'default'       => __( 'Request a Quote ', $this->domain ),
							),
							'cost' => array(
									'type'          => 'text',
									'title'         => __('Coast', $this->domain),
									'description'   => __( 'Enter a cost', $this->domain ),
									'default'       => '',
							),
					);
			}

				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping( $package = array() ) {
					$rate = array(
						'label' => $this->title,
						'cost' => '10.99',
						'calc_tax' => 'per_item'
					);

					// Register the rate
					$this->add_rate( $rate );
				}
			}