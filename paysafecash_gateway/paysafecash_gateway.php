<?php
/*
 * Plugin Name: Paysafecash
 * Plugin URI: https://www.paysafecash.com/
 * Description: Take paysafecash payments on your store.
 * Author: Paysafecash
 * Text Domain: paysafecash
 * Author URI: https://www.paysafecash.com/
 * Version: 1.0.0
 *
*/
include(plugin_dir_path(__FILE__) . 'libs/PaymentClass.php');
add_filter('woocommerce_payment_gateways', 'paysafecash_add_gateway_class');
function paysafecash_add_gateway_class($methods)
{
    $methods[] = 'WC_Paysafecash_Gateway';

    return $methods;
}

add_action('plugins_loaded', 'paysafecash_init_gateway_class');

function paysafecash_textdomain()
{
    load_plugin_textdomain('paysafecash', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

function paysafecash_country_restriction($available_gateways)
{
    global $woocommerce;

    $options = get_option('woocommerce_paysafecash_settings');
    $is_diabled = true;

    foreach ($options["country"] AS $country) {
        if ($woocommerce->customer->get_country() == $country) {
            $is_diabled = false;
        }
    }

    if ($is_diabled == true) {
        unset($available_gateways["paysafecash"]);
    }
    return $available_gateways;

}

function paysafecash_init_gateway_class()
{
    paysafecash_textdomain();

    class WC_Paysafecash_Gateway extends WC_Payment_Gateway
    {

        public function __construct()
        {
            $this->id = 'paysafecash';
            $this->icon = '';
            $this->has_fields = true;
            $this->method_title = 'Paysafecash';
            $this->method_description = __('Paysafecash is a cash payment option. Generate a QR/barcode and pay at a nearby shop.More information and our payment points can be found at <a href=\"https://www.paysafecash.com\" target=\"_blank\">www.paysafecash.com</a>', 'paysafecash');
            $this->description = $this->method_description;
            $this->version = "1.0.0";
            $this->supports = array(
                'products',
                'refunds'
            );

            $this->init_form_fields();
            $this->init_settings();
            $this->title = "Paysafecash";
            $this->description = __('Paysafecash is a cash payment option. Generate a QR/barcode and pay at a nearby shop.More information and our payment points can be found at <a href=\"https://www.paysafecash.com\" target=\"_blank\">www.paysafecash.com</a>', 'paysafecash');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            $this->private_key = $this->testmode ? $this->get_option('api_test_key') : $this->get_option('api_test_key');
            $this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));

            add_action('plugins_loaded', 'paysafecash_textdomain');

            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'callback_handler'));

            add_action('woocommerce_thankyou_paysafecash', array($this, 'check_response'));

            add_filter('woocommerce_available_payment_gateways', 'paysafecash_country_restriction');

        }

        function paysafecash_textdomain()
        {
            load_plugin_textdomain('paysafecash', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }


        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable Paysafecash',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                'testmode' => array(
                    'title' => 'Test mode',
                    'label' => 'Enable Test Mode',
                    'type' => 'checkbox',
                    'description' => __('If the test mode is enabled you are making transactions against Paysafecash test environment. Therefore the test environment API key is necessary to be set.', 'paysafecash'),
                    'default' => 'yes',
                    'desc_tip' => false,
                ),
                'api_key' => array(
                    'title' => 'API Key',
                    'description' => __('This key is provided by the Paysafecash support team. There is one key for the test- and one for production environment.', 'paysafecash'),
                    'type' => 'password'
                ),
                'submerchant_id' => array(
                    'title' => 'Submerchant ID',
                    'description' => __('This field specifies the used Reporting Criteria. You can use this parameter to distinguish your transactions per brand/URL. Use this field only if agreed beforehand with the Paysafecash support team. The value has to be configured in both systems.', 'paysafecash'),
                    'type' => 'text'
                ),
                'country' => array(
                    'title' => 'Countries',
                    'description' => __('Please select all countries where Paysafecash is live and your webshop is live. For details about the available countries for Paysafecash, please align with the Paysafecash support team.', 'paysafecash'),
                    'type' => 'multiselect',
                    'options' => array(
                        'AF' => 'Afghanistan',
                        'AX' => 'Aland Islands',
                        'AL' => 'Albania',
                        'DZ' => 'Algeria',
                        'AS' => 'American Samoa',
                        'AD' => 'Andorra',
                        'AO' => 'Angola',
                        'AI' => 'Anguilla',
                        'AQ' => 'Antarctica',
                        'AG' => 'Antigua And Barbuda',
                        'AR' => 'Argentina',
                        'AM' => 'Armenia',
                        'AW' => 'Aruba',
                        'AU' => 'Australia',
                        'AT' => 'Austria',
                        'AZ' => 'Azerbaijan',
                        'BS' => 'Bahamas',
                        'BH' => 'Bahrain',
                        'BD' => 'Bangladesh',
                        'BB' => 'Barbados',
                        'BY' => 'Belarus',
                        'BE' => 'Belgium',
                        'BZ' => 'Belize',
                        'BJ' => 'Benin',
                        'BM' => 'Bermuda',
                        'BT' => 'Bhutan',
                        'BO' => 'Bolivia',
                        'BA' => 'Bosnia And Herzegovina',
                        'BW' => 'Botswana',
                        'BV' => 'Bouvet Island',
                        'BR' => 'Brazil',
                        'IO' => 'British Indian Ocean Territory',
                        'BN' => 'Brunei Darussalam',
                        'BG' => 'Bulgaria',
                        'BF' => 'Burkina Faso',
                        'BI' => 'Burundi',
                        'KH' => 'Cambodia',
                        'CM' => 'Cameroon',
                        'CA' => 'Canada',
                        'CV' => 'Cape Verde',
                        'KY' => 'Cayman Islands',
                        'CF' => 'Central African Republic',
                        'TD' => 'Chad',
                        'CL' => 'Chile',
                        'CN' => 'China',
                        'CX' => 'Christmas Island',
                        'CC' => 'Cocos (Keeling) Islands',
                        'CO' => 'Colombia',
                        'KM' => 'Comoros',
                        'CG' => 'Congo',
                        'CD' => 'Congo, Democratic Republic',
                        'CK' => 'Cook Islands',
                        'CR' => 'Costa Rica',
                        'CI' => 'Cote D\'Ivoire',
                        'HR' => 'Croatia',
                        'CU' => 'Cuba',
                        'CY' => 'Cyprus',
                        'CZ' => 'Czech Republic',
                        'DK' => 'Denmark',
                        'DJ' => 'Djibouti',
                        'DM' => 'Dominica',
                        'DO' => 'Dominican Republic',
                        'EC' => 'Ecuador',
                        'EG' => 'Egypt',
                        'SV' => 'El Salvador',
                        'GQ' => 'Equatorial Guinea',
                        'ER' => 'Eritrea',
                        'EE' => 'Estonia',
                        'ET' => 'Ethiopia',
                        'FK' => 'Falkland Islands (Malvinas)',
                        'FO' => 'Faroe Islands',
                        'FJ' => 'Fiji',
                        'FI' => 'Finland',
                        'FR' => 'France',
                        'GF' => 'French Guiana',
                        'PF' => 'French Polynesia',
                        'TF' => 'French Southern Territories',
                        'GA' => 'Gabon',
                        'GM' => 'Gambia',
                        'GE' => 'Georgia',
                        'DE' => 'Germany',
                        'GH' => 'Ghana',
                        'GI' => 'Gibraltar',
                        'GR' => 'Greece',
                        'GL' => 'Greenland',
                        'GD' => 'Grenada',
                        'GP' => 'Guadeloupe',
                        'GU' => 'Guam',
                        'GT' => 'Guatemala',
                        'GG' => 'Guernsey',
                        'GN' => 'Guinea',
                        'GW' => 'Guinea-Bissau',
                        'GY' => 'Guyana',
                        'HT' => 'Haiti',
                        'HM' => 'Heard Island & Mcdonald Islands',
                        'VA' => 'Holy See (Vatican City State)',
                        'HN' => 'Honduras',
                        'HK' => 'Hong Kong',
                        'HU' => 'Hungary',
                        'IS' => 'Iceland',
                        'IN' => 'India',
                        'ID' => 'Indonesia',
                        'IR' => 'Iran, Islamic Republic Of',
                        'IQ' => 'Iraq',
                        'IE' => 'Ireland',
                        'IM' => 'Isle Of Man',
                        'IL' => 'Israel',
                        'IT' => 'Italy',
                        'JM' => 'Jamaica',
                        'JP' => 'Japan',
                        'JE' => 'Jersey',
                        'JO' => 'Jordan',
                        'KZ' => 'Kazakhstan',
                        'KE' => 'Kenya',
                        'KI' => 'Kiribati',
                        'KR' => 'Korea',
                        'KW' => 'Kuwait',
                        'KG' => 'Kyrgyzstan',
                        'LA' => 'Lao People\'s Democratic Republic',
                        'LV' => 'Latvia',
                        'LB' => 'Lebanon',
                        'LS' => 'Lesotho',
                        'LR' => 'Liberia',
                        'LY' => 'Libyan Arab Jamahiriya',
                        'LI' => 'Liechtenstein',
                        'LT' => 'Lithuania',
                        'LU' => 'Luxembourg',
                        'MO' => 'Macao',
                        'MK' => 'Macedonia',
                        'MG' => 'Madagascar',
                        'MW' => 'Malawi',
                        'MY' => 'Malaysia',
                        'MV' => 'Maldives',
                        'ML' => 'Mali',
                        'MT' => 'Malta',
                        'MH' => 'Marshall Islands',
                        'MQ' => 'Martinique',
                        'MR' => 'Mauritania',
                        'MU' => 'Mauritius',
                        'YT' => 'Mayotte',
                        'MX' => 'Mexico',
                        'FM' => 'Micronesia, Federated States Of',
                        'MD' => 'Moldova',
                        'MC' => 'Monaco',
                        'MN' => 'Mongolia',
                        'ME' => 'Montenegro',
                        'MS' => 'Montserrat',
                        'MA' => 'Morocco',
                        'MZ' => 'Mozambique',
                        'MM' => 'Myanmar',
                        'NA' => 'Namibia',
                        'NR' => 'Nauru',
                        'NP' => 'Nepal',
                        'NL' => 'Netherlands',
                        'AN' => 'Netherlands Antilles',
                        'NC' => 'New Caledonia',
                        'NZ' => 'New Zealand',
                        'NI' => 'Nicaragua',
                        'NE' => 'Niger',
                        'NG' => 'Nigeria',
                        'NU' => 'Niue',
                        'NF' => 'Norfolk Island',
                        'MP' => 'Northern Mariana Islands',
                        'NO' => 'Norway',
                        'OM' => 'Oman',
                        'PK' => 'Pakistan',
                        'PW' => 'Palau',
                        'PS' => 'Palestinian Territory, Occupied',
                        'PA' => 'Panama',
                        'PG' => 'Papua New Guinea',
                        'PY' => 'Paraguay',
                        'PE' => 'Peru',
                        'PH' => 'Philippines',
                        'PN' => 'Pitcairn',
                        'PL' => 'Poland',
                        'PT' => 'Portugal',
                        'PR' => 'Puerto Rico',
                        'QA' => 'Qatar',
                        'RE' => 'Reunion',
                        'RO' => 'Romania',
                        'RU' => 'Russian Federation',
                        'RW' => 'Rwanda',
                        'BL' => 'Saint Barthelemy',
                        'SH' => 'Saint Helena',
                        'KN' => 'Saint Kitts And Nevis',
                        'LC' => 'Saint Lucia',
                        'MF' => 'Saint Martin',
                        'PM' => 'Saint Pierre And Miquelon',
                        'VC' => 'Saint Vincent And Grenadines',
                        'WS' => 'Samoa',
                        'SM' => 'San Marino',
                        'ST' => 'Sao Tome And Principe',
                        'SA' => 'Saudi Arabia',
                        'SN' => 'Senegal',
                        'RS' => 'Serbia',
                        'SC' => 'Seychelles',
                        'SL' => 'Sierra Leone',
                        'SG' => 'Singapore',
                        'SK' => 'Slovakia',
                        'SI' => 'Slovenia',
                        'SB' => 'Solomon Islands',
                        'SO' => 'Somalia',
                        'ZA' => 'South Africa',
                        'GS' => 'South Georgia And Sandwich Isl.',
                        'ES' => 'Spain',
                        'LK' => 'Sri Lanka',
                        'SD' => 'Sudan',
                        'SR' => 'Suriname',
                        'SJ' => 'Svalbard And Jan Mayen',
                        'SZ' => 'Swaziland',
                        'SE' => 'Sweden',
                        'CH' => 'Switzerland',
                        'SY' => 'Syrian Arab Republic',
                        'TW' => 'Taiwan',
                        'TJ' => 'Tajikistan',
                        'TZ' => 'Tanzania',
                        'TH' => 'Thailand',
                        'TL' => 'Timor-Leste',
                        'TG' => 'Togo',
                        'TK' => 'Tokelau',
                        'TO' => 'Tonga',
                        'TT' => 'Trinidad And Tobago',
                        'TN' => 'Tunisia',
                        'TR' => 'Turkey',
                        'TM' => 'Turkmenistan',
                        'TC' => 'Turks And Caicos Islands',
                        'TV' => 'Tuvalu',
                        'UG' => 'Uganda',
                        'UA' => 'Ukraine',
                        'AE' => 'United Arab Emirates',
                        'GB' => 'United Kingdom',
                        'US' => 'United States',
                        'UM' => 'United States Outlying Islands',
                        'UY' => 'Uruguay',
                        'UZ' => 'Uzbekistan',
                        'VU' => 'Vanuatu',
                        'VE' => 'Venezuela',
                        'VN' => 'Viet Nam',
                        'VG' => 'Virgin Islands, British',
                        'VI' => 'Virgin Islands, U.S.',
                        'WF' => 'Wallis And Futuna',
                        'EH' => 'Western Sahara',
                        'YE' => 'Yemen',
                        'ZM' => 'Zambia',
                        'ZW' => 'Zimbabwe'
                    )
                ),
            );
        }

        public function admin_options()
        {
            echo '<h3>' . __('Paysafecash', 'paysafecash') . '</h3>';
            echo '<p>' . __('Paysafecash is a cash payment option. Generate a QR/barcode and pay at a nearby shop.More information and our payment points can be found at <a href=\"https://www.paysafecash.com\" target=\"_blank\">www.paysafecash.com</a>', 'paysafecash') . '</p>';
            echo '<p>' . __('<a href="' . plugins_url('Installation_guidelines_wooCommerce_EN.pdf', __FILE__) . '">Here</a> you can find the Installation instructions', 'paysafecash') . '</a>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';

        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields()
        {
            global $woocommerce;
            if ($this->description) {
                if ($this->testmode) {
                    $this->description .= ' TEST MODE ENABLED';
                    $this->description = trim($this->description);
                }
                echo wpautop(wp_kses_post('<img width="270px" height="77px" style="max-height: 100%; width:100%; float:left;" src="' . plugins_url('img/paysafecash.png', __FILE__) . '" ><br>' . $this->description));
            }
        }

        public function process_payment($order_id)
        {
            global $woocommerce;

            $this->init_settings();
            $this->api_key = $this->settings['api_key'];
            $this->submerchant_id = $this->settings['submerchant_id'];
            $this->testmode = $this->settings['testmode'];

            $order = wc_get_order($order_id);

            exec("echo ".$this->testmode." >> /tmp/wp.log");

            if ($this->testmode == "yes") {
                $env = "TEST";
            } else {
                $env = "PRODUCTION";
            }

            $pscpayment = new PaysafecardCashController($this->api_key, $env);
            $success_url = $order->get_checkout_order_received_url() . "&paysafecash=true&success=true&order_id=" . $order->get_order_number() . "&payment_id={payment_id}";
            $failure_url = $order->get_checkout_payment_url() . "&paysafecash=false&failed=true&payment_id={payment_id}";
            $notification_url = $this->get_return_url($order) . "&wc-api=wc_paysafecash_gateway&order_id=" . $order->get_order_number() . "&payment_id={payment_id}";

            if (empty($order->get_customer_id())) {
                $customerhash = md5($order->get_billing_email());
            } else {
                $customerhash = md5($order->get_customer_id());
            }

            $response = $pscpayment->initiatePayment($order->get_total(), $order->get_currency(), $customerhash, $order->get_customer_ip_address(), $success_url, $failure_url, $notification_url, $correlation_id = "", $country_restriction = "", $kyc_restriction = "", $min_age = "", $shop_id = "Woocommerce: " . $woocommerce->version . " | " . $this->version, $this->submerchant_id);

            $order->add_order_note(sprintf(__('%s payment initiated: Transaction ID: %s', 'paysafecash'), $this->title, $response["id"]));

            if (isset($response["object"])) {
                return array(
                    'result' => 'success',
                    'redirect' => $response["redirect"]['auth_url']
                );

            }
        }

        public function process_refund($order_id, $amount = null, $reason = '')
        {

            global $woocommerce;

            $this->init_settings();
            $this->api_key = $this->settings['api_key'];
            $this->submerchant_id = $this->settings['submerchant_id'];
            $this->testmode = $this->settings['testmode'];

            $order = wc_get_order($order_id);

            if ($this->testmode == "yes") {
                $env = "TEST";
            } else {
                $env = "PRODUCTION";
            }

            $pscpayment = new PaysafecardCashController($this->api_key, $env);

            if (empty($order->get_customer_id())) {
                $customerhash = md5($order->get_billing_email());
            } else {
                $customerhash = md5($order->get_customer_id());
            }

            $currency = $order->get_currency();
            $payment_id = $order->get_transaction_id();

            $response = $pscpayment->captureRefund($payment_id, $amount, $currency, $customerhash, $order->get_billing_email(), "", $this->submerchant_id, $shop_id = "Woocommerce: " . $woocommerce->version, $this->submerchant_id . " | " . $this->version);
            if ($response == false || isset($response['number'])) {
                $error = new WP_Error();
                $error->add($response['number'], $response['message']);

                return $error;

            } else if (isset($response["object"])) {
                if ($response["status"] == "SUCCESS") {
                    return true;
                } else {
                    $error = new WP_Error();
                    $error->add($response['number'], $response['message']);

                    return $error;
                }
            }

            return false;
        }

        public function check_response()
        {
            global $woocommerce;


            if (isset($_GET['paysafecash'])) {

                $payment_id = $_GET['payment_id'];
                $order_id = $_GET['order_id'];

                $order = wc_get_order($order_id);


                if ($order_id == 0 || $order_id == '') {
                    return;
                }

                if ($this->testmode == "yes") {
                    $env = "TEST";
                } else {
                    $env = "PRODUCTION";
                }

                $this->init_settings();
                $this->api_key = $this->settings['api_key'];
                $pscpayment = new PaysafecardCashController($this->api_key, $env);
                $response = $pscpayment->retrievePayment($payment_id);

                if ($response == false) {
                    wc_add_notice('Error Request' . var_dump($response), 'error');

                    return array(
                        'result' => 'failed',
                        'redirect' => ''
                    );


                } else if (isset($response["object"])) {
                    if ($response["status"] == "SUCCESS") {
                        $order->payment_complete($payment_id);
                        $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paysafecash'), $this->title, $payment_id));
                        $woocommerce->cart->empty_cart();

                    } else if ($response["status"] == "INITIATED") {
                        echo "<p><h2>Payment notification</h2>" . __('Thank you, please go to the Point of Sales and pay the transaction', 'paysafecash') . "</p>";
                    } else if ($response["status"] == "REDIRECTED") {
                        echo "<p><h2>Payment notification</h2>" . __('Thank you, please go to the Point of Sales and pay the transaction', 'paysafecash') . "</p>";
                    } else if ($response["status"] == "EXPIRED") {
                        echo "<p><h2>Payment notification</h2>" . __('Unfortunately, your payment failed. Please try again', 'paysafecash') . "</p>";
                    } else if ($response["status"] == "AUTHORIZED") {
                        $response = $pscpayment->capturePayment($payment_id);
                        if ($response == true) {
                            if (isset($response["object"])) {
                                if ($response["status"] == "SUCCESS") {
                                    $order->payment_complete($payment_id);
                                    $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paysafecash'), $this->title, $payment_id));
                                    $order->set_status('pending', 'Payment Approved.');
                                    retrun;
                                }
                            }
                        }
                    }
                }

                if (isset($_GET["failed"])) {
                    $order = new WC_Order($order_id);
                    $order->update_status('cancelled', sprintf(__('%s payment cancelled! Transaction ID: %d', 'paysafecash'), $this->title, $payment_id));
                }
            }
        }

        public function payment_scripts()
        {

        }

        public function callback_handler()
        {
            global $woocommerce;

            $payment_id = $_GET['payment_id'];
            $order_id = $_GET['order_id'];

            $this->init_settings();
            $this->api_key = $this->settings['api_key'];
            $this->submerchant_id = $this->settings['submerchant_id'];

            if ($this->testmode == "yes") {
                $env = "TEST";
            } else {
                $env = "PRODUCTION";
            }

            $pscpayment = new PaysafecardCashController($this->api_key, $env);
            $response = $pscpayment->retrievePayment($payment_id);

            $order = wc_get_order($order_id);

            if ($response == false) {

            } else if (isset($response["object"])) {

                if ($response["status"] == "SUCCESS") {
                    if ('processing' == $order->status) {
                        $order->payment_complete($payment_id);
                        $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paysafecash'), $this->title, $payment_id));
                        $order->set_status('pending', 'Payment Approved.');
                    }
                } else if ($response["status"] == "INITIATED") {
                } else if ($response["status"] == "REDIRECTED") {
                } else if ($response["status"] == "EXPIRED") {
                } else if ($response["status"] == "AUTHORIZED") {
                    $response = $pscpayment->capturePayment($payment_id);
                    if ($response == true) {
                        if (isset($response["object"])) {
                            if ($response["status"] == "SUCCESS") {
                                $order->payment_complete($payment_id);
                                $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paysafecash'), $this->title, $payment_id));
                                $order->set_status('pending', 'Payment Approved.');
                                retrun;
                            }
                        }
                    }
                }
            }

            update_option('webhook_debug', $_GET);
        }
    }
}
