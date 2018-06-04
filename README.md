FOR PRIVACY AND CODE PROTECTING REASONS THIS IS A SIMPLIFIED VERSION OF CHANGES AND NEW FEATURES

TASK DATE: 26.04.2018 - FINISHED: 08.05.2018

TASK LEVEL: HARD (API calls using JSON and REST API)

TASK SHORT DESCRIPTION: [Automatic login to support site]


GITHUB REPOSITORY CODE: feature/support-site-sso


CHANGES

	NEW FILES	
	
		- support_site_m.php
		- support_site.php
		- api_helper.php
		
 
	IN FILES: 
	
		network_settings.php
	
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
						................
					}

					echo $token;
				} //END function ajaxGenerateLoginToken
	
	
	
	
		nb_header.html
		
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
						//NOTE: token will be generated just in that case if user exists in both system with the same username and passwprd
						if ($('.action-generate-login-token')[0]) {
							$('.action-generate-login-token').live('click', function(event) {
								event.preventDefault();
								AJAX.call('ajaxGenerateLoginToken', {}, function(response) {
									location.href = '..../login/' + response;
								});
								return false;
							})
						}
					})
				</script>
	
	
		bbuser_m.php
	
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
	
	
	
	
		ion_auth_model.php
	
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
			
				
	
	
	
		Ion_auth.php

			CHANGED CODE:
			
				Inside function login
				
					added one more param to arg list of the function: , $userId = 0
					
					FROM: if ($this->ci->ion_auth_model->login($identity, $password, $remember, $check))
					
					TO: if ($this->ci->ion_auth_model->login($identity, $password, $remember, $check, $userId))
				




		users.php
		
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
					
		
	
		bbusers.php

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
							
							................
							
							#set form_data for FORM validation
							$this->form_validation->set_data($_POST);
						}
						
						#deleting token from table anyway - these tokens can be used just once - these token are generated when we try true login via API calls
						$this->bbuser_m->delete_login_token($token);
					}					
					



		user_m.php
		
			ADDED CODE: 
			
				Inside function get

					if (isset($params['username']) and isset($params['password']))
					{
						$this->db->where('users.username', $params['username']);
						$this->db->where('users.password', $params['password']);
					}


		
		rest.php
			
			ADDED CODE:
				
				$config['rest_api_user'] 	 = 'api_user'; 
				$config['rest_api_password'] = 'Zahajdu77a6adshflk8asd';
				$config['rest_valid_logins'] = array($config['rest_api_user'] => $config['rest_api_password']);
		
		
		
		
		details.php
		
			ADDED NEW functions
			
				private function _insertApiKeysTable() 
				{
					$table = $this->db->dbprefix('api_keys');
					$date = date('Y-m-d H:i:s');
					
					.................
				} //END function _insertApiKeysTable

				

				private function _createTableApiLoginTokens()
				{
					$this->db->query(
						............
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
