jQuery(document).ready(function($){
    // Create Video on join button 
    jQuery(document).on('click', 'button.vjoinButton', function(){
        var apikey = tockbox.tockbox_api;
        var sessionId = jQuery(this).closest('div.single-session').data('sessionid');
        var token = jQuery(this).closest('div.single-session').data('token');

        // (optional) add server code here
        initializeSession(apikey, sessionId, token);
        
    });


    
});


// Handling all of our errors here by alerting them
function handleError(error) {
    if (error) {
      alert(error.message);
    }
  }

  function reduseUserCredit(){
    jQuery.ajax({
        method: 'POST',
        dataType: "json",
        url: tockbox.ajax_url,
        data: {
            action: 'reduseUserCreditWhileSessionOn', 
            userid: tockbox.wp_user
        },
        success: function (result) {
            // console.log(result);
        }
    });
}
  
function initializeSession(apiKey, sessionId, token) {
    var session = OT.initSession(apiKey, sessionId);
  
    // Subscribe to a newly created stream
    session.on('streamCreated', function(event) {
        session.subscribe(event.stream, 'subscriber', {
        insertMode: 'append',
        width: '100%',
        height: '100%'
        }, handleError);
    });
  
    // Create a publisher
    var publisher = OT.initPublisher('publisher', {
      insertMode: 'append',
      width: '100%',
      height: '100%'
    }, handleError);
  
    // Connect to the session
    session.connect(token, function(error) {
      // If the connection is successful, publish to the session
      if (error) {
        handleError(error);
      } else {
        session.publish(publisher, handleError);
      }

    setInterval(function() {
       reduseUserCredit();
    }, 30 * 1000); // 60 * 1000 milsec
    

      
    });



}