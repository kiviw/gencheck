<?php
/**
 * Plugin Name: Monero Subaddress Generator
 * Description: Generates a Monero subaddress using RPC and displays it on the WooCommerce checkout page.
 * Version: 1.2
 * Author: Your Name
 */

// Enqueue JavaScript for AJAX functionality
add_action('wp_enqueue_scripts', 'monero_subaddress_ajax_enqueue');
function monero_subaddress_ajax_enqueue() {
    wp_enqueue_script('monero-subaddress-ajax', plugin_dir_url(__FILE__) . 'monero-subaddress-ajax.js', array('jquery'), '1.0', true);
    wp_localize_script('monero-subaddress-ajax', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

// Generate Monero subaddress when user proceeds to checkout
add_action('woocommerce_before_checkout_form', 'generate_monero_subaddress_on_checkout');

function generate_monero_subaddress_on_checkout() {
    ?>
    <div id="monero-subaddress-container">
        <p id="monero-subaddress-result">Click the button to generate Monero subaddress.</p>
        <button id="generate-monero-subaddress">Generate Subaddress</button>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            $('#generate-monero-subaddress').on('click', function () {
                $.ajax({
                    type: 'POST',
                    url: ajax_object.ajax_url,
                    data: { action: 'generate_monero_subaddress_checkout' },
                    success: function (response) {
                        $('#monero-subaddress-result').html('Generated Monero Subaddress: ' + response);
                    }
                });
            });
        });
    </script>
    <?php
}

// AJAX action for subaddress generation on checkout
add_action('wp_ajax_generate_monero_subaddress_checkout', 'generate_monero_subaddress_callback_checkout');
function generate_monero_subaddress_callback_checkout() {
    $rpc_url = 'http://127.0.0.1:18080/json_rpc'; // Replace with your Monero RPC URL

    $request_body = json_encode([
        'jsonrpc' => '2.0',
        'id' => '0',
        'method' => 'create_address',
        'params' => [
            'count' => 1,
        ],
    ]);

    // Log the request for debugging
    error_log('Monero RPC Request for Checkout: ' . $request_body);

    $response = wp_remote_post($rpc_url, [
        'body' => $request_body,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);

        // Log the response for debugging
        error_log('Monero RPC Response for Checkout: ' . $body);

        $result = json_decode($body, true);

        if (isset($result['result']['address'])) {
            echo $result['result']['address'];
        } else {
            echo 'Error generating Monero subaddress';
        }
    }

    wp_die();
}
