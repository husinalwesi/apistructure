<?php
class category extends mainController
{
	var $table = "category";
	var $querySelector = array('id', 'slug', 'title', 'description', 'img', 'img_inner', 'owner', 'created_date', 'isDeleted', 'isActive');
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
			$this->getItemBySlug($params[0]);			
			// $this->getItemByID($params[0]);
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
		$show_deleted = $this->getSecureParams("show_deleted");
		if ($show_deleted === 'true')
			$where = "where isDeleted='1'";
		else if ($show_deleted === 'false')
			$where = "where isDeleted='0'";

		$data["config"] = $this->getTotalWhere($this->table, 'id', $where);
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where order by created_date desc $handlePagination"));
		// foreach ($data["data"] as $key => $value) {
		// 	$data["data"][$key]['count'] = 3;
		// }		
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

		$this->checkRequiredFields(['title', 'slug', 'description', 'owner']);

		$this->checkRequiredFiles(['img']);
		// $this->checkRequiredFiles(['img', 'img_inner']);		

		$filesToBeUploaded = array();
		$filesToBeUploaded['img'] = $payload['files']['img'];
		$filesToBeUploaded['img_inner'] = $payload['files']['img_inner'];

		$slug = $payload['fields']['slug'];
		$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$slug'");
		if ($ifSlugAlreadyExist) $this->getResponse(501, 'slug already exist, use another one');

		$uploadedFilesPaths = $this->uploadMedia($filesToBeUploaded); // to upload files

		
		$params = array(
			'id' => '',
			'slug' => $payload['fields']['slug'],			
			'title' => $payload['fields']['title'],
			'description' => $payload['fields']['description'],
			'img' => $uploadedFilesPaths['img'],
			'img_inner' => $uploadedFilesPaths['img_inner'],
			'owner' => $payload['fields']['owner'],//to get from token passed in header
			'created_date' => time(),
			'isDeleted' => '0'
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


		if($uploadedFilesPaths['img'] && $ifSlugAlreadyExist[0]['img']){
			// if there is a new image, also there is an old image, so delete the old image file.
			$this->deleteMedia($ifSlugAlreadyExist[0]['img']);
		}

		if($uploadedFilesPaths['img_inner'] && $ifSlugAlreadyExist[0]['img_inner']){
			// if there is a new image, also there is an old image, so delete the old image file.
			$this->deleteMedia($ifSlugAlreadyExist[0]['img_inner']);
		}


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

	public function deleteItem($id)
	{
		$this->checkAuth();

		$data = $this->getItemByIDFn($id, " and isDeleted='0'", false);
		if (!$data) $this->getResponse(404, "No data found for the given ID.");



		$checkIfThereABooksInTheCategory = $this->queryResponse("select count(id) as 'count' from book where category = '$id' ");
		$count = $checkIfThereABooksInTheCategory[0]['count'];
		if($count > 0) $this->getResponse(301, "Cannot delete this category because it contains books.");	

		$params = array();
		$params['isDeleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$id'"))
			$this->getResponse(503, "An Error Occure.");

		// $this->deleteMedia($data['image']['img']);
		// $this->deleteMedia($data['image']['img_inner']);

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelCategoryData($temp, $fullPathImage = true)
	{
	// $questionsCount = $this->queryResponse("select count(id) as 'questionsCount' from questions where category='".$temp['id']."'");

	
	// $temp['questionsCount'] = $questionsCount[0]['questionsCount'];


		$isDeleted = +$temp['isDeleted'] === 1;
		$isActive = +$temp['isActive'] === 1;		
		
		// $temp['title'] = array(
		// 	'en' => $temp['title_en'],
		// 	'ar' => $temp['title_ar'],
		// );

		// $temp['description'] = array(
		// 	'en' => $temp['description_en'],
		// 	'ar' => $temp['description_ar'],
		// );

		if ($isDeleted) {
			// $temp['image'] = array(
			// 	'desktop' => null,
			// 	'mobile' => null,
			// );
		} else if ($fullPathImage) {
			$temp['img'] = IMG_BASE_URL . $temp['img'];
			$temp['img_inner'] = IMG_BASE_URL . $temp['img_inner'];			
			// $temp['image'] = array(
			// 	'desktop' => IMG_BASE_URL . $temp['desktop_img'],
			// 	'mobile' => IMG_BASE_URL . $temp['mobile_img'],
			// );
		} else {
			// $temp['image'] = array(
			// 	'desktop' => $temp['desktop_img'],
			// 	'mobile' => $temp['mobile_img'],
			// );
		}

		// unset($temp['title_en']);
		// unset($temp['title_ar']);
		// unset($temp['description_en']);
		// unset($temp['description_ar']);
		// unset($temp['desktop_img']);
		// unset($temp['mobile_img']);

		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['isDeleted'] = $isDeleted;
		$temp['isActive'] = $isActive;
		$temp['owner'] = $this->getAdminByIDFn($temp['owner']);


		$result = $this->queryResponse("select count(id) as 'count' from book where category='" . $temp['id'] . "'");
		$temp['book_in_category'] = $result[0]['count'];		
		return $temp;
	}

	function modelAllData($temp, $fullPathImage = true)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelCategoryData($temp[$key], $fullPathImage);
		}
		return $data;
	}

}
?>