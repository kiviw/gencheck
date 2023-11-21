<?php
/**
 * Plugin Name: Monero Subaddress Generator
 * Description: Generates a Monero subaddress using RPC and displays it on the WooCommerce checkout page.
 * Version: 1.1
 * Author: Your Name
 */

function generate_monero_subaddress_on_order($order_id) {
    // Debugging: Before retrieving order ID
    error_log('Debugging: Before retrieving order ID');
    $order_id = wc_get_checkout_order_received_id();
    // Debugging: After retrieving order ID
    error_log('Debugging: After retrieving order ID');

    $rpc_url = 'http://127.0.0.1:18080/json_rpc'; // Replace with your Monero RPC URL

    $request_body = json_encode([
        'jsonrpc' => '2.0',
        'id' => '0',
        'method' => 'create_address',
        'params' => [
            'count' => 1, // Adjust the count as needed
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

    if (is_wp_error($response)) {
        // Log the error for debugging
        error_log('Error generating Monero subaddress for Order ' . $order_id . ': ' . $response->get_error_message());
    } else {
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

add_action('woocommerce_new_order', 'generate_monero_subaddress_on_order', 10, 1);

// Display the subaddress on the WooCommerce checkout page
add_action('woocommerce_before_checkout_form', 'display_monero_subaddress_on_checkout');

function display_monero_subaddress_on_checkout() {
    // Debugging: Before retrieving subaddress
    error_log('Debugging: Before retrieving subaddress');
    $order_id = wc_get_checkout_order_received_id();
    $subaddress = get_post_meta($order_id, '_monero_subaddress', true);
    // Debugging: After retrieving subaddress
    error_log('Debugging: After retrieving subaddress');

    if (!empty($subaddress)) {
        echo '<p id="monero-subaddress-container"><strong>Monero Subaddress:</strong> ' . esc_html($subaddress) . '</p>';
    }
}
