<?php
if (!defined('ABSPATH')) {die;}

add_action('add_meta_boxes', 'partial_payments_metabox', 31);

function partial_payments_metabox()
{
    global $post;
    if (is_null($post)) {
        return;
    }

    $order = wc_get_order($post->ID);

    if ($order) {

        if ($order->get_meta('deposit_mode') !== 'yes') {
            return;
        }

        if ($order->get_type() !== 'wcpp_payment') {
            add_meta_box('wc_deposits_partial_payments',
                esc_html__('Partial Payments', 'advanced-partial-payment-or-deposit-for-woocommerce'),
                'mage_partial_payments_summary',
                'shop_order',
                'normal',
                'high'
            );
        }
    }
}

function mage_partial_payments_summary()
{
    global $post;
    $order = wc_get_order($post->ID);
    if (!$order) {
        return;
    }
    echo mep_pp_history_get($order->get_id(), false, false);
}