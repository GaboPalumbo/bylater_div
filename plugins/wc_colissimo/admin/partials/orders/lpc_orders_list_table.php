<?php
$lpc_orders_table = (isset($args['table'])) ? $args['table'] : array();
$get_args         = (isset($args['get'])) ? $args['get'] : array();
wp_nonce_field('wc_colissimo_view');
?>
<div class="wrap">
	<?php
	$lpc_orders_table->prepare_items($get_args);
	$lpc_orders_table->displayHeaders();
	?>
	<iframe type="application/pdf" src=""
			width="100%" style="display:none;" height="100%" id="lpcPrintIframe">
	</iframe>
	<form method="get">
		<?php
		if (isset($_REQUEST['page'])) {
			?>
			<input type="hidden" name="page" value="<?= esc_attr(sanitize_text_field(wp_unslash($_REQUEST['page']))); ?>"/>
			<?php
		}
		$lpc_orders_table->search_box('search', 'search_id');
		$lpc_orders_table->display();
		?>
	</form>
	<script type="text/Javascript">
        jQuery(function($) {
            $('.lpc_unit_actions').change(function(event) {
                if (event.target.id != undefined && event.target.id != '') {
                    var $optionSelected = $('#' + event.target.id + ' option:selected');
                    var specificAction = $optionSelected.attr('data-action');

                    var orderId = $('#' + event.target.id).attr('data-orderid');
                    lpc_handleSpin(orderId, true);

                    if ($optionSelected.attr('data-type') == 'link') {
                        if (specificAction != undefined && specificAction != '') {
                            location.href = specificAction;
                            lpc_handleSpin(orderId, false);
                        }
                    } else {
                        var ePdf = document.getElementById('lpcPrintIframe');
                        if (ePdf && ePdf.tagName === 'IFRAME') {
                            ePdf.src = specificAction;
                            ePdf.onload = function() {
                                ePdf.contentWindow.focus();
                                ePdf.contentWindow.print();
                                lpc_handleSpin(orderId, false);
                            }
                        }
                    }
                    $(event.target).val('choose');
                }
            });

            function lpc_handleSpin(orderId, doSpin = false) {
                if (doSpin == true) {
                    $('#lpcspinner_' + orderId).show();
                    $('#unitAction_' + orderId).hide();
                } else {
                    $('#lpcspinner_' + orderId).hide();
                    $('#unitAction_' + orderId).show();
                }
            }
        });


	</script>
</div>
