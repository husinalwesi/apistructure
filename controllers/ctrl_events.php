<?php
class events extends mainController
{
	var $table = "events";

	var $querySelector = array('id', 'userid', 'created_date', 'is_deleted', 'description', 'img');
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
		if ($params[0] === 'full' && $this->isAllowedMethod('GET')) {
			$this->getItemsWithOutPagination();
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

	public function getItemsWithOutPagination()
	{
		$this->checkAuth();
		// $querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = array();
		// 
		$where = "";
		$show_deleted = $this->getSecureParams("show_deleted");
		if ($show_deleted === 'true')
			$where = "where is_deleted='1'";
		else if ($show_deleted === 'false')
			$where = "where is_deleted='0'";

		$data = $this->queryResponse("select description, img from $this->table $where order by created_date desc");
		foreach ($data as $key => $value) {
			$data[$key]['img'] = IMG_BASE_URL . $data[$key]['img'];
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
			$where = "where is_deleted='1'";
		else if ($show_deleted === 'false')
			$where = "where is_deleted='0'";

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
		// $data['count'] = 3;
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function createItem()
	{
		$this->checkAuth();
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['userid', 'description']);

		$this->checkRequiredFiles(['img']);

		$filesToBeUploaded = array();
		$filesToBeUploaded['img'] = $payload['files']['img'];

		$uploadedFilesPaths = $this->uploadMedia($filesToBeUploaded); // to upload files

		
		$params = array(
			'id' => '',
			'userid' => $payload['fields']['userid'],			
			'created_date' => time(),
			'is_deleted' => '0',
			'description' => $payload['fields']['description'],
			'img' => $uploadedFilesPaths['img']
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
		if (!$ifIDAlreadyExist) $this->getResponse(501, 'there is no event with this id');


		$userid = $payload['fields']['userid'];		
		$description = $payload['fields']['description'];

		$filesToBeUploaded = array();
		if ($payload['files']['img']) $filesToBeUploaded['img'] = $payload['files']['img'];


		if (!$userid && !$description && !$filesToBeUploaded['img'])
			$this->getResponse(501, 'there is nothing to be updated!');

		$params = array();

		$uploadedFilesPaths = $this->uploadMediaPut($filesToBeUploaded); // to upload files		

		if($uploadedFilesPaths['img'] && $ifIDAlreadyExist[0]['img']){
			// if there is a new image, also there is an old image, so delete the old image file.
			$this->deleteMedia($ifIDAlreadyExist[0]['img']);
		}


		if ($userid)
			$params['userid'] = $payload['fields']['userid'];		
		if ($description)
			$params['description'] = $payload['fields']['description'];

		if ($uploadedFilesPaths['img'])
			$params['img'] = $uploadedFilesPaths['img'];

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

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelEventsData($temp, $fullPathImage = true)
	{
		$temp['img'] = IMG_BASE_URL . $temp['img'];
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['is_deleted'] = +$temp['is_deleted'] === 1;
		$temp['user'] = $this->getAdminByIDFn($temp['userid']);
		unset($temp['userid']);
		return $temp;
	}

	function modelAllData($temp, $fullPathImage = true)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelEventsData($temp[$key], $fullPathImage);
		}
		return $data;
	}

}
?>