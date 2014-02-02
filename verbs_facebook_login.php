<?php /*
Plugin Name: Verbs Facebook Login
Description: Facebook Login intergration with wordpress
Version: 1.0
Author:Verb
Author URI: http://formula04.com
*/ 

$fb = new VerbFBLogin();



class VerbFBLogin{
	
  private $plugin_path;	
  private $app_id;	
  private $app_secret;	
  private $site_url;
  private $fb_instance;	
		
  function __construct() {	
 
 	//Make this  $this->plugin_path accessible for all function sin this class.
	 $this->plugin_path =  plugin_dir_path( __FILE__ );
	 
	 //Ap ID
	  $this->app_id = get_option('vfbl_app_id');;
	  //Ap Secret
	  $this->app_secret =  get_option('vfbl_app_secret');
	  //Application Configurations
	  $this->site_url = get_option('vfbl_site_url');	 
	 
	 //Include Facebook PHP SDK 
	 require_once($this->plugin_path.'admin/facebook_admin.php');
	 require_once($this->plugin_path.'facebook_php_sdk/facebook.php');
	 
	  //Create a new FB instance user our app id and app secret.	   
	 $this->fb_instance = new Facebook(array(
		'appId'		=> $this->app_id,
		'secret'	=> $this->app_secret,
	   ));

   
  	//Intialize Plugin
 	 add_action( 'init', array( $this, 'init' ), 3 );
	 
	 if ( ! is_admin() ):
	 //Log the user in AND or Create New Account
	 add_action('init', array( $this, 'fb_log_or_create' ), 4);
	 endif;
  
  }//End Construct
  
  function init(){
	  
	//Add FB Button to login Form
	add_action('login_form', array( $this, 'fb_access_button' ));  
	
	//Add FB Button to Register Form
	add_action('register_form', array( $this, 'fb_access_button' )); 
		
	//Add Styles to login Page
	add_action('login_enqueue_scripts',  array( $this, 'front_end_styles' ));
	
	//Destory Session on logout
    add_action('wp_logout', array( $this, 'logout_destroy_session' ));
	
	
	 
	
  }//init
  
  
  
  function fb_log_or_create(){
	  $facebook =  $this->fb_instance; 
	  
	  //See if we have a USER
	  $user = $facebook->getUser();
	  
	  if ($user):
		  //We have a user already logged into our APP
		  try {
		  // Proceed knowing you have a logged in user who's authenticated.
		  $user_profile = $facebook->api('/'.$user);
		  $access_token = $facebook->getAccessToken();
		  
		  $valid_user = true;
		  
		  //Lets Verify the accesstoken.
		  $verify_access_token = json_decode(file_get_contents('https://graph.facebook.com/me?access_token='.$access_token));
		  
		  //We got back a json object, now lets make sure its set to verify.
		  if($verify_access_token):
		 
		 
			  if($verify_access_token->verified):
			  //Access token is cool
			  //User is logged into our app
			  //So lets log them into our wordpress universe.  
			  //We may neeed to create an account for them first.
			  
			  //Lets check if there is already a user with this FB ID in our wordpress universe.
			  $existing_fb_user = get_users( array('meta_key' => 'facebook_id', 'meta_value' => $verify_access_token->id  ) );
			  if($existing_fb_user):
			  	//We have an existing FB user in out wordpress universe, so lets log them in.
				 $this->facebook_logemin($existing_fb_user[0]->ID);			  
				
				
			  else:
				  //We don't have an existing user, we need to create one.
				  //Check if email exists within our wordpress universe.			
				  $email_exists =  email_exists($verify_access_token->email);
				  //if this email does exists
				  if($email_exists):
					//Add Facebook ID to this Users Usermeta
					update_user_meta( $email_exists, 'facebook_id', $verify_access_token->id);	
					
					//$email_exists should have retuned the user id.
					//Lets log this user in.
					 $this->facebook_logemin($email_exists);	
				  else:
				 	 $user_email = $verify_access_token->email;
						  
				  endif;
				  
				  //So now we know email did not exists already, and there is no FB user already in our wordpress universe.
				  //We need to create a new user.
				  //username
				  $user_name = username_exists( $verify_access_token->username );
				  
				  if($user_name != NULL):
					  $user_name = false;
					  
					  //We have a problem.
					  //This username already exists in our wordpress universe, but it is not connected to this FB account.
						 
					  //We have a potential username the user has submitted to be intergrated.	  
					  if(isset($_POST['fb_intergration_username_nonce'])):
						if(wp_verify_nonce( $_POST['fb_intergration_username_nonce'], 'fb_intergration_username_action' )):
							$user_name = !empty($_POST['fb_intergration_username']) ? $_POST['fb_intergration_username'] : false;	
							//Do we have a user name
							if($user_name):
								//Does this user name exists
								$user_name = username_exists($user_name );
								
								if($user_name):
									$_POST['fb_intergration_username_error'] = 'This Username already exists';
									$user_name = false;
								else:
									$user_name = $_POST['fb_intergration_username'];
								endif;
								
								
								
							
							endif;					
						 endif;
					  endif;// if(isset($_POST['fb_intergration_username_nonce'])):
					
								 
				  else:
				  //username
				  $user_name = $verify_access_token->username;
				  endif;// if($user_name != NULL):		
					
				  
				  //If we have issues with the user name	
				  if(!$user_name):
					   $this->reload_with_usename_field();	
				  
				  
				  else:  
				  //We have a clean username, clean email, lets go ahead and create this user and log them in.
				
				 //random password					
				  $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
				  //Create the actual user.
				  $user_id = wp_create_user( $user_name, $random_password, $user_email );
				  
					  //If our user was sucessfully created
					  if($user_id):
						  //Add FB ID to their user table.
						  update_user_meta( $user_id, 'facebook_id', $verify_access_token->id);	
						  //Create this user and log them in.
						  $this->facebook_logemin($user_id);		
					  endif;//if($user_id):
				  
				  endif;// if(!$user_name):  
			  
			  endif;
		
		  endif;// if($verify_access_token->verified):	
			
	  else:
	  //Access Token is bogus || no json object was returned.
	  endif;// if($verify_access_token):

	  
	  } catch (FacebookApiException $e) {
	  error_log($e);
	  $user = null;
	  }
  endif;
 } //fb_log_or_create 
  
  private function reload_with_usename_field(){
	 add_thickbox(); 
	 
	 add_action('before_fb_access_button_wrapper', array( $this, 'show_username_field' ));
	 add_action('wp_footer', array( $this, 'thickbox_autopop'));
	 add_action('login_footer', array( $this, 'thickbox_autopop'));
	 
	 //add_action( 'login_head', array( $this, 'add_to_login_error' ));  
     //add_filter( 'shake_error_codes', array( $this, 'shake_error_codes_filters' ) );  
   }//reload_with_usename_field
   
   
   
   function thickbox_autopop(){?>
   <div id="my-content-id" style="display:none;">
   
    <form method="post">
     <div id="login_error">
     Your Facebook username is unavailable on this site. Please select a new one using the <strong>Facebook Intergration Username</strong> field below.
        <?php 
		
          if( isset($_POST['fb_intergration_username_error']) ): 
              echo '<br><strong style="color:red">'.$_POST['fb_intergration_username_error'].'</strong>'; 
          endif; 
        ?>
     </div>
      <p class="fb_access_button_wrapper">
      Facebook Intergration Username
	    <br />
         <?php echo wp_nonce_field( 'fb_intergration_username_action', 'fb_intergration_username_nonce' ); ?>
       	  <input type="text" name="fb_intergration_username" id="user_login" class="input" value="<?php echo isset($_POST['fb_intergration_username']) ? $_POST['fb_intergration_username'] : ''; ?>" size="20">
	  </p>
      <button  id="fb_access_button" class="verb_fb_login button button-primary button-large">Complete Facebook Connection</button>
    </form>   
   </div>
	
    
   
   
	   <script type="text/javascript">
	  	 window.onload = function(){
	     document.getElementById("fb_int_un").click()		 
		 }
	   </script>
	   
	   
  <?php }
   
   
   
  
 //Add our specific error name to the shake error code array
 //This way it will shake and register as an error.  
 function shake_error_codes_filters($filter){
	 $filter[] = 'fb_username_already_in_use';
	 return $filter; 
 }//shake_error_codes_filters
 
 function add_to_login_error(){	 
	global $errors;
	   $errors->add('fb_username_already_in_use', __("ERROR: Your Facebook username is unavailable on this site. Please select a new one using the <strong>Facebook Intergration Username</strong> field below. "));
	   
 }//add_to_login_error
 
 function show_username_field(){?>
    <a id="fb_int_un" href="#TB_inline?width=300&inlineId=my-content-id" class="thickbox">Add Facebook Intergration Username</a>	
 <?php }//show_username_field
 
 
  private function facebook_logemin($user_id){
		$user = get_user_by( 'id', $user_id ); 
		wp_set_current_user( $user_id );
       	wp_set_auth_cookie( $user_id );
		//do_action( 'wp_login', $user->user_login );		
       //$redirect_to = get_permalink(get_admin_url());
   		
		$logintime = isset($_GET['loginmeinfb']) ? $_GET['loginmeinfb'] : '';
		if($logintime == 'true'):
		$redirect_me = get_bloginfo('url').'#fblogin=success';
		wp_redirect($redirect_me);		
		exit();
 		endif;
	  
	  
  }//facebook_logemin
  
  
  
  public function logout_destroy_session(){
	 session_destroy();	  
  }//logout_destroy_session(){
  
  
  function fb_access_button(){
	  
	  //Load our facebook instance
	  $facebook =  $this->fb_instance;
	  
	  //If user is not lgged in.
	  if ( !is_user_logged_in() ):
      
	  //Get Facebook Login Button      
      $loginUrl = $facebook->getLoginUrl(array(
		'scope'		=> 'read_stream, publish_stream, user_birthday, user_location, user_work_history, user_hometown, user_photos, email',
		'redirect_uri'	=> wp_login_url().'?loginmeinfb=true',
		'response_type' => 'code'
		));
      
	  
	  do_action('before_fb_access_button_wrapper');
	  if(isset($_GET['action']) && $_GET['action']  == 'register'):
	  	$fb_button_text = 'Register With Facebook';
	  else:
	  	$fb_button_text = 'Log In With Facebook';
	  endif;
	  
	  
	  ?>
      <p class="fb_access_button_wrapper">
       	  <a href="<?php echo $loginUrl; ?>"  id="fb_access_button" class="verb_fb_login button button-primary button-large"><?php echo $fb_button_text ?></a>
	  </p>
 	
	
	<?php 
 	   endif;//if ( !is_user_logged_in() ): ?>
 
 
 
  <?php }//facebook_login_button	
  
  
  function front_end_styles(){?>
	<style type="text/css">
   #login form p.fb_access_button_wrapper, .fb_access_button_wrapper{
		display:block;
		overflow:hidden;
		margin:10px auto;
		
	}
    
	#fb_access_button{
		width: 100%;
		text-align: center;
		overflow: hidden;
		vertical-align: middle;
		line-height:44px;
		height: 44px;
		background-color:#4c66a4;
		border-color:#394C7A;
		-webkit-box-shadow: inset 0 1px 0 rgba(110, 149, 207,.5),0 1px 0 rgba(0,0,0,.15);
		box-shadow: inset 0 1px 0 rgba(110, 149, 207,.5),0 1px 0 rgba(0,0,0,.15);
		color: #fff;
		
		}
    
    
    
    </style>
  <?php }//front_end_styles	
  
  
  
  
  
  }//VerbFBLogin


