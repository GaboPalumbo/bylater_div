jQuery(function($) {
    function applyRelayPointInfoToShippingAddress(point) {
        $('#ship-to-different-address-checkbox')
            .attr('checked', true)
            .trigger('change');
        $('#shipping_company').val(point.nom);
        $('#shipping_address_1').val(point.adresse1);
        $('#shipping_address_2').val(point.adresse2);
        $('#shipping_postcode').val(point.codePostal);
        $('#shipping_city').val(point.localite);
        $('#shipping_first_name').val($('#billing_first_name').val());
        $('#shipping_last_name').val($('#billing_last_name').val());

        $('#shipping_country')
            .val(point.codePays)
            .trigger('change');
    }

    function preventOrderValidation() {
        var nbInput = $('ul#shipping_method li input').length;
        var inputRelay = $('ul#shipping_method input[id$="_lpc_relay"]')[0];
        if (inputRelay != undefined && (inputRelay.checked || inputRelay.type =='hidden' || nbInput == 1) && !$('#lpc_pick_up_info').data('pickup-id')) {
            $('#payment .place-order')
                .addClass('processing')
                .block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6,
                    },
                });
        } else {
            $('#payment .place-order')
                .removeClass('processing')
                .unblock();
        }
    }

    $(document.body).on('updated_checkout', function() {
        preventOrderValidation();
    });

    window.lpc_pickup_applyRelayPointInfoToShippingAddress = applyRelayPointInfoToShippingAddress;
});
