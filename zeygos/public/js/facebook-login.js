$(document).ready(function() {

    var facebookAppId = $('#facebook_app_id').val();
    var facebookAuthUrl = $('#facebook_auth_url').val();

    window.fbAsyncInit = function() {
        FB.init({
            appId      : facebookAppId,
            xfbml      : true,
            version    : 'v2.9'
        });

        $('#loginFacebook').click(function(e){
            e.preventDefault();
            $('#login-type').val('facebook');
            FB.getLoginStatus(function(response) {
                if (response.status === 'connected') {
                    // connected
                    getUserInfo();
                } else if (response.status === 'not_authorized') {
                    // not_authorized
                    login();
                } else {
                    // not_logged_in
                    login();
                }
            });
        });

        /* FB.getLoginStatus(function(response) {
         if (response.status === 'connected') {
         console.log('Logged in.');
         }
         else {
         console.log('Not logged in.');
         FB.login();
         }
         });*/

        /*FB.login(function(response) {
         if (response.status === 'connected') {
         // Logged into your app and Facebook.
         } else {
         // The person is not logged into this app or we are unable to tell.
         }
         });*/
    };

    (function(d, s, id){
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));




    function login() {
        FB.login(function(response) {
            if (response.authResponse) {
                // connected
                getUserInfo();
            } else {
                // cancelled
            }
        }, {scope: 'email'});
    }

    function getUserInfo() {
        FB.api('/me?fields=first_name,last_name,email', function(response) {

            var id = response.id;
            var email = response.email;
            var first_name = response.first_name;
            var last_name = response.last_name;

            $.getJSON(
                facebookAuthUrl, {
                    id: id,
                    email: email,
                    first_name: first_name,
                    last_name: last_name
                }, function(data) {

                    console.log(data);

                    if (data.status == 'error'){
                        $('#facebook-alert').show();
                        $('#facebook-alert').html(data.message);

                    } else {
                        window.location = data.redirect;
                    }
                }
            );
        });
    }




      var validUsername = false;
      var validEmail = true;

      var user_facebook_id = $('#postFacebookloginForm > .user_facebook_id').val();            

      function validateUsername(input, forceValidate){
          var t = input;
          if (input.val() != input.attr('lastValue') || forceValidate == true) {
              if (input.attr('timer')) clearTimeout(input.attr('timer'));
              input.addClass('input-loader');
              input.empty();
              var timer = setTimeout(function () {
                no_history_server_post( { m : 'register', action : 'check_username' , user_username : input.val()} ,
                function(data){
                    $('#user_username').removeClass('input-loader');
                    if(data.content=='ok'){
                        input.addClass('input-checked');
                        input.parent('.control-group').removeClass('error');
                        $('#validateUsername').html('');
                        validUsername = true;
                    }else{
                        input.removeClass('input-checked');
                        input.removeClass('input-loader');
                        input.parent('.control-group').addClass('error');
                        $('#validateUsername').html(data.content);
                        validUsername = false;
                    }
                validateRegisterForm();
                });
              }, 200);
            input.attr('timer', timer);
            input.attr('lastValue', input.val());
          }
      }

      function validateRegisterForm(){
          if(validUsername==true && validEmail==true && validPassword == true && validPasswordConfirm == true){
              $('#registerBtn').removeClass('disabled');
          }else{
              if(!$('#registerBtn').hasClass('disabled')){
                  $('#registerBtn').addClass('disabled');
              }
          }
      }

      $('#registerBtn').click(function(){
          if(!$('#registerBtn').hasClass('disabled')){
              $('#registerForm').submit();
          }
      });
});