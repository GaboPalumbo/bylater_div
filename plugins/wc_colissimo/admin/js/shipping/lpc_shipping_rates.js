jQuery(function($){
    $('#lpc_shipping_rates_add').click(function(){
        var newRowId = $('tr').length;

        var newRow = $('<tr>')
            .append($('<td class="check-column"><input type="checkbox" /></td>'))
            .append($('<td style="text-align: right;"><input name="shipping_rates[' + newRowId + '][weight]"/></td>'))
            .append($('<td style="text-align: right;"><input name="shipping_rates[' + newRowId + '][price]"/></td>'));

        $(this).closest('table').children('tbody').append(newRow);

        if (!newRow.prev().hasClass('alternate')) {
            newRow.addClass('alternate');
        }
    });

    $('#lpc_shipping_rates_remove').click(function(){
        if (confirm(window.lpc_i18n_delete_selected_rate)) {
            $('table.shippingrows tbody input:checked').closest('tr').remove();
            $('table.shippingrows input:checked').prop('checked', false);
        }
    });

    $('[id$="_use_cart_price"]').click(function(target){
        if (target.target.checked) {
            $('#lpc_shipping_rates_title_weight').addClass('lpc_shipping_rates_title_hide');
            $('#lpc_shipping_rates_title_price').removeClass('lpc_shipping_rates_title_hide');
        } else {
            $('#lpc_shipping_rates_title_weight').removeClass('lpc_shipping_rates_title_hide');
            $('#lpc_shipping_rates_title_price').addClass('lpc_shipping_rates_title_hide');
        }
    });
});
