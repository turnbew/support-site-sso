<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Support SITE API call's controller - Created by Lajos Deli (alias lali) - lali@toucantech.com
 */

class Support_site extends API_Controller 
{



    function __construct() 
	{	
		//loading rest (API) config file \system\cms\config\rest.php
		$this->load->config('rest');

		//Checking API password and username - this is the first (before other 3-4) check before we give access to the DB to anybody
		if ( $this->input->post('api_param_username') != $this->config->item('rest_api_user') or $this->input->post('api_param_password') != $this->config->item('rest_api_password') ) {
			 $this->response(array('status' => false, 'error' => "Wrong REST API user's name and/or password" ), 401); exit;
		}	
		
		//loading api_helper controller - for requests via CURL, shared_addons/modules/api/api_helper.php
		$this->load->controller('api_helper', 'api');	

		//Important - parent's construct function just called the end of this method, because there'll be another 3 checks in that class as well
		parent::__construct();
		
    }//END function __construct

	
	
	
	
	
	/* method which is call via API - note: _post or _get must be put end of the called method, depend on request type
	 * @input 
	 *	- $username : string 
	 *  - $password : string
	 * @return
	 *	- url of the support domain's login site with or without token
	*/
    public function generateTokenToLogin_post($username, $password) 
	{
		//Loading some models
		$this->load->model('users/user_m');		
		$this->load->model('api/support_site_m');	
		
		//Checking user's username and password pair in users table
		$user = $this->user_m->get(array('username' => $username, 'password' => $password));
		
		//if user exist in DB, in that case we create a one-time used token and that token will be bound the user's ID
		$token = '';
		if ( count($user) > 0 ) {
			#creating a new unique (random) token - it will be used just one time 
			$token = random_string('alnum', 20);
			
			#insert new token into DB
			if ( ! $this->support_site_m->insertToken($token, $user->id) ) {
				$token = '';
			}
		}
		
		//Return with response
		$this->response(array('status' => true, 'result' => $token));
	}//END function generateTokenToLogin_post
		
		
		

} //END class checkApiUser_post