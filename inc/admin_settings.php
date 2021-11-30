<?php
if (!defined('ABSPATH')) {die;}

class WC_Settings_Tab_Mage_Partial_Basic
{

    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public static function init()
    {
        add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
        add_action('woocommerce_settings_tabs_settings_tab_mage_partial', __CLASS__ . '::settings_tab');
        add_action('woocommerce_update_options_settings_tab_mage_partial', __CLASS__ . '::update_settings');
    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab($settings_tabs)
    {
        $settings_tabs['settings_tab_mage_partial'] = __('Partial/Deposit', 'woocommerce-settings-tab-demo');
        return $settings_tabs;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function settings_tab()
    {
        woocommerce_admin_fields(self::get_settings());
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings()
    {
        woocommerce_update_options(self::get_settings());
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings()
    {

        $settings = array(
            'section_title' => array(
                'name' => __('Partial/Deposit Settings', 'woocommerce-settings-tab-demo'),
                'type' => 'title',
                'desc' => '',
                'id' => 'wc_settings_tab_mage_partial_section_title',
            ),
            'mepp_label_pay_deposit' => array(
                'name' => __('Label for text: Pay Deposit', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Pay Deposit',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_pay_deposit',
            ),

            'mepp_label_full_payment' => array(
                'name' => __('Label for text: Full Payment', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Full Payment',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_full_payment',
            ),

            'mepp_label_payment_total' => array(
                'name' => __('Label for text: Payments Total', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Payments Total',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_payment_total',
            ),

            'mepp_label_deposit' => array(
                'name' => __('Label for text: Deposit', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Deposit',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_deposit',
            ),

            'mepp_label_due_amount' => array(
                'name' => __('Label for text: Due Amount', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Due Amount',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_due_amount',
            ),

            'mepp_label_partially_paid' => array(
                'name' => __('Label for text: Partially Paid', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Partially Paid',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_partially_paid',
            ),

            'mepp_label_due_payment' => array(
                'name' => __('Label for text: Due Payment', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Due Payment',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_due_payment',
            ),

            'mepp_label_deposit_type' => array(
                'name' => __('Label for text: Deposit Type', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Deposit Type',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_deposit_type',
            ),
            'mepp_label_to_pay' => array(
                'name' => __('Label for text: To Pay', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'To Pay',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_to_pay',
            ),
            'mepp_label_pay_due_payment' => array(
                'name' => __('Label for text: Pay Due Payment', 'woocommerce-settings-tab-demo'),
                'type' => 'text',
                'default' => 'Pay Due Payment',
                'desc' => __('', 'woocommerce-settings-tab-demo'),
                'id' => 'mepp_text_translation_string_pay_due_payment',
            ),
            'mepp_allow_regular_and_deposit_product_in_cart' => array(
                'name' => __( 'Allow Regular product and Partial product in Cart', 'woocommerce-settings-tab-demo' ),
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-settings-tab-demo'),
                    'yes' => __('Yes', 'woocommerce-settings-tab-demo'),
                ),
                'default' => 'yes',
                'desc' => __( 'Allow Regular product and Partial product in Cart?', 'woocommerce-settings-tab-demo' ),
                'id'   => 'meppp_allow_regular_and_deposit_product_in_cart'
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_mage_partial_section_end',
            ),
        );

        return apply_filters('wc_settings_tab_mage_partial_settings', $settings);
    }
}

WC_Settings_Tab_Mage_Partial_Basic::init();
