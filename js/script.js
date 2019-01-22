jQuery(document).ready(function (e) {
    /**
     * Disables dates with given dates.
     */
    function padjsDisableDates(input) {
        if (typeof jQuery(input).pickadate === 'function') {
            var $inputStart = jQuery(input).pickadate({
                editable: true,
                container: undefined,
                holder: 'picker__holder',
            });
            // pickerStart = $inputStart.pickadate("picker");
            var pickerStart = $inputStart.data('pickadate');
            var pickerStartItem = pickerStart.component.item;

            var today = new Date();
            var dd = today.getDate();
            var mm = today.getMonth(); //January is 0!
            var yyyy = today.getFullYear();

            pickerStart.set('disable', [{
                from: [0, 0, 0],
                to: [yyyy, mm, dd - 1]
            }]);

            for (var i = 0; i < padjsAdditionalData.length; i++) {
                var startDay = padjsAdditionalData[i].start[0];
                var startMonth = padjsAdditionalData[i].start[1];
                var startYear = padjsAdditionalData[i].start[2];

                var endDay = padjsAdditionalData[i].end[0];
                var endMonth = padjsAdditionalData[i].end[1];
                var endYear = padjsAdditionalData[i].end[2];

                pickerStart.set('disable', [{
                    from: [startYear, startMonth, startDay],
                    to: [endYear, endMonth, endDay]
                }]);
                jQuery(input).on('click', function (e) {
                    // stop the click from bubbling
                    e.stopPropagation();
                    // prevent the default click action
                    e.preventDefault();
                    // open the date picker
                    pickerStart.open(false);
                });
            }
        }
    }
    padjsDisableDates(".datepicker_start");
    padjsDisableDates(".datepicker_end");
    /**
     * Disables the Add to Cart button.
     */
    if (typeof $body !== 'undefined') {
        $body.on("update_price", function () {
            if (wceb.get.basePrice())
                $add_to_cart_button.prop("disabled", !1)
            else $add_to_cart_button.prop("disabled", 1)
        });
    }
});