jQuery(document).ready(function($){
    if(jQuery('.datepicker').length){
        var cardate = new Date();
        $('.datepicker').datetimepicker({
            format: 'd/m/Y H:i',
            // formatTime: 'h:i a',
            minDate: 0,
            step: 15,
            startDate: new Date(),
            closeOnDateSelect: false,
            validateOnBlur: false,
            yearStart: cardate.getFullYear(),
            yearEnd: cardate.getFullYear() + 1,
            onShow: function (ct) {
            },
            onGenerate:function(ct,$i){
            }, 
            onChangeDateTime: function (dp, $input) {
            }
        });
    }

});