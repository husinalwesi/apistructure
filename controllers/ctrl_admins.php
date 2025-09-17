<?php
class admins extends mainController
{
	var $table = "admin";
	var $querySelector = array('id', 'username', 'isdeleted');
	public function __construct()
	{
		// handle header params // token // language // and translate for api response message
		// handle file upload - single and multiple
		// in put i used x-www-form-urlencoded i guess we will change it, since it should be a multiform to accept files, we need to control this one.
		$this->callMethod($this);
	}

	public function mainAPI()
	{
		$params = $this->extractUrlParams();
		if(count($params) === 0 && $this->isAllowedMethod('GET')) {
			$this->getItems();
		} elseif(count($params) === 1 && $this->isAllowedMethod('GET')) {
			$this->getItemByID($params[0]);
		} elseif(count($params) === 0 && $this->isAllowedMethod('POST')) {
			$this->createItem();
		} elseif(count($params) === 1 && $this->isAllowedMethod('PUT')) {
			$this->updateItem($params[0]);
		} elseif(count($params) === 1 && $this->isAllowedMethod('DELETE')) {
			$this->deleteItem($params[0]);
		}

      $this->getResponse(422, "Method not supported");	
	}

	public function getItems(){		
		$this->checkAuth();
		$querySelectorString = $this->getQuerySelector($this->querySelector);		
		$data = array();
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table where isdeleted='0'"));
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function getItemByIDFn($userID){
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from $this->table where CAST(id AS CHAR)='$userID'");
		if(!$result || count($result) === 0) return null;
		$data = array();		
		$data = $this->modelAllData($result);		
		if(!$data || count($data) === 0) return null;
		return $data[0];
	}

	public function getItemByID($userID){
		$this->checkAuth();
		$data = $this->getItemByIDFn($userID);
		if(!$data) $this->getResponse(404, "No data found for the given ID.");
		$this->dataArray = $data;
		$this->getResponse(200);		
	}
	/////////////////////////////////////////////////////////////

	public function createItem(){
		$this->checkAuth();

		$params = array(
			'id' => '',
			'username' => $this->getSecureParamsBody('username'),
			'isdeleted' => ''
		);
		if (!$newUserID = $this->queryInsert($this->table, $params)) $this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($newUserID);

		$this->dataArray = $data;		
		$this->getResponse(200, 'created successfully..');
	}

	public function updateItem($userID){
		$this->checkAuth();
		$putData = $this->getSecureParamsPut();
		if(!$putData['username']) $this->getResponse(503, "Missing parameter username");
		$params = array();
		$params['username'] = $putData['username'];
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$userID'")) $this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($userID);

		$this->dataArray = $data;
		$this->getResponse(200, 'updated successfully..');
	}
	
	public function deleteItem($userID){
		$this->checkAuth();
		$params = array();
		$params['isdeleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$userID'")) $this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, 'deleted successfully..');
	}


	public function profileAPI()
	{
		$this->setAllowedMethod("GET");
		$this->checkAuth();
		// 
		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$data = $this->getAdminDetails($id);
		if (!$data)
			$this->getResponse(204);
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function currentUserDetailsAPI()
	{
		$data = $this->getCurrentUserDetails(array('isPasswordEnabled' => false));
		$this->dataArray = $data['formattedData'];
		$this->getResponse(200);
	}

	public function loginAPI()
	{
		$this->setAllowedMethod("POST");
		// Without auth check..
		$params = $this->getPayload();
		$email = $params->email;
		$password = $params->password;
		if (!$email || !$password)
			$this->getResponse(201, "Missed Data.");
		//
		$password = md5($password);

		$tempQuerySelector = $this->querySelector;
		$tempQuerySelector[] = "basicAuth";
		$querySelectorString = $this->getQuerySelector($tempQuerySelector);
		$data = array();
		// 
		$data = $this->queryResponse("select $querySelectorString from $this->table where email='$email' and password='$password' and is_deleted='0'");
		if (!$data)
			$this->getResponse(503, "invalid credentials.");
		$userDetails = $data[0];
		// 
		if ($userDetails["status"] != 1)
			$this->getResponse(503, "User Inactive.");
		// 
		$this->dataArray = $this->modelAdminData($userDetails);
		// $this->setIPLog($userDetails["id"]);
		$this->getResponse(200, "User successfully logged in.");
	}

	public function createAPI()
	{
		$this->setAllowedMethod("POST");
		$this->checkAuth();
		// 
		$params = array(
			'username' => '',
			'full_name' => '',
			'email' => '',
			'img' => '',
			'password' => ''
		);

		$email = "";
		$password = "";

		$payload = $this->getPayload();
		foreach ($params as $key => $value) {
			if (!$payload->$key && ($key != "img" && $key != "password"))
				$this->getResponse(201, "Missing Parameter `$key`");
			$params[$key] = $key === "password" ? md5($payload->$key ? $payload->$key : DEFAULT_PASSWORD) : $payload->$key;
			// 
			if ($key === "email")
				$email = $payload->$key;
			if ($key === "password")
				$password = $params[$key];
		}

		$params['basicAuth'] = $this->getBasicAuthorization($email, $password);
		$params['created_date'] = time();
		$params['is_deleted'] = 0;
		$params['status'] = 1;

		$isEmailInUse = $this->queryResponse("select id from $this->table where email='$payload->email'");
		if ($isEmailInUse)
			$this->getResponse(201, "Email already exist.");


		if (!$newUserID = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");
		// send mail..

		$this->dataArray = $this->getAdminDetails($newUserID);
		$this->getResponse(200, "Admin created successfully.");
	}

	public function updateAPI()
	{
		$this->setAllowedMethod("PATCH");
		$this->checkAuth();
		// 
		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$oldUserDetails = $this->getAdminDetails($id);
		if (!$oldUserDetails)
			$this->getResponse(204);

		$params = array(
			'username' => '',
			'full_name' => '',
			'img' => '',
		);

		$payload = $this->getPayload();
		foreach ($params as $key => $value) {
			if ($payload->$key)
				$params[$key] = $payload->$key;
			else
				unset($params[$key]);
		}

		if (count($params) == 0)
			$this->getResponse(503, "Missing Parameters.");

		if (!$this->queryUpdate($this->table, $params, "where id='$id'"))
			$this->getResponse(503, "An Error Occure.");


		$this->dataArray = $this->getAdminDetails($id);
		$this->getResponse(200, "Admin updated successfully.");
	}

	public function deleteAPI()
	{
		$this->setAllowedMethod("DELETE");
		$this->checkAuth();
		// 
		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$oldUserDetails = $this->getAdminDetails($id);
		if (!$oldUserDetails || $oldUserDetails['isDeleted'])
			$this->getResponse(204);

		$params = array(
			'is_deleted' => '1'
		);

		if (!$this->queryUpdate($this->table, $params, "where id='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, "Admin Soft Deleted successfully.");
	}

	public function unDeleteAPI()
	{
		$this->setAllowedMethod("PATCH");
		$this->checkAuth();
		// 
		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$oldUserDetails = $this->getAdminDetails($id);
		if (!$oldUserDetails)
			$this->getResponse(204);

		if (!$oldUserDetails['isDeleted'])
			$this->getResponse(503, "Cannot Undelete undeleted admin");


		$params = array(
			'is_deleted' => '0'
		);

		if (!$this->queryUpdate($this->table, $params, "where id='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, "Admin Un-Deleted successfully.");
	}

	public function activateAPI()
	{
		$this->setAllowedMethod("PATCH");
		$this->checkAuth();
		// 
		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$oldUserDetails = $this->getAdminDetails($id);
		if (!$oldUserDetails)
			$this->getResponse(204);

		if ($oldUserDetails['isActive'])
			$this->getResponse(503, "Admin already activated.");


		$params = array(
			'status' => '1'
		);

		if (!$this->queryUpdate($this->table, $params, "where id='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, "Admin Activated successfully.");
	}

	public function deActivateAPI()
	{
		$this->setAllowedMethod("PATCH");
		$this->checkAuth();
		// 
		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$oldUserDetails = $this->getAdminDetails($id);
		if (!$oldUserDetails)
			$this->getResponse(204);

		if (!$oldUserDetails['isActive'])
			$this->getResponse(503, "Admin already deactivated.");

		$params = array(
			'status' => '0'
		);

		if (!$this->queryUpdate($this->table, $params, "where id='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, "Admin Deactivated successfully.");
	}

	public function changePasswordAPI()
	{
		$this->setAllowedMethod("POST");
		$data = $this->getCurrentUserDetails(array('isPasswordEnabled' => true)); //this will check and get the user data
		$id = $data['formattedData']['id'];

		$payload = $this->getPayload();

		if (!$payload->password || !$payload->oldPassword)
			$this->getResponse(503, "Missing Params");

		if (md5($payload->oldPassword) !== $data['unFormattedData']['password'])
			$this->getResponse(503, "Wrong password");


		$password = md5($payload->password);
		$params = array(
			'password' => $password,
			'basicAuth' => $this->getBasicAuthorization($data['formattedData']['email'], $password)
		);

		if (!$this->queryUpdate($this->table, $params, "where id='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, "Password updated successfully.");
	}

	// 

	public function modelAdminData($temp)
	{
		$userID = +$temp['id'];
		// $username = $temp['username'];	
		// $username = $temp['isdeleted'];	
		// $lastLoginDetails = $this->getLastUserLoginDetails($userID);
		$data = array();

		// if ($temp['basicAuth'])
		// 	$data['authentication'] = $temp['basicAuth'];

		$data['id'] = $userID;
		$data['username'] = $temp['username'];		
		$data['isdeleted'] = $temp['isdeleted'];				
		// $data['username'] = $temp['username'];
		// $data['fullName'] = $temp['full_name'];
		// $data['email'] = $temp['email'];
		// $data['createdDate'] = $this->timeStampToDate($temp['created_date'], "dateTime");
		// $data['isDeleted'] = $this->toBoolean($temp['is_deleted']);
		// $data['image'] = $temp['img'];
		// $data['isActive'] = $this->toBoolean($temp['status']);
		// // 
		// $data['loginDetails'] = $lastLoginDetails ? array() : false;
		// if ($lastLoginDetails) {
		// 	$data['loginDetails']['ip'] = $lastLoginDetails['ip'];
		// 	$data['loginDetails']['createdDate'] = $lastLoginDetails['created_date'] ? $this->timeStampToDate($lastLoginDetails['created_date'], "dateTime") : '';
		// }
		return $data;
	}

	function modelAllData($temp)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelAdminData($temp[$key]);
		}
		return $data;
	}

	function getLastUserLoginDetails($userID)
	{
		$query = "ip,created_date";
		$table = "users_log";
		$last_login = $this->queryResponse("select $query from $table where user_id='$userID'");
		return $last_login ? $last_login[0] : false;
	}

	function getAdminDetails($id)
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = $this->queryResponse("select $querySelectorString from $this->table where id=$id");
		if (!$data)
			return false;
		return $this->modelAdminData($data[0]);
	}

}
?>