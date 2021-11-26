<?php
if (!defined('ABSPATH')) {
    die;
}

add_action('init', 'mep_pp_register_payment_post_type');
add_action('init', 'mep_pp_register_order_status');
if (!function_exists('mep_pp_register_payment_post_type')) {
function mep_pp_register_payment_post_type()
{
    wc_register_order_type(
        'wcpp_payment',
        array(            
            'labels'            => array(
                'name'          => esc_html__('Partial Payments', 'advanced-partial-payment-or-deposit-for-woocommerce'),
                'singular_name' => esc_html__('Partial Payment', 'advanced-partial-payment-or-deposit-for-woocommerce'),
                'edit_item'     => esc_html_x('Edit Partial Payment', 'custom post type setting', 'advanced-partial-payment-or-deposit-for-woocommerce'),
                'search_items'  => esc_html__('Search Partial Payments', 'advanced-partial-payment-or-deposit-for-woocommerce'),
                'parent'        => esc_html_x('Order', 'custom post type setting', 'advanced-partial-payment-or-deposit-for-woocommerce'),
                'menu_name'     => esc_html__('Partial Payments', 'advanced-partial-payment-or-deposit-for-woocommerce'),
            ),
            'public'            => false,
            'show_ui'           => false,
            'capability_type'   => 'shop_order',
            'capabilities'      => array(
                'create_posts'  => 'do_not_allow',
            ),
            'map_meta_cap'      => true,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_in_menu'      =>  'woocommerce',
            'hierarchical'      => false,
            'show_in_nav_menus' => false,
            'rewrite'           => false,
            'query_var'         => false,
            'supports'          => array('title', 'comments', 'custom-fields'),
            'has_archive'       => false,

            // wc_register_order_type() params
            'exclude_from_orders_screen'    => true,
            'add_order_meta_boxes'          => true,
            'exclude_from_order_count'      => true,
            'exclude_from_order_views'      => true,
            'exclude_from_order_webhooks'   => true,
            'exclude_from_order_reports'    => true,
            'exclude_from_order_sales_reports' => true,
            'class_name'                    => 'WCPP_Payment',
        )

    );
}
}

if (!function_exists('mep_pp_register_order_status')) {
function mep_pp_register_order_status()
{
    register_post_status('wcpp-partially-paid', array(
        'label'                     => _x('Partially Paid', 'Order status', 'advanced-partial-payment-or-deposit-for-woocommerce'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Partially Paid <span class="count">(%s)</span>',
            'Partially Paid <span class="count">(%s)</span>',
            'advanced-partial-payment-or-deposit-for-woocommerce'
        )
    ));
}
}