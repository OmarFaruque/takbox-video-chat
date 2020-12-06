jQuery(document).ready(function($){
  // document.getElementById("msgTxt").required = true;
  // if( tockbox.post_author_name == tockbox.user_name ){
  // document.getElementById("admin_set_name").required = true;
  // }
    // Create Video on join button 
    jQuery(document).on('click', 'button.vjoinButton', function(){
      var apikey = tockbox.tockbox_api;
      var sessionId = jQuery(this).closest('div.single-session').data('sessionid');
      var token = jQuery(this).closest('div.single-session').data('token');

      // (optional) add server code here
      initializeSession(apikey, sessionId, token);


        
      jQuery(".video_test").remove();
      jQuery(".vjoinButton").remove();
      jQuery(".endButton").css("display", "block");
      jQuery(".video-and-textchat-cover").css("display", "block");
    
    });


    // end Video on end button 
    jQuery(document).on('click', '.endButton', function(){
      var apikey = tockbox.tockbox_api;
      var sessionId = jQuery(this).closest('div.single-session').data('sessionid');
      var session = OT.initSession(apikey, sessionId);
      
      post_author_id = tockbox.post_author_id;
      wp_user_id = tockbox.wp_user;

      if(post_author_id == wp_user_id){
        session.disconnect();
      }else{
        location.reload();
      }

    });

    
});


jQuery(document).on('click', '.text-chat-head', function(){
  console.log('HI on click');
  jQuery(this).prev("div#textchat").attr("style", "display:inline-block");
  jQuery(this).attr("style", "display:none");

  // Mark all unseen as seen via rest Api 
  

});
jQuery(document).on('click', 'svg.chat-minimiser', function(){
  jQuery(this).closest('div#textchat').attr("style", "display:none");
  jQuery(this).closest('div#textchat').next('.text-chat-head').attr("style", "display:inline-block");
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
            userid: tockbox.wp_user,
            current_page_id: tockbox.current_page_id
        },
        success: function (result) {
          if( result.forcedisconnect == 'yes' ){
            location.reload();
          }
          console.log(result.forcedisconnect);
            console.log(result);
        }
    });
}

var connectionCount = 0;
function initializeSession(apiKey, sessionId, token) {
    
    var session = OT.initSession(apiKey, sessionId);

    session.on("connectionCreated", function(event) {
       connectionCount++;
       displayConnectionCount();

      //  jQuery("#subscriber").attr("style", "width:"+(((connectionCount*180)+10)-160)+"px");

       if (event.connection.connectionId != session.connection.connectionId) {
        // console.log('Another client connected. ' + connectionCount + ' total.');
      }

      //  console.log('event.connection.connectionId :' + event.connection.connectionId);
      //  console.log('session.connection.connectionId :' + session.connection.connectionId);
    });
    session.on("connectionDestroyed", function(event) {
       connectionCount--;
       displayConnectionCount();
    });
  

    // // Subscribe to a newly created stream
    // session.on('streamCreated', function(event) {
    //     session.subscribe(event.stream, 'subscriber', {
    //     insertMode: 'append',
    //     width: '100%',
    //     height: '100%',
    //     }, handleError);

    //     var i = 0;
    //     if(i < 1){
    //       var OT_subscriber = document.querySelectorAll('div.OT_subscriber');
    //       for (i = 0; i < OT_subscriber.length; i++) {
    //         jQuery('div#subscriber').find('div.OT_subscriber').removeClass('active');
    //         OT_subscriber[i].classList.add('active');
    //       }
    //       i++;
    //     }
    //     // console.log('jony : ' + connectionCount);
    // });

    session.on('streamCreated', function(event) {
      var subscriberProperties = {insertMode: 'append'};
      var subscriber = session.subscribe(event.stream,
        'subscriber',
        subscriberProperties,
        function (error) {
          if (error) {
            console.log(error);
          } else {

            if( tockbox.user_name == tockbox.post_author_name ){
              var i = 0;
              if(i < 1){
                var OT_subscriber = document.querySelectorAll('div.OT_subscriber');
                for (i = 0; i < OT_subscriber.length; i++) {
                  jQuery('div#subscriber').find('div.OT_subscriber').removeClass('active');
                  OT_subscriber[i].classList.add('active');
                }
                i++;
              }
              jQuery('div#subscriber').find('div.OT_subscriber').attr("style", "display:block");
            }else{

              var OT_subscriber = document.querySelectorAll('div.OT_subscriber');
              for (i = 0; i < OT_subscriber.length; i++) {
                var OT_name = OT_subscriber[i].querySelector('h1.OT_name');
                if( tockbox.post_author_name == OT_name.textContent ){
                  OT_subscriber[i].classList.add('active');
                }
              }

            }
            // console.log('Subscriber added.');
          }
      });
      subscriber.on({
        audioBlocked: function(event) {
        //  console.log("Subscriber audio is blocked.")
        },
        audioUnblocked: function(event) {
        //  console.log("Subscriber audio is unblocked.")
        }
      });
    });

    session.on("sessionDisconnected", function(event) {
      var i = 0;
      if(i < 1){
        var OT_subscriber = document.querySelectorAll('div.OT_subscriber');
        for (i = 0; i < OT_subscriber.length; i++) {
          jQuery('div#subscriber').find('div.OT_subscriber').removeClass('active');
          OT_subscriber[i].classList.add('active');
        }
        i++;
      }

      // console.log('created session sdasd');
      jQuery.ajax({
        method: 'POST',
        dataType: "json",
        url: tockbox.ajax_url,
        data: {
            action:         'saveArchiveVideoLink', 
            sessionId:       sessionId,
            current_page_id: tockbox.current_page_id,
        },
        success:function(data){
            console.log(data);
               if(data.message == 'success'){
                location.reload();
               }
            }
      });


    });

    // Initialize the publisher
    var publisherOptions = {
      insertMode: 'append',
      width: '100%',
      height: '100%',
    };
    var publisher = OT.initPublisher('publisher', {    name: tockbox.user_name,
      style: { nameDisplayMode: "off" }}, publisherOptions, function initCallback(initErr) {
      if (initErr) {
        console.error('There was an error initializing the publisher: ', initErr.name, initErr.message);
        return;
      }
    });
  

    // var publisher = OT.initPublisher("myPublisher",
    // {
    //   name: tockbox.user_name,
    //   style: { nameDisplayMode: "off" }
    // });
    // session.publish(publisher);


    var count = 0;
    // Connect to the session
    session.connect(token, function(error) {

        // If the connection is successful, publish to the session
        if (error) {
          handleError(error);
        } else {
          session.publish(publisher, handleError);
          // counts = count++;
          // console.log(counts);
          // console.log(publisher);

          if( tockbox.post_author_name != tockbox.user_name ){
            var user_name = tockbox.user_name;
            var up_user_name = user_name.substr(0, 1).toUpperCase() + user_name.substr(1);
            var joining_massage_for_subscriber = tockbox.joining_massage_for_subscriber;

            var mas = joining_massage_for_subscriber.replace("{user-name}", up_user_name);

            session.signal({
              type: 'msg',
              data: {
                msgTxt:    mas,
                user_name: user_name,
                admin_set_name: '',
                massage_author: '',
            },
            }, function signalCallback(error) {
              if (error) {
                console.error('Error sending signal:', error.name, error.message);
              } else {
                mas = '';
              }
            });

            

          }

        }

      setInterval(function() {
        reduseUserCredit();
      }, 30 * 1000); // 60 * 1000 milsec

  });
  

  // Receive a message and append it to the history
  // var msgHistory = document.querySelector('#history');

  session.on('signal:msg', function signalCallback(event) {

    
    // if( tockbox.user_name === event.data.user_name || tockbox.post_author_name === tockbox.user_name ){

      var class_name = jQuery('div').hasClass(event.data.user_name);
      if( tockbox.post_author_name === tockbox.user_name && class_name === false ){

        jQuery(".all-textchat").append(
          jQuery("#textchat-and-header").clone().addClass('user ' + event.data.user_name)
        );
        jQuery(".textchat-and-header.user."+event.data.user_name).children('#textchat').children('div.textchat-cover').attr("style", "display:none");
        // jQuery(".textchat-and-header."+event.data.user_name).css("right", ((connectionCount-2)*(300+10))+80);
        jQuery("div.all-textchat").css("width", ((connectionCount-1)*(300+10)));

        var hedder_name_class = document.querySelector(".textchat-and-header.user."+event.data.user_name);
        var chat_hedder_name = hedder_name_class.querySelector('.chat-name-header');
        var chat_hedder_name1 = hedder_name_class.querySelector('.text-chat-head');
        var form_add_class = hedder_name_class.querySelector('.massage_author');
        var admin_set_user_name = hedder_name_class.querySelector('.admin-set-user-name');
        var user_and_author = hedder_name_class.querySelector('.user_and_author');
        chat_hedder_name.textContent = event.data.user_name;
        chat_hedder_name1.textContent = event.data.user_name;
        form_add_class.textContent = event.data.user_name;
        admin_set_user_name.textContent = event.data.user_name;
        user_and_author.textContent = event.data.user_name;

        var msgHistory = hedder_name_class.querySelector('#history');
        msgHistory.textContent = '';
        var cover = document.createElement('div');
        var msg = document.createElement('p');
        name.textContent = event.data.user_name;
        msg.textContent = event.data.msgTxt;
        cover.className = event.from.connectionId === session.connection.connectionId ? 'mine' : 'theirs';
        msg.className = 'owner-msg';
        cover.appendChild(msg);
        msgHistory.appendChild(cover);
        cover.scrollIntoView();

        // console.log('tockbox.post_author_name === tockbox.user_name && class_name === false');

      }else if( tockbox.post_author_name === tockbox.user_name ){

        if( event.data.admin_set_name == '' ){
          // console.log(event.data.massage_author );
          var admin_send_massage = ( event.data.massage_author == tockbox.post_author_name ) ? event.data.user_name : event.data.massage_author;
          var hedder_name_class = document.querySelector(".textchat-and-header.user."+admin_send_massage);
          var msgHistory = hedder_name_class.querySelector('#history');
          var cover = document.createElement('div');
          var msg = document.createElement('p');
          name.textContent = event.data.user_name;
          msg.textContent = event.data.msgTxt;
          cover.className = event.from.connectionId === session.connection.connectionId ? 'mine' : 'theirs';
          msg.className = 'owner-msg';
          cover.appendChild(msg);
          msgHistory.appendChild(cover);
          cover.scrollIntoView();
        }
        // console.log('tockbox.post_author_name === tockbox.user_name' + admin_send_massage);

      }

      if( tockbox.user_name === event.data.user_name ){

        var msgHistory = document.querySelector('#history');
        var cover = document.createElement('div');
        var msg = document.createElement('p');
        name.textContent = event.data.user_name;
        msg.textContent = event.data.msgTxt;
        cover.className = event.from.connectionId === session.connection.connectionId ? 'mine' : 'theirs';
        msg.className = 'owner-msg';
        cover.appendChild(msg);
        msgHistory.appendChild(cover);
        cover.scrollIntoView();

        // console.log('tockbox.user_name === event.data.user_name');

      }

      if( tockbox.user_name === event.data.massage_author ){

        // console.log(event.data.admin_set_name);
        if( event.data.admin_set_name != '' && event.data.admin_set_name != undefined ){
          var hedder_name_class = document.querySelector(".textchat-and-header");
          var chat_hedder_name = hedder_name_class.querySelector('.chat-name-header');
          var chat_hedder_name1 = hedder_name_class.querySelector('.text-chat-head');
          chat_hedder_name.textContent = event.data.admin_set_name;
          chat_hedder_name1.textContent = event.data.admin_set_name;
        }else{
          var msgHistory = document.querySelector('#history');
          var cover = document.createElement('div');
          var msg = document.createElement('p');
          name.textContent = event.data.user_name;
          msg.textContent = event.data.msgTxt;
          cover.className = event.from.connectionId === session.connection.connectionId ? 'mine' : 'theirs';
          msg.className = 'owner-msg';
          cover.appendChild(msg);
          msgHistory.appendChild(cover);
          cover.scrollIntoView();
        }

        // console.log('tockbox.user_name === event.data.massage_author_name');
      }
      if( tockbox.post_author_name === tockbox.user_name ){
        console.log(tockbox.post_author_name);
        console.log(tockbox.user_name);
        console.log(JSON.stringify(event.data));

        var msgText = event.data.msgTxt;
        var user_name = event.data.user_name;
        var massage_author = event.data.massage_author;
        var admin_set_name = event.data.admin_set_name;
        var author_and_user = event.data.author_and_user;
        var user_and_author = event.data.user_and_author;
        var post_id = tockbox.current_page_id;
        jQuery.ajax({
          method: 'POST',
          dataType: "json",
          url: tockbox.ajax_url,
          data: {
              action:         'saveTextChat',
              msgText: msgText,
              user_name: user_name,
              massage_author: massage_author,
              admin_set_name: admin_set_name,
              current_page_id: tockbox.current_page_id,
              author_and_user: author_and_user,
              user_and_author: user_and_author,
              chat_page: 'no',
              post_id: post_id,
          },
          success:function(data){
              console.log(data);
                if(data.message == 'success'){
                }
              }
        });
      }


      
  });

}

// function initializeSessionForTextChat(apiKey, sessionId, token){
//     var session = OT.initSession(apiKey, sessionId);
//     session.on('signal:msg', function signalCallback(event) {

//       var msgHistory = document.querySelector('#history');
//       var cover = document.createElement('div');
//       var msg = document.createElement('p');
//       name.textContent = event.data.user_name;
//       msg.textContent = event.data.msgTxt;
//       cover.className = event.from.connectionId === session.connection.connectionId ? 'mine' : 'theirs';
//       msg.className = 'owner-msg';
//       cover.appendChild(msg);
//       msgHistory.appendChild(cover);
//       cover.scrollIntoView();
          
//     });
// }

jQuery(document).on('focus', 'input#msgTxt', function(event){
    jQuery('div.textchat-cover').find('form.text-sender').removeClass('active');
    jQuery(this).closest('form.text-sender').addClass('active');
});

// Text chat
// var form = document.querySelector('form.active');
// var msgTxt = document.querySelector('#msgTxt');

// var eventLesenerColback = function ( form , msgTxt ){
// Send a signal once the user enters data in the form
// form.addEventListener('submit', function submit(event) {
jQuery(document).on('submit', 'form.active' , function(event){

// jQuery("form.text-sender").submit(function(event){
  event.preventDefault();

  var apiKey = tockbox.tockbox_api;
  var sessionId = jQuery(this).closest('#textchat').data('sessionid');
  var session = OT.initSession(apiKey, sessionId);

  var msgTxt = jQuery(this).find('input#msgTxt').val();
  var massage_author = jQuery(this).find('div.massage_author').text();
  var author_and_user = jQuery(this).find('div.author_and_user').text();
  var user_and_author = jQuery(this).find('div.user_and_author').text();

  var massage_author_name = ( tockbox.user_name == massage_author ) ? '' : massage_author;
  
  session.signal({
    type: 'msg',
    data: {
      msgTxt:    msgTxt,
      user_name: tockbox.user_name,
      massage_author: massage_author_name,
      admin_set_name: '',
      author_and_user: author_and_user,
      user_and_author: user_and_author,

  },
  }, function signalCallback(error) {
    if (error) {
      console.error('Error sending signal:', error.name, error.message);
    } else {
      
      var i;
      var msgTxt = document.querySelectorAll('#msgTxt');
      for (i = 0; i < msgTxt.length; i++) {
        msgTxt[i].value = '';
      }
    }
  });

});
  
// }

jQuery(document).on('click', 'button.admin-set-name-button', function(event){
  jQuery('div.admin-set-name').find('form.admin-set-name-submit').removeClass('active');
  jQuery(this).closest('form.admin-set-name-submit').addClass('active');
});

jQuery(document).on('submit', 'form.admin-set-name-submit.active' , function(event){
  event.preventDefault();

  var apiKey = tockbox.tockbox_api;
  var sessionId = jQuery(this).closest('#textchat').data('sessionid');
  var session = OT.initSession(apiKey, sessionId);

  var admin_set_name = jQuery(this).find('input.admin-set-name').val();
  var massage_author = jQuery(this).find('div.admin-set-user-name').text();
  var msgTxt = 'Author has set his name is : ' + admin_set_name;
  // console.log(massage_author);
  session.signal({
    type: 'msg',
    data: {
      msgTxt:    msgTxt,
      user_name: tockbox.user_name,
      massage_author: massage_author,
      admin_set_name: admin_set_name,
  },
  }, function signalCallback(error) {
    if (error) {
      console.error('Error sending signal:', error.name, error.message);
    }

  });

  jQuery(this).closest('div.admin-set-name').attr("style", "display:none");
  
  var hedder_name_class = document.querySelector(".textchat-and-header.user."+massage_author);
  var admin_set_user_name = hedder_name_class.querySelector('.set-admin-name-show-text');
  admin_set_user_name.textContent = 'You set your name to '+ admin_set_name.substr(0, 1).toUpperCase() + admin_set_name.substr(1);
  jQuery(this).closest('div.admin-set-name').next('div.set-admin-name-show').attr("style", "display:inline-block");
  jQuery(this).closest('div.admin-set-name').closest('#textchat').find('div.textchat-cover').attr("style", "display:inline-block");
});

function displayConnectionCount() {
  var totalConnection = connectionCount.toString();
  console.log(totalConnection);
}


jQuery(document).on('click', 'div.OT_subscriber', function(event){
  jQuery('div#subscriber').find('div.OT_subscriber').removeClass('active');
  jQuery(this).addClass('active');
  // jQuery("div#videos").attr("style", "height: 860px");
});



var date_time = tockbox.date_time;
var countDownDate = new Date(date_time).getTime();
var current_date = new Date().getTime();

var date_time_expired = '';
if( current_date <= countDownDate ){
  var date_time_expired = 'no';
  jQuery(".single-session").remove();
}else{
  jQuery(".count-cover").remove();
}

// var date_time_expired = tockbox.date_time_expired;
if( date_time_expired == 'no' ){

  // var current_date = tockbox.current_date;

  // Set the date we're counting down to
  // console.log(countDownDate);
  // Update the count down every 1 second
  var x = setInterval(function() {

    // Get today's date and time
    var now = new Date().getTime();

    // Find the distance between now and the count down date
    var distance = countDownDate - now;
      
    // Time calculations for days, hours, minutes and seconds
    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
      

    // If the count down is over, write some text 
    if (distance > 0 ){
        // Output the result in an element with id="demo"
        document.getElementById("countDownDate").innerHTML = days + "d " + hours + "h "
        + minutes + "m " + seconds + "s ";
          
  }else{
      clearInterval(x);
      location.reload();
    }
  }, 1000);
}else{

  var video = document.getElementById('video_test');
  if( video == '' ){
    if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream) {
          video.srcObject = stream;
          video.play();
      });
    }
  }
}
