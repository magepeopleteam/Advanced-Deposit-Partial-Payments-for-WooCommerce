<?php
if (!defined('ABSPATH')) {die;}

class MEP_PP_Order
{
    public function __construct()
    {

        add_filter('woocommerce_order_item_quantity_html', [$this, 'display_item_pp_deposit_data'], 20, 2);
        add_action('woocommerce_admin_order_totals_after_tax', [$this, 'deposit_data_dispaly_table_tr'], 20, 1);
        add_filter('woocommerce_get_order_item_totals', [$this, 'table_row_data'], 10, 2);
        add_action("woocommerce_order_after_calculate_totals", [$this, 'recalculate_pp_deposits_meta_data'], 20, 2);
        add_filter("woocommerce_cancel_unpaid_order", [$this, 'prevent_auto_order_cancel'], 10, 2);
        add_filter("woocommerce_can_restore_order_stock", [$this, 'prevent_order_restock'], 10, 2);

        // add_filter('wc_order_statuses', array($this, 'order_statuses'));
    }

    /**
     * Do not increase the product stock level if
     * the order have deposit items for pending status.
     * because the default behavior is modify the stock level
     * at the 'pending payment' status
     *
     * @param  [boolen] $value
     * @param  [obj]    $order
     * @return boolen
     */
    public function prevent_order_restock($value, $order)
    {
        if (get_post_meta($order->get_id(), 'due_payment', true) != '0') {
            $value = false;
        }

        return $value;
    }
    /**
     * By default WooCommerce behavior pending payments
     * are cancel after a certain time which provide by site admin
     * in this function we just simply use a condition to check the cancel hook
     * not run for @deposit_type order.
     * @return object
     */
    public function prevent_auto_order_cancel($condition, $order)
    {
        $condition = 'checkout' === $order->get_created_via() && '1' != get_post_meta($order->get_id(), 'paying_pp_due_payment', true);
        return $condition;
    }

    /**
     * Adjust the due payment when click recalculate
     * from admin edit order page
     */
    public function recalculate_pp_deposits_meta_data($and_taxes, $order)
    {
        if (get_post_meta($order->get_id(), 'due_payment', true) == '0') {
            return;
        }
        // Loop over $cart items
        $due_payment_value = 0; // no value

        // calculate amount of all deposit items
        foreach ($order->get_items() as $item_key => $item) {
            $due_payment_value += absint($item->get_meta('Due Payment')) * $item['quantity'];
        }

        // -- Make your checking and calculations --
        // deposit value calculation

        // for admin meta data
        $order->update_meta_data('total_value', $order->get_total(), true);
        $order->update_meta_data('due_payment', $due_payment_value, true);

        // Set the new calculated total
        $order->set_total(get_post_meta($order->get_id(), 'due_payment', true));

        do_action('dfwc_recalculate_order_meta', $order, $due_payment_value);
    }

    /**
     * Add deposit Tabel data in order details
     */
    public function table_row_data($total_rows, $order)
    {
        if (empty(get_post_meta($order->get_id(), 'paying_pp_due_payment', true))) {
            return $total_rows;
        }
        // Deposit order no need to show 'order again' button
        remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button');

        do_action('dfwc_table_row_data', $total_rows, $order);

        if (is_checkout() && !empty(is_wc_endpoint_url('order-received'))) {
            $paytoHtml = wc_price(get_post_meta($order->get_id(), 'due_payment', true));
        }

        $order_payment_plan = $order->get_id();

        // Overrirde : default order tr
        $total_rows['order_total'] = array(
            'label' => apply_filters('label_order_total', __('Total:', 'advanced-partial-payment-or-deposit-for-woocommerce')),
            'value' => apply_filters('woocommerce_pp_deposit_top_pay_html', wc_price(get_post_meta($order->get_id(), 'total_value', true))),
        );
        $total_rows['despoit_paid'] = array(
            'label' => apply_filters('label_pp_deposit_paid', __('Paid:', 'advanced-partial-payment-or-deposit-for-woocommerce')),
            'value' => wc_price(get_post_meta($order->get_id(), 'deposit_value', true)),
        );
        $total_rows['deposit'] = array(
            'label' => mepp_get_option('mepp_text_translation_string_to_pay', __('To Pay:', 'advanced-partial-payment-or-deposit-for-woocommerce')),
            'value' => isset($paytoHtml) ? $paytoHtml : '<input type="number" data-total="' . get_post_meta($order->get_id(), 'due_payment', true) . '" name="manually_pay_amount" min="' . get_post_meta($order->get_id(), 'due_payment', true) . '" max="5" step="1" value="' . get_post_meta($order->get_id(), 'due_payment', true) . '" />',
        );
        $total_rows['due_payment'] = array(
            'label' => mepp_get_option('mepp_text_translation_string_due_payment', __('Due Payment:', 'advanced-partial-payment-or-deposit-for-woocommerce')),
            'value' => 'Free',
        );

        return $total_rows;
    }

    /**
     * Dispaly Due amount in order table after deposit - for admin
     * Dispaly Total deposit amount in order table [for admin]
     */
    public function deposit_data_dispaly_table_tr($order_id)
    {
        if (get_post_meta($order_id, 'due_payment', true) == '0' && get_post_meta($order_id, 'paying_pp_due_payment', true) != '1') {
            return;
        }
        $order = wc_get_order($order_id);?>
        <tr>
			<td class="label"><?php echo mepp_get_option('mepp_text_translation_string_deposit', __('Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce')); ?>:</td>

			<td width="1%"></td>
			<td class="total">
				<?php echo wc_price(get_post_meta($order_id, 'deposit_value', true)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped   ?>
			</td>
        </tr>
        <tr>
			<td class="label"><?php mepp_get_option('mepp_text_translation_string_due_amount', __('Due Amount', 'advanced-partial-payment-or-deposit-for-woocommerce'));?>:</td>
			<td width="1%"></td>
			<td class="total">
				<?php echo wc_price(get_post_meta($order_id, 'due_payment', true)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped   ?>
			</td>
		</tr>
        <?php
}

    /**
     * Display deposit data below the cart item in
     * order review section
     */
    public function display_item_pp_deposit_data($quantity, $item)
    {
        if (isset($item['_pp_deposit'])) {
            $item['_pp_deposit'] = $item['_pp_deposit'] * $item['quantity'];
            $item['_pp_due_payment'] = $item['_pp_due_payment'] * $item['quantity'];
            $quantity .= sprintf(
                '<p>' . mepp_get_option('mepp_text_translation_string_deposit', __('Deposit:', 'advanced-partial-payment-or-deposit-for-woocommerce')) . ' %s <br> ' . mepp_get_option('mepp_text_translation_string_due_payment', __('Due Payment:', 'advanced-partial-payment-or-deposit-for-woocommerce')) . '%s</p>',
                wc_price($item['_pp_deposit']),
                wc_price($item['_pp_due_payment'])
            );
        }

        return $quantity;
    }

    // public function order_statuses($order_statuses)
    // {
    //     $new_statuses = array();
    //     // Place the new status after 'Pending payment'
    //     foreach ($order_statuses as $key => $value) {
    //         $new_statuses[$key] = $value;
    //         if ($key === 'wc-pending') {
    //             $new_statuses['wcpp-partially-paid'] = mepp_get_option('mepp_text_translation_string_partially_paid', __('Partially Paid', 'advanced-partial-payment-or-deposit-for-woocommerce'));
    //         }
    //     }
    //     return $new_statuses;
    // }

    // public function order_has_status($has_status, $order, $status)
    // {
    //     if ($order->get_status() === 'partially-paid') {
    //         if (is_array($status)) {
    //             if (in_array('pending', $status)) {
    //                 $has_status = true;
    //             }
    //         } else {
    //             if ($status === 'pending') {
    //                 $has_status = true;
    //             }
    //         }
    //     }
    //     return $has_status;
    // }
}

new MEP_PP_Order();