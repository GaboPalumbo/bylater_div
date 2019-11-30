jQuery(function($) {
    function initLpcModal() {
        $('[data-lpc-template]').on('click', function(e) {
            e.preventDefault();

            $(this).WCBackboneModal({
                template: $(this).attr('data-lpc-template')
            });

            if ($(this).is('[data-lpc-callback]')) {
                window[$(this).attr('data-lpc-callback')]();
            }
        });
    }

    initLpcModal();
    window.initLpcModal = initLpcModal;
});
