<?php
class contactform extends mainController
{
	var $table = "contactform";
	var $querySelector = array('id', 'name', 'phone', 'email', 'message', 'created_date', 'status');
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
		$this->checkAuth();
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
		$where = "";
		$status = $this->getSecureParams("status");
		if ($status) $where = "where status='$status'";

		$data["config"] = $this->getTotalWhere($this->table, 'id', $where);
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where order by created_date desc $handlePagination"));
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function getItemBySlugFn($slug, $where = '', $fullPathImage = true)
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from $this->table where slug='$slug' $where");
		if (!$result || count($result) === 0)
			return null;
		$data = array();
		$data = $this->modelAllData($result, $fullPathImage);
		if (!$data || count($data) === 0)
			return null;
		return $data[0];
	}	

	public function getItemByIDFn($id, $where = '', $fullPathImage = true)
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from $this->table where id='$id' $where");
		if (!$result || count($result) === 0)
			return null;
		$data = array();
		$data = $this->modelAllData($result, $fullPathImage);
		if (!$data || count($data) === 0)
			return null;
		return $data[0];
	}

	public function getItemBySlug($slug)
	{
		$this->checkAuth();
		$data = $this->getItemBySlugFn($slug);
		if (!$data)
			$this->getResponse(404, "No data found for the given Slug.");
		// $data['count'] = 3;
		$this->dataArray = $data;
		$this->getResponse(200);
	}	

	public function getItemByID($slug)
	{
		$this->checkAuth();
		$data = $this->getItemByIDFn($slug);
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function createItem()
	{
		$this->checkAuth();
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['name', 'phone', 'email', 'message']);
		
		$params = array(
			'id' => '',
			'name' => $payload['fields']['name'],			
			'phone' => $payload['fields']['phone'],
			'email' => $payload['fields']['email'],
			'message' => $payload['fields']['message'],
			'created_date' => time(),
			'status' => '0'
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

		$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where id='$id'");
		if (!$ifSlugAlreadyExist)
			$this->getResponse(501, 'there is no category with this id');


		$slug = $payload['fields']['slug'];		
		$title = $payload['fields']['title'];
		$description = $payload['fields']['description'];

		$filesToBeUploaded = array();
		if ($payload['files']['img'])
			$filesToBeUploaded['img'] = $payload['files']['img'];
		if ($payload['files']['img_inner'])
			$filesToBeUploaded['img_inner'] = $payload['files']['img_inner'];


		// if (!$title && !$description && !$filesToBeUploaded['img'] && !$filesToBeUploaded['img_inner'])		
		if (!$title && !$description && !$filesToBeUploaded['img'] && !$slug)
			$this->getResponse(501, 'there is nothing to be updated!');


		// check if new slug is already used or not
		$newSlug = $payload['fields']['slug'];
		if($newSlug){
			$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$newSlug' and id != '$id'");
			if ($ifSlugAlreadyExist) $this->getResponse(501, 'the new slug already exist, use another one');
		}
		// 

		$params = array();

		$uploadedFilesPaths = $this->uploadMediaPut($filesToBeUploaded); // to upload files		

		if ($slug)
			$params['slug'] = $payload['fields']['slug'];		
		if ($title)
			$params['title'] = $payload['fields']['title'];
		if ($description)
			$params['description'] = $payload['fields']['description'];

		if ($uploadedFilesPaths['img'])
			$params['img'] = $uploadedFilesPaths['img'];
		if ($uploadedFilesPaths['img_inner'])
			$params['img_inner'] = $uploadedFilesPaths['img_inner'];

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

	// 	$this->deleteMedia($data['image']['img']);
	// 	// $this->deleteMedia($data['image']['img_inner']);

	// 	$this->getResponse(200, 'deleted successfully..');
	// }

	public function modelContactFormData($temp, $fullPathImage = true)
	{
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		return $temp;
	}

	function modelAllData($temp, $fullPathImage = true)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelContactFormData($temp[$key], $fullPathImage);
		}
		return $data;
	}

}
?>