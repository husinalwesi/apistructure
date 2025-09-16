<?php
class category extends mainController
{
	var $table = "category";
	var $querySelector = array('id', 'title_en', 'title_ar', 'desc_en', 'desc_ar', 'image', 'is_deleted', 'created_date');
	public function __construct()
	{
		$this->checkAuth();
		$this->callMethod($this);
	}


	public function listAPI()
	{
		$this->setAllowedMethod("GET");
		$handleIsDeleted = $this->handleIsDeleted();
		$handlePagination = $this->handlePagination();
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		// 
		$data = array();
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table where $handleIsDeleted $handlePagination"));
		// 
		$data["config"] = $this->getTotal($this->table, 'id');
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function itemAPI()
	{
		$this->setAllowedMethod("GET");
		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$data = $this->getItemDetails($id);
		if (!$data)
			$this->getResponse(204);
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function createAPI()
	{
		$this->setAllowedMethod("POST");
		$params = array(
			'title_en' => '',
			'title_ar' => '',
			'desc_en' => '',
			'desc_ar' => '',
			'image' => '',
		);

		$payload = $this->getPayload();
		foreach ($params as $key => $value) {
			if (!$payload->$key)
				$this->getResponse(201, "Missing Parameter `$key`");
			$params[$key] = $payload->$key;
		}

		$params['is_deleted'] = 0;
		$params['created_date'] = time();

		if (!$categoryID = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");

		$this->dataArray = $this->getItemDetails($categoryID);
		$this->getResponse(200, "Category created successfully.");
	}

	public function updateAPI()
	{
		$this->setAllowedMethod("PATCH");

		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$oldItemDetails = $this->getItemDetails($id);
		if (!$oldItemDetails)
			$this->getResponse(204);

		$params = array(
			'title_en' => '',
			'title_ar' => '',
			'desc_en' => '',
			'desc_ar' => '',
			'image' => '',
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


		$this->dataArray = $this->getItemDetails($id);
		$this->getResponse(200, "Category updated successfully.");
	}

	public function deleteAPI()
	{
		$this->setAllowedMethod("DELETE");

		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$oldItemDetails = $this->getItemDetails($id);
		if (!$oldItemDetails || $oldItemDetails['isDeleted'])
			$this->getResponse(204);

		$params = array(
			'is_deleted' => '1'
		);

		if (!$this->queryUpdate($this->table, $params, "where id='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, "Category Deleted successfully.");
	}

	public function unDeleteAPI()
	{
		$this->setAllowedMethod("PATCH");

		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$oldItemDetails = $this->getItemDetails($id);
		if (!$oldItemDetails)
			$this->getResponse(204);

		if (!$oldItemDetails['isDeleted'])
			$this->getResponse(503, "Category Already Undeleted");


		$params = array(
			'is_deleted' => '0'
		);

		if (!$this->queryUpdate($this->table, $params, "where id='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, "Category Un-Deleted successfully.");
	}

	// 
	function modelData($temp)
	{
		$data = array();
		$data['id'] = $temp['id'];

		$data['title'] = array();
		$data['title']['en'] = $temp['title_en'];
		$data['title']['ar'] = $temp['title_ar'];

		$data['description'] = array();
		$data['description']['en'] = $temp['desc_en'];
		$data['description']['ar'] = $temp['desc_ar'];

		$data['image'] = $temp['image'];
		$data['createdDate'] = $this->timeStampToDate($temp['created_date'], "dateTime");
		$data['isDeleted'] = $this->toBoolean($temp['is_deleted']);
		return $data;
	}

	function modelAllData($temp)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelData($temp[$key]);
		}
		return $data;
	}

	function getItemDetails($id)
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = $this->queryResponse("select $querySelectorString from $this->table where id=$id");
		if (!$data)
			return false;
		return $this->modelData($data[0]);
	}
}
?>