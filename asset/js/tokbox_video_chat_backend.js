jQuery(document).ready(function($){
    if(jQuery('.datepicker').length){
        var cardate = new Date();
        $('.datepicker').datetimepicker({
            format: 'M d, Y H:i:s',
            formatTime: 'H:i:s',
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



    if(jQuery('.jquerydatatable').length){
        jQuery('.jquerydatatable').DataTable();
    }
    jQuery(document).on('click', ".tokbox_video_chat-accordion", function($){

        var panel = this.nextElementSibling;
        if (panel.style.display === "block") {
          panel.style.display = "none";

          jQuery(this).removeClass( "tokbox_video_chat-accordion-true" ).addClass( "tokbox_video_chat-accordion-false" );

        } else {
          panel.style.display = "block";

          jQuery(this).removeClass( "tokbox_video_chat-accordion-false" ).addClass( "tokbox_video_chat-accordion-true" );

        }
    });
    /*
    * Settings form submit via ajax
    */
   jQuery(document.body).on('submit', 'form#tokbox_video_chat_settings_submit_form', function(e){
    e.preventDefault();
    jQuery('.tokbox-video-chat-loder').fadeIn();
    var formVar = $(this).serializeArray();

    console.log(formVar);
    jQuery.ajax({
        type : 'post',
        dataType: 'json',
        data : {
            'formVar'             : formVar,
            'action'              : 'tokbox_video_chatsettingssaveajax' 
         },
         url : tockboxAjax,
         success:function(data){
             console.log(data);
                if(data.message == 'success'){
                    jQuery('.tokbox-video-chat-loder').fadeOut();
                }
             }
        });
    }); // End settings form submit
});
