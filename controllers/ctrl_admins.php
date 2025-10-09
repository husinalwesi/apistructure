<?php
class admins extends mainController
{
	var $table = "admin";
	var $querySelector = array('id', 'username', 'password', 'createdDate', 'isDeleted');
	public function __construct()
	{
		// $this->deleteMedia("/uploads/1759611600/68e21db164270_1756022608096.jpeg");		
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
		} else if ($params[0] === 'logs' && $this->isAllowedMethod('GET')) {
			$this->getLogs();
		} else if (count($params) === 0 && $this->isAllowedMethod('GET')) {
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

	public function signin()
	{
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['username', 'password']);

		$username = $payload['fields']['username'];
		$password = md5($payload['fields']['password']);

		$data = $this->queryResponse("select * from $this->table where username='$username' and password='$password' and isDeleted='0'");
		if ($data) {
			$this->createLog($data[0]['id'], 'signin');
			$this->dataArray = $this->modelAdminData($data[0]);
			$this->getResponse(200);
		}
		$this->getResponse(501, 'invalid credentials');
	}

	public function getLogs()
	{
		$this->checkAuth();
		$handlePagination = $this->handlePagination();
		$data = array();

		$data["config"] = $this->getTotalWhere('logs', 'id', '');
		$data["data"] = $this->queryResponse("select * from logs $handlePagination");

		foreach ($data["data"] as $key => $value) {
			$data["data"][$key]["created_date"] = $this->timeStampToDate($data["data"][$key]["created_date"]);
			$userID = $data["data"][$key]["userid"];
			unset($data["data"][$key]["userid"]);
			$data["data"][$key]["user"] = $this->getAdminByIDFn($userID);
		}

		$this->dataArray = $data;
		$this->getResponse(200);
	}

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
		$this->checkAuth();
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['username', 'password']);

		$username = $payload['fields']['username'];


		$ifUsernameAlreadyExist = $this->queryResponse("select * from $this->table where username='$username'");
		if ($ifUsernameAlreadyExist)
			$this->getResponse(501, 'username already exist, use another one');

		$params = array(
			'id' => '',
			'username' => $username,
			'password' => md5($payload['fields']['password']),
			'createdDate' => time(),
			'isDeleted' => '0'
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
		$password = $payload['fields']['password'];
		if (!$username && !$password)
			$this->getResponse(501, 'there is nothing to be updated!');

		$params = array();

		if ($username)
			$params['username'] = $payload['fields']['username'];
		if ($password)
			$params['password'] = md5($payload['fields']['password']);

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
	// 	$temp['createdDate'] = $this->timeStampToDate($temp['createdDate']);
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

	public function createLog($userID, $note)
	{
		$params = array(
			'id' => '',
			'userid' => $userID,
			'created_date' => time(),
			'note' => $note,
		);

		$this->queryInsert('logs', $params);
		return true;
	}

}
?>