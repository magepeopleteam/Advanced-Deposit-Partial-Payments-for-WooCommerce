<?php
if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
/**
 * @package WCPP_Plugin
 */

class WCPP_Welcome
{
  public function __construct(){
    add_action("admin_menu",array($this,"WCPP_welcome_init"));
  }
  
  public function WCPP_welcome_init(){
    add_submenu_page(
        'mage-partial',
        __( 'Welcome to WCPP', 'advanced-partial-payment-or-deposit-for-woocommerce' ),
        '<span style="color:#13df13">'.__( 'Welcome', 'advanced-partial-payment-or-deposit-for-woocommerce' ).'</span>',
        'manage_options',
        'wcpp_welcome',
        array($this,"WCPP_welcome_page_callback")
    );
  } 

  public function WCPP_welcome_page_callback(){
    $pro_badge = '<span class="badge">'.__( "PRO", "advanced-partial-payment-or-deposit-for-woocommerce" ).'</span>';
    $arr = array( 'strong' => array() );
    ?>
    <div class="wrap wcpp_welcome_wrap">
    <?php settings_errors(); ?>
        <h1><?php _e( 'Welcome to Advanced Partial/Deposit Payment For Woocommerce', 'advanced-partial-payment-or-deposit-for-woocommerce' ); ?></h1>
            <ul class="tabs">
                <li class="tab-link current" data-tab="tab-1"><?php _e( 'Basic', 'advanced-partial-payment-or-deposit-for-woocommerce' ); ?></li>
                <li class="tab-link" data-tab="tab-2"><?php _e( 'Pro version', 'advanced-partial-payment-or-deposit-for-woocommerce' ); ?></li>
            </ul>
            <!-- Start Tab One Content -->
            <div id="tab-1" class="tab-content current">
            <h1><?php _e( 'Welcome to Advanced Partial/Deposit Payment For Woocommerce Plugin Guideline', 'advanced-partial-payment-or-deposit-for-woocommerce' ); ?></h1>
            <p><?php echo wp_kses('To Enable Partial Payment to WooCommerce product just simple edit or add a Product, then you will see a new menu <strong>deposit</strong>.', $arr); ?></p>
            <p><?php _e('Here you can enable deposit add deposit type and enter deposit amount.', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></p>
            <p><img src="<?php echo esc_url(plugins_url('advanced-partial-payment-or-deposit-for-woocommerce') . "/asset/img/wcpp-documentation-1.jpg"); ?>" alt="<?php esc_attr_e('Documentation','advanced-partial-payment-or-deposit-for-woocommerce'); ?>"></p>
            <p><?php echo wp_kses('Partial Payment settings:  <strong>Partial payment -> Setting</strong>.', $arr); ?></p>
            <p><?php _e('Here any label change or translation text can be added.', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></p>
            <p><?php _e('Partial Order: Here all partial order will be listed with history.', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></p>
            <h1><?php _e( 'Video Tutorial', 'advanced-partial-payment-or-deposit-for-woocommerce' ); ?></h1>
            <iframe width="709" height="399" src="<?php echo esc_url('https://www.youtube.com/embed/TIg2fawS6AY'); ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
             <!-- End Start Tab One Content -->

             <!-- Start Tab Two Content --> 
            <div id="tab-2" class="tab-content">
            <h1><?php _e( 'Welcome to Advanced Partial/Deposit Payment For Woocommerce PRO Plugin Guideline', 'advanced-partial-payment-or-deposit-for-woocommerce' ); ?></h1>
            <a href="<?php echo esc_url('https://mage-people.com/product/advanced-deposit-partial-payment-for-woocommerce-pro/'); ?>" class="wcpp-top-pro-btn"><?php _e( 'BUY PRO', 'advanced-partial-payment-or-deposit-for-woocommerce' ); ?></a>    
            <p><strong><?php _e('New deposit type will be added', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></strong></p>
            <ul>
                <li><p><strong><?php _e('Custom amount: ', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></strong><?php _e('It can be enabled with minimum custom amount, customer can by paying any amount for partial payment order.', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></p></li>
                <li><p><strong><?php _e('Payment Plan: ', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></strong><?php _e('Payment can be done by monthly week, or yearly or any duration. It is mainly will work as installment payment.', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></p></li>
            </ul>
            <p><strong><?php _e('Partial Payment Reminder: ', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></strong><?php _e('In pro version there is a reminder system. Admin can setup when payment reminder will be sent and reminder history log will be added.', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></p>
            <p><strong><?php _e('Allow customer to pay partial in cart or checkout section: ', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></strong><?php _e('This is mostly unique feature admin can enable option to pay any amount from cart page directly to confirm booking.', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></p>
            <p><strong><?php _e('Stock Reduce option: ', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></strong><?php _e('Admin can set when stock will be reduced.', 'advanced-partial-payment-or-deposit-for-woocommerce'); ?></p>
            <p><?php echo wp_kses('<strong>Setup Allowed payment method</strong> and <strong>setup allowed user group</strong> for partial payment. Both are pro feature.', $arr); ?></p>
            </div>
            <!-- End Tab Two Content --> 
    </div>
    <style>
        .wcpp_welcome_wrap h1{
            margin-bottom: 20px;           
        }
        .wcpp_welcome_wrap #tab-1 h1{
            display:block !important;
        }
        .wcpp_welcome_wrap ul.tabs{
			margin: 0px;
			padding: 0px;
			list-style: none;
		}
		.wcpp_welcome_wrap ul.tabs li{
			background: #DBDBDB;
			color: #222;
			display: inline-block;
			padding: 10px 15px;
			cursor: pointer;
		}

		.wcpp_welcome_wrap ul.tabs li.current{
			background: #fff;
			color: #222;
		}

		.wcpp_welcome_wrap .tab-content{
			display: none;
			background: #fff;
			padding: 15px;
            position: relative;
		}

		.wcpp_welcome_wrap .tab-content.current{
			display: inherit;
		}
        .wcpp_welcome_wrap .wcpp-d-btn{
            color: #fff;
            background-color: #337ab7;
            border-color: #2e6da4;
            border-radius: 3px;            
            font-family: inherit;
            font-size: .875rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-block;
            line-height: 1.125rem;
            padding: .5rem 1rem;
            margin: 0;
            height: auto;
            border: 1px solid transparent;
            vertical-align: middle;
            -webkit-appearance: none;
            text-decoration:none;
            margin-right:10px;
        }
        .wcpp_welcome_wrap .wcpp-top-pro-btn{
            color: #fff;
            background: #f95656;
            border-color: #2e6da4;
            border-radius: 3px;            
            font-family: inherit;
            font-size: .875rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-block;
            line-height: 1.125rem;
            padding: .5rem 1rem;
            margin: 0;
            height: auto;
            border: 1px solid transparent;
            vertical-align: middle;
            -webkit-appearance: none;
            text-decoration:none;      
        }
        @media only screen and (min-width: 768px) {
            .wcpp_welcome_wrap .wcpp-top-pro-btn{
                position: absolute;
                right: 20px;
                top: 20px;
            }
        }        
        .wcpp_welcome_wrap .wcpp-d-btn:hover, .wcpp_welcome_wrap .wcpp-top-pro-btn:hover{
            color:#fff;
        }
        .wcpp_welcome_wrap p{
            margin-top:0;
        }
        .wcpp_welcome_wrap .badge{
            background: #f95656;
            padding: 0px 5px 0px 5px;
            border-radius: 5px;
            margin-left: 5px;
        }
        .wcpp_welcome_wrap .mt-10{
            margin-top: 10px;
        }

        .wcpp_welcome_wrap ul{
            padding-left: 25px;
        }
        .wcpp_welcome_wrap ul li{
            list-style-type: disc;
        }
    </style>
    <script>
    jQuery(document).ready(function(){
	
        jQuery('.wcpp_welcome_wrap ul.tabs li').click(function(){
		var tab_id = jQuery(this).attr('data-tab');

		jQuery('.wcpp_welcome_wrap ul.tabs li').removeClass('current');
		jQuery('.wcpp_welcome_wrap .tab-content').removeClass('current');

		jQuery(this).addClass('current');
		jQuery("#"+tab_id).addClass('current');
	    });

        jQuery('.wcpp_welcome_wrap ul.accordion .toggle').click(function(e) {
            e.preventDefault();
        
            var $this = jQuery(this);
        
            if ($this.next().hasClass('show')) {
                $this.next().removeClass('show');
                $this.removeClass('active');
                $this.next().slideUp(350);
            } else {
                $this.parent().parent().find('li .inner').removeClass('show');
                $this.parent().parent().find('li a').removeClass('active');
                $this.parent().parent().find('li .inner').slideUp(350);
                $this.next().toggleClass('show');
                $this.toggleClass('active');
                $this.next().slideToggle(350);
            }
        });   
    });
    </script>
    <?php
  }
}
new WCPP_Welcome();
