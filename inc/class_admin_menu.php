<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Mepp_Admin_Menu')) {
    class Mepp_Admin_Menu
    {
        protected $menu_title;

        public function __construct()
        {
            $this->menu_title = 'Partial Payment';

            add_action('admin_menu', array($this, 'init'));
        }

        public function init()
        {
            add_menu_page($this->menu_title, $this->menu_title, 'manage_options', 'mage-partial', null, 'dashicons-megaphone', 50);

            add_submenu_page('mage-partial', 'Partial Order', 'Partial Order', 'manage_options', 'mage-partial', array($this, 'partial_order_screen'), 1);

            do_action('wcpp_partial_payment_menu');

            // add_submenu_page('mage-partial', 'Reminder Log', 'Reminder Log', 'manage_options', 'mage-reminder-log', array($this, 'reminder_log_screen'), 3);
            do_action('wcpp_reminder_log_menu');

            add_submenu_page('mage-partial', 'Setting', 'Setting', 'manage_options', 'admin.php?page=wc-settings&tab=settings_tab_mage_partial', null, 4);
        }

        public function partial_order_screen()
        {
            echo '<h1 class="mepp-page-heading">' . __('Partial Order', 'advanced-partial-payment-or-deposit-for-woocommerce') . '</h1>'; // Page Heading

            $partial_orders = $this->partial_order_query(); // Data Query

            $this->partial_order_filter(); // Data Filter

            // Data Output
            ?>
            <div class="mepp-table-container">
                <?php wcpp_get_partial_order_data($partial_orders) ?>
            </div>

            <?php
            mep_modal_html();
        }

        public function partial_order_query()
        {
            $args = array(
                'post_type' => 'shop_order',
                'posts_per_page' => -1,
                'orderby' => 'ID',
                'order' => 'desc',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_pp_deposit_system',
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key' => 'due_payment',
                        'value' => 0,
                        'compare' => '>',
                    )
                )
            );

            $data = new WP_Query($args);

            wp_reset_postdata();

            return $data->found_posts > 0 ? $data : null;
        }

        public function partial_order_filter()
        {
            ?>

            <div class="mepp-filter-container">
                <form action="">
                    <div class="mepp-form-inner">
                        <div class="mepp-form-group">
                            <label for="filter_order_id"><?php _e('Order No', 'advanced-partial-payment-or-deposit-for-woocommerce') ?></label>
                            <input type="text" id="filter_order_id" data-filter-type="order_id" placeholder="#0001">
                        </div>
                        <div class="mepp-form-group">
                            <label for="filter_deposit_type"><?php _e('Deposit Type', 'advanced-partial-payment-or-deposit-for-woocommerce') ?></label>
                            <select name="" id="filter_deposit_type" data-filter-type="deposit_type">
                                <option value=""><?php _e('Select Deposit Type', 'advanced-partial-payment-or-deposit-for-woocommerce') ?></option>
                                <option value="percent"><?php _e('Percentage of Amount', 'advanced-partial-payment-or-deposit-for-woocommerce') ?></option>
                                <option value="fixed"><?php _e('Fixed Amount', 'advanced-partial-payment-or-deposit-for-woocommerce') ?></option>
                                <option value="minimum_amount"><?php _e('Minimum Amount', 'advanced-partial-payment-or-deposit-for-woocommerce') ?></option>
                                <option value="payment_plan"><?php _e('Payment Plan', 'advanced-partial-payment-or-deposit-for-woocommerce') ?></option>
                            </select>
                        </div>
                        <div class="mepp-form-group wcpp-filter-loader">
                            <img src="<?php echo WCPP_PLUGIN_URL . 'asset/img/wcpp-loader.gif' ?>" alt="">
                        </div>
                    </div>
                </form>
            </div>

            <?php
        }


    }

    new Mepp_Admin_Menu;
}