<?php
/**
 * Plugin Name: Monero Subaddress Generator
 * Description: Generates a Monero subaddress using RPC and displays it on the WooCommerce order received page.
 * Version: 1.2
 * Author: Your Name
 */

// Enqueue JavaScript for AJAX functionality
add_action('wp_enqueue_scripts', 'monero_subaddress_ajax_enqueue');
function monero_subaddress_ajax_enqueue() {
    wp_enqueue_script('monero-subaddress-ajax', plugin_dir_url(__FILE__) . 'monero-subaddress-ajax.js', array('jquery'), '1.0', true);
    wp_localize_script('monero-subaddress-ajax', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

// Generate Monero subaddress when user places an order
add_action('woocommerce_thankyou', 'generate_monero_subaddress_on_order_received', 10, 1);

function generate_monero_subaddress_on_order_received($order_id) {
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
    error_log('Monero RPC Request for Order ' . $order_id . ': ' . $request_body);

    $response = wp_remote_post($rpc_url, [
        'body' => $request_body,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);

        // Log the response for debugging
        error_log('Monero RPC Response for Order ' . $order_id . ': ' . $body);

        $result = json_decode($body, true);

        if (isset($result['result']['address'])) {
            // Update the order with the generated subaddress
            update_post_meta($order_id, '_monero_subaddress', $result['result']['address']);
        } else {
            // Log an error message if the response format is unexpected
            error_log('Monero RPC Error: Unexpected response format for Order ' . $order_id);

            // Log the unexpected response for further investigation
            error_log('Monero RPC Unexpected Response: ' . $body);
        }
    }
}

// Display the subaddress on the WooCommerce order received page
add_action('woocommerce_thankyou', 'display_monero_subaddress_on_order_received', 20);

function display_monero_subaddress_on_order_received($order_id) {
    $subaddress = get_post_meta($order_id, '_monero_subaddress', true);

    if (!empty($subaddress)) {
        echo '<p id="monero-subaddress-container"><strong>Monero Subaddress:</strong> ' . esc_html($subaddress) . '</p>';
    }
}
?>
