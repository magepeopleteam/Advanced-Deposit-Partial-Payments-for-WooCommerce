(function ($) {
    'use strict';

    $(document).ready(function () {
        $('#_mep_pp_deposits_type[name="_mep_pp_deposits_type"]').trigger('change');

    });
    $(document).on('change', 'div.tab-content #_mep_pp_deposits_type[name="_mep_pp_deposits_type"] ', function (e) {
        e.preventDefault();
        let value=$(this).val();
        let parent=$(this).closest('table');
        let depositTarget=parent.find('[name="_mep_pp_deposits_value"]');
        let paymentTarget=parent.find('[name="_mep_pp_payment_plan[]"]');
        let customTarget=parent.find('[name="_mep_pp_minimum_value"]');
        if(value==='percent' || value==='fixed'){
            depositTarget.closest('tr').slideDown(250);
            paymentTarget.closest('tr').slideUp(250);
            customTarget.closest('tr').slideUp(250);
        } else if(value==='payment_plan'){
            depositTarget.closest('tr').slideUp(250);
            customTarget.closest('tr').slideUp(250);
            paymentTarget.closest('tr').slideDown(250);
        }else{
            depositTarget.closest('tr').slideUp(250);
            paymentTarget.closest('tr').slideUp(250);
            customTarget.closest('tr').slideDown(250);
        }
    });
    $(document).on('change', '#woo_desposits_options [name="_mep_pp_deposits_type"] ', function (e) {
        e.preventDefault();
        let value=$(this).val();
        let parent=$(this).closest('#woo_desposits_options');
        let depositTarget=parent.find('[name="_mep_pp_deposits_value"]');
        let paymentTarget=parent.find('[name="_mep_pp_payment_plan[]"]');
        let customTarget=parent.find('[name="_mep_pp_minimum_value"]');
        if(value==='percent' || value==='fixed'){
            depositTarget.closest('p').slideDown(250);
            paymentTarget.closest('p').slideUp(250);
            customTarget.closest('p').slideUp(250);
        } else if(value==='payment_plan'){
            depositTarget.closest('p').slideUp(250);
            customTarget.closest('p').slideUp(250);
            paymentTarget.closest('p').slideDown(250);
        }else{
            depositTarget.closest('p').slideUp(250);
            paymentTarget.closest('p').slideUp(250);
            customTarget.closest('p').slideDown(250);
        }
    })

})(jQuery);

// Other code using $ as an alias to the other library