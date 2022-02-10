<?php
if (!defined('ABSPATH')) {
    die;
}

class MEP_PP_Checkout
{
    public $zero_price_checkout_allow = 'no';

    public function __construct()
    {
        add_action('woocommerce_review_order_after_order_total', [$this, 'to_pay_html']);
        add_action('woocommerce_review_order_before_payment', [$this, 'deposit_type_selection']);
        add_action('woocommerce_checkout_create_order', [$this, 'adjust_order'], 10, 1);
        add_action('woocommerce_before_pay_action', [$this, 'before_pay_action'], 1, 1);
        add_action('woocommerce_before_thankyou', [$this, 'due_payment_order_data'], 10, 1);
        add_filter('woocommerce_get_checkout_payment_url', array($this, 'checkout_payment_url'), 10, 2);
        add_action('woocommerce_thankyou', [$this, 'send_notification'], 20, 1);
        add_action('woocommerce_email', [$this, 'unhook_new_order_email']);
        /***  Disable suborder email to customer ****/
        add_filter('woocommerce_email_recipient_customer_completed_order', [$this, 'disable_email_for_sub_order'], 10, 2);

        // Zero price Checkout
        $this->zero_price_checkout_allow = apply_filters('mepp_enable_zero_price_checkout', 'no');
        if ($this->zero_price_checkout_allow === 'yes') {
            add_filter('woocommerce_cart_needs_payment', '__return_false');
            add_filter('woocommerce_order_needs_payment', '__return_false');
            remove_filter('woocommerce_order_needs_payment', 'WC_Order');
            add_filter('woocommerce_order_needs_payment', array($this, 'check_order_payment'), 10, 3);
        }
        // Zero price Checkout END

        add_filter('wc_order_statuses', array($this, 'order_statuses'));
        add_filter('woocommerce_order_has_status', array($this, 'order_has_status'), 10, 3);

        add_action('woocommerce_order_details_after_order_table', [$this, 'pending_payment_button'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'deposit_order_complete'], 10, 1);
        add_action('woocommerce_order_status_partially-paid', [$this, 'deposit_order_partially_paid'], 10, 1);
        add_action('woocommerce_order_status_processing', [$this, 'deposit_order_processing'], 10, 1);
        add_action('woocommerce_order_status_cancelled', [$this, 'deposit_order_cancelled'], 10, 1);
        add_filter('woocommerce_checkout_cart_item_quantity', [$this, 'display_item_pp_deposit_data'], 20, 3);
        add_filter('woocommerce_checkout_create_order_line_item', [$this, 'save_cart_item_custom_meta_as_order_item_meta'], 20, 4);
        add_filter('woocommerce_payment_complete_order_status', array($this, 'prevent_status_to_processing'), 10, 2);
        add_action('wp_ajax_manually_pay_amount_input', array($this, 'manually_pay_amount_input'));
        add_action('wp_ajax_nopriv_manually_pay_amount_input', array($this, 'manually_pay_amount_input'));

        do_action('dfwc_checkout', $this);
    }

    public function deposit_type_selection()
    {
        $default_partial_for_page = apply_filters('mepp_partial_option_for_page', 'product_detail');
        if ($default_partial_for_page === 'product_detail') {
            return 0;
        }
        $default_deposit_type = get_option('mepp_default_partial_type') ? get_option('mepp_default_partial_type') : 'percent';

        $default_payment_plans = get_option('mepp_default_payment_plan') ? maybe_unserialize(get_option('mepp_default_payment_plan')) : [];
        $payment_plans = get_terms(
            array(
                'taxonomy' => 'mepp_payment_plan',
                'hide_empty' => false
            )
        );
        $all_plans = array();
        if (!empty($payment_plans)) {
            foreach ($payment_plans as $payment_plan) {
                $all_plans[$payment_plan->term_id] = $payment_plan->name;
            }
        }

        $cart = WC()->cart->cart_contents;
        $deposit_type = 'check_full';
        $cart_payment_plan_id = '';
        foreach ($cart as $item) {
            if (isset($item['_pp_deposit_system'])) {
                $deposit_type = $item['_pp_deposit_system'];
                $cart_payment_plan_id = isset($item['_pp_deposit_payment_plan_id']) ? $item['_pp_deposit_payment_plan_id'] : '';
                break;
            }
        }

        $isForcePartialPayment = apply_filters('mepp_force_partial_payment', 'no');

        ?>

        <div class="wcpp-deposit-types">
            <h5><?php _e('Deposit Type', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></h5>
            <div class="inner">
                <?php if ($isForcePartialPayment !== 'yes') : ?>
                    <div class="wcpp-input-group">
                        <input type="radio" name="_pp_deposit_system" value="check_full"
                               id="check_full" <?php echo $deposit_type === '' || $deposit_type === 'check_full' ? "checked" : "" ?>>
                        <label for="check_full"><?php _e('Full Payment', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></label>
                    </div>
                <?php endif; ?>
                <div class="wcpp-input-group">
                    <input type="radio" name="_pp_deposit_system" value="check_pp_deposit"
                           data-deposit-type="<?php echo $default_deposit_type; ?>"
                           id="check_pp_deposit" <?php echo $deposit_type !== 'check_full' && $deposit_type !== '' ? "checked" : "" ?>>
                    <label for="check_pp_deposit"><?php _e('Pay Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></label>
                </div>
                <?php if ($default_deposit_type === 'payment_plan') : ?>
                    <div class="mepp-payment-plan-option-frontend">
                        <label for="mepp_default_payment_plan"><?php _e('Payment plan', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></label>
                        <select name="mepp_default_payment_plan[]" id="mepp_default_payment_plan">
                            <option value=""><?php _e('Select one', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></option>
                            <?php if ($all_plans) : foreach ($all_plans as $id => $plan) : ?>
                                <?php if (in_array($id, $default_payment_plans)) : ?>
                                    <option value="<?php echo $id; ?>" <?php echo($id == $cart_payment_plan_id ? 'selected' : '') ?>><?php echo $plan; ?></option>
                                <?php endif; ?>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
    }

    public function before_pay_action($order)
    {
        $manually_pay_amount = isset($_POST['manually_pay_amount']) ? sanitize_text_field($_POST['manually_pay_amount']) : 0;
        // Parent Order
        $parent_order_id = $order->get_parent_id();
        $parent_order = wc_get_order($parent_order_id);
        $prev_deposited = get_post_meta($parent_order_id, 'deposit_value', true);

        $order_total_value = get_post_meta($parent_order_id, 'total_value', true);

        $current_due_amount = $order_total_value - ($prev_deposited + $manually_pay_amount);

        $manually_due_amount = isset($_POST['manually_due_amount']) ? sanitize_text_field($_POST['manually_due_amount']) : $current_due_amount;

        if ($manually_pay_amount == 0) {
            $payTo = $current_due_amount;
            $manually_due_amount = 0;
        } else {
            $payTo = $manually_pay_amount;
        }

        $order_id = $order->get_id();
        $order->set_total($payTo);

        update_post_meta($parent_order_id, 'deposit_value', $prev_deposited + $payTo);
        update_post_meta($parent_order_id, 'due_payment', $manually_due_amount);
        update_post_meta($parent_order_id, '_order_total', $prev_deposited + $payTo);

        $data = array(
            'deposite_amount' => $payTo,
            'due_amount' => $manually_due_amount,
            'payment_date' => date('Y-m-d'),
            'payment_method' => '',
        );

        $order_payment_plan = get_post_meta($parent_order_id, 'order_payment_plan', true);

        if (!$order_payment_plan) {
            mep_pp_history_add($order_id, $data, $parent_order_id);
        }

        $due_amount = get_post_meta($parent_order_id, 'due_payment', true);

        if ($due_amount && !$order_payment_plan) {

            // New
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            $order_vat_exempt = WC()->cart->get_customer()->get_is_vat_exempt() ? 'yes' : 'no';
            $user_agent = wc_get_user_agent();

            $payment_schedule = array(
                'last_payment' => array(
                    'id' => '',
                    'title' => 'Second Deposit',
                    'term' => 2,
                    'type' => 'last',
                    'total' => $due_amount,
                ),
            );

            $deposit_id = null;
            if ($payment_schedule) {
                foreach ($payment_schedule as $partial_key => $payment) {

                    $partial_payment = new WCPP_Payment();
                    $partial_payment->set_customer_id(apply_filters('woocommerce_checkout_customer_id', get_current_user_id()));

                    $amount = $payment['total'];

                    //allow partial payments to be inserted only as a single fee without item details
                    $name = esc_html__('Partial Payment for order %s', 'advanced-partial-payment-or-deposit-for-woocommerce');
                    $partial_payment_name = apply_filters('wc_deposits_partial_payment_name', sprintf($name, $order->get_order_number()), $payment, $order->get_id());

                    $item = new WC_Order_Item_Fee();

                    $item->set_props(
                        array(
                            'total' => $amount,
                        )
                    );

                    $item->set_name($partial_payment_name);
                    $partial_payment->add_item($item);

                    $partial_payment->set_parent_id($parent_order_id);
                    $partial_payment->add_meta_data('is_vat_exempt', $order_vat_exempt);
                    $partial_payment->add_meta_data('_wc_pp_payment_type', $payment['type']);

                    if (is_numeric($partial_key)) {
                        $partial_payment->add_meta_data('_wc_pp_partial_payment_date', $partial_key);
                    }
                    $partial_payment->set_currency(get_woocommerce_currency());
                    $partial_payment->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
                    $partial_payment->set_customer_ip_address(WC_Geolocation::get_ip_address());
                    $partial_payment->set_customer_user_agent($user_agent);

                    $partial_payment->set_total($amount);
                    $partial_payment->add_meta_data('_wc_pp_reminder_email_sent', 'no');
                    $partial_payment->save();

                    $payment_schedule[$partial_key]['id'] = $partial_payment->get_id();

                }
            }

            //update the schedule meta of parent order
            $parent_order->update_meta_data('_wc_pp_payment_schedule', $payment_schedule);
            $parent_order->update_meta_data('_wc_pp_last_payment_date', strtotime(date('Y-m-d', current_time('timestamp'))));

            // Generate Next Payment Reminder Date
            $next_payment_reminder_day_num = get_option('mepp_day_before_second_payment_reminder'); // Days number from setting
            $next_payment_reminder_date = strtotime("+" . intval($next_payment_reminder_day_num) . " days");
            $parent_order->update_meta_data('_wc_pp_next_payment_reminder_date', $next_payment_reminder_date);

            $parent_order->save();

        }

        // Generate Next Payment Reminder Date
        if ($order_payment_plan) {
            // For Payment plan
            $next_payment_reminder_day_num = get_option('mepp_day_before_payment_plan_reminder'); // Days number from setting
            $next_payment_order_id = mep_get_next_payment_order_id($parent_order->get_id());
            $next_payment_date = get_post_meta($next_payment_order_id, '_wc_pp_partial_payment_date', true);
            $next_payment_reminder_date = strtotime("-" . intval($next_payment_reminder_day_num) . " days", $next_payment_date);
            $parent_order->update_meta_data('_wc_pp_next_payment_reminder_date', $next_payment_reminder_date);

            $parent_order->save();
        }

        return $order;
    }

    /**
     * Save cart item custom meta as order item meta data
     * and display it everywhere on orders and email notifications.
     */
    public function save_cart_item_custom_meta_as_order_item_meta($item, $cart_item_key, $values, $order)
    {
        foreach ($item as $cart_item_key => $values) {
            if (isset($values['_pp_deposit']) && $values['_pp_deposit_type'] == 'check_pp_deposit') {
                $deposit_amount = $values['_pp_deposit'] * $item->get_quantity();
                $item->add_meta_data(__('Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce'), wc_price($deposit_amount), true);
            }
            if (isset($values['_pp_due_payment']) && $values['_pp_deposit_type'] == 'check_pp_deposit') {
                $due_payment = $values['_pp_due_payment'] * $item->get_quantity();
                $item->add_meta_data(__('Due Payment', 'advanced-partial-payment-or-deposit-for-woocommerce'), wc_price($due_payment), true);
                $item->add_meta_data('_DueAmount', $due_payment, true);
            }
        }
    }

    /**
     * Display deposit data below the cart item in
     * order review section
     */
    public function display_item_pp_deposit_data($order, $cart_item)
    {
        if (isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') {
            $cart_item['_pp_deposit'] = $cart_item['_pp_deposit'] * $cart_item['quantity'];
            $cart_item['_pp_due_payment'] = $cart_item['_pp_due_payment'] * $cart_item['quantity'];
            $order .= sprintf(
                '<p>' . mepp_get_option('mepp_text_translation_string_deposit', __('Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce')) . ': %s <br> ' . mepp_get_option('mepp_text_translation_string_due_payment', __('Due Payment', 'advanced-partial-payment-or-deposit-for-woocommerce')) . ':  %s <br> ' . mepp_get_option('mepp_text_translation_string_deposit_type', __('Deposit Type', 'advanced-partial-payment-or-deposit-for-woocommerce')) . ':  <strong>%s</strong></p>',
                wc_price($cart_item['_pp_deposit']),
                wc_price($cart_item['_pp_due_payment']),
                mep_pp_deposti_type_display_name($cart_item['_pp_deposit_system'], $cart_item, true)
            );
        }
        return $order;
    }

    /**
     * Dispaly Deposit amount to know user how much need to pay.
     */
    public function to_pay_html()
    {
        if (meppp_cart_have_pp_deposit_item()) {
            meppp_display_to_pay_html();
        }
    }

    /**
     * Method for set custom amount based on deposit
     * Add or update desposit meta
     */
    public function adjust_order($order)
    {
        if (apply_filters('dfwc_disable_adjust_order', false)) {
            return;
        }

        // Get order total
        $total = WC()->cart->get_total('f');

        // Loop over $cart items
        $deposit_value = 0; // no value
        $due_payment_value = 0; // no value
        $is_deposit_mode = false;
        // calculate amount of all deposit items
        $cart_has_payment_plan = false; // Check cart has payment plan deposit system. init false
        $order_payment_plan = array();
        $pp_deposit_system = '';
        $grand_total_price = 0;
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
//            echo '<pre>';print_r($cart_item);die;
            $deposit_value += (isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') ? $cart_item['_pp_deposit'] * $cart_item['quantity'] : 0;
            $due_payment_value += (isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') ? $cart_item['_pp_due_payment'] * $cart_item['quantity'] : 0;

            $grand_total_price += (isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') ? $cart_item['line_total'] * $cart_item['quantity'] : 0;

            if (isset($cart_item['_pp_deposit_system'])) {
                if ($cart_item['_pp_deposit_system'] == 'payment_plan') {
                    $cart_has_payment_plan = true;
                    $order_payment_plan = isset($cart_item['_pp_order_payment_terms']) ? $cart_item['_pp_order_payment_terms'] : array();
                }

                if ($pp_deposit_system == '') {
                    $pp_deposit_system = $cart_item['_pp_deposit_system'];
                }

                if (isset($cart_item['_pp_deposit_system'])) {
                    $is_deposit_mode = true;
                }
            }
        }

        if (!$is_deposit_mode) {
            return;
        }

        // -- Make your checking and calculations --
        // $new_total = $total - $due_payment_value; // <== deposit value calculation
        $new_total = isset($_POST['manually_due_amount']) ? ($total - sanitize_text_field($_POST['manually_due_amount'])) : ($total - $due_payment_value);

        if (in_array('ppcp-gateway', meppp_available_payment_methods())) {
            $deposit_amount = $total;
            $due_amount = $grand_total_price - $deposit_amount;

            // for admin meta data
            $order->update_meta_data('total_value', $grand_total_price, true);
            $order->update_meta_data('deposit_value', $deposit_amount, true);
            $order->update_meta_data('due_payment', $due_amount, true);
            $order->update_meta_data('order_payment_plan', $order_payment_plan, true); // Payment Plans
            // Set the new calculated total
            $order->set_total(apply_filters('dfwc_cart_total', $deposit_amount));

            $order->update_meta_data('deposit_mode', 'yes', true);
            $order->update_meta_data('_pp_deposit_system', $pp_deposit_system, true);
            if ($pp_deposit_system == 'zero_price_checkout') {
                $order->update_meta_data('zero_price_checkout_allow', 'no', true);
            }

            do_action('dfwc_adjust_order', $order, $grand_total_price);
        } else {
            // for admin meta data
            $order->update_meta_data('total_value', $total, true);
            $order->update_meta_data('deposit_value', $new_total, true);
            $order->update_meta_data('due_payment', $total - $new_total, true);
            $order->update_meta_data('order_payment_plan', $order_payment_plan, true); // Payment Plans
            // Set the new calculated total
            $order->set_total(apply_filters('dfwc_cart_total', $new_total));

            $order->update_meta_data('deposit_mode', 'yes', true);
            $order->update_meta_data('_pp_deposit_system', $pp_deposit_system, true);
            if ($pp_deposit_system == 'zero_price_checkout') {
                $order->update_meta_data('zero_price_checkout_allow', 'no', true);
            }

            do_action('dfwc_adjust_order', $order, $new_total);

            $deposit_amount = $new_total;
            $due_amount = $total - $new_total;
        }

        // ********************************
        if ($due_amount) {
            $order->set_status('wc-partially-paid');
//            $order->update_meta_data('paying_pp_due_payment', 1, true);

            $order->update_meta_data('_wc_pp_deposit_paid', 'yes');
            $order->update_meta_data('_wc_pp_second_payment_paid', 'no');
            $order->update_meta_data('_wc_pp_deposit_payment_time', time());
            $order->save();

            $payment_method = get_post_meta($order->get_order_number(), '_payment_method', true);

            // New
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            $order_vat_exempt = WC()->cart->get_customer()->get_is_vat_exempt() ? 'yes' : 'no';
            $user_agent = wc_get_user_agent();

            if ($order_payment_plan) {
                $payment_schedule = $order_payment_plan;
            } else {
                $payment_schedule = array(
                    'deposit' => array(
                        'id' => '',
                        'title' => 'First Deposit',
                        'term' => 1,
                        'type' => 'deposit',
                        'total' => $deposit_amount,
                    ),
                    'last_payment' => array(
                        'id' => '',
                        'title' => 'Second Deposit',
                        'term' => 2,
                        'type' => 'last',
                        'total' => $due_amount,
                    ),
                );
            }

            $deposit_id = null;
            if ($payment_schedule) {
                foreach ($payment_schedule as $partial_key => $payment) {

                    $partial_payment = new WCPP_Payment();
                    $partial_payment->set_customer_id(apply_filters('woocommerce_checkout_customer_id', get_current_user_id()));

                    $amount = $payment['total'];

                    //allow partial payments to be inserted only as a single fee without item details
                    $name = esc_html__('Partial Payment for order %s', 'advanced-partial-payment-or-deposit-for-woocommerce');
                    $partial_payment_name = apply_filters('wc_deposits_partial_payment_name', sprintf($name, $order->get_order_number()), $payment, $order->get_id());

                    $item = new WC_Order_Item_Fee();

                    $item->set_props(
                        array(
                            'total' => $amount,
                        )
                    );

                    $item->set_name($partial_payment_name);
                    $partial_payment->add_item($item);

                    $partial_payment->set_parent_id($order->get_id());
                    $partial_payment->add_meta_data('is_vat_exempt', $order_vat_exempt);
                    $partial_payment->add_meta_data('_wc_pp_payment_type', $payment['type']);

                    if (isset($payment['date'])) {
                        $partial_payment->add_meta_data('_wc_pp_partial_payment_date', strtotime($payment['date']));
                    }
                    $partial_payment->set_currency(get_woocommerce_currency());
                    $partial_payment->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
                    $partial_payment->set_customer_ip_address(WC_Geolocation::get_ip_address());
                    $partial_payment->set_customer_user_agent($user_agent);

                    $partial_payment->set_total($amount);
                    $partial_payment->save();

                    $payment_schedule[$partial_key]['id'] = $partial_payment->get_id();

                    //fix wpml language
                    $wpml_lang = $order->get_meta('wpml_language', true);
                    if ($payment['type'] === 'deposit') { // First

                        $partial_payment->set_status('wc-completed');
                        //we need to save to generate id first
                        $partial_payment->save();

                        $deposit_id = $partial_payment->get_id();

                        //add wpml language for all child orders for wpml
                        if (!empty($wpml_lang)) {
                            $partial_payment->update_meta_data('wpml_language', $wpml_lang);
                        }

                        if ($cart_has_payment_plan) { // Payment Plan
                            $data = array(
                                'deposite_amount' => $payment['total'],
                                'due_amount' => $due_amount,
                                'payment_date' => date('Y-m-d'),
                                'payment_method' => $payment_method,
                            );
                            mep_pp_history_add($order->get_id(), $data, $order->get_id());
                        }

                        $partial_payment->set_payment_method(isset($available_gateways[$payment_method]) ? $available_gateways[$payment_method] : $payment_method);

                    } else {
                        if ($cart_has_payment_plan) { // Payment Plan
                            $data = array(
                                'deposite_amount' => $payment['total'],
                                'due_amount' => $payment['due'],
                                'payment_date' => $payment['date'],
                                'payment_method' => '',
                            );
                            mep_pp_history_add($partial_payment->get_id(), $data, $order->get_id());
                        }
                        $partial_payment->add_meta_data('_wc_pp_reminder_email_sent', 'no');
                    }

                    $partial_payment->save();

                }
            }

            //update the schedule meta of parent order
            $order->update_meta_data('_wc_pp_payment_schedule', $payment_schedule);
            $order->update_meta_data('_wc_pp_last_payment_date', strtotime(date('Y-m-d', current_time('timestamp'))));
            if ($cart_has_payment_plan) {
                // For Payment plan
                $next_payment_reminder_day_num = get_option('mepp_day_before_payment_plan_reminder'); // Days number from setting
                $next_payment_order_id = mep_get_next_payment_order_id($order->get_id());
                $next_payment_date = get_post_meta($next_payment_order_id, '_wc_pp_partial_payment_date', true);
                $next_payment_reminder_date = strtotime("-" . intval($next_payment_reminder_day_num) . " days", $next_payment_date);
                $order->update_meta_data('_wc_pp_next_payment_reminder_date', $next_payment_reminder_date);
            } else {
                $next_payment_reminder_day_num = get_option('mepp_day_before_second_payment_reminder'); // Days number from setting
                $next_payment_reminder_date = strtotime("+" . intval($next_payment_reminder_day_num) . " days");
                $order->update_meta_data('_wc_pp_next_payment_reminder_date', $next_payment_reminder_date);
            }
            $order->save();

            $data = array(
                'deposite_amount' => $deposit_amount,
                'due_amount' => $due_amount,
                'payment_date' => date('Y-m-d'),
                'payment_method' => $payment_method,
            );

            if (!$cart_has_payment_plan) { // Not payment plan
                mep_pp_history_add($order->get_id(), $data, $order->get_id());
            }
        }

        // Stock Reduce on by setting
        $stock_reduce_on = mepp_get_option('meppp_quantity_reduce_on', 'full');
        if ($stock_reduce_on === 'full') {
            add_filter('woocommerce_payment_complete_reduce_order_stock', '__return_false');
        }


        // ********************************
    }

    /**
     * When due payment is paid and if order will processing by default
     * so here we can run some code on order processing
     */
    public function deposit_order_processing($order_id)
    {
        // Get an instance of the WC_Order object (same as before)
        $order = wc_get_order($order_id);

        if (get_post_meta($order_id, 'deposit_mode', true) != 'yes') {
            return;
        }

        if (get_post_meta($order_id, 'paying_pp_due_payment', true) != '1') {
            return;
        }

        $order->set_total(get_post_meta($order_id, 'total_value', true));
        $order->update_meta_data('deposit_value', get_post_meta($order_id, 'total_value', true), true);
        $order->update_meta_data('due_payment', 0, true);
        $order->save();
    }

    /**
     * When due payment is process the order will completed by default
     * so here we can run some code on order completed
     */
    public function deposit_order_complete($order_id)
    {
        // Get an instance of the WC_Order object (same as before)


        $order = wc_get_order($order_id);

//        if (get_post_meta($order_id, 'paying_pp_due_payment', true) != '1') {
//            // Trigger when desposit order completed.
//            $email_customer = WC()->mailer()->get_emails()['WC_Email_Customer_Completed_Order'];
//            $email_customer->trigger($order_id);
//
//            return;
//        }

        // if(!is_admin()) {
        //     $order->set_total(get_post_meta($order_id, 'total_value', true));
        //     $order->update_meta_data('deposit_value', get_post_meta($order_id, 'total_value', true), true);
        //     $order->update_meta_data('due_payment', 0, true);
        // }

        if (is_admin()) {
            $order_amount = get_post_meta($order_id, 'total_value', true);
            $paid_amount = get_post_meta($order_id, 'deposit_value', true);
            $due = (float)$order_amount - (float)$paid_amount;
            $order->update_meta_data('due_state', $due, true);
        }
        $order->save();

        // Trigger when desposit order completed.
        $email_customer = WC()->mailer()->get_emails()['WC_Email_Customer_Completed_Order'];
        $email_customer->trigger($order_id);
    }

    public function deposit_order_partially_paid($order_id)
    {
        $order = wc_get_order($order_id);
        $total_value = $order->get_meta('total_value', true);
        $due_state = $order->get_meta('due_state', true);
        if (is_admin() && $due_state) {
            $order->update_meta_data('due_payment', $due_state, true);
            $order->set_total($total_value - $due_state);
            $order->save();
        }
    }

    public function deposit_order_cancelled($order_id)
    {
        $args = array(
            'post_type' => 'mep_pp_history',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'order_id',
                    'value' => $order_id,
                    'compare' => '=',
                ),
            ),
        );
        $history = new WP_Query($args);
        if ($history->post) {
            wp_delete_post($history->post->ID);
        }
    }

    /**
     * after create an order need to update total value &
     * after complete first payment change the order total to
     * due payment amount
     */
    public function due_payment_order_data($order_id)
    {
        // Get an instance of the WC_Order object (same as before)
        $order = wc_get_order($order_id);
        $due_amount = get_post_meta($order_id, 'due_payment', true);

        if ($due_amount == '0' || get_post_meta($order_id, 'paying_pp_due_payment', true) == '1') {
            return null;
        }

        $payment_method = get_post_meta($order_id, '_payment_method', true);

        if ($order->get_parent_id()) {
            $parent_id = $order->get_parent_id();
        } else {
            $parent_id = $order_id;
        }

        $get_due_amount = get_post_meta($parent_id, 'due_payment', true);
        $get_due_amount = $get_due_amount === '' ? 'no_data' : $get_due_amount;
        $parent_order = wc_get_order($parent_id);

        if ($get_due_amount) {
            $parent_order->set_status('wc-partially-paid');

        } elseif ($get_due_amount === 'no_data') {
            return null;
        } elseif ($get_due_amount == 0) {
            $parent_order->set_status('wc-processing');
            $parent_order->update_meta_data('paying_pp_due_payment', 1, true);
            $parent_order->update_meta_data('_wc_pp_payment_schedule', '', true);
        }

        $parent_order->save();

        if ($order->get_type() == 'wcpp_payment' && $payment_method) {
            $args = array(
                'post_type' => 'mep_pp_history',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'asc',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'parent_order_id',
                        'value' => $parent_id,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'order_id',
                        'value' => $order_id,
                        'compare' => '=',
                    ),
                ),
            );


            $pp_history = new WP_Query($args);

            if ($pp_history->found_posts > 0) {
                $history_id = $pp_history->posts[0]->ID;
                update_post_meta($history_id, 'payment_method', $payment_method);
                update_post_meta($history_id, 'payment_date', date('Y-m-d'));
            }
        }

    }

    public function partial_confirm_notification($order_id)
    {
        $return_id = null;
        if ($order_id) {
            $args = array(
                'post_type' => 'wcpp_payment',
                'posts_per_page' => -1,
                'post_parent' => $order_id,
                'order' => 'ASC',
                'orderby' => 'ID',
                'post_status' => 'any'
            );

            $wcpp_payments = new WP_Query($args);

            while ($wcpp_payments->have_posts()) {
                $wcpp_payments->the_post();
                $id = get_the_ID();
                $order = wc_get_order($id);
                $status = get_post_status($id);
                $confirm_email_sent = get_post_meta($id, 'order_confirm_email_sent', true);
                if ($status == 'wc-partially-paid' && $confirm_email_sent !== 'yes') {
                    $return_id = $id;
                    $_date_paid = get_post_meta($id, '_date_paid', true);
                    $_date_completed = get_post_meta($id, '_date_completed', true);
                    // if($_date_paid && $_date_completed) {
                    $order->set_status('wc-completed');
                    $order->save();
                    update_post_meta($id, 'order_confirm_email_sent', 'yes');
                    break;
                    // }
                }
            }

            wp_reset_postdata();
        }

        return $return_id;
    }

    /**
     * send email notification
     *
     * @param  [int]  $order_id
     * @return void
     */
    public function send_notification($order_id)
    {
        // $order_id = $this->partial_confirm_notification($order_id);
        $order = wc_get_order($order_id);

        if (get_post_meta($order_id, 'paying_pp_due_payment', true) == '1' && $order->get_status() == 'processing') {
            $email_customer_Processing = WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order'];
            $email_customer_Processing->trigger($order_id);
        } elseif (get_post_meta($order_id, 'paying_pp_due_payment', true) != '1' && $order->get_status() == 'processing') {
            $email_customer_Processing = WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order'];
            $email_customer_Processing->trigger($order_id);
        } elseif (get_post_meta($order_id, 'paying_pp_due_payment', true) != '1' && $order->get_status() == 'completed') {
            $email_customer_Completed = WC()->mailer()->get_emails()['WC_Email_Customer_Completed_Order'];
            $email_customer_Completed->trigger($order_id);
        }

        // Trigger if order type is deposit first draft
        if (get_post_meta($order_id, 'due_payment', true) > 0 && apply_filters('dfwc_customer_invoice', true)) {
            $email_customer = WC()->mailer()->get_emails()['WC_Email_Customer_Invoice'];
            $email_customer->trigger($order_id);
        }

        do_action('dfwc_send_notification', $order_id);
    }

    /**
     * This method is for prevent the default email
     * hooks whoch is conflcit with dfwc email notification
     *
     * @param  [type] $email_class
     * @return void
     */
    public function unhook_new_order_email($email_class)
    {
        remove_action('woocommerce_order_status_pending_to_completed_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_pending_to_processing_notification', array($email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger'));
        // Completed order emails
        remove_action('woocommerce_order_status_completed_notification', array($email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger'));

        do_action('dfwc_unhook_new_order_email', $email_class);
    }

    /**
     * Display due payment button
     */
    public function pending_payment_button($order)
    {
        $order_id = $order->get_id();
        if ($order->get_parent_id()) {
            $order_id = $order->get_parent_id();
            $order = wc_get_order($order_id);
        }
        echo mep_pp_history_get($order->get_id());
    }

    public function manually_pay_amount_input()
    {
        $total = sanitize_text_field($_POST['total']);
        $pay = sanitize_text_field($_POST['pay']);
        $due = $total - $pay;

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

            if (isset($cart_item['_pp_deposit_type']) && $cart_item['_pp_deposit_type'] == 'check_pp_deposit') {
                // echo '<pre>';print_r($cart_item);die;
                $cart_item['_pp_deposit'] = $pay;
                $cart_item['_pp_due_payment'] = $due;

                WC()->cart->cart_contents[$cart_item_key] = $cart_item;
            }
        }

        WC()->cart->set_session(); // Finally Update Cart

        $data = array(
            'with_symbol' => wc_price($due),
            'amount' => $due,
        );
        echo json_encode($data);
        exit;
    }

    public function checkout_payment_url($url, $order)
    {
        if (get_post_meta($order->get_id(), 'due_payment', true) == '0' || get_post_meta($order->get_id(), 'paying_pp_due_payment', true) == '1') {
            return $url;
        }

        if ($order->get_type() !== 'wcpp_payment') {

//            $order_id = $order->get_id();
//            $parent_id = $order->get_parent_id();
//            if($parent_id == 0 || !$parent_id) {
//                $pp_deposit_system = get_post_meta($order_id, '_pp_deposit_system', true);
//                $permition_for_next_payment = get_post_meta($order_id, 'zero_price_checkout_allow', true); // Only for Zero price Checkout Order
//
//                if($pp_deposit_system == 'zero_price_checkout' && $permition_for_next_payment == 'no') {
//                    return;
//                }
//            }

            $payment_schedule = $order->get_meta('_wc_pp_payment_schedule', true);

            if (is_array($payment_schedule) && !empty($payment_schedule)) {

                foreach ($payment_schedule as $payment) {
                    if (!isset($payment['id'])) {
                        continue;
                    }

                    $payment_order = wc_get_order($payment['id']);

                    if (!$payment_order) {
                        continue;
                    }
//create one

                    if (!$payment_order || !$payment_order->needs_payment()) {
                        continue;
                    }

                    $url = $payment_order->get_checkout_payment_url();
                    $url = add_query_arg(
                        array(
                            'payment' => $payment['type'],
                        ), $url
                    );

                    //already reached a payable payment
                    break;
                }

            }

        }

        return $url;
    }

    public function prevent_status_to_processing($order_status, $order_id)
    {
        $order = new WC_Order($order_id);

        $due = get_post_meta($order_id, 'due_payment', true);
        if ($due) {
            if ('processing' == $order_status) {
                return 'partially-paid';
            }
        }

        return $order_status;
    }

    public function disable_email_for_sub_order($recipient, $order)
    {
        if (wp_get_post_parent_id($order->get_id())) {
            return;
        } else {
            return $recipient;
        }
    }

    function check_order_payment($th, $order, $valid_order_statuses)
    {
        return $order->has_status($valid_order_statuses);
    }

    public function order_statuses($order_statuses)
    {
        $new_statuses = array();
        // Place the new status after 'Pending payment'
        foreach ($order_statuses as $key => $value) {
            $new_statuses[$key] = $value;
            if ($key === 'wc-pending') {
                $new_statuses['wc-partially-paid'] = esc_html__('Partially Paid', 'woocommerce-deposits');
            }
        }
        return $new_statuses;
    }

    public function order_has_status($has_status, $order, $status)
    {
        if ($order->get_status() === 'partially-paid') {
            if (is_array($status)) {
                if (in_array('pending', $status)) {
                    $has_status = true;
                }
            } else {
                if ($status === 'pending') {
                    $has_status = true;
                }
            }
        }
        return $has_status;
    }
}

new MEP_PP_Checkout();