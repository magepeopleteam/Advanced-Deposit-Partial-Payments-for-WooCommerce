<?php
if (!defined('ABSPATH')) {
    die;
}

class MEP_PP_Cart
{
    public function __construct()
    {
        add_filter('woocommerce_cart_item_name', [$this, 'display_cart_item_pp_deposit_data'], 10, 3);
        add_action('woocommerce_cart_totals_after_order_total', [$this, 'to_pay_html']);

        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_pp_deposit_data'], 100, 2);

        add_filter('woocommerce_calculated_total', array($this, 'adjust_cart_total'), 100, 1); // For paypal

        do_action('dfwc_cart', $this);
        do_action('appdw_cart', $this);
    }

    public function to_pay_html()
    {
        if (meppp_cart_have_pp_deposit_item()) {
            meppp_display_to_pay_html();
        }
    }

    public function add_cart_item_pp_deposit_data($cart_item_data, $product_id)
    {
        // echo '<pre>';print_r($_POST);die;
        if (function_exists('mep_product_exists')) {
            if (get_post_meta($product_id, 'link_mep_event', true)) {
                $linked_event_id = get_post_meta($product_id, 'link_mep_event', true);
            } else {
                $linked_event_id = null;
            }

            if ($linked_event_id) {
                if (!wcppe_enable_for_event()) return $cart_item_data;
                $product_id = mep_product_exists($linked_event_id) ? $linked_event_id : $product_id;
            }
        }

        // Partial Option Page
        $partial_option_page = apply_filters('mepp_partial_option_for_page', 'product_detail');
        if ($partial_option_page === 'checkout') {
            $cart_item_data['_pp_deposit_system'] = 'check_full';
            return $cart_item_data;
        }

        // Exclude from global setting
        if (apply_filters('mepp_exclude_product_from_default_setting_checking', false, $product_id) === 'stop') {
            return $cart_item_data;
        }

        // Check user and role
        if (apply_filters('mepp_user_role_allow', $cart_item_data) === 'stop') {
            return $cart_item_data;
        }

        $deposit_mode = isset($_POST['deposit-mode']) ? $_POST['deposit-mode'] : null;

        // Global Settings
        $default_deposit_type = get_option('mepp_default_partial_type') ? get_option('mepp_default_partial_type') : '';
        $default_deposit_value = get_option('mepp_default_partial_amount') ? get_option('mepp_default_partial_amount') : '';
        $default_partial_enable = apply_filters('mepp_enable_partial_by_default', 'no');

        if ($deposit_mode) { // From Product Page
            $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 0;
            $deposit_setting_apply = 'local';
        } else { // From Shop Page
            $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;
            $deposit_mode = 'check_pp_deposit';
            $deposit_setting_apply = 'global';
        }

        // Check Product allow deposit and payment type
        if ((meppp_is_product_type_pp_deposit($product_id) == false && $default_partial_enable !== 'yes') || $deposit_mode == 'check_full') {
            return $cart_item_data;
        }

        $product_type = get_post_type($product_id);
        $product_price_total = 0;

        if ($product_type == 'mep_events') {
            $product_price_total = $cart_item_data['line_total'];
        } else {
            $product = wc_get_product($product_id);
            $product_price_total = $product->get_price() * (int)sanitize_text_field($quantity);
            if ($product->is_type('variable')) {
                // Product has variations
                $variation_id = sanitize_text_field($_POST['variation_id']);
                $product = new WC_Product_Variation($variation_id);
                $product_price_total = $product->get_price() * (int)sanitize_text_field($quantity);
            }
        }

        // Check is zero price checkout
        $enable_checkout_zero_price = apply_filters('mepp_enable_zero_price_checkout', 'no');
        if ($enable_checkout_zero_price === 'yes') {
            $cart_item_data['_pp_deposit'] = 0;
            $cart_item_data['_pp_deposit_value'] = 0;
            $cart_item_data['_pp_due_payment'] = $product_price_total;
            $cart_item_data['_pp_deposit_type'] = sanitize_text_field($deposit_mode);
            $cart_item_data['_pp_deposit_system'] = 'zero_price_checkout';
            $cart_item_data['_pp_deposit_payment_plan_name'] = '';

            return $cart_item_data;
        }

        $is_exclude_from_global = get_post_meta($product_id, '_mep_exclude_from_global_deposit', true);
        $is_deposit_enable = get_post_meta($product_id, '_mep_enable_pp_deposit', true);

        if ($is_exclude_from_global === 'yes' && $is_deposit_enable === 'yes') {
            $deposit_value = get_post_meta($product_id, '_mep_pp_deposits_value', true) ? get_post_meta($product_id, '_mep_pp_deposits_value', true) : 0;
            $deposit_type = get_post_meta($product_id, '_mep_pp_deposits_type', true) ? get_post_meta($product_id, '_mep_pp_deposits_type', true) : '';
        } else {
            $deposit_value = $default_deposit_value;
            $deposit_type = $default_deposit_type;
        }

        // Exception handle for deposit type
        if (!wcppe_enable_for_event()) {
            if ($deposit_type === 'minimum_amount' || $deposit_type === 'payment_plan') {
                update_option('mepp_default_partial_type', 'percent');
                $deposit_type = 'percent';
            }
        }

        // Calculate data
        if ($deposit_type == 'percent') {
            $deposit_amount = ($deposit_value / 100) * $product_price_total;

        } elseif ($deposit_type == 'manual' || $deposit_type == 'ticket_type') {
            $deposit_amount = isset($_POST['user-deposit-amount']) ? sanitize_text_field($_POST['user-deposit-amount']) / sanitize_text_field($quantity) : 0;

        } elseif ($deposit_type == 'minimum_amount') {
            $deposit_amount = isset($_POST['user-deposit-amount']) ? sanitize_text_field($_POST['user-deposit-amount']) / sanitize_text_field($quantity) : 0;

        } elseif ($deposit_type == 'payment_plan') {
            $get_payment_terms = mep_make_payment_terms($product_price_total, sanitize_text_field($_POST['mep_payment_plan']));
            $cart_item_data['_pp_order_payment_terms'] = $get_payment_terms['payment_terms'];
            $deposit_amount = $get_payment_terms['deposit_amount'];

        } else {
            $deposit_amount = $deposit_value / sanitize_text_field($quantity);

        }
        $cart_item_data['_pp_deposit'] = $deposit_amount;
        $cart_item_data['_pp_deposit_value'] = $deposit_value;
        $cart_item_data['_pp_due_payment'] = $product_price_total - $deposit_amount;
        $cart_item_data['_pp_deposit_type'] = sanitize_text_field($deposit_mode);
        $cart_item_data['_pp_deposit_system'] = $deposit_type;
        $cart_item_data['_pp_deposit_payment_plan_name'] = isset($_POST['mep_payment_plan']) ? mep_pp_payment_plan_name(sanitize_text_field($_POST['mep_payment_plan'])) : '';
        // echo '<pre>';print_r($cart_item_data);die;
        return $cart_item_data;
    }

    public function adjust_cart_total($total)
    {

        $total_deposit = 0;
        $is_deposit_pass = false;
        $has_deposit_type_minimum = false;
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') {
                $total_deposit += $cart_item['_pp_deposit'];
                $is_deposit_pass = true;
                if ($cart_item['_pp_deposit_system'] === 'minimum_amount') {
                    $has_deposit_type_minimum = true;
                }
            }
        }

        // Has paypal && deposit type !== 'minimum_amount'
        if (mepp_check_paypal_has() && !$has_deposit_type_minimum) {
            return $is_deposit_pass ? $total_deposit : $total;
        } else {
            return $total;
        }

    }

    public function display_cart_item_pp_deposit_data($name, $cart_item, $cart_item_key)
    {
        // echo '<pre>';print_r($cart_item);die;
        if (isset($cart_item['_pp_deposit']) && is_cart() && isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') {
            $name .= sprintf(
                '<strong><p>' . mepp_get_option('mepp_text_translation_string_deposit', __('Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce')) . ': %s <br> ' . mepp_get_option('mepp_text_translation_string_due_payment', __('Due Payment', 'advanced-partial-payment-or-deposit-for-woocommerce')) . ': %s <br> ' . mepp_get_option('mepp_text_translation_string_deposit_type', __('Deposit Type', 'advanced-partial-payment-or-deposit-for-woocommerce')) . ': %s</p></strong>',
                wc_price($cart_item['_pp_deposit']),
                wc_price($cart_item['_pp_due_payment']),
                mep_pp_deposti_type_display_name($cart_item['_pp_deposit_system'], $cart_item, true)
            );
            if (isset($cart_item['_pp_order_payment_terms']) && isset($cart_item['_pp_deposit_system'])) {
                if ($cart_item['_pp_order_payment_terms'] && $cart_item['_pp_deposit_system'] === 'payment_plan') {
                    $name .= '<div class="mep-product-payment-plans"><button class="mepp-payment-plan-cart-btn mep-pp-show-detail">' . __("Show detail", "advanced-partial-payment-or-deposit-for-woocommerce") . '</button>';
                    $name .= '<div class="mep-single-plan plan-details"><table><thead><tr><th>' . __("Payment Date", "advanced-partial-payment-or-deposit-for-woocommerce") . '</th><th>' . __("Amount", "advanced-partial-payment-or-deposit-for-woocommerce") . '</th></tr></thead><tbody>';
                    foreach ($cart_item['_pp_order_payment_terms'] as $plan) {
                        if ($plan['type'] !== 'deposit') {
                            $name .= '<tr><td>' . $plan['date'] . '</td> <td>' . wc_price($plan['total']) . '</td></tr>';
                        }
                    }
                    $name .= '</tbody></table></div></div>';
                }
            }
            return $name;
        }
        return $name;
    }
}

new MEP_PP_Cart();