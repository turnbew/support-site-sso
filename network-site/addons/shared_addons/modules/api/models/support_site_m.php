<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Support_site_m extends MY_Model
{
	var $tokenTable;

	
	function __construct() 
	{
        parent::__construct();

        $this->tokenTable = $this->db->dbprefix('api_login_tokens');
    }//END function __construct
	
	
	
	public function insertToken($token = '', $userId = '')
	{
		$result = false;
		
		if ( $token != '' and $userId != '' ) {
			$result = $this->db->insert($this->tokenTable, array('token' => $token, 'user_id' => $userId));
		}
		
		return $result;
	}//END function insertToken
	

} //END class Support_site_m