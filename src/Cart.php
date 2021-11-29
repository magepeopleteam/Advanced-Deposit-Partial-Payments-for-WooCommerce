<?php
if (!defined('ABSPATH')) {die;}
class MEP_PP_Cart
{
    public function __construct()
    {
        add_filter('woocommerce_cart_item_name', [$this, 'display_cart_item_pp_deposit_data'], 10, 3);
        add_action('woocommerce_cart_totals_after_order_total', [$this, 'to_pay_html']);

        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_pp_deposit_data'], 100, 2);
       
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
        if (function_exists('mep_product_exists')) {
            if (get_post_meta($product_id, 'link_mep_event', true)) {
                $linked_event_id = get_post_meta($product_id, 'link_mep_event', true);
            } else {
                $linked_event_id = null;
            }

            if($linked_event_id) {
                if(!wcppe_enable_for_event()) return $cart_item_data;
                $product_id = mep_product_exists($linked_event_id) ? $linked_event_id : $product_id;
            }
        }

        if (meppp_is_product_type_pp_deposit($product_id) == false || $_POST['deposit-mode'] == 'check_full') {
            return $cart_item_data;
        }

        $product_type           = get_post_type($product_id);
        $product_price_total    = 0;

        if ($product_type == 'mep_events') {
            $product_price_total = $cart_item_data['line_total'];
        } else {
            $product = wc_get_product($product_id);
            if ($product->is_type('variable')) {
                // Product has variations
                $variation_id = sanitize_text_field($_POST['variation_id']);
                $product = new WC_Product_Variation($variation_id);
                $product_price_total = $product->get_price() * (int) sanitize_text_field($_POST['quantity']);
            } else {
                $product_price_total = $product->get_price() * (int) sanitize_text_field($_POST['quantity']);
            }

        }

        $enable_checkout_zero_price = get_option('meppp_checkout_zero_price') ? get_option('meppp_checkout_zero_price') : 'no';

        if($enable_checkout_zero_price === 'yes') {
            $cart_item_data['_pp_deposit'] = 0;
            $cart_item_data['_pp_deposit_value'] = 0;
            $cart_item_data['_pp_due_payment'] = $product_price_total;
            $cart_item_data['_pp_deposit_type'] = sanitize_text_field($_POST['deposit-mode']);
            $cart_item_data['_pp_deposit_system'] = 'zero_price_checkout';
            $cart_item_data['_pp_deposit_payment_plan_name'] = '';
            
            return $cart_item_data;
        }

        $deposit_value      = get_post_meta($product_id, '_mep_pp_deposits_value', true) ? get_post_meta($product_id, '_mep_pp_deposits_value', true) : 0;
        $deposit_type       = get_post_meta($product_id, '_mep_pp_deposits_type', true);
        if ($deposit_type == 'percent') {
            $deposit_amount = ($deposit_value / 100) * $product_price_total;
        } elseif ($deposit_type == 'manual' || $deposit_type == 'ticket_type') {
            $deposit_amount = isset($_POST['user-deposit-amount']) ? sanitize_text_field($_POST['user-deposit-amount']) / sanitize_text_field($_POST['quantity']) : 0;
        } elseif ($deposit_type == 'minimum_amount') {
            $deposit_amount = isset($_POST['user-deposit-amount']) ? sanitize_text_field($_POST['user-deposit-amount']) / sanitize_text_field($_POST['quantity']) : 0;
        } elseif ($deposit_type == 'payment_plan') {
            $get_payment_terms = $this->mep_make_payment_terms($product_id, $product_price_total, sanitize_text_field($_POST['mep_payment_plan']));
            $cart_item_data['_pp_order_payment_terms'] = $get_payment_terms['payment_terms'];
            $deposit_amount = $get_payment_terms['deposit_amount'];
        } else {
            $deposit_amount = $deposit_value / sanitize_text_field($_POST['quantity']);
        }
        $cart_item_data['_pp_deposit']                      = $deposit_amount;
        $cart_item_data['_pp_deposit_value']                = $deposit_value;
        $cart_item_data['_pp_due_payment']                  = $product_price_total - $deposit_amount;
        $cart_item_data['_pp_deposit_type']                 = sanitize_text_field($_POST['deposit-mode']);
        $cart_item_data['_pp_deposit_system']               = $deposit_type;
        $cart_item_data['_pp_deposit_payment_plan_name']    = isset($_POST['mep_payment_plan']) ? mep_pp_payment_plan_name(sanitize_text_field($_POST['mep_payment_plan'])) : '';
        //echo '<pre>';print_r($cart_item_data);die;
        return $cart_item_data;
    }

    public function mep_make_payment_terms($product_id, $total_amount, $pament_plan_id)
    {
        $payment_terms          = array();
        $deposit_amount         = 0;
        $due_amount             = 0;
        $product                = wc_get_product($product_id);
        $this_plan              = get_term_meta($pament_plan_id);
        $this_plan_schedule     = maybe_unserialize((maybe_unserialize($this_plan['mepp_plan_schedule'][0])));
        if ($this_plan_schedule) {
            $date = date('Y-m-d');
            $down_payment = $this_plan['mepp_plan_schedule_initial_pay_parcent'][0];
            $payment_terms[] = array(
                'id' => '',
                'title' => 'Deposit',
                'type' => 'deposit',
                'date' => $date,
                'total' => mep_percentage_value($down_payment, $total_amount),
                'due' => $total_amount - mep_percentage_value($down_payment, $total_amount),
            );

            $deposit_amount = mep_percentage_value($down_payment, $total_amount);
            $due_amount = $total_amount - mep_percentage_value($down_payment, $total_amount);

            foreach ($this_plan_schedule as $schedule) {
                $date = mep_payment_plan_date($schedule["plan_schedule_date_after"], $schedule["plan_schedule_parcent_date_type"], $date);
                $amount = mep_percentage_value($schedule["plan_schedule_parcent"], $total_amount);
                $due_amount = $due_amount - $amount;
                $payment_terms[] = array(
                    'id' => '',
                    'title' => 'Future Payment',
                    'type' => 'future_payment',
                    'date' => $date,
                    'total' => $amount,
                    'due' => $due_amount,
                );
            }
        }

        return array(
            'deposit_amount' => $deposit_amount,
            'payment_terms' => $payment_terms,
        );
    }

    public function display_cart_item_pp_deposit_data($name, $cart_item, $cart_item_key)
    {
        if (isset($cart_item['_pp_deposit']) && is_cart() && isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') {
            $cart_item['_pp_deposit'] = $cart_item['_pp_deposit'] * $cart_item['quantity'];
            $cart_item['_pp_due_payment'] = $cart_item['_pp_due_payment'] * $cart_item['quantity'];
            $name .= sprintf(
                '<p>' . mepp_get_option('mepp_text_translation_string_deposit', __('Deposit:', 'advanced-partial-payment-or-deposit-for-woocommerce')) . ' %s <br> ' . mepp_get_option('mepp_text_translation_string_due_payment', __('Due Payment:', 'advanced-partial-payment-or-deposit-for-woocommerce')) . '  %s <br> ' . mepp_get_option('mepp_text_translation_string_deposit_type', __('Deposit Type:', 'advanced-partial-payment-or-deposit-for-woocommerce')) . '  %s</p>',
                wc_price($cart_item['_pp_deposit']),
                wc_price($cart_item['_pp_due_payment']),
                mep_pp_deposti_type_display_name($cart_item['_pp_deposit_system'], $cart_item, true)
            );
            return $name;
        }
        return $name;
    }
}
new MEP_PP_Cart();