(function ($) {
    'use strict';
    $(document).ready(function () {
        let parent = $('form.cart');
        parent.find('div.quantity [name="quantity"]').trigger('change');

        // Initail deposit type check in setting
        const deposit_type_selected = $('input[name="_pp_deposit_system"]:checked').attr('data-deposit-type');
        if (deposit_type_selected === 'payment_plan') {
            $('.mepp-payment-plan-option-frontend').show();
        } else {
            $('.mepp-payment-plan-option-frontend').hide();
        }
    });
    $(document).on('change', '#mage_event_submit [name="option_qty[]"],#mage_event_submit [name="event_extra_service_qty[]"]', function () {
        mpwemapp_price_calculation();
    });
    $(document).on('change', 'form.cart [name="variation_id"]', function () {
        let parent = $('form.cart');
        parent.find('div.quantity [name="quantity"]').trigger('change');
    });
    $(document).on('change', 'form.cart div.quantity [name="quantity"]', function () {
        let parent = $(this).closest('form.cart');
        let qty = parseInt($(this).val());
        let total_price = 0;
        let variation_id = parseInt(parent.find('[name="variation_id"]').val());
        if (parent.find('[name="variation_id"]').length > 0 && variation_id > 0) {
            let product = parent.data('product_variations');

            product.forEach(function (item) {
                if (parseInt(item.variation_id) === variation_id) {
                    total_price = qty * parseFloat(item.display_price);
                }
            });
        } else {
            let price = parseFloat(parent.find('.mep-product-payment-plans').data('total-price'));
            total_price = price * qty;
        }
        mpwemapp_payment_schedule(parent, total_price);
    });
    $(document).on('click', 'form.cart .minus,form.cart .plus', function () {
        let parent = $(this).closest('form.cart');
        parent.find('div.quantity [name="quantity"]').trigger('change');
    });
    $(document).on('click', '#mep_pp_partial_payment', function () {
        $('.mep-product-payment-plans').slideDown(200);
    });
    $(document).on('click', '#mep_pp_full_payment', function () {
        $('.mep-product-payment-plans').slideUp(200);
    });
    $(document).on('click', '.mep-single-plan-wrap label', function () {
        let parent = $(this).closest('.mep-product-payment-plans');
        parent.find('.mep-single-plan.plan-details').each(function () {
            $(this).slideUp(200);
            $(this).prev('.mep-pp-show-detail').html('View Details');
        });
    });
    $(document).on('click', '.mep-pp-show-detail', function (e) {
        e.preventDefault();
        let $this = $(this);
        let parent = $(this).closest('.mep-product-payment-plans');
        let next = $this.next('.mep-single-plan.plan-details');
        next.addClass('active');
        parent.find('.mep-single-plan.plan-details').each(function () {
            if (!$(this).hasClass('active')) {
                $(this).slideUp(200);
                $(this).prev('.mep-pp-show-detail').html('View Details');
            }
        }).promise().done(function () {
            if (next.is(':hidden')) {
                next.slideDown(200).removeClass('active');
                $this.html('Hide Details');
            } else {
                next.slideUp(200).removeClass('active');
                $this.html('View Details');
            }

        });
    });

    $(document).ready(function () {
        /*jQuery firing on add to cart button click*/
        jQuery('.ajax_add_to_cart').click(function () {
            /*Product meta data being sent; this is where you'd do your 
            document.getElementsByName or .getElementById*/

            var product_meta_data = 'product-meta-data',
                product_id = this.getAttribute('data-product_id');

            /*Ajax request URL being stored*/
            jQuery.ajax({
                url: deposits_params.ajax_url,
                type: "POST",
                data: {
                    //action name (must be consistent with your php callback)
                    action: 'deposit_pro_cart_btn',
                    product_id: product_id,
                    nonce: deposits_params.ajax_nonce
                },
                async: false,
                success: function (data) {

                }
            });
        })

        // On checkout page
        var pay_input = null;
        $(document).on('keyup', 'input[name="manually_pay_amount"]', function () {
            let $this = $(this)
            let total = $this.attr('data-total');
            let pay = $this.val();
            let max = $this.attr('max');
            let due = total - pay;
            let currency = $(this).attr('data-currency');
            let deposit_type = $this.attr('data-deposit-type');

            let validate = true;

            if(max) {
                max = parseFloat(max);
                pay = parseFloat(pay);

                if(max < pay) {
                    validate = false;
                    $this.val('');
                    console.log(due);
                }
            }

            if(validate) {
                pay_input = setTimeout(async function () {

                    let res = pay;
                    if (deposit_type == 'minimum_amount') {
                        res = await mepMinAmountValidation($this, deposit_type, pay);
                        $this.val(res);
                    }
    
                    if (res) {
                        jQuery.ajax({
                            url: php_vars.ajaxurl,
                            type: "POST",
                            dataType: 'json',
                            async: true,
                            data: {
                                //action name (must be consistent with your php callback)
                                action: 'manually_pay_amount_input',
                                total: total,
                                pay: res,
                            },
                            success: function (data) {
                                if (data) {
                                    $this.parents('tr').next().find('td').html(data.with_symbol);
                                    $this.parents('tr').next().find('td').append('<input type="hidden" name="manually_due_amount" value="' + data.amount + '" />')
                                }
    
                            }
                        })
                    } else {
                        $this.parents('tr').next().find('td').html(currency + due);
                        $this.parents('tr').next().find('td').append('<input type="hidden" name="manually_due_amount" value="' + due + '" />')
                    }
                    pay_input = null;
                }, 1000);
            }


        });

        // Minimum deposit value restric
        $(document).on('keyup', 'input[name="user-deposit-amount"]', function () {
            const $this = $(this);
            let val = $this.val();
            const deposit_type = $this.attr('data-deposit-type');

            const call = async function () {
                if (deposit_type == 'minimum_amount') {
                    const res = await mepMinAmountValidation($this, deposit_type, val);
                    $this.val(res);
                }
            }

            call();

        });

        // Deposit Type switch in checkout page
        $(document).on('change', 'input[name="_pp_deposit_system"]', function () {
            const value = $(this).val();
            const deposit_type = $(this).attr('data-deposit-type');

            if (deposit_type === 'payment_plan') {
                $('.mepp-payment-plan-option-frontend').show();
                return 0;
            } else {
                $("#mepp_default_payment_plan").val($("#mepp_default_payment_plan option:first").val());
                $('.mepp-payment-plan-option-frontend').hide();
            }

            if (value) {
                mepRequestSwitchPaymentType(value);
            }
        })

        $(document).on('change', 'select[id="mepp_default_payment_plan"]', function () {
            const selected_value = $("option:selected", this).val();
            if (selected_value) {
                mepRequestSwitchPaymentType('check_pp_deposit', selected_value);
            }
        });
    });

    function mepRequestSwitchPaymentType(payment_type, payment_plan_id = '') {
        jQuery.ajax({
            url: php_vars.ajaxurl,
            type: "POST",
            data: {
                action: 'wcpp_deposit_type_switch_frontend',
                payment_type,
                payment_plan_id
            },
            success: function (data) {
                jQuery('body').trigger('update_checkout'); //  for checkout page
                // For cart page
                const cart_update_btn = jQuery('button[name="update_cart"]');
                cart_update_btn.removeAttr('disabled');
                cart_update_btn.attr('aria-disabled', 'false');
                cart_update_btn.trigger('click');
            }
        })
    }

    function mepMinAmountValidation($this, deposit_type, val) {
        let returnValue = '';
        return new Promise(function (resolve) {
            setTimeout(function () {
                const min_val = $this.attr('min');

                if (min_val) {
                    if (parseFloat(min_val) > parseFloat(val)) {
                        returnValue = min_val;
                    } else {
                        returnValue = val;
                    }
                } else {
                    returnValue = min_val;
                }

                resolve(returnValue);
            }, 100)
        })
    }

    function mpwemapp_price_calculation() {
        let target = $('body #mage_event_submit');
        let price = 0;
        let deposit_type = target.find('[name="payment_plan"]').val();
        target.find('.price_jq').each(function () {
            let current_qty = parseInt($(this).closest('tr').find('[name="option_qty[]"],[name="event_extra_service_qty[]"]').val());
            current_qty = current_qty > 0 ? current_qty : 0;
            let current_price = 0;
            if (deposit_type === 'ticket_type') {
                current_price = parseFloat($(this).closest('tr').find('[name="option_price_pp[]"]').val());
            } else {
                current_price = parseFloat($(this).html());
            }
            current_price = current_price > 0 ? current_price : 0;

            price += current_price * current_qty;

        }).promise().done(function () {
            mpwemapp_payment_schedule(target, price);
        });
    }

    function mpwemapp_payment_schedule(target, price) {
        let deposit_type = target.find('[name="payment_plan"]').val();
        if (deposit_type === 'payment_plan') {
            target.find('.total_pp_price').each(function () {
                //alert(price);
                let total_payment = parseFloat($(this).data('total-percent'));
                let total_price = total_payment * price / 100;
                //alert(price);
                $(this).html(mp_event_wo_commerce_price_format(total_price));

                let current_deposit = $(this).closest('.plan-details').find('.total_deposit');
                let down_payment = parseFloat(current_deposit.data('deposit'));
                let down_pay = down_payment * total_price / 100;
                //alert(down_pay);
                current_deposit.html(mp_event_wo_commerce_price_format(down_pay));
                $(this).closest('.plan-details').find('[data-payment-plan]').each(function () {
                    let plan = parseFloat($(this).data('payment-plan'));
                    let plan_price = plan * price / 100;
                    $(this).html(mp_event_wo_commerce_price_format(plan_price));
                });

            });
        }
        if (deposit_type === 'percent') {
            let percent = parseFloat(target.find('[name="payment_plan"]').data('percent'));
            price = price * percent / 100;
            target.find('.payment_amount').html(mp_event_wo_commerce_price_format(price));
        }
        if (deposit_type === 'ticket_type') {
            target.find('#mep_pp_ticket_type_partial_total').html(mp_event_wo_commerce_price_format(price));
            target.find('.mep-pp-user-amountinput').val(price);
        }
        if (deposit_type === 'fixed') {
            price = target.find('[name="payment_plan"]').data('percent');
            $('div.mep-pp-payment-btn-wraper').slideDown(200);
            target.find('.payment_amount').html(mp_event_wo_commerce_price_format(price));
        }
        if (price > 0) {
            $('div.mep-pp-payment-btn-wraper').slideDown(200);
        }
        // else {
        //     //$('div.mep-pp-payment-btn-wraper').slideUp(200);
        // }
    }

    function mp_event_wo_commerce_price_format(price) {
        let currency_position = jQuery('input[name="currency_position"]').val();
        let currency_symbol = jQuery('input[name="currency_symbol"]').val();
        let currency_decimal = jQuery('input[name="currency_decimal"]').val();
        let currency_thousands_separator = jQuery('input[name="currency_thousands_separator"]').val();
        let currency_number_of_decimal = jQuery('input[name="currency_number_of_decimal"]').val();
        let price_text = '';

        price = price.toFixed(currency_number_of_decimal);
        // console.log('price= '+ price);
        let total_part = price.toString().split(".");
        total_part[0] = total_part[0].replace(/\B(?=(\d{3})+(?!\d))/g, currency_thousands_separator);
        price = total_part.join(currency_decimal);

        if (currency_position === 'right') {
            price_text = price + currency_symbol;
        } else if (currency_position === 'right_space') {
            price_text = price + '&nbsp;' + currency_symbol;
        } else if (currency_position === 'left') {
            price_text = currency_symbol + price;
        } else {
            price_text = currency_symbol + '&nbsp;' + price;
        }
        // console.log('price= '+ price_text);
        return price_text;
    }

})(jQuery);

// Other code using $ as an alias to the other library