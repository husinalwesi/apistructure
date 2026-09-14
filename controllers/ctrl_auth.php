<?php
class auth extends mainController
{
	var $table = "admin";
	var $querySelector = array('id', 'username', 'password', 'created_date', 'isDeleted', 'name', 'role');
	public function __construct()
	{
		// 		
		// handle header params // token


		// $uploadedFilesPaths = $this->uploadMedia($payload['files']); // to upload files
		// http://localhost:8080/apistructure/uploads/1759093200/68da69a1c19e2_chrome-un-secure.txt		
		$this->callMethod($this);
	}

	public function mainAPI()
	{
		$params = $this->extractUrlParams();
		if ($params[0] === 'signin' && $this->isAllowedMethod('POST')) {
			$this->signin();
		} else if ($params[0] === 'refreshtoken' && $this->isAllowedMethod('POST')) {
			$this->refreshtoken();
		} else if ($params[0] === 'signout' && $this->isAllowedMethod('POST')) {
			$this->signout();
		}
		//  else if ($params[0] === 'logs' && $this->isAllowedMethod('GET')) {
		// 	$this->getLogs();
		// }
		 else if (count($params) === 0 && $this->isAllowedMethod('GET')) {
			$this->getItems();
		} elseif (count($params) === 1 && $this->isAllowedMethod('GET')) {
			$this->getItemByID($params[0]);
		} elseif (count($params) === 0 && $this->isAllowedMethod('POST')) {
			$this->createItem();
		} elseif (count($params) === 1 && $this->isAllowedMethod('PUT')) {
			$this->updateItem($params[0]);
		} elseif (count($params) === 1 && $this->isAllowedMethod('DELETE')) {
			$this->deleteItem($params[0]);
		}

		$this->getResponse(422, "Method not supported");
	}

	public function refreshtoken(){
	    $authorization = $this->getAuthorizationToken();	
		$payload = $this->getRequestData();
		$refresh_token_hash = $payload['fields']['refresh_token'];
		if(!$authorization || !$refresh_token_hash) $this->getResponse(501, 'Missing refresh_token or Authorization');

		$tokenDetails = $this->queryResponse("select * from api_tokens where access_token_hash='$authorization' and refresh_token_hash='$refresh_token_hash'");
		if (!$tokenDetails) $this->getResponse(501, 'Invalid Token');

		// 
		$accessToken = $this->generateToken();
		$refreshToken = $this->generateToken();
		$accessTokenHash = $this->hashToken($accessToken);
		$refreshTokenHash = $this->hashToken($refreshToken);

		$params = array();
		$params['access_token_hash'] = $accessTokenHash;
		$params['refresh_token_hash'] = $refreshTokenHash;
		$params['access_expires_at'] = date('Y-m-d H:i:s', strtotime('+45 minutes'));
		$params['refresh_expires_at'] = date('Y-m-d H:i:s', strtotime('+30 days'));		

		if (!$this->queryUpdate('api_tokens', $params, "where CAST(id AS CHAR)='" . $tokenDetails[0]['id'] . "'")) $this->getResponse(503, "An Error Occure.");

		$result = array();
		$tokenData = $this->queryResponse("select * from api_tokens where id='". $tokenDetails[0]['id'] ."'")[0];
		$result['access_token'] = $tokenData['access_token_hash'];
		$result['refresh_token'] = $tokenData['refresh_token_hash'];
		$result['expires_in'] = 900;
		
		$this->dataArray = $result;
		$this->getResponse(200, "Token refreshed successfully..");
	}

	public function signin()
	{
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['username', 'password', 'role']);

		$username = $payload['fields']['username'];
		$password = md5($payload['fields']['password']);
		$role = $payload['fields']['role'];		

		$data = $this->queryResponse("select * from $this->table where username='$username' and password='$password' and isDeleted='0' and role='$role'");
		if ($data) {
			// $this->createLog($data[0]['id'], 'signin');
			// 
			$accessToken = $this->generateToken();
			$refreshToken = $this->generateToken();
			$accessTokenHash = $this->hashToken($accessToken);
			$refreshTokenHash = $this->hashToken($refreshToken);

			$params = array(
				'id' => '',
				
				'user_id' => $data[0]['id'],
				'access_token_hash' => $accessTokenHash,
				'refresh_token_hash' => $refreshTokenHash,
				'access_expires_at' => date('Y-m-d H:i:s', strtotime('+45 minutes')),
				'refresh_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),

				'revoked_at' => '0',
				'created_at' => '0'
			);

			if (!$newTokenID = $this->queryInsert('api_tokens', $params)) $this->getResponse(503, "An Error Occure.");


			$result = array();
			$result = $this->modelAdminData($data[0]);
			
			// 
			$tokenData = $this->queryResponse("select * from api_tokens where id='". $newTokenID ."'")[0];
			$result['access_token'] = $tokenData['access_token_hash'];
			$result['refresh_token'] = $tokenData['refresh_token_hash'];
			$result['expires_in'] = 900;

			$this->queryDelete("api_tokens", "where user_id = '" . $data[0]['id'] . "' and id != '" . $newTokenID . "'");
			// delete all other tokens for the same user


			$this->dataArray = $result;
			$this->getResponse(200);
		}
		$this->getResponse(501, 'invalid credentials');
	}

	public function signout()
	{
		$authorization = $this->getAuthorizationToken();
		if(!$authorization) $this->getResponse(201, 'There is no logged in user.');


		$isUserLoggedIn = $this->queryResponse("select * from api_tokens where access_token_hash = '" . $authorization . "'");
		if (!$isUserLoggedIn) $this->getResponse(201, 'There is no logged in user.');


		$this->queryDelete("api_tokens", "where access_token_hash = '" . $authorization . "'");
		$this->getResponse(200, 'Logged out successfully..');
	}	

	// public function getLogs()
	// {
	// 	$this->checkAuth();
	// 	$handlePagination = $this->handlePagination();
	// 	$data = array();

	// 	$data["config"] = $this->getTotalWhere('logs', 'id', '');
	// 	$data["data"] = $this->queryResponse("select * from logs $handlePagination");

	// 	foreach ($data["data"] as $key => $value) {
	// 		$data["data"][$key]["created_date"] = $this->timeStampToDate($data["data"][$key]["created_date"]);
	// 		$userID = $data["data"][$key]["userid"];
	// 		unset($data["data"][$key]["userid"]);
	// 		$data["data"][$key]["user"] = $this->getAdminByIDFn($userID);
	// 	}

	// 	$this->dataArray = $data;
	// 	$this->getResponse(200);
	// }

	public function getItems()
	{
		$this->checkAuth();
		$handlePagination = $this->handlePagination();
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = array();
		// 
		$where = "";
		$show_deleted = $this->getSecureParams("show_deleted");
		if ($show_deleted === 'true')
			$where = "where isDeleted='1'";
		else if ($show_deleted === 'false')
			$where = "where isDeleted='0'";

		$data["config"] = $this->getTotalWhere($this->table, 'id', $where);
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where $handlePagination"));
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function getItemByID($userID)
	{
		$this->checkAuth();
		$data = $this->getAdminByIDFn($userID);
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");
		$this->dataArray = $data;
		$this->getResponse(200);
	}
	/////////////////////////////////////////////////////////////

	public function createItem()
	{
		// $this->checkAuth();
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['username', 'password', 'name', 'role']);

		$username = $payload['fields']['username'];
		$name = $payload['fields']['name'];		
		$role = $payload['fields']['role'];				


		$ifUsernameAlreadyExist = $this->queryResponse("select * from $this->table where username='$username'");
		if ($ifUsernameAlreadyExist)
			$this->getResponse(501, 'username already exist, use another one');

		$params = array(
			'id' => '',
			'username' => $username,
			'password' => md5($payload['fields']['password']),
			'name' => $name,			
			'created_date' => time(),
			'isDeleted' => '0',
			'role' => $role
		);

		if (!$newUserID = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getAdminByIDFn($newUserID);

		$this->dataArray = $data;
		$this->getResponse(200, 'created successfully..');
	}

	public function updateItem($userID)
	{
		$this->checkAuth();

		$payload = $this->getRequestData();

		$username = $payload['fields']['username'];
		$name = $payload['fields']['name'];		
		$password = $payload['fields']['password'];
		$role = $payload['fields']['role'];		
		
		if (!$username && !$password && !$role)
			$this->getResponse(501, 'there is nothing to be updated!');

		$params = array();

		if ($username)
			$params['username'] = $payload['fields']['username'];
		if ($password)
			$params['password'] = md5($payload['fields']['password']);
		if ($name)
			$params['name'] = $payload['fields']['name'];
		if ($role)
			$params['role'] = $payload['fields']['role'];		


		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$userID'"))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getAdminByIDFn($userID);

		$this->dataArray = $data;
		$this->getResponse(200, 'updated successfully..');
	}

	public function deleteItem($userID)
	{
		$this->checkAuth();

		$data = $this->getAdminByIDFn($userID, " and isDeleted='0'");
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");


		$params = array();
		$params['isDeleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$userID'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, 'deleted successfully..');
	}

	// public function modelAdminData($temp)
	// {
	// 	unset($temp['password']);
	// 	$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
	// 	$temp['isDeleted'] = +$temp['isDeleted'] === 1 ? true : false;
	// 	return $temp;
	// }

	function modelAllData($temp)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelAdminData($temp[$key]);
		}
		return $data;
	}

	// public function createLog($userID, $note)
	// {
	// 	$params = array(
	// 		'id' => '',
	// 		'userid' => $userID,
	// 		'created_date' => time(),
	// 		'note' => $note,
	// 	);

	// 	$this->queryInsert('logs', $params);
	// 	return true;
	// }

}
?>