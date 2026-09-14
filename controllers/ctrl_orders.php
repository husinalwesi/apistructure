<?php
class orders extends mainController
{
	var $table = "orders";
	var $querySelector = array('id', 'created_date', 'owner_id', 'status', 'payment_response', 'cart');

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
		if (count($params) === 0 && $this->isAllowedMethod('GET')) {
			$this->getItems();
		} elseif (count($params) === 1 && $this->isAllowedMethod('GET')) {
			$this->getItemByID($params[0]);
		} elseif (count($params) === 0 && $this->isAllowedMethod('POST')) {
			$this->createItem();
		} elseif (count($params) === 1 && $this->isAllowedMethod('PUT')) {
			$this->updateItem($params[0]);
		}
		//  elseif (count($params) === 1 && $this->isAllowedMethod('DELETE')) {
		// 	$this->deleteItem($params[0]);
		// }

		$this->getResponse(422, "Method not supported");
	}

	public function getItemsWithOutPagination()
	{
		// $this->checkAuth();
		// $querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = array();
		// 
		$where = "";
		$show_deleted = $this->getSecureParams("show_deleted");
		if ($show_deleted === 'true')
			$where = "where isDeleted='1'";
		else if ($show_deleted === 'false')
			$where = "where isDeleted='0'";

		$data = $this->queryResponse("select id, title from $this->table $where");
		foreach ($data as $key => $value) {
			$data[$key]['count'] = $this->queryResponse("select count(id) as 'count' from book where category='" . $data[$key]['id'] . "'");
			$data[$key]['count'] = $data[$key]['count'][0]['count'];
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

		$userID = $this->getUserID();
		
		$userRole = $this->getUserRole();
		if($userRole === 'admin') $where = ""; //get all orders..
		else $where = "where owner_id = '$userID'";


		$data["config"] = $this->getTotalWhere($this->table, 'id', $where);
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where order by created_date desc $handlePagination"));

		$this->dataArray = $data;
		$this->getResponse(200);
	}


	public function getItemByIDFn($id, $where = '', $fullPathImage = true)
	{
		$userID = $this->getUserID();
		$and = "";
		$userRole = $this->getUserRole();
		if($userRole === 'admin') $and = ""; //get all orders..
		else $and = "and owner_id = '$userID'";		

		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from $this->table where id='$id' $and $where");
		if (!$result || count($result) === 0)
			return null;
		$data = array();
		$data = $this->modelAllData($result, $fullPathImage);
		if (!$data || count($data) === 0)
			return null;
		return $data[0];
	}


	public function getItemByID($slug)
	{
		$this->checkAuth();
		$data = $this->getItemByIDFn($slug);
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");
		// $data['count'] = 3;
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function createItem()
	{
		$this->checkAuth();
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['status', 'payment_response', 'cart']);

		$params = array(
			'id' => '',
			'created_date' => time(),
			'owner_id' => $this->getUserID(),
			'status' => $payload['fields']['status'],			
			'payment_response' => $payload['fields']['payment_response'],
			'cart' => $payload['fields']['cart']
		);

		if (!$newID = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($newID);

		$this->dataArray = $data;
		$this->getResponse(200, 'created successfully..');
	}

	public function updateItem($id)
	{
		$this->checkAuth();

		$payload = $this->getRequestData();

		$ifIDAlreadyExist = $this->queryResponse("select * from $this->table where id='$id'");
		if (!$ifIDAlreadyExist) $this->getResponse(501, 'there is no order with this id');

		$status = $payload['fields']['status'];		
		$payment_response = $payload['fields']['payment_response'];
		$cart = $payload['fields']['cart'];

		if (!$status && !$payment_response && !$cart)
			$this->getResponse(501, 'there is nothing to be updated!');

		$params = array();

		if ($status) $params['status'] = $payload['fields']['status'];		
		if ($payment_response) $params['payment_response'] = $payload['fields']['payment_response'];		
		if ($cart) $params['cart'] = $payload['fields']['cart'];

		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($id);

		$this->dataArray = $data;
		$this->getResponse(200, 'updated successfully..');
	}

	// public function deleteItem($id)
	// {
	// 	$this->checkAuth();

	// 	$data = $this->getItemByIDFn($id, " and isDeleted='0'", false);
	// 	if (!$data) $this->getResponse(404, "No data found for the given ID.");



	// 	$checkIfThereABooksInTheCategory = $this->queryResponse("select count(id) as 'count' from book where category = '$id' ");
	// 	$count = $checkIfThereABooksInTheCategory[0]['count'];
	// 	if($count > 0) $this->getResponse(301, "Cannot delete this category because it contains books.");	

	// 	$params = array();
	// 	$params['isDeleted'] = 1;
	// 	if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$id'"))
	// 		$this->getResponse(503, "An Error Occure.");

	// 	// $this->deleteMedia($data['image']['img']);
	// 	// $this->deleteMedia($data['image']['img_inner']);

	// 	$this->getResponse(200, 'deleted successfully..');
	// }

	public function modelOrderData($temp, $fullPathImage = true)
	{
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['owner'] = $this->getAdminByIDFn($temp['owner_id']);
		unset($temp['owner_id']);
		return $temp;
	}

	function modelAllData($temp, $fullPathImage = true)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelOrderData($temp[$key], $fullPathImage);
		}
		return $data;
	}

}
?>