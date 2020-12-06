jQuery(document).ready(function($){

  var user_login = tockbox.user_login;
  var current_user_role = tockbox.current_user_role;

if( user_login == 1 ){
  if( current_user_role == 'yes' ){
    var apikey = tockbox.tockbox_api;
    var sessionId = tockbox.chat_session_id;
    var token = tockbox.chat_token;

    initializeSession(apikey, sessionId, token);

    console.log('author-cannected');

  }else{
      
      if( tockbox.template_name == 'template-profiles.php' ){
        var apikey = tockbox.tockbox_api;
        var sessionId = tockbox.chat_session_id;
        var token = tockbox.chat_token;

        initializeSession(apikey, sessionId, token);

      }
    }
}
  

    // Create Video on join button 
    jQuery(document).on('click', '.profile-option a.icon-comment', function(e){
        e.preventDefault();
        var current_user_role = tockbox.current_user_role;
        if( current_user_role == 'no' ){
        var href = jQuery(this).attr('href');
        // if( href !== undefined || href !== '' ){
        var res = href.split('/');
        var userId = res[res.length-1];

        //   $(this).after('<div class="user-id">'+ userId +'</div>');
        // }
  
        // var apikey = tockbox.tockbox_api;
        // var sessionId = tockbox.chat_session_id;
        // var token = tockbox.chat_token;
        // initializeSession(apikey, sessionId, token);
  
        jQuery.ajax({
          method: 'POST',
          dataType: "json",
          url: tockbox.ajax_url,
          data: {
              action:         'all_user_details',
              userId:         userId,
              sendTOappend:   'yes',
              append_sms: 'no',
          },
          success:function(data){
              console.log(data);
                if(data.message == 'success'){
                  var hedder_name_class = document.querySelector(".textchat-and-header");
                  var chat_hedder_name = hedder_name_class.querySelector('.chat-name-header');
                  var chat_hedder_name1 = hedder_name_class.querySelector('.text-chat-head');
                  var chat_hedder_name2 = hedder_name_class.querySelector('.massage_author');
                  var chat_hedder_name3 = hedder_name_class.querySelector('.author_and_user');
                  var chat_hedder_name4 = hedder_name_class.querySelector('.user_and_author');
                  var massage_unique_id = hedder_name_class.querySelector('.massage_unique_id');

                  chat_hedder_name.textContent = data.admin_set_name;
                  chat_hedder_name1.textContent = data.admin_set_name;
                  chat_hedder_name2.textContent = data.user_name;
                  chat_hedder_name3.textContent = data.user_name;
                  chat_hedder_name4.textContent = tockbox.user_name;
                  massage_unique_id.textContent = data.user_name + '_' + tockbox.user_name;


                  jQuery(".textchat-and-header").removeClass("demo-chat");
                  jQuery(".textchat-and-header").addClass("demo-chat-count");

                  var all_length = document.querySelectorAll("div.textchat-and-header.demo-chat-count");
                  var all_lengths = all_length.length;
                  jQuery("div.all-textchat").css("width", ((all_lengths)*(300+10)));

                  // jQuery('.textchat-and-header').attr("style", "display:block");
                  // jQuery('.text-chat-head').attr("style", "display:block");
                  // jQuery('.textchat-and-header').closest('div.admin-set-name').attr("style", "display:none");

                  // jQuery(".textchat-and-header.user."+event.data.user_name).attr("style", "display:inline-block");
                  // jQuery(".textchat-and-header.user."+event.data.user_name).find('.text-chat-head').attr("style", "display:inline-block");
            

                  var msgHistory = hedder_name_class.querySelector('#history');
                  msgHistory.textContent = '';
                  data.tokbox_text_chat.forEach(function(item) {
                    var msgText = item.msgText;
                    var user_name = item.user_name;
                    var massage_author = item.massage_author;

                    var cover = document.createElement('div');
                    var msg = document.createElement('p');
                    name.textContent = user_name;
                    msg.textContent = msgText;
                    cover.className = user_name === tockbox.user_name ? 'mine' : 'theirs';
                    msg.className = 'owner-msg';
                    cover.appendChild(msg);
                    msgHistory.appendChild(cover);
                    cover.scrollIntoView();
                  });

                  
                }
              }
          });

        }else{
          console.log('your ar contributer');
        }
      }); 

});


var sendAjaxtoMarkAllasSeen = function(author_and_user, user_and_author, element){

  jQuery.ajax({
    method: 'POST',
    dataType: "json",
    url: tockbox.ajax_url,
    data: {
        action:         'all_user_details',
        author_and_user: author_and_user,
        user_and_author: user_and_author,
        sendTOappend: 'no',
        append_sms: 'no',
        seen: true
    },
    success:function(data){
          if(data.message == 'success'){
            element.next('.notification').remove();
          }
        }
    });
    
}


jQuery(document).on('click', '.text-chat-head', function(){
    const element = jQuery(this);
    const author_and_user = jQuery(this).prev('div').find('form.text-sender').find('div.author_and_user').text();
    const user_and_author = jQuery(this).prev('div').find('form.text-sender').find('div.user_and_author').text();
    var message = sendAjaxtoMarkAllasSeen(author_and_user, user_and_author, element);
    

    jQuery(this).prev("div#textchat").attr("style", "display:inline-block");
    jQuery(this).attr("style", "display:none");

});
jQuery(document).on('click', 'svg.chat-minimiser', function(){
    jQuery(this).closest('div#textchat').attr("style", "display:none");
    jQuery(this).closest('div#textchat').next('.text-chat-head').attr("style", "display:inline-block");
});
jQuery(document).on('focus', 'input#msgTxt', function(event){
    jQuery('div.textchat-cover').find('form.text-sender').removeClass('active');
    jQuery(this).closest('form.text-sender').addClass('active');
});
jQuery(document).on('click', 'button.admin-set-name-button', function(event){
    jQuery('div.admin-set-name').find('form.admin-set-name-submit').removeClass('active');
    jQuery(this).closest('form.admin-set-name-submit').addClass('active');
});

var connectionCount = 0;
function initializeSession(apiKey, sessionId, token) {
  var session = OT.initSession(apiKey, sessionId);

  var i = 0;
  if( i == 0 ){
    session.connect(token, function(error) {
        if (error) 
        {
            console.log('token: ' + token);
            console.log('apikey: ' + apiKey);
            console.log('LessionID: ' + sessionId);
            console.log(error);
        } 
        else 
        {
            i++;
        }
    });
  }

  session.on("connectionCreated", function(event) {
    connectionCount++;
  });
  session.on("connectionDestroyed", function(event) {
      connectionCount--;
  });



  // var width = function(){
  //   jQuery('div.textchat-and-header').each(function(k, v){
  //     console.log(v);
  //   });
  // } sfsfsdf afdasfsdfsdf
  

  session.on('signal:msg', function signalCallback(event) {
    // width();
    // console.log('width : ' + width());


    console.log(JSON.stringify(event.data));

    var class_name = jQuery('div').hasClass(event.data.user_name);
    if( event.data.author_and_user === tockbox.user_name && class_name === false ){

      jQuery(".all-textchat").append(
        jQuery("#textchat-and-header").clone().addClass('user ' + event.data.user_name)
      );

      jQuery(".textchat-and-header.user."+event.data.user_name).addClass("demo-chat-count");
      // jQuery(".textchat-and-header.user."+event.data.user_name).children('#textchat').children('div.textchat-cover').attr("style", "display:none");

      var all_length = document.querySelectorAll("div.textchat-and-header.demo-chat-count");
      var all_lengths = all_length.length;
      jQuery("div.all-textchat").css("width", ((all_lengths)*(300+10)));

      jQuery(".textchat-and-header.user."+event.data.user_name).attr("style", "display:inline-block");
      // jQuery(".textchat-and-header.user."+event.data.user_name).find('.text-chat-head').attr("style", "display:inline-block");

      var hedder_name_class = document.querySelector(".textchat-and-header.user."+event.data.user_name);


      var user_name = event.data.user_name;
      console.log('username: ' + user_name);
      
      jQuery(".textchat-and-header.user."+user_name).removeClass("demo-chat");
      
      jQuery.ajax({
        method: 'POST',
        dataType: "json",
        url: tockbox.ajax_url,
        data: {
            action: 'all_user_details',
            author_and_user: event.data.author_and_user,
            user_and_author: event.data.user_and_author,
            massage_unique_id: event.data.massage_unique_id,
            append_sms: 'yes',
        },
        success:function(data){
            
            console.log(data);
              if(data.message == 'success'){

                if( data.admin_set_name == '' ){
                  console.log('inside if');
                  var set_name_html = '<div class="admin-set-name-cover"><form class="admin-set-name-submit"><label for="admin_set_name" class="admin-set-name-label">Set your name for this user :</label><input type="text" placeholder="Set your name for this user" id="admin_set_name" class="admin-set-name" value=""><div class="admin-set-user-name"></div><button class="admin-set-name-button"><svg class="sqpo3gyd" height="20px" width="20px" viewBox="0 0 24 24"><path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 C22.8132856,11.0605983 22.3423792,10.4322088 21.714504,10.118014 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.8376543,3.0486314 1.15159189,3.99121575 L3.03521743,10.4322088 C3.03521743,10.5893061 3.34915502,10.7464035 3.50612381,10.7464035 L16.6915026,11.5318905 C16.6915026,11.5318905 17.1624089,11.5318905 17.1624089,12.0031827 C17.1624089,12.4744748 16.6915026,12.4744748 16.6915026,12.4744748 Z" fill-rule="evenodd" stroke="none"></path></svg></button></form></div><div class="set-admin-name-show"><div class="set-admin-name-show-text"></div></div>';
                  jQuery(set_name_html).insertAfter(jQuery(hedder_name_class).find('.header-name-and-minimise-cover'));
                  jQuery(hedder_name_class).find('.header-name-and-minimise-cover').addClass('ThisIsInsidejQuery5');

                  
                  var admin_set_name = hedder_name_class.querySelector('.admin-set-name');
                  var admin_set_user_name = hedder_name_class.querySelector('.admin-set-user-name');
                  admin_set_name.value = event.data.author_and_user;
                  admin_set_user_name.textContent = event.data.user_and_author;

                }else{
                  
                  var set_name_html = '<div class="set-admin-name-show"><div class="set-admin-name-show-text"></div></div>';
                  jQuery(set_name_html).insertAfter(jQuery(hedder_name_class).find('.header-name-and-minimise-cover'));
                  jQuery(hedder_name_class).find('.header-name-and-minimise-cover').addClass('ThisIsInsidejQuery5');

                  var admin_set_name = hedder_name_class.querySelector('.set-admin-name-show-text');
                  admin_set_name.textContent = data.admin_set_name;

                  // jQuery(".textchat-and-header.user."+event.data.user_name).children('#textchat').children('div.textchat-cover').attr("style", "display:inline-block");
                }
              }
            }
      });

      var chat_hedder_name = hedder_name_class.querySelector('.chat-name-header');
      var chat_hedder_name1 = hedder_name_class.querySelector('.text-chat-head');
      var form_add_class = hedder_name_class.querySelector('.massage_author');
      var author_and_user = hedder_name_class.querySelector('.author_and_user');
      var user_and_author = hedder_name_class.querySelector('.user_and_author');
      var massage_unique_id = hedder_name_class.querySelector('.massage_unique_id');
      var notification = 0;
      
      chat_hedder_name.textContent = event.data.user_name;
      chat_hedder_name1.textContent = event.data.user_name;
      form_add_class.textContent = event.data.user_name;
      author_and_user.textContent = event.data.author_and_user;
      user_and_author.textContent = event.data.user_and_author;
      massage_unique_id.textContent = event.data.author_and_user +'_'+ event.data.user_and_author;


      var msgHistory = hedder_name_class.querySelector('#history');


      jQuery.ajax({
        method: 'POST',
        dataType: "json",
        url: tockbox.ajax_url,
        data: {
            action:         'all_user_details',
            author_and_user: event.data.author_and_user,
            user_and_author: event.data.user_and_author,
            sendTOappend: 'no',
            append_sms: 'no',
        },
        success:function(data){
            // console.log(data);
              if(data.message == 'success'){
                
                msgHistory.textContent = '';
                data.tokbox_text_chat.forEach(function(item) {
                  if(item.seen == 'false') notification++;
                  var msgText = item.msgText;
                  var user_name = item.user_name;

                  var cover = document.createElement('div');
                  var msg = document.createElement('p');
                  name.textContent = user_name;
                  msg.textContent = msgText;
                  cover.className = user_name === tockbox.user_name ? 'mine' : 'theirs';
                  msg.className = 'owner-msg';
                  cover.appendChild(msg);
                  msgHistory.appendChild(cover);
                  cover.scrollIntoView();
                  
                });

                var notificationText = document.createElement('span'); 
                notificationText.textContent = notification;
                notificationText.className = 'notification';
                if(notification > 0){
                  console.log('this is notification: ' + notification);
                  hedder_name_class.appendChild(notificationText);
                }

                var cover = document.createElement('div');
                var msg = document.createElement('p');
                name.textContent = event.data.user_name;
                msg.textContent = event.data.msgTxt;
                cover.className = event.from.connectionId === session.connection.connectionId ? 'mine' : 'theirs';
                msg.className = 'owner-msg';
                cover.appendChild(msg);
                msgHistory.appendChild(cover);
                cover.scrollIntoView();



                // make all unseen message seen if chatbox are open 
                // if(sfsdf){

                // }

              }
            }
      });

    }else if( event.data.author_and_user === tockbox.user_name ){
      console.log('inside else omar');
      if( event.data.massage_unique_id === tockbox.user_name +'_'+ event.data.user_and_author || event.data.massage_unique_id === event.data.user_and_author + '_' + tockbox.user_name ){

        
          var admin_send_massage = ( event.data.massage_author == event.data.author_and_user ) ? event.data.user_name : event.data.massage_author;
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
        

          if(!hedder_name_class.querySelector('.text-chat-head[style="display:none"]')){
            hedder_name_class.querySelector('.notification').textContent = parseInt(hedder_name_class.querySelector('.notification').textContent) + 1;
          }
      }

    }


    
    if( tockbox.user_name === event.data.user_name  && tockbox.user_name +'_'+ event.data.massage_author !== tockbox.user_name + '_' + tockbox.user_name ){
      

      console.log(event.data.massage_unique_id + '/' + tockbox.user_name +'_'+ event.data.massage_author + '::' + event.data.massage_author + '_' + tockbox.user_name);
      if( event.data.massage_unique_id === tockbox.user_name +'_'+ event.data.massage_author || event.data.massage_unique_id === event.data.massage_author + '_' + tockbox.user_name ){

        var admin_send_massage = ( event.data.massage_author == event.data.author_and_user ) ? event.data.user_name : event.data.massage_author;
        var hedder_name_class = document.querySelector(".textchat-and-header.user."+admin_send_massage);
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


        console.log('2 ' + admin_send_massage);
      }
    }

    if( tockbox.user_name === event.data.massage_author && tockbox.user_name +'_'+ event.data.author_and_user !== tockbox.user_name + '_' + tockbox.user_name ){

      if( event.data.admin_set_name != '' && event.data.admin_set_name != undefined ){
        var hedder_name_class = document.querySelector(".textchat-and-header");
        var chat_hedder_name = hedder_name_class.querySelector('.chat-name-header');
        var chat_hedder_name1 = hedder_name_class.querySelector('.text-chat-head');
        chat_hedder_name.textContent = event.data.admin_set_name;
        chat_hedder_name1.textContent = event.data.admin_set_name;
      }

      if( event.data.massage_unique_id === tockbox.user_name +'_'+ event.data.author_and_user || event.data.massage_unique_id === event.data.author_and_user + '_' + tockbox.user_name ){
        var admin_send_massage = ( event.data.massage_author == event.data.author_and_user ) ? event.data.user_name : event.data.massage_author;
        var hedder_name_class = document.querySelector(".textchat-and-header.user."+admin_send_massage);
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
        console.log('4 ' + admin_send_massage);
      }
    }

  });

}


jQuery(document).on('submit', 'form.active' , function(event){
      event.preventDefault();

      var apiKey = tockbox.tockbox_api;

      var sessionId = jQuery(this).closest('#textchat').data('sessionid');
      console.log('session id: ' + sessionId);
      var session = OT.initSession(apiKey, sessionId);
      var token = tockbox.chat_token;

      session.connect(token, function(error) {
        if (error) {
          console.log(error.message);
        } else {
          // You have connected to the session. You could publish a stream now.
        }
      });



      var msgTxt = jQuery(this).find('input#msgTxt').val();
      var massage_author = jQuery(this).find('div.massage_author').text();
      var author_and_user = jQuery(this).find('div.author_and_user').text();
      var user_and_author = jQuery(this).find('div.user_and_author').text();
      var massage_unique_id = jQuery(this).find('div.massage_unique_id').text();
      
      session.signal({
        type: 'msg',
        data: {
          msgTxt:    msgTxt,
          user_name: tockbox.user_name,
          massage_author: massage_author,
          author_and_user: author_and_user,
          user_and_author: user_and_author,
          massage_unique_id: massage_unique_id,
          seen: false
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


    jQuery.ajax({
      method: 'POST',
      dataType: "json",
      url: tockbox.ajax_url,
      data: {
          action: 'saveTextChat',
          msgText: msgTxt,
          user_name: tockbox.user_name,
          massage_author: massage_author,
          current_page_id: tockbox.current_page_id,
          author_and_user: author_and_user,
          user_and_author: user_and_author,
          massage_unique_id: massage_unique_id,
          chat_page: 'yes',
          name_set: 'no',
          seen: false,
      },
      success:function(data){
          console.log(data);
            if(data.message == 'success'){
            }
          }
    });
    
});

jQuery(document).on('submit', 'form.admin-set-name-submit.active' , function(event){
  event.preventDefault();

  var apiKey = tockbox.tockbox_api;
  var sessionId = jQuery(this).closest('#textchat').data('sessionid');
  var session = OT.initSession(apiKey, sessionId);

  var admin_set_name = jQuery(this).find('input.admin-set-name').val();
  var user_and_author = jQuery(this).find('div.admin-set-user-name').text();
  var msgTxt = 'Author has set his name is : ' + admin_set_name;
  console.log(admin_set_name);
  session.signal({
    type: 'msg',
    data: {
      msgTxt:    msgTxt,
      user_name: tockbox.user_name,
      massage_author: user_and_author,
      author_and_user: tockbox.user_name,
      user_and_author: user_and_author,
      admin_set_name: admin_set_name,
  },
  }, function signalCallback(error) {
    if (error) {
      console.error('Error sending signal:', error.name, error.message);
    }

  });

  jQuery(this).closest('div.admin-set-name-cover').attr("style", "display:none");
  
  var hedder_name_class = document.querySelector(".textchat-and-header.user."+user_and_author);
  var admin_set_user_name = hedder_name_class.querySelector('.set-admin-name-show-text');
  admin_set_user_name.textContent = admin_set_name;
  jQuery(this).closest('div.admin-set-name-cover').next('div.set-admin-name-show').attr("style", "display:inline-block");
  jQuery(this).closest('div.admin-set-name-cover').closest('#textchat').find('div.textchat-cover').attr("style", "display:inline-block");

  var massage_unique_id = tockbox.user_name + '_' + user_and_author;
  jQuery.ajax({
    method: 'POST',
    dataType: "json",
    url: tockbox.ajax_url,
    data: {
        action: 'saveTextChat',
        msgTxt:    msgTxt,
        user_name: tockbox.user_name,
        massage_author: user_and_author,
        author_and_user: tockbox.user_name,
        user_and_author: user_and_author,
        admin_set_name: admin_set_name,
        current_page_id: tockbox.current_page_id,
        massage_unique_id: massage_unique_id,
        chat_page: 'yes',
        name_set: 'yes',
        seen: false,
    },
    success:function(data){
        console.log(data);
          if(data.message == 'success'){
          }
        }
  });
});