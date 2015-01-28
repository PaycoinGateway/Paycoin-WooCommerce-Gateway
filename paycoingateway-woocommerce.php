<?php
/**
 * Plugin Name: paycoingateway-woocommerce
 * Plugin URI: https://github.com/paycoingateway/
 * Description: Accept Paycoin on your WooCommerce-powered website with PaycoinGateway.
 * Version: 2.6.5
 * Author: PaycoinGateway.com
 * Author URI: https://www.paycoingateway.com
 * License: MIT
 * Text Domain: paycoingateway-woocommerce
 */

/*  Copyright 2015 PaycoinGateway.com.

MIT License

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

	function paycoingateway_woocommerce_init() {

		if (!class_exists('WC_Payment_Gateway'))
			return;

		/**
		 * paycoin Payment Gateway
		 *
		 * Provides a paycoin Payment Gateway.
		 *
		 * @class       WC_Gateway_Paycoin
		 * @extends     WC_Payment_Gateway
		 * @version     2.6.5
		 * @author      PaycoinGateway.com
		 */
		class WC_Gateway_PaycoinGateway extends WC_Payment_Gateway {
			var $notify_url;

			public function __construct() {
				$this->id   = 'paycoingateway';
				$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/paycoin.png';

				$this->has_fields        = false;
				$this->order_button_text = __('Proceed to PaycoinGateway', 'paycoingateway-woocommerce');
				$this->notify_url        = $this->construct_notify_url();

				$this->init_form_fields();
				$this->init_settings();

				$this->title       = $this->get_option('title');
				$this->description = $this->get_option('description');

				// Actions
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
					$this,
					'process_admin_options'
				));
				add_action('woocommerce_receipt_paycoingateway', array(
					$this,
					'receipt_page'
				));

				// Payment listener/API hook
				add_action('woocommerce_api_wc_gateway_paycoingateway', array(
					$this,
					'check_paycoingateway_callback'
				));
			}

			public function admin_options() {
				echo '<h3>' . __('Paycoin Payment Gateway', 'paycoingateway-woocommerce') . '</h3>';
				$paycoin_account_email = get_option("paycoin_account_email");
				$paycoin_error_message = get_option("paycoin_error_message");
				if ($paycoin_account_email != false) {
					echo '<p>' . __('Successfully connected PaycoinGateway.com account', 'paycoingateway-woocommerce') . " '$paycoin_account_email'" . '</p>';
				} elseif ($paycoin_error_message != false) {
					echo '<p>' . __('Could not validate API Key:', 'paycoingateway-woocommerce') . " $paycoin_error_message" . '</p>';
				}
				echo '<table class="form-table">';
				$this->generate_settings_html();
				echo '</table>';
			}

			function process_admin_options() {
				if (!parent::process_admin_options())
					return false;

				require_once(plugin_dir_path(__FILE__) . 'paycoingateway-php' . DIRECTORY_SEPARATOR . 'paycoin.php');

				$api_key    = $this->get_option('apiKey');
				$api_secret = $this->get_option('apiSecret');

				// Validate merchant API key
				try {
					$paycoingateway = PaycoinGateway::withApiKey($api_key, $api_secret);
					$user     = $paycoingateway->getUser();
					update_option("paycoin_account_email", $user->email);
					update_option("paycoin_error_message", false);
				}
				catch (Exception $e) {
					$error_message = $e->getMessage();
					update_option("paycoin_account_email", false);
					update_option("paycoin_error_message", $error_message);
					return;
				}
			}

			function construct_notify_url() {
				$callback_secret = get_option("paycoin_callback_secret");
				if ($callback_secret == false) {
					$callback_secret = sha1(openssl_random_pseudo_bytes(20));
					update_option("paycoin_callback_secret", $callback_secret);
				}
				$notify_url = WC()->api_request_url('WC_Gateway_PaycoinGateway');
				$notify_url = add_query_arg('callback_secret', $callback_secret, $notify_url);
				return $notify_url;
			}

			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title' => __('Enable PaycoinGateway plugin', 'paycoingateway-woocommerce'),
						'type' => 'checkbox',
						'label' => __('Show paycoin as an option to customers during checkout?', 'paycoingateway-woocommerce'),
						'default' => 'yes'
					),
					'title' => array(
						'title' => __('Title', 'woocommerce'),
						'type' => 'text',
						'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
						'default' => __('Paycoin', 'paycoingateway-woocommerce')
					),
					'description' => array(
						'title'       => __( 'Description', 'woocommerce' ),
						'type'        => 'textarea',
						'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
						'default'     => __('Pay with paycoin, a virtual currency.', 'paycoingateway-woocommerce')
											. " <a href='http://Paycoin.com/' target='_blank'>"
											. __('What is paycoin?', 'paycoingateway-woocommerce')
											. "</a>"
	             	),
					'apiKey' => array(
						'title' => __('API Key', 'paycoingateway-woocommerce'),
						'type' => 'text',
						'description' => __('')
					),
					'apiSecret' => array(
						'title' => __('API Secret', 'paycoingateway-woocommerce'),
						'type' => 'password',
						'description' => __('')
					)
				);
			}

			function process_payment($order_id) {

				require_once(plugin_dir_path(__FILE__) . 'paycoingateway-php' . DIRECTORY_SEPARATOR . 'paycoin.php');
				global $woocommerce;

				$order = new WC_Order($order_id);

				$success_url = add_query_arg('return_from_paycoingateway', true, $this->get_return_url($order));

				// PaycoinGateway mangles the order param so we have to put it somewhere else and restore it on init
				$cancel_url = add_query_arg('return_from_paycoingateway', true, $order->get_cancel_order_url());
				$cancel_url = add_query_arg('cancelled', true, $cancel_url);
				$cancel_url = add_query_arg('order_key', $order->order_key, $cancel_url);

				$params = array(
					'name'               => 'Order #' . $order_id,
					'price_string'       => $order->get_total(),
					'price_currency_iso' => get_woocommerce_currency(),
					'callback_url'       => $this->notify_url,
					'custom'             => $order_id,
					'success_url'        => $success_url,
					'cancel_url'         => $cancel_url,
				);

				$api_key    = $this->get_option('apiKey');
				$api_secret = $this->get_option('apiSecret');

				if ($api_key == '' || $api_secret == '') {
					$woocommerce->add_error(__('Sorry, but there was an error processing your order. Please try again or try a different payment method. (plugin not configured)', 'paycoingateway-woocommerce'));
					return;
				}

				try {
					$PaycoinGateway = PaycoinGateway::withApiKey($api_key, $api_secret);
					$code     = $PaycoinGateway->createButtonWithOptions($params)->button->code;
				}
				catch (Exception $e) {
					$order->add_order_note(__('Error while processing paycoin payment:', 'paycoingateway-woocommerce') . ' ' . var_export($e, TRUE));
					$woocommerce->add_error(__( $e . ' Sorry, but there was an error processing your order. Please try again or try a different payment method.', 'paycoingateway-woocommerce'));
					return;
				}
                //$woocommerce->add_error(__('results: '.$code.'', 'paycoingateway-woocommerce'));
                //return;
                //echo json_encode($PaycoinGateway);
				return array(
					'result'   => 'success',
					'redirect' => "https://www.paycoingateway.com/checkouts/$code"
				);
			}

			function check_paycoingateway_callback() {
				$callback_secret = get_option("paycoin_callback_secret");
				if ($callback_secret != false && $callback_secret == $_REQUEST['callback_secret']) {
					$post_body = json_decode(file_get_contents("php://input"));
					if (isset($post_body->order)) {
						$paycoin_order = $post_body->order;
						$order_id       = $paycoin_order->custom;
						$order          = new WC_Order($order_id);
					} else if (isset($post_body->payout)) {
						header('HTTP/1.1 200 OK');
						exit("PaycoinGateway Payout Callback Ignored");
					} else {
						header("HTTP/1.1 400 Bad Request");
						exit("Unrecognized PaycoinGateway Callback");
					}
				} else {
					header("HTTP/1.1 401 Not Authorized");
					exit("Spoofed callback");
				}

				// Legitimate order callback from PaycoinGateway
				header('HTTP/1.1 200 OK');
				// Add PaycoinGateway metadata to the order
				update_post_meta($order->id, __('PaycoinGateway Order ID', 'paycoingateway-woocommerce'), wc_clean($paycoin_order->id));
				if (isset($paycoin_order->customer) && isset($paycoin_order->customer->email)) {
					update_post_meta($order->id, __('PaycoinGateway Account of Payer', 'paycoingateway-woocommerce'), wc_clean($paycoin_order->customer->email));
				}

				switch (strtolower($paycoin_order->status)) {

					case 'completed':

						// Check order not already completed
						if ($order->status == 'completed') {
							exit;
						}

						$order->add_order_note(__('PaycoinGateway payment completed', 'paycoingateway-woocommerce'));
						$order->payment_complete();

						break;
					case 'canceled':

						$order->update_status('failed', __('PaycoinGateway reports payment cancelled.', 'paycoingateway-woocommerce'));
						break;

				}

				exit;
			}
		}

		/**
		 * Add this Gateway to WooCommerce
		 **/
		function woocommerce_add_paycoingateway_gateway($methods) {
			$methods[] = 'WC_Gateway_PaycoinGateway';
			return $methods;
		}

		function woocommerce_handle_paycoingateway_return() {
			if (!isset($_GET['return_from_paycoingateway']))
				return;

			if (isset($_GET['cancelled'])) {
				$order = new WC_Order($_GET['order']['custom']);
				if ($order->status != 'completed') {
					$order->update_status('failed', __('Customer cancelled PaycoinGateway payment', 'paycoingateway-woocommerce'));
				}
			}

			// paycoingateway order param interferes with woocommerce
			unset($_GET['order']);
			unset($_REQUEST['order']);
			if (isset($_GET['order_key'])) {
				$_GET['order'] = $_GET['order_key'];
			}
		}

		add_action('init', 'woocommerce_handle_paycoingateway_return');
		add_filter('woocommerce_payment_gateways', 'woocommerce_add_paycoingateway_gateway');
	}

	add_action('plugins_loaded', 'paycoingateway_woocommerce_init', 0);
}
