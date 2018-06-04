TASK DATE: 26.04.2018 - FINISHED: 08.05.2018

TASK LEVEL: HARD (API calls)


TASK SHORT DESCRIPTION: [Automatic login to support site]


GITHUB REPOSITORY CODE: feature/support-site-sso


ORIGINAL WORK: https://github.com/BusinessBecause/network-site/tree/feature/support-site-sso


CHANGES

	NEW FILES	
	
		\network-site\addons\shared_addons\modules\api\models\support_site_m.php
		\network-site\addons\shared_addons\modules\api\controllers\support_site.php
		\network-site\addons\shared_addons\modules\api\controllers\api_helper.php
		
 
	IN FILES: 
	
		\network-site\addons\default\modules\network_settings\controllers\network_settings.php
	
			ADDED NEW function 
			
				public function ajaxGenerateLoginToken() 
				{
					$token = "";
				
					//Checking the request is came vie AJAX call
					if ( ! $this->input->is_ajax_request() ) exit;
					
					//if user not Logged in - there's no chance for that, but this is just one more check before API call - then redirect to support site homepage
					if ( ! $this->ion_auth->logged_in() ) {
						echo $token;
					}				
					else {
						//If we are over the first two checks then loading some necessary stuffs
						$this->load->controller('api_helper', 'api');
						$this->load->model('users/user_m');				
						
						//Initialization before API call
						$this->api_helper->initilaziation(array(
							'host' => 'http://test39.toucantech.com',
							'api_class'	 => 'support_site'
						));

						//Getting user's data from DB
						$user = $this->user_m->get( $this->session->userdata('id') );

						//making a API call
						$token = $this->api_helper->call($method = 'generateTokenToLogin', $params = array('username' => $user->username, 'password' => $user->password));
					}

					echo $token;
				} //END function ajaxGenerateLoginToken
	
	
	
	
		\network-site\addons\default\themes\toucantechV2\views\partials\nb_header.html
		
			CHANGED CODE:
			
				FROM: 
				
					<li class="<?php if ( ! group_has_role('network_settings', 'help_about')) echo 'disabled' ?> <?php if($this->uri->segment(2)=="help-support") echo 'active' ?>">
					<?php if (group_has_role('network_settings', 'help_about')): ?>
						<a href="{{ url:site uri='admin-portal/help-support' }}">Support</a>
					<?php else: ?>
						<a href="#">Support</a>
					<?php endif; ?>    
					</li>
					
				TO: 
				
					<li class="<?php if ( ! group_has_role('network_settings', 'help_about')) echo 'disabled' ?> <?php if($this->uri->segment(2)=="help-support") echo 'active' ?>">
					<?php if (group_has_role('network_settings', 'help_about')): ?>
						<a class="action-generate-login-token" href="#">Support</a>
					<?php else: ?>
						<a href="#">Support</a>
					<?php endif; ?>    
					</li>
			
			
			ADDED CODE:
	
				<script>
					$(function()
					{	
						//small control for action of generating login token with API call
						//after generating token - redirection to https://support.toucantech.com/users/login/<token>
						//NOTE: token will be generated just in that case if user exists in both system with the same username and passwprd
						if ($('.action-generate-login-token')[0]) {
							$('.action-generate-login-token').live('click', function(event) {
								event.preventDefault();
								AJAX.call('network_settings/network_settings/ajaxGenerateLoginToken', {}, function(response) {
									location.href = 'http://test39.toucantech.com/users/login/' + response;
								});
								return false;
							})
						}
					})
				</script>
	
	
		\network-site\addons\default\modules\bbusers\models\bbuser_m.php
	
			ADDED functions
			
				/**
				 * getting user's ID by token from api_login_tokens table
				 * @input: 
				 *		- token: string 
				 * @return: 
				 *		- int: user's id
				 **/
				public function get_user_id_by_token($token)
				{
					$query = $this->db->where('token', $token)->get($this->db->dbprefix('api_login_tokens'));

					if ( $query->num_rows()  > 0 ) {
						return $query->row()->user_id;
					}
					
					return 0;
				}
				
				/**
				 * deleting from api_login_tokens table
				 * @input: 
				 *		- token: string 
				 * @return: VOID
				 **/
				public function delete_login_token($token)
				{
					$this->db->delete($this->db->dbprefix('api_login_tokens'), array('token' => $token)); 
				}
	
	
	
	
		\network-site\system\cms\modules\users\models\ion_auth_model.php
	
			CHANGED CODE I.:
			
				Inside function login
				
					added one more param to arg list of the function: , $userId = 0
	
					FROM: 
					
						if ( (empty($identity) || empty($password))) {
							return false;
						}
					
					TO:
					
						if ( (empty($identity) || empty($password)) and $userId == 0) {
							return false;
						}
	
					FROM: 
					
						$this->db->where(sprintf('(username = "%1$s" OR email = "%1$s")', $this->db->escape_str($identity)));
						
					TO: 
					
						if ( $userId > 0 ) { //Login happened via API call (probably)
							$rPassword = $this->input->post('remote_login_password');
							$this->db->where('id', $userId);
						}
						else {
							$this->db->where(sprintf('(username = "%1$s" OR email = "%1$s")', $this->db->escape_str($identity)));
						}
			
				
	
	
	
		\network-site\system\cms\modules\users\libraries\Ion_auth.php

			CHANGED CODE:
			
				Inside function login
				
					added one more param to arg list of the function: , $userId = 0
					
					FROM: if ($this->ci->ion_auth_model->login($identity, $password, $remember, $check))
					
					TO: if ($this->ci->ion_auth_model->login($identity, $password, $remember, $check, $userId))
				




		\network-site\system\cms\modules\users\controllers\users.php
		
			ADDED CODE:
			
				Inside function _check_login
				
					$userId = $this->input->post('remote_login_user_id') > 0 ? $this->input->post('remote_login_user_id') : 0;

			CHANGED CODE:
			
				Inside function _check_login 
				
					FROM:
						if ($this->ion_auth->login($email, $this->input->post('password'), $remember, $check)) {
							return true;
						}

					TO:
						if ($this->ion_auth->login($email, $this->input->post('password'), $remember, $check, $userId)) {
							return true;
						} 
					
		
	
		\network-site\addons\default\modules\bbusers\controllers\bbusers.php

			ADDED CODE:
			
				Inside function login 
				
					//If token exists, that means logging in is happening via API call 
					if ($token != '' ) {
						#getting user's id by token
						$userId = $this->bbuser_m->get_user_id_by_token($token);

						#if we have userId we will set some data which are necessary for login
						if ($userId > 0) {
							#getting user's data 
							$usersData = $this->bbuser_m->get($userId);
							
							#setting some POST variables
							$_POST['remote_login_token'] = $token;
							$_POST['remote_login_user_id'] = $userId;
							$_POST['remote_login_password'] = $usersData->password;
							$_POST['email'] = $usersData->email;
							$_POST['password'] = $token; #this is just fake - necessary for Form validation - but won't be used
							
							#set form_data for FORM validation
							$this->form_validation->set_data($_POST);
						}
						
						#deleting token from table anyway - these tokens can be used just once - these token are generated when we try true login via API calls
						$this->bbuser_m->delete_login_token($token);
					}					
					



		\network-site\system\cms\modules\users\models\user_m.php
		
			ADDED CODE: 
			
				Inside function get

					if (isset($params['username']) and isset($params['password']))
					{
						$this->db->where('users.username', $params['username']);
						$this->db->where('users.password', $params['password']);
					}



		
		\network-site\addons\shared_addons\modules\api\config\routes.php
		
			ADDED CODE:
		
				$route['api/support/site(/:any)?']		= 'support_site$1';
		
		
		
		\network-site\system\cms\config\rest.php
			
			ADDED CODE:
				
				$config['rest_api_user'] 	 = 'api_user'; 
				$config['rest_api_password'] = 'Zahajdu77a6adshflk8asd';
				$config['rest_valid_logins'] = array($config['rest_api_user'] => $config['rest_api_password']);
		
		
		
		
		\network-site\addons\shared_addons\modules\api\details.php
		
			ADDED NEW functions
			
				private function _insertApiKeysTable() 
				{
					$table = $this->db->dbprefix('api_keys');
					$date = date('Y-m-d H:i:s');
					
					if ( ! $this->db->value_exists('support_site_jk9lhjasdfhakjd', 'key', $table)) {
					   $this->db->insert($table, array(
							'created' => $date, 
							'created_by' => '1',
							'key' => 'support_site_jk9lhjasdfhakjd',
						));
					}
				} //END function _insertApiKeysTable

				

				private function _createTableApiLoginTokens()
				{
					$this->db->query(
						"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix("api_login_tokens") . "` ( " .
						" `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
						" `token` varchar(20) NOT NULL,  " .
						" `user_id` varchar(11) NOT NULL " .
						") ENGINE=InnoDB  DEFAULT CHARSET=utf8;"
					);	
				} //END function _createTableApiLoginTokens				
		
		
			ADDED CODE I. 
			
				Inside function Install 
				
					$this->_insertApiKeysTable(); 
					$this->_createTableApiLoginTokens();
					
			ADDED CODE II. 
			
				Inside function upgrade
				
					//Adding API fields to the settings table
					if( version_compare($old_version, '1.0.1', 'lt') ) {
					  $this->_insertApiKeysTable(); 
					  $this->_createTableApiLoginTokens();
					}
