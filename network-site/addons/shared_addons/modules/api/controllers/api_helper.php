<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


	/* API helper class - getting and handling API calls' results
	 * 
	 * Created by: Lajos Deli (Lali) - lali@toucantech.com
	*/

	 
	class Api_helper extends MY_Controller
	{	
	
		/* Name of the domain, which is target of the API call
		 * Type: string, example: http://support.toucantech.com
		 * Must be set before API call, generally via Initialization function
		*/
		private $host;
		
		/* Number of the port of the domain, which is target of the API call
		 * Type: Integer, example: 8080
		 * Not required, can be set generally via Initialization function
		*/	
		private $port;
		
		/* API call timeout, while we waiting for answer, given in miliseconds
		 * Type: Integer
		 * Not required, can be set generally via Initialization function
		*/		
		private $timeout;
		
		/* Exact URL to the API controller, part of the full URL ... generally the host, url and port will give us the full URL
		 * Type: string, example: api/support_site
		 * Not required, can be set generally via Initialization function
		 * NOTE: if apiClass variable is defined and url is empty string, in that case url will be created from using $apiClass variable.
		*/	
		private $url;
		
		/* Name of the API controller
		 * Type: String, example: support_site
		 * Required, can be set generally via Initialization function
		*/	
		private $apiClass;
		
		
		/* a generated KEY - generally comes from DB, api_keys table - without that there's no communictaion between two servers
		 * Type: String, example: support_site_lkasudfhHDSZSK2131 - check for keys in api_keys table
		 * Required
		*/	
		private $apiKey;
		
		
		/* Name of the API request type
		 * Type: String, can be post, get ... default is post 
		 * Required, can be set generally via Initialization function
		*/	
		private $requestType = 'post';

		
		
		public function __construct() 
		{
			parent::__construct();
			
			$this->load->config('rest');
			
			$this->load->model('api/api_key_m');
		}//End function __construct
		
		
		
		

		/* Setting API call's parameters 
		* config - params 
		*		- host: string: url to the page/server - i. e.: http://show.toucantech.com/
		*		- url: string: part of the full url, path to the API control. If API class is defined then: api/<api_class>/. i.e. api/support_site/	
		*		- port: int: number of the api call port - optional
		*		- timeout: int : CURLOPT_TIMEOUT 
		*		- class: string: API controller's name - this can be part of the URL if that's not defined
		*		- apiKey: string: generally comes from DB - but can be defined in settings as well
		*/
		public function initilaziation($settings = array()) 
		{	
			$this->host = isset($settings['host']) ? $settings['host'] . (( ! isset($settings['port']) OR $settings['port'] == '') ? '/' : '') : 'http://localhost';
			
			$this->url = '';
			if ( isset($settings['url']) ) {
				$this->url = '%s%s' . $settings['url'] . '%s%s';
			}
			else if ( isset($settings['api_class']) ) {
				$this->url = '%s%s' . 'api/' . str_replace('_', '/', $settings['api_class']) . '/%s%s';
			}
 			
			$this->port = ((isset($settings['port']) AND $settings['port'] != '') ? ':' . $settings['port'] . "/" : '');
			
			$this->timeout = isset($settings['timeout']) ? $settings['timeout'] : 10;
			
			$this->apiClass = isset($settings['api_class']) ? $settings['api_class'] : '';
			
			$this->apiKey = (isset($settings['api_class']) and $settings['api_class'] != '') ? $this->api_key_m->getKeyByPart($settings['api_class']) : NULL;
		}//End function initilaziation



		


		public function createKey($method) 
		{
			if(is_array($method)) {
				$method = json_encode($method);
			}
			return hash('sha256', $this->username . $method . $this->password);
		}//End function createKey






		public function makeUrl($method = '', array $args = array()) 
		{
			$json = array();
			
			if ( count($args) > 0 ) {
				$json['arguments'] = $args;
				$json['username'] = $this->username;
				$json['password'] = $this->password;
				if ($method != '') $json['key'] = $this->createKey($method); 
			}
			
			$parameters = count($json) > 0 ? rawurlencode(json_encode($json)) : ''; 
			
			return sprintf($this->url, $this->host, $this->port, $method, $parameters);
		}//End function makeUrl






		private function curl($method, $params = array() ) 
		{
			//Adding extra details to the API call such as: api-key, api username, api password, called API class and method
			$params['api_param_method'] = $method;
			$params['api_param_class'] = $this->apiClass;
			$params['api_param_username'] = $this->config->item('rest_api_user');
			$params['api_param_password'] = $this->config->item('rest_api_password');
			$params[$this->config->item('rest_key_name')] = $this->apiKey;
			
			//Ha request type is POST in that case we're creating URL without parameters (those will be POSTED)
			$args = ($this->requestType == 'post') ? array() : $params;
			
			$url = $this->makeUrl($method, $args);

			if( extension_loaded('curl') ) {
				$ch = curl_init($url);
				if ( $this->requestType == 'post' ) {		
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
					curl_setopt($ch, CURLOPT_POST, true);
				}
				curl_setopt($ch, CURLOPT_PORT, $this->port);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);	
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

				$result = curl_exec($ch);			
				
				curl_close($ch);		
				
				return $result;
			} 
			else {
				$url = $this->makeUrl($method, $params);
				
				$opts = array(
					'http' => array(
						'timeout' => $this->timeout
					)
				);
				return file_get_contents($url, false, stream_context_create($opts));
			}
		}//End function curl		
			
		
		
		
	
		public function call($method, $params = array())
		{ 
			$result = json_decode($this->curl($method, $params), true);

			if ( ! $result['status'] ) 
			{
				return $result['error']; 
			}
			else {
				return $result['result'];
			}
		}//END function call


		
	}//END class Api_helper 
	
?>