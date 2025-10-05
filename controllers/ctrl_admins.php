<?php
class admins extends mainController
{
	var $table = "admin";
	var $querySelector = array('id', 'username', 'isdeleted');
	public function __construct()
	{
		// $this->deleteMedia("/uploads/1759611600/68e21db164270_1756022608096.jpeg");		
		// handle header params // token // language // and translate for api response message
		$this->callMethod($this);
	}

	public function mainAPI()
	{
		$params = $this->extractUrlParams();
		if (count($params) === 0 && $this->isAllowedMethod('GET')) {
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

	public function getItems()
	{
		$this->checkAuth();
		$handlePagination = $this->handlePagination();
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = array();
		$where = "where isdeleted='0'";
		$data["config"] = $this->getTotalWhere($this->table, 'id', $where);
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where $handlePagination"));
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function getItemByIDFn($userID)
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from $this->table where CAST(id AS CHAR)='$userID'");
		if (!$result || count($result) === 0)
			return null;
		$data = array();
		$data = $this->modelAllData($result);
		if (!$data || count($data) === 0)
			return null;
		return $data[0];
	}

	public function getItemByID($userID)
	{
		$this->checkAuth();
		$data = $this->getItemByIDFn($userID);
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

		$params = array(
			'id' => '',
			'username' => $payload['fields']['username'],
			'isdeleted' => ''
		);

		// 
		// $uploadedFilesPaths = $this->uploadMedia($payload['files']); // to upload files
		// http://localhost:8080/apistructure/uploads/1759093200/68da69a1c19e2_chrome-un-secure.txt


		if (!$newUserID = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($newUserID);

		$this->dataArray = $data;
		$this->getResponse(200, 'created successfully..');
	}

	public function updateItem($userID)
	{
		$this->checkAuth();

		$payload = $this->getRequestData();

		if (!$payload['fields']['username'])
			$this->getResponse(503, "Missing parameter username");
		$params = array();
		$params['username'] = $payload['fields']['username'];
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$userID'"))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($userID);

		$this->dataArray = $data;
		$this->getResponse(200, 'updated successfully..');
	}

	public function deleteItem($userID)
	{
		$this->checkAuth();
		$params = array();
		$params['isdeleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$userID'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelAdminData($temp)
	{
		$userID = +$temp['id'];
		$data = array();

		$data['id'] = $userID;
		$data['username'] = $temp['username'];
		$data['isdeleted'] = $temp['isdeleted'];
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

}
?>