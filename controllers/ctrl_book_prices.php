<?php
class book_prices extends mainController
{
	var $table = "book_prices";
	var $querySelector = array('id', 'country', 'price', 'created_date', 'owner', 'is_deleted', 'bookid');
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
		} elseif (count($params) === 1 && $this->isAllowedMethod('DELETE')) {
			$this->deleteItem($params[0]);
		}

		$this->getResponse(422, "Method not supported");
	}

	public function getItems()
	{
		// $this->checkAuth(false);
		$handlePagination = $this->handlePagination();
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = array();
		// 
		$where = "";
		$show_deleted = $this->getSecureParams("show_deleted");
		if ($show_deleted === 'true')
			$where = "where is_deleted='1'";
		else if ($show_deleted === 'false')
			$where = "where is_deleted='0'";

		$bookid = $this->getSecureParams("bookid");		
		if($bookid) $where.= " and bookid='$bookid'";


		$data["config"] = $this->getTotalWhere($this->table, 'id', $where);
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where order by created_date desc $handlePagination"));

		$this->dataArray = $data;
		$this->getResponse(200);
	}

	// public function getItemBySlugFn($slug, $where = '', $fullPathImage = true)
	// {
	// 	$querySelectorString = $this->getQuerySelector($this->querySelector);
	// 	$result = $this->queryResponse("select $querySelectorString from $this->table where slug='$slug' $where");
	// 	if (!$result || count($result) === 0)
	// 		return null;
	// 	$data = array();
	// 	$data = $this->modelAllData($result, $fullPathImage);
	// 	if (!$data || count($data) === 0)
	// 		return null;
	// 	return $data[0];
	// }	

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

	// public function getItemBySlug($slug)
	// {
	// 	// $this->checkAuth(false);
	// 	$data = $this->getItemBySlugFn($slug);
	// 	if (!$data)
	// 		$this->getResponse(404, "No data found for the given Slug.");
	// 	// $data['count'] = 3;
	// 	$this->dataArray = $data;
	// 	$this->getResponse(200);
	// }	

	public function getItemByID($slug)
	{
		// $this->checkAuth(false);
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

		$this->checkRequiredFields(['country', 'price', 'bookid']);

		
		$params = array(
			'id' => '',
			'country' => $payload['fields']['country'],			
			'price' => $payload['fields']['price'],
			'created_date' => time(),
			'is_deleted' => '0',
			'owner' => $this->getUserID(),//to get from token passed in header
			'bookid' => $payload['fields']['bookid'],
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


		$country = $payload['fields']['country'];		
		$price = $payload['fields']['price'];
		$bookid = $payload['fields']['bookid'];		

		if (!$country && !$price && !$bookid)
			$this->getResponse(501, 'there is nothing to be updated!');		

		$params = array();

		if ($country) $params['country'] = $payload['fields']['country'];		
		if ($price) $params['price'] = $payload['fields']['price'];		
		if ($bookid) $params['bookid'] = $payload['fields']['bookid'];				


		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($id);

		$this->dataArray = $data;
		$this->getResponse(200, 'updated successfully..');
	}

	public function deleteItem($id)
	{
		$this->checkAuth();

		$data = $this->getItemByIDFn($id, " and is_deleted='0'", false);
		if (!$data) $this->getResponse(404, "No data found for the given ID.");

		$params = array();
		$params['is_deleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$id'"))
			$this->getResponse(503, "An Error Occure.");

		// $this->deleteMedia($data['image']['img']);
		// $this->deleteMedia($data['image']['img_inner']);

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelPricesData($temp, $fullPathImage = true)
	{
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['is_deleted'] = +$temp['is_deleted'] === 1;
		$temp['owner'] = $this->getAdminByIDFn($temp['owner']);

		
		$result = $this->queryResponse("select * from books_with_avg_rate where id='" . $temp['bookid'] . "'");
		$temp['book'] = $this->modelBookData($result[0]);
		unset($temp['bookid']);

		return $temp;
	}

	public function modelBookData($temp)
	{
		$temp['img'] = IMG_BASE_URL . $temp['img'];
		$temp['inner_img'] = IMG_BASE_URL . $temp['inner_img'];			


		$temp['index_file'] = IMG_BASE_URL . $temp['index_file'];			
		$temp['file'] = IMG_BASE_URL . $temp['file'];					

		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['is_deleted'] = +$temp['is_deleted'] === 1 ? true : false;
		$temp['owner'] = $this->getAdminByIDFn($temp['owner_id']);

		$temp['category'] = $this->getCategoryByIDFn($temp['category']);
		unset($temp['file']);
		return $temp;
	}		

	function modelAllData($temp, $fullPathImage = true)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelPricesData($temp[$key], $fullPathImage);
		}
		return $data;
	}

}
?>