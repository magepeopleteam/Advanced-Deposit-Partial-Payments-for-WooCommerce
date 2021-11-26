<?php
if (!defined('ABSPATH')) {
    die;
}

add_action('admin_enqueue_scripts', 'mep_pp_add_admin_scripts', 10, 1);
if (!function_exists('mep_pp_add_admin_scripts')) {
    function mep_pp_add_admin_scripts($hook)
    {
        wp_enqueue_style('mep-pp-admin-style', plugin_dir_url(__DIR__) . 'asset/css/admin.css', array());
        wp_enqueue_script('mep--pp-admin-script', plugin_dir_url(__DIR__) . '/asset/js/admin.js', array(), time(), true);
    }
}
add_action('wp_enqueue_scripts', 'mep_pp_add_scripts', 10, 1);
if (!function_exists('mep_pp_add_scripts')) {
    function mep_pp_add_scripts($hook)
    {
        wp_enqueue_style('mep-pp-admin-style', plugin_dir_url(__DIR__) . 'asset/css/style.css', array());
        wp_enqueue_script('mep--pp-script', plugin_dir_url(__DIR__) . '/asset/js/public.js', array('jquery'), time(), true);
        wp_localize_script('mep--pp-script', 'php_vars', array('ajaxurl' => admin_url('admin-ajax.php')));
    }
}

add_action('mep_pricing_table_head_after_price_col', 'mep_pp_price_col_head');
if (!function_exists('mep_pp_price_col_head')) {
    function mep_pp_price_col_head()
    {
        ?>
        <th width="20%"><?php _e('Partial', 'advanced-partial-payment-or-deposit-for-woocommerce');?></th>
        <?php
}
}
add_action('mep_pricing_table_empty_after_price_col', 'mep_pp_price_empty_col');
if (!function_exists('mep_pp_price_empty_col')) {
    function mep_pp_price_empty_col()
    {
        ?>
        <td><input type="number" size="4" pattern="[0-9]*" class="mp_formControl" step="0.001" name="option_price_pp[]"
                   placeholder="Ex: 10" value=""/></td>
        <?php
}
}

add_action('mep_pricing_table_data_after_price_col', 'mep_pp_price_col_data', 10, 2);
if (!function_exists('mep_pp_price_col_data')) {
    function mep_pp_price_col_data($data, $event_id)
    {
        ?>
        <td><input type="number" size="4" pattern="[0-9]*" class="mp_formControl" step="0.001" name="option_price_pp[]"
                   placeholder="Ex: 10" value="<?php echo esc_attr($data['option_price_pp']); ?>"/></td>
        <?php
}
}

add_filter('mep_ticket_type_arr_save', 'mep_pp_save_data', 99);
if (!function_exists('mep_pp_save_data')) {
    function mep_pp_save_data($data)
    {
        $spp = $_POST['option_price_pp'] ? mep_pp_sanitize_array($_POST['option_price_pp']) : [];
        if (sizeof($spp) > 0) {
            $count = count($spp);
            for ($i = 0; $i < $count; $i++) {
                $new[$i]['option_price_pp'] = !empty($spp[$i]) ? stripslashes(strip_tags($spp[$i])) : '';
            }
            $final_data = mep_merge_saved_array($data, $new);
        } else {
            $final_data = $data;
        }
        return $final_data;
    }
}

add_action('mep_ticket_type_list_row_end', 'mep_pp_ticket_type_list_data', 10, 2);
if (!function_exists('mep_pp_ticket_type_list_data')) {
    function mep_pp_ticket_type_list_data($field, $event_id)
    {
        $saldo_price = array_key_exists('option_price_pp', $field) && !empty($field['option_price_pp']) ? $field['option_price_pp'] : 0;
        $deposit_type = get_post_meta($event_id, '_mep_pp_deposits_type', true) ? get_post_meta($event_id, '_mep_pp_deposits_type', true) : 'percent';
        $deposit_status = get_post_meta($event_id, '_mep_enable_pp_deposit', true) ? get_post_meta($event_id, '_mep_enable_pp_deposit', true) : 'no';
        if ($deposit_status == 'yes' && array_key_exists('option_price_pp', $field) && $deposit_type == 'ticket_type') {
            ?>
            <td>
                <span class="tkt-pric">
                    <?php echo mepp_get_option('mepp_text_translation_string_deposit', __('Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce')); ?>:
                </span> <strong><?php echo wc_price(mep_get_price_including_tax($event_id, $saldo_price)); ?></strong>
                <input type="hidden" name="option_price_pp[]" value="<?php echo esc_attr($saldo_price); ?>">
            </td>
            <?php
add_filter('mep_hidden_row_colspan_no', 'mep_pp_modify_hudden_col_no');
        }
    }
}

if (!function_exists('mep_pp_modify_hudden_col_no')) {
    function mep_pp_modify_hudden_col_no($current)
    {
        $current = 4;
        return $current;
    }
}

if (!function_exists('meppp_pp_deposit_to_pay')) {
    /**
     *  Amount to pay for now html
     */
    function meppp_pp_deposit_to_pay()
    {

        // Loop over $cart items
        $total_pp_deposit = 0; // no value
        $cart_has_payment_plan = false; // Check cart has payment plan deposit system. init false
        $order_payment_plan = array();
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

            if (function_exists('mep_product_exists')) {
                $linked_event_id = get_post_meta($cart_item['product_id'], 'link_mep_event', true) ? get_post_meta($cart_item['product_id'], 'link_mep_event', true) : $cart_item['product_id'];
                $product_id = mep_product_exists($linked_event_id) ? $linked_event_id : $cart_item['product_id'];
            } else {
                $product_id = $cart_item['product_id'];
            }

            $total_pp_deposit += (meppp_is_product_type_pp_deposit($product_id) && isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') ? $cart_item['_pp_due_payment'] : null;

            if ($cart_item['_pp_deposit_system'] == 'payment_plan') {
                // $cart_has_payment_plan = true;
                $order_payment_plan = isset($cart_item['_pp_order_payment_terms']) ? $cart_item['_pp_order_payment_terms'] : array();
            }
        }

        $enable_checkout_zero_price = get_option('meppp_checkout_zero_price') ? get_option('meppp_checkout_zero_price') : 'no';
        $value = $enable_checkout_zero_price == 'yes' ? WC()->cart->get_total('f') - $total_pp_deposit : WC()->cart->get_total('f') - $total_pp_deposit;

        echo apply_filters('mep_pp_deposit_top_pay_checkout_page_html', wc_price($value), $value, $order_payment_plan, $product_id); // WPCS: XSS ok.
    }
}

if (!function_exists('meppp_due_to_pay')) {
    /**
     *  Due Amount html
     */
    function meppp_due_to_pay()
    {

        // Loop over $cart items
        $due_value = 0; // no value
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

            if (function_exists('mep_product_exists')) {
                $linked_event_id = get_post_meta($cart_item['product_id'], 'link_mep_event', true) ? get_post_meta($cart_item['product_id'], 'link_mep_event', true) : $cart_item['product_id'];
                $product_id = mep_product_exists($linked_event_id) ? $linked_event_id : $cart_item['product_id'];
            } else {
                $product_id = $cart_item['product_id'];
            }

            $due_value += (meppp_is_product_type_pp_deposit($product_id) && isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') ? $cart_item['_pp_due_payment'] : null;
        }
        if (WC()->session->get('dfwc_shipping_fee')) {
            $due_value += absint(WC()->session->get('dfwc_shipping_fee'));
        }

        echo '<input type="hidden" name="manually_due_amount" value="' . esc_attr($due_value) . '" />';
        echo apply_filters('woocommerce_pp_deposit_top_pay_html', wc_price($due_value)); // WPCS: XSS ok.
    }
}

if (!function_exists('meppp_display_to_pay_html')) {
    /**
     * Cart & checkout page hook for
     * display deposit table
     */
    function meppp_display_to_pay_html()
    {
        ?>
        <tr class="order-topay">
            <th><?php echo esc_html(meppp_get_option('txt_to_pay', 'To Pay')); ?></th>
            <td data-title="<?php echo esc_html(meppp_get_option('txt_to_pay', 'To Pay')); ?>"><?php meppp_pp_deposit_to_pay();?></td>
        </tr>
        <tr class="order-duepay">
            <th><?php echo mepp_get_option('mepp_text_translation_string_due_payment', __('Due Payment:', 'advanced-partial-payment-or-deposit-for-woocommerce')) ?></th>
            <td data-title="<?php echo mepp_get_option('mepp_text_translation_string_due_payment', __('Due Payment:', 'advanced-partial-payment-or-deposit-for-woocommerce')) ?>">
                <?php meppp_due_to_pay();?>
                <?php echo (WC()->session->get('dfwc_shipping_fee')) ? '<small>' . esc_html(apply_filters('dfwc_after_pp_due_payment_label', null)) . '</small>' : null; ?>
            </td>
        </tr>
        <?php
}
}

if (!function_exists('meppp_cart_have_pp_deposit_item')) {

    function meppp_cart_have_pp_deposit_item()
    {
        $cart_item_pp_deposit = [];
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

            if (function_exists('mep_product_exists')) {
                $linked_event_id = get_post_meta($cart_item['product_id'], 'link_mep_event', true) ? get_post_meta($cart_item['product_id'], 'link_mep_event', true) : $cart_item['product_id'];
                $product_id = mep_product_exists($linked_event_id) ? $linked_event_id : $cart_item['product_id'];
            } else {
                $product_id = $cart_item['product_id'];
            }

            $cart_item_pp_deposit[] = (meppp_is_product_type_pp_deposit($product_id) && isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') ? $cart_item['_pp_deposit_type'] : null;
        }

        if (!array_filter($cart_item_pp_deposit)) {
            return false;
        }
        return true;
    }
}

if (!function_exists('meppp_is_product_type_pp_deposit')) {
    /**
     * check for if product type is deposit/partial
     * @return boolen
     */
    function meppp_is_product_type_pp_deposit($product_id)
    {
        $ed = get_post_meta($product_id, '_mep_enable_pp_deposit', true);

        if ('yes' == $ed || apply_filters('global_product_type_pp_deposit', false)) {
            return true;
        }
        return false;
    }
}

/**
 * Get deposit Orders
 *
 * @param array $args
 * @return void
 */

if (!function_exists('mep_pp_get_orders')) {
    function mep_pp_get_orders($args = array())
    {
        $defaults = array(
            'numberposts' => '',
            'offset' => '',
            'meta_query' => array(
                array(
                    'key' => 'paying_pp_due_payment',
                    'value' => 1,
                    'compare' => '=',
                ),
            ),
            'meta_key' => 'deposit_value',
            'post_type' => wc_get_order_types('view-orders'),
            'post_status' => array_keys(wc_get_order_statuses()),
            'orderby' => 'date',
            'order' => 'DESC',

        );

        $args = wp_parse_args($args, $defaults);

        $items = array();
        foreach (get_posts($args) as $key => $order) {
            # code...
            $deposit_value = get_post_meta($order->ID, 'deposit_value', true);
            $due_payment = get_post_meta($order->ID, 'due_payment', true);
            $total_value = get_post_meta($order->ID, 'total_value', true);
            // fw_print($order);
            $items[$key]['name'] = $order->ID;
            $items[$key]['date'] = $order->post_date;
            $items[$key]['status'] = $order->post_status;
            $items[$key]['deposit'] = apply_filters('woocommerce_pp_deposit_top_pay_html', wc_price($deposit_value));
            $items[$key]['due'] = ($due_payment > 0) ? apply_filters('woocommerce_pp_deposit_top_pay_html', wc_price($due_payment)) : '-';
            $items[$key]['total'] = apply_filters('woocommerce_pp_deposit_top_pay_html', wc_price($total_value));
        }

        return $items;
    }
}
/**
 * count Deposit orders
 * @return int
 */
if (!function_exists('mep_pp_count')) {
    function mep_pp_count()
    {
        $defaults = array(
            'numberposts' => -1,
            'meta_key' => 'paying_pp_due_payment',
            'meta_value' => '1',
            'post_type' => wc_get_order_types('view-orders'),
            'post_status' => array_keys(wc_get_order_statuses()),

        );
        $items = get_posts($defaults);
        return count($items);
    }
}

if (!function_exists('meppp_get_option')) {
    function meppp_get_option($option, $default = '', $section = 'deposits_settings')
    {
        $options = get_option($section);

        if (isset($options[$option])) {
            return $options[$option];
        }

        return $default;
    }
}

add_action('woocommerce_before_add_to_cart_button', 'mep_pp_show_payment_option');
add_action('mep_before_add_cart_btn', 'mep_pp_show_payment_option');
if (!function_exists('mep_pp_show_payment_option')) {
    function mep_pp_show_payment_option($product_id)
    {
        $product_id = $product_id ? $product_id : get_the_id();

        if (function_exists('mep_product_exists')) {
            if (get_post_meta($product_id, 'link_mep_event', true)) {
                $linked_event_id = get_post_meta($product_id, 'link_mep_event', true);
            } else {
                $linked_event_id = null;
            }
            if ($linked_event_id) {
                if (!wcppe_enable_for_event()) {
                    return null;
                }

                $product_id = mep_product_exists($linked_event_id) ? $linked_event_id : $product_id;
            }
        }

        mep_pp_show_payment_option_html($product_id);

    }
}

if (!function_exists('mep_pp_show_payment_option_html')) {
    function mep_pp_show_payment_option_html($event_id)
    {
        // $event_id = $event_id ? $event_id : get_the_id();
        if (meppp_is_product_type_pp_deposit($event_id)) {
            $deposit_type = get_post_meta($event_id, '_mep_pp_deposits_type', true) ? get_post_meta($event_id, '_mep_pp_deposits_type', true) : 'percent';
            $_pp_deposit_value = get_post_meta($event_id, '_mep_pp_deposits_value', true) ? get_post_meta($event_id, '_mep_pp_deposits_value', true) : 0;
            $_pp_minimum_value = get_post_meta($event_id, '_mep_pp_minimum_value', true) ? get_post_meta($event_id, '_mep_pp_minimum_value', true) : 0;
            // ticket_type
            // get payment plan id of this event
            $_pp_payment_plan_ids = get_post_meta($event_id, '_mep_pp_payment_plan', true);
            $_pp_payment_plan_ids = $_pp_payment_plan_ids ? maybe_unserialize($_pp_payment_plan_ids) : array();
            $deposit_type = get_post_meta($event_id, '_mep_pp_deposits_type', true);
            ?>
            <div class="mep-pp-payment-btn-wraper">
                <input type="hidden" name='currency_symbol' value="<?php echo get_woocommerce_currency_symbol(); ?>">
                <input type="hidden" name='currency_position' value="<?php echo get_option('woocommerce_currency_pos'); ?>">
                <input type="hidden" name='currency_decimal' value="<?php echo wc_get_price_decimal_separator(); ?>">
                <input type="hidden" name='currency_thousands_separator' value="<?php echo wc_get_price_thousand_separator(); ?>">
                <input type="hidden" name='currency_number_of_decimal' value="<?php echo wc_get_price_decimals(); ?>">
                <input type="hidden" name="payment_plan" value="<?php echo esc_attr($deposit_type); ?>" data-percent="<?php echo esc_attr($_pp_deposit_value); ?>">
                <?php if (apply_filters('mep_pp_frontend_cart_radio_input', true)) {?>
                    <ul class="mep-pp-payment-terms">
                        <li>
                            <label for="mep_pp_partial_payment">
                                <input type="radio" id='mep_pp_partial_payment' name="deposit-mode" value="check_pp_deposit" <?php if (meppp_is_product_type_pp_deposit($event_id)) {
                echo 'Checked';
            }?> />
                                <?php echo mepp_get_option('mepp_text_translation_string_pay_deposit', __('Pay Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce')); ?>
                                <?php if ($deposit_type == 'manual') {?>
                                    <input type="number" class="mep-pp-user-amountinput" data-deposit-type="<?php echo $deposit_type; ?>" name="user-deposit-amount" value="<?php echo esc_attr($_pp_deposit_value); ?>" min="<?php echo esc_attr($_pp_deposit_value); ?>" max="">
                                    <?php
} elseif ($deposit_type == 'ticket_type') {
                ?>
                                    <span id='mep_pp_ticket_type_partial_total'></span>
                                    <input type="hidden" class="mep-pp-user-amountinput" data-deposit-type="<?php echo $deposit_type; ?>" name="user-deposit-amount" value="">
                                    <?php
} elseif ($deposit_type == 'minimum_amount') {?>
                                    <input type="number" class="mep-pp-user-amountinput" data-deposit-type="<?php echo $deposit_type; ?>" name="user-deposit-amount" value="<?php echo esc_attr($_pp_minimum_value); ?>" min="<?php echo esc_attr($_pp_minimum_value); ?>" max="">
                                    <?php
} elseif ($deposit_type == 'percent') {
                echo esc_attr($_pp_deposit_value) . '%';
            } elseif ($deposit_type == 'fixed') {
                echo wc_price(esc_attr($_pp_deposit_value));
                esc_attr_e(' Only', 'advanced-partial-payment-or-deposit-for-woocommerce');
            } else {
                echo '';
            }
                ?>
                            </label>
                        </li>
                        <li>
                            <label for='mep_pp_full_payment'>
                                <input type="radio" id='mep_pp_full_payment' name="deposit-mode" value="check_full" />
                                <?php echo mepp_get_option('mepp_text_translation_string_full_payment', __('Full Payment', 'advanced-partial-payment-or-deposit-for-woocommerce')); ?>
                            </label>
                        </li>
                    </ul>
                    <?php ($deposit_type == 'payment_plan' || $deposit_type == 'percent') ? do_action('mep_payment_plan_list', $_pp_payment_plan_ids, $event_id, $deposit_type) : null;?>
                <?php } else {
                echo '<input type="hidden" name="deposit-mode" value="check_pp_deposit">';
            }?>
            </div>
            <?php
}
    }
}

// add_filter('mep_event_cart_item_data', 'mep_pp_event_cart_item_data', 90, 6);
if (!function_exists('mep_pp_event_cart_item_data')) {
    function mep_pp_event_cart_item_data($cart_item_data, $product_id, $total_price, $user, $ticket_type_arr, $event_extra)
    {
        if (meppp_is_product_type_pp_deposit($product_id)) {
            $deposit_type = get_post_meta($product_id, '_mep_pp_deposits_type', true) ? get_post_meta($product_id, '_mep_pp_deposits_type', true) : 'percent';
            $_pp_deposit_value = get_post_meta($product_id, '_mep_pp_deposits_value', true) ? get_post_meta($product_id, '_mep_pp_deposits_value', true) : 0;

            if ($deposit_type == 'percent') {
                $deposit_value = ($_pp_deposit_value / 100) * $total_price;
            } elseif ($deposit_type == 'manual' || $deposit_type == 'ticket_type') {
                $deposit_value = isset($_POST['user-deposit-amount']) && !empty($_POST['user-deposit-amount']) ? sanitize_text_field($_POST['user-deposit-amount']) : 0;
            } else {
                $deposit_value = $_pp_deposit_value;
            }

            $cart_item_data['_pp_deposit'] = $deposit_value;
            $cart_item_data['_pp_due_payment'] = $total_price - $deposit_value;
            $cart_item_data['_pp_deposit_type'] = sanitize_text_field($_POST['deposit-mode']);
        }
        return $cart_item_data;
    }
}

add_filter('woocommerce_add_to_cart_validation', 'mep_pp_validate_frontend_input', 50, 3);
if (!function_exists('mep_pp_validate_frontend_input')) {
    function mep_pp_validate_frontend_input($passed, $product_id, $quantity)
    {

        $linked_event_id = get_post_meta($product_id, 'link_mep_event', true) ? get_post_meta($product_id, 'link_mep_event', true) : $product_id;
        if (function_exists('mep_product_exists')) {
            $product_id = mep_product_exists($linked_event_id) ? $linked_event_id : $product_id;
        }

        if (!is_plugin_active('mage-partial-payment-pro/mage_partial_pro.php')) {
            $passed = apply_filters('woocommerce_add_to_cart_validation_additional', $passed, $product_id);
        }

        if (meppp_is_product_type_pp_deposit($product_id) == false) {
            return $passed;
        }

        return $passed;
    }
}

add_filter('woocommerce_add_to_cart_validation_additional', 'mep_pp_woocommerce_add_to_cart_validation_additional_callback', 10, 2);
if (!function_exists('mep_pp_woocommerce_add_to_cart_validation_additional_callback')) {
    function mep_pp_woocommerce_add_to_cart_validation_additional_callback($passed, $product_id)
    {

        if (!WC()->cart->is_empty() && meppp_cart_have_pp_deposit_item() && meppp_is_product_type_pp_deposit($product_id) == false && apply_filters('deposits_mode', true)) {
            $passed = false;
            wc_add_notice(__('We detected that your cart has Deposit products. Please remove them before being able to add this product.', 'advanced-partial-payment-or-deposit-for-woocommerce'), 'error');
            return $passed;
        }

        if (!WC()->cart->is_empty() && meppp_cart_have_pp_deposit_item() == false && meppp_is_product_type_pp_deposit($product_id) && apply_filters('deposits_mode', true)) {
            $passed = false;
            wc_add_notice(__('We detected that your cart has Regular products. Please remove them before being able to add this product.', 'advanced-partial-payment-or-deposit-for-woocommerce'), 'error');
            return $passed;
        }

        return $passed;
    }
}

add_filter('mep_event_attendee_dynamic_data', 'mep_pp_event_pp_deposit_data_save', 90, 6);
if (!function_exists('mep_pp_event_pp_deposit_data_save')) {
    function mep_pp_event_pp_deposit_data_save($the_array, $pid, $type, $order_id, $event_id, $_user_info)
    {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item_id => $item_values) {
            $item_id = $item_id;
        }
        $time_slot = wc_get_order_item_meta($item_id, '_DueAmount', true) ? wc_get_order_item_meta($item_id, '_DueAmount', true) : 0;

        if ($time_slot > 0) {
            $the_array[] = array(
                'name' => 'ea_partial_status',
                'value' => 'partial_payment',
            );
        }
        return $the_array;
    }
}

add_filter('mep_sold_meta_query_or_attribute', 'mep_pp_partial_meta_query');
if (!function_exists('mep_pp_partial_meta_query')) {
    function mep_pp_partial_meta_query($current_query)
    {
        $partial_meta_condition = array(
            'key' => 'ea_partial_status',
            'value' => 'partial_payment',
            'compare' => '=',
        );
        return array_merge($current_query, $partial_meta_condition);
    }
}

// Add per payment to post type 'mep_pp_history'
if (!function_exists('mep_pp_history_add')) {
    function mep_pp_history_add($order_id, $data, $parent_id)
    {

        $order = wc_get_order($order_id);
        $order_status = $order->get_status();
        if ($order_status == 'pending') {
            $postdata = array(
                'post_type' => 'mep_pp_history',
                'post_status' => 'publish',
            );
            $post = wp_insert_post($postdata);
            if ($post) {
                update_post_meta($post, 'order_id', $order_id);
                update_post_meta($post, 'parent_order_id', $parent_id);
                update_post_meta($post, 'deposite_amount', $data['deposite_amount']);
                update_post_meta($post, 'due_amount', $data['due_amount']);
                update_post_meta($post, 'payment_date', $data['payment_date']);
                update_post_meta($post, 'payment_method', $data['payment_method']);
            }
        }
    }
}

if (!function_exists('mep_pp_get_order_due_amount')) {
    function mep_pp_get_order_due_amount($order_id)
    {
        $args = array(
            'post_type' => 'mep_pp_history',
            'posts_per_page' => 1,
            'orderby' => 'date',
            // 'order'             => 'asc',
            'meta_query' => array(
                array(
                    'key' => 'order_id',
                    'value' => $order_id,
                    'compare' => '=',
                ),
            ),
        );
        $loop = new WP_Query($args);
        $payment_id = 0;
        foreach ($loop->posts as $value) {
            # code...
            $payment_id = $value->ID;
        }

        $due = $payment_id > 0 && get_post_meta($payment_id, 'due_amount', true) ? get_post_meta($payment_id, 'due_amount', true) : 0;
        return $due;
    }
}

// Get history by order_id
if (!function_exists('mep_pp_history_get')) {
    function mep_pp_history_get($order_id, $title = true)
    {
        $due_payment = get_post_meta($order_id, 'due_payment', true);

        $pp_deposit_system = get_post_meta($order_id, '_pp_deposit_system', true);
        $permition_for_next_payment = get_post_meta($order_id, 'zero_price_checkout_allow', true); // Only for Zero price Checkout Order

        $args = array(
            'post_type' => 'mep_pp_history',
            'posts_per_page' => -1,
            'order' => 'asc',
            'orderby' => 'ID',
            'meta_query' => array(
                array(
                    'key' => 'parent_order_id',
                    'value' => $order_id,
                    'compare' => '=',
                ),
            ),
        );

        $pp_history = new WP_Query($args);
        $count = $pp_history->post_count;

        $payment_term_pay_now_appear = true; // Only For Payment Terms

        if ($count):
        ?>

            <?php echo ($title ? '<h2 class="woocommerce-column__title">' . __("Payment history", "advanced-partial-payment-or-deposit-for-woocommerce") . '</h2>' : null); ?>
            <table cellspacing="0" cellpadding="6" border="1" class="mep-pp-history-table woocommerce-table"
                   style="width:100%;text-align:left">
                <thead>
                <th style="text-align:left"><?php esc_attr_e('Sl.', 'advanced-partial-payment-or-deposit-for-woocommerce')?></th>
                <th style="text-align:left"><?php esc_attr_e('Payment Date', 'advanced-partial-payment-or-deposit-for-woocommerce')?></th>
                <th style="text-align:left"><?php esc_attr_e('Amount', 'advanced-partial-payment-or-deposit-for-woocommerce')?></th>
                <th style="text-align:left"><?php esc_attr_e('Due', 'advanced-partial-payment-or-deposit-for-woocommerce')?></th>
                <th style="text-align:left"><?php esc_attr_e('Payment Method', 'advanced-partial-payment-or-deposit-for-woocommerce')?></th>
                <th style="text-align:left"><?php esc_attr_e('Status', 'advanced-partial-payment-or-deposit-for-woocommerce')?></th>
                </thead>
                <tbody>

                <?php
$x = 1;
        while ($pp_history->have_posts()):
            $pp_history->the_post();
            $id = get_the_ID();
//                        $order_id           = esc_attr(get_post_meta($id, 'order_id', true));
            $this_order = wc_get_order($order_id);
            $amount = esc_attr(get_post_meta($id, 'deposite_amount', true));
            $due = esc_attr(get_post_meta($id, 'due_amount', true));
            $date = esc_attr(get_post_meta($id, 'payment_date', true));
            $payment_method = esc_attr(get_post_meta($id, 'payment_method', true));

            $pay_button = '';
            $status = '';
            if ($pp_deposit_system == 'zero_price_checkout' && $permition_for_next_payment == 'yes' && $due_payment != 0) { // Only For zero price checkout and permmited for payment and Amount Due
                $pay_button = sprintf('<a href="%s" class="mep_due_pay_btn">%s</a>', $this_order->get_checkout_payment_url(), __('Pay Now', 'advanced-partial-payment-or-deposit-for-woocommerce'));
            } elseif ($pp_deposit_system == 'zero_price_checkout' && $permition_for_next_payment == 'yes' && $due_payment == 0) { // Only For zero price checkout and permmited for payment and Amount all paid
            $status = 'Paid';
        } elseif ($pp_deposit_system == 'zero_price_checkout' && $permition_for_next_payment == 'no' && $payment_method == '') { // Only For zero price checkout and Not permmited for payment
            // $pay_button = '';
            //
        } elseif ($pp_deposit_system != 'zero_price_checkout' && $due_payment == 0) { // Not zero price checkout
            $status = $x == $count ? __('Paid', 'advanced-partial-payment-or-deposit-for-woocommerce') : __('Partialy Paid', 'advanced-partial-payment-or-deposit-for-woocommerce');

        } elseif ($pp_deposit_system != 'zero_price_checkout' && $due_payment > 0) { // Not zero price checkout
            $status = $payment_method ? __('Partialy Paid', 'advanced-partial-payment-or-deposit-for-woocommerce') : '';
            if ($pp_deposit_system == 'payment_plan') {

                if (!$payment_method && $payment_term_pay_now_appear) {
                    $pay_button = sprintf('<a href="%s" class="mep_due_pay_btn">%s</a>', $this_order->get_checkout_payment_url(), __('Pay Now', 'advanced-partial-payment-or-deposit-for-woocommerce'));
                    $payment_term_pay_now_appear = false;
                }

            } else {
                $pay_button = ($due > 0 && $x == $count) ? sprintf('<a href="%s" class="mep_due_pay_btn">%s</a>', $this_order->get_checkout_payment_url(), __('Pay Now', 'advanced-partial-payment-or-deposit-for-woocommerce')) : '';
            }
        }

        echo '<tr>';
        echo '<td>' . (esc_attr($x)) . '</td>';
        echo '<td>' . date(get_option('date_format'), strtotime($date)) . '</td>';
        echo '<td class="mep_style_ta_r">' . wc_price($amount) . '</td>';
        echo '<td class="mep_style_ta_r ' . (($due > 0 && $x == $count) ? "mep_current_last_due" : null) . '">' . wc_price(esc_html($due)) . $pay_button . '</td>';
        echo '<td class="mep_style_tt_upper">' . esc_html($payment_method) . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
        $x++;
        endwhile;
        ?>
                </tbody>
            </table>

            <?php
wp_reset_postdata();
        endif;
    }
}

// Overwrite woocommerce template [form-pay.php]
add_filter('woocommerce_locate_template', 'mep_pp_template', 1, 3);
if (!function_exists('mep_pp_template')) {
    function mep_pp_template($template, $template_name, $template_path)
    {
        global $woocommerce;
        $_template = $template;
        if (!$template_path) {
            $template_path = $woocommerce->template_url;
        }

        $plugin_path = untrailingslashit(plugin_dir_path(__FILE__)) . '/templates/woocommerce/';

        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name,
            )
        );

        if (!$template && file_exists($plugin_path . $template_name)) {
            $template = $plugin_path . $template_name;
        }

        if (!$template) {
            $template = $_template;
        }

        return $template;
    }
}

// Get Deposit Type Display name
if (!function_exists('mep_pp_deposti_type_display_name')) {
    function mep_pp_deposti_type_display_name($deposit_type, $cart_item, $with_value = false)
    {
        $name = '';
        if ($deposit_type) {
            switch ($deposit_type) {
                case 'percent':
                    $name = 'Percent';
                    $name = $with_value ? $cart_item['_pp_deposit_value'] . ' ' . $name : $name;
                    break;
                case 'fixed':
                    $name = 'Fixed';
                    $name = $with_value ? wc_price($cart_item['_pp_deposit_value']) . ' ' . $name : $name;
                    break;
                case 'manual':
                    $name = 'Custom Amount';
                    $name = $with_value ? wc_price($cart_item['_pp_deposit']) . ' ' . $name : $name;
                    break;
                case 'payment_plan':
                    $name = 'Payment Plan';
                    $name = $with_value ? $cart_item['_pp_deposit_payment_plan_name'] . ' ' . $name : $name;
                    break;
                case 'zero_price_checkout':
                    $name = 'Checkout with Zero Price';
                    $name = $with_value ? $cart_item['_pp_deposit_payment_plan_name'] . ' ' . $name : $name;
                    break;
                default:
            }
        }

        return $name;
    }
}

if (!function_exists('mep_get_enable_payment_gateway')) {
    function mep_get_enable_payment_gateway()
    {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $enabled_gateways = [];
        if ($gateways) {
            foreach ($gateways as $gateway) {
                if ($gateway->enabled == 'yes') {
                    $enabled_gateways[] = $gateway;
                }
            }
        }

        return $enabled_gateways;
    }
}

// Get payment term name by id
if (!function_exists('mep_pp_payment_plan_name')) {
    function mep_pp_payment_plan_name($plan_id)
    {
        $name = '';
        if ($plan_id) {
            $name = get_term($plan_id)->name;
        }
        return $name;
    }
}

if (!function_exists('mepp_get_option')) {
    function mepp_get_option($meta_key, $default = null)
    {
        return get_option($meta_key) ? get_option($meta_key) : esc_html__($default);
    }
}

// Payment plan in product page
add_action('mep_payment_plan_list', 'mep_payment_plan_list_callback', 10, 3);
if (!function_exists('mep_payment_plan_list_callback')) {
    function mep_payment_plan_list_callback($plan_ids, $product_id, $deposit_type)
    {
        if (mep_is_zero_price_checkout_allow() == 'yes') {
            return;
        }

        $product_type = get_post_type($product_id);
        $total_price = 0;
        $variations_price = array();
        if ($product_type != 'mep_events') {
            $product = wc_get_product($product_id);
            $total_price = $product->get_price();

            if ($product->is_type('variable')) {
                // Product has variations
                $variations = $product->get_available_variations();
                if ($variations) {
                    foreach ($variations as $variation) {
                        if ($variation['variation_is_active']) {
                            $variations_price[] = array(
                                'variation_id' => $variation['variation_id'],
                                'price' => $variation['display_price'],
                                'regular_price' => $variation['display_regular_price'],
                            );
                        }
                    }
                }

                $total_price = $product->get_price();
            } else {
                $total_price = $product->get_price();
            }
        }
        $_pp_deposit_value = get_post_meta($product_id, '_mep_pp_deposits_value', true) ? get_post_meta($product_id, '_mep_pp_deposits_value', true) : 0;
        ob_start();
        ?>
        <div class="mep-product-payment-plans" data-total-price="<?php echo esc_attr($total_price); ?>">
            <div class="mep-single-plan-wrap">
                <input type="hidden" name="payment_plan" value="<?php echo esc_attr($deposit_type); ?>"
                       data-percent="<?php echo esc_attr($_pp_deposit_value); ?>">
                <?php if ($plan_ids && $deposit_type == 'payment_plan'):
            $i = 0;
            foreach ($plan_ids as $plan):
                $data = get_term_meta($plan);
                ?>
		                        <div>
		                            <label>
		                                <input type="radio"
		                                       name="mep_payment_plan" <?php echo esc_html($i) == 0 ? "checked" : ""; ?>
		                                       value="<?php echo esc_attr($plan); ?>"/>
		                                <?php echo get_term($plan)->name; ?>

		                            </label>
		                            <span class="mep-pp-show-detail"><?php esc_attr_e('View Details', 'advanced-partial-payment-or-deposit-for-woocommerce');?></span>
		                            <?php mep_payment_plan_detail($data, $total_price);?>
		                        </div>
		                        <?php
        $i++;
            endforeach;
        elseif ($deposit_type == 'percent'):
        ?><p style="text-align: center">
                    <strong><?php esc_attr_e('Deposit Amount :', 'advanced-partial-payment-or-deposit-for-woocommerce');?>
                        <span class="payment_amount"></span></strong></p>
                <?php
endif;
        ?>
            </div>
        </div>
        <?php
echo ob_get_clean();
    }
}

// Show payment plan detail
if (!function_exists('mep_payment_plan_detail')) {
    function mep_payment_plan_detail($data, $total)
    {
        if (mep_is_zero_price_checkout_allow() == 'yes') {
            return;
        }

        if ($data) {
            // $plan_schedule = maybe_unserialize(maybe_unserialize($data['mepp_plan_schedule'][0]));
            $down_payment = $data['mepp_plan_schedule_initial_pay_parcent'][0];
            $payment_schdule = maybe_unserialize((maybe_unserialize($data['mepp_plan_schedule'][0])));
            ob_start();
            $percent = 0;
            if ($payment_schdule) {
                foreach ($payment_schdule as $payments) {
                    $percent = $percent + $payments['plan_schedule_parcent'];
                }
            }
            ?>
            <div class="mep-single-plan plan-details">
                <div>
                    <p><?php esc_attr_e('Payments Total', 'advanced-partial-payment-or-deposit-for-woocommerce');?>
                        <strong class="total_pp_price" data-init-total="<?php echo esc_attr($total); ?>"
                                data-total-percent="<?php echo esc_attr($percent) + esc_attr($down_payment); ?>"></strong>
                    </p>
                </div>
                <div>
                    <p><?php esc_attr_e('Pay Deposit:', 'advanced-partial-payment-or-deposit-for-woocommerce');?>
                        <strong class="total_deposit" data-deposit="<?php echo esc_attr($down_payment); ?>"></strong>
                    </p>
                </div>
                <table>
                    <thead>
                    <tr>
                        <th><?php esc_attr_e('Payment Date', 'advanced-partial-payment-or-deposit-for-woocommerce');?></th>
                        <th><?php esc_attr_e('Amount', 'advanced-partial-payment-or-deposit-for-woocommerce');?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($payment_schdule):
                $date = date('Y-m-d');
                foreach ($payment_schdule as $plan):
                    $date = mep_payment_plan_date($plan["plan_schedule_date_after"], $plan["plan_schedule_parcent_date_type"], $date);
                    ?>
		                            <tr>
		                                <td><?php echo date(get_option('date_format'), strtotime($date)); ?></td>
		                                <td data-payment-plan="<?php echo esc_attr($plan["plan_schedule_parcent"]); ?>"></td>
		                            </tr>
		                        <?php
    endforeach;
            endif;
            ?>
                    </tbody>
                </table>
            </div>
            <?php

            echo ob_get_clean();
        } else {
            return null;
        }
    }
}

// Get percentage value
if (!function_exists('mep_percentage_value')) {
    function mep_percentage_value($percent_amount, $total_amount)
    {
        return ($total_amount * $percent_amount) / 100;
    }
}
// Get payment date
if (!function_exists('mep_payment_plan_date')) {
    function mep_payment_plan_date($date_after, $date_type, $date)
    {
        $date = date('Y-m-d', strtotime(sprintf('+%d %s', $date_after, $date_type), strtotime($date)));
        return $date;
    }
}

if (!function_exists('mep_pp_sanitize_array')) {
    function mep_pp_sanitize_array($array_or_string)
    {
        if (is_string($array_or_string)) {
            $array_or_string = sanitize_text_field($array_or_string);
        } elseif (is_array($array_or_string)) {
            foreach ($array_or_string as $key => &$value) {
                if (is_array($value)) {
                    $value = mep_pp_sanitize_array($value);
                } else {
                    $value = sanitize_text_field($value);
                }
            }
        }
        return $array_or_string;
    }
}

// Get Zero price checkout allow from setting
function mep_is_zero_price_checkout_allow()
{
    $res = get_option('meppp_checkout_zero_price') ? get_option('meppp_checkout_zero_price') : 'no';

    return $res;
}

add_filter('woocommerce_my_account_my_orders_actions', 'mep_conditional_pay_button_my_orders_actions', 10, 2);
function mep_conditional_pay_button_my_orders_actions($actions, $order)
{

    $order_id = $order->get_id();
    $is_payable = get_post_meta($order_id, 'zero_price_checkout_allow', true);
    if ($is_payable == 'no') {
        unset($actions['pay']);
    }
    return $actions;
}

// Check if partial payment enable addon for event is activate
function wcppe_enable_for_event()
{
    return is_plugin_active('woocommerce-event-manager-addon-advanced-partial-payment-for-event/plugin.php');
}

// Escape Html
if (!function_exists('mep_esc_html')) {
    function mep_esc_html($string)
    {
        $allow_attr = array(
            'input' => array(
                'br' => [],
                'type' => [],
                'class' => [],
                'id' => [],
                'name' => [],
                'value' => [],
                'size' => [],
                'placeholder' => [],
                'min' => [],
                'max' => [],
                'checked' => [],
                'required' => [],
                'disabled' => [],
                'readonly' => [],
                'step' => [],
                'data-default-color' => [],
            ),
            'p' => [
                'class' => [],
            ],
            'img' => [
                'class' => [],
                'id' => [],
                'src' => [],
                'alt' => [],
            ],
            'fieldset' => [
                'class' => [],
            ],
            'label' => [
                'for' => [],
                'class' => [],
            ],
            'select' => [
                'class' => [],
                'name' => [],
                'id' => [],
            ],
            'option' => [
                'class' => [],
                'value' => [],
                'id' => [],
                'selected' => [],
            ],
            'textarea' => [
                'class' => [],
                'rows' => [],
                'id' => [],
                'cols' => [],
                'name' => [],
            ],
            'h2' => ['class' => [], 'id' => []],
            'a' => ['class' => [], 'id' => [], 'href' => []],
            'div' => ['class' => [], 'id' => [], 'data' => []],
            'span' => [
                'class' => [],
                'id' => [],
                'data' => [],
            ],
            'i' => [
                'class' => [],
                'id' => [],
                'data' => [],
            ],
            'table' => [
                'class' => [],
                'id' => [],
                'data' => [],
            ],
            'tr' => [
                'class' => [],
                'id' => [],
                'data' => [],
            ],
            'td' => [
                'class' => [],
                'id' => [],
                'data' => [],
            ],
            'thead' => [
                'class' => [],
                'id' => [],
                'data' => [],
            ],
            'tbody' => [
                'class' => [],
                'id' => [],
                'data' => [],
            ],
            'th' => [
                'class' => [],
                'id' => [],
                'data' => [],
            ],
            'svg' => [
                'class' => [],
                'id' => [],
                'width' => [],
                'height' => [],
                'viewBox' => [],
                'xmlns' => [],
            ],
            'g' => [
                'fill' => [],
            ],
            'path' => [
                'd' => [],
            ],
            'br' => array(),
            'em' => array(),
            'strong' => array(),
        );
        return wp_kses($string, $allow_attr);
    }
}