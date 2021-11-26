<?php
if (!defined('ABSPATH')) {die;}

if ( !class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Deposits_View extends \WP_List_Table {

    public function __construct() {
        parent::__construct(
            array(
                'plural'   => 'Desposit Order',
                'singular' => 'Desposit Orders',
                'ajax'     => false,
            )
        );
    }
    /**
     * Prepares the list of items for displaying.
     */
    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $per_page     = 10;
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $args = array(
            'numberposts' => $per_page,
            'offset'      => $offset,
        );

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $args['orderby'] = sanitize_text_field( $_REQUEST['orderby'] );
            $args['order']   = sanitize_text_field( $_REQUEST['order'] );
        }

        $this->items = mep_pp_get_orders( $args );

        $this->set_pagination_args(
            array(
                'total_items' => mep_pp_count(),
                'per_page'    => $per_page,
            )
        );
    }

    /**
     * Get sortable columns
     * @return array
     */
    public function get_sortable_columns() {
        $s_columns = array(
            'date'    => ['date', false],
            'name'    => ['ID', false],
            'deposit' => ['meta_value_num', false],
        );
        return $s_columns;
    }

    /**
     * Gets a list of columns.
     * @return array
     */
    public function get_columns() {
        return array(

            'name'    => __( 'Desposit Order', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
            'date'    => __( 'Date', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
            'status'  => __( 'Status', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
            'deposit' => __( 'Deposit', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
            'due'     => __( 'Due', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
            'total'   => __( 'Total', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
        );
    }

    /**
     * @param  $item
     * @param  $column_name
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {

        case 'name':
        case 'date':
        case 'status':
        case 'deposit':
        case 'due':
        case 'total':

            return $item[$column_name];

            break;

        default:
            return isset( $item->$column_name ) ? $item->$column_name : '';
        }
    }

    /**
     * Render columm: order_name.
     */
    public function column_name( $item ) {
        $actions = array(
            'edit' => '<a href="' . admin_url( array( 'action' => 'edit', 'id' => $item['name'] ) ) . '">Edit</a>',

        );
        $order = wc_get_order( $item['name'] );

        return sprintf(
            '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $item['name'] ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $item['name'] ) . ' ' . esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) . '</strong></a>',
            $this->row_actions( $actions )
        );
    }

    /**
     * Render columm: order_date.
     */
    public function column_date( $item ) {

        // Check if the order was created within the last 24 hours, and not in the future.
        $order_timestamp = strtotime( $item['date'] );

        if ( $order_timestamp > strtotime( '-1 day', current_time( 'timestamp' ) ) && $order_timestamp <= current_time( 'timestamp' ) ) {
            $show_date = sprintf(
                /* translators: %s: human-readable time difference */
                _x( '%s ago', '%s = human-readable time difference', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
                human_time_diff( $order_timestamp, current_time( 'timestamp' ) )
            );
        } else {
            $show_date = date( 'M,j,Y', strtotime( $item['date'] ) );
        }
        return sprintf(
            '<time datetime="%1$s" title="%2$s">%3$s</time>',
            esc_attr( $show_date ),
            esc_html( $show_date ),
            esc_html( $show_date )
        );
    }
    /**
     * Render columm: order_status.
     */
    public function column_status( $item ) {
        $order        = wc_get_order( $item['name'] );
        $order_status = $order->get_status(); // order status

        return sprintf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $order_status ) ), esc_html( ucfirst( $order_status ) ) );
    }
}
