<?php
class questions extends mainController
{
	var $table = "questions";
						
	var $querySelector = array('id', 'title', 'description', 'category', 'score', 'q_file', 'a_title', 'a_description', 'a_file', 'created_date', 'owner', 'isDeleted');
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
		// 
		$where = "";
		$show_deleted = $this->getSecureParams("show_deleted");
		if ($show_deleted === 'true')
			$where = "where isDeleted='1'";
		else if ($show_deleted === 'false')
			$where = "where isDeleted='0'";

		$data["config"] = $this->getTotalWhere($this->table, 'id', $where);
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where order by created_date desc $handlePagination"));
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function getItemBySlugFn($id, $where = '')
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from $this->table where id='$id' $where");
		if (!$result || count($result) === 0)
			return null;
		$data = array();
		$data = $this->modelAllData($result);
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

	public function getItemByID($id)
	{
		$this->checkAuth();
		$data = $this->getItemBySlugFn($id);
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function createItem()
	{
		$this->checkAuth();
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['title', 'description', 'category', 'score', 'a_title', 'a_description']);
		$this->checkRequiredFiles(['q_file', 'a_file']);

		// $slug = $payload['fields']['slug'];

		// $ifSlugAlreadyExistContent = $this->queryResponse("select * from content where slug='$slug'");
		// $ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$slug'");
		// if ($ifSlugAlreadyExist || $ifSlugAlreadyExistContent)
		// 	$this->getResponse(501, 'slug already exist, use another one');


		$filesToBeUploaded = array();
		$filesToBeUploaded['q_file'] = $payload['files']['q_file'];
		$filesToBeUploaded['a_file'] = $payload['files']['a_file'];


		// $categoriesTxt = $payload['fields']['categories'];
		// $categoriesArray = $categoriesTxt ? json_decode($categoriesTxt) : array();
		// if (count($categoriesArray) === 0)
		// 	$this->getResponse(501, "Missing required field(s): {categories}");

		$uploadedFilesPaths = $this->uploadMedia($filesToBeUploaded); // to upload files

		$params = array(
			'id' => '',
			'title' => $payload['fields']['title'],
			'description' => $payload['fields']['description'],
			'category' => $payload['fields']['category'],
			'created_date' => time(),
			'owner' => '11',//to get from token passed in header
			'isDeleted' => '0',
			'score' => $payload['fields']['score'],
			'a_title' => $payload['fields']['a_title'],
			'a_description' => $payload['fields']['a_description'],
			'q_file' => $uploadedFilesPaths['q_file'],
			'a_file' => $uploadedFilesPaths['a_file'],
		);

		if (!$id = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($id);

		$this->dataArray = $data;
		$this->getResponse(200, 'created successfully..');
	}

	public function updateItem($id)
	{
		$this->checkAuth();

		$payload = $this->getRequestData();

		$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where id='$id'");
		if (!$ifSlugAlreadyExist)
			$this->getResponse(501, 'there is no content with this id');


		$title = $payload['fields']['title'];
		$description = $payload['fields']['description'];
		$category = $payload['fields']['category'];
		$score = $payload['fields']['score'];
		$a_title = $payload['fields']['a_title'];		
		$a_description = $payload['fields']['a_description'];		


		$filesToBeUploaded = array();
		if ($payload['files']['q_file'])
			$filesToBeUploaded['q_file'] = $payload['files']['q_file'];
		if ($payload['files']['a_file'])
			$filesToBeUploaded['a_file'] = $payload['files']['a_file'];


		if (!$title && !$description && !$category && !$score && !$a_title && !$a_description && !$filesToBeUploaded['q_file'] && !$filesToBeUploaded['a_file'])
			$this->getResponse(501, 'there is nothing to be updated!');


		$params = array();

		// if ($slug) {
		// 	$newSlug = $payload['fields']['slug'];
		// 	$params['slug'] = $newSlug;

		// 	$ifSlugAlreadyExistContent = $this->queryResponse("select * from content where slug='$newSlug' and id != '$slugID'");
		// 	$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$newSlug' and id != '$slugID'");
		// 	if ($ifSlugAlreadyExist || $ifSlugAlreadyExistContent)
		// 		$this->getResponse(501, 'the new slug already exist, use another one');
		// }
		// 
		$uploadedFilesPaths = $this->uploadMediaPut($filesToBeUploaded); // to upload files

		if ($title)
			$params['title'] = $payload['fields']['title'];
		if ($description)
			$params['description'] = $payload['fields']['description'];
		if ($category)
			$params['category'] = $payload['fields']['category'];
		if ($score)
			$params['score'] = $payload['fields']['score'];		
		if ($a_title)
			$params['a_title'] = $payload['fields']['a_title'];
		if ($a_description)
			$params['a_description'] = $payload['fields']['a_description'];

		// $categoriesTxt = $payload['fields']['categories'];
		// $categoriesArray = $categoriesTxt ? json_decode($categoriesTxt) : array();
		// if (count($categoriesArray) > 0)
		// 	$params['categories'] = json_encode($categoriesArray);


		if ($uploadedFilesPaths['q_file'])
			$params['q_file'] = $uploadedFilesPaths['q_file'];
		if ($uploadedFilesPaths['a_file'])
			$params['a_file'] = $uploadedFilesPaths['a_file'];

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
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");

		$params = array();
		$params['isDeleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$id'"))
			$this->getResponse(503, "An Error Occure.");


		$this->deleteMedia($data['a_file']);
		$this->deleteMedia($data['q_file']);

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelCategoryData($temp, $fullPathImage = true)
	{
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
			// $temp['img_inner'] = IMG_BASE_URL . $temp['img_inner'];			
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
		return $temp;
	}

	public function modelContentData($temp, $fullPathImage = true)
	{
		

		$categoryDetails = $this->queryResponse("select * from category where id='".$temp['category']."'");		


		$isDeleted = +$temp['isDeleted'] === 1;
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['categoryDetails'] = $this->modelCategoryData($categoryDetails[0]);
// var $querySelector = array('id', 'title', 'description', 'category', 'score', 'q_file', 'a_title', 'a_description', 'a_file', '', '', '');

		// $tags = $temp['tags'];
		// $tagsArray = $tags ? json_decode($tags) : array();
		// unset($temp['tags']);
		// $temp['tags'] = $tagsArray;


		// $categories = $temp['categories'];
		// $categoriesArray = $categories ? json_decode($categories) : array();
		// $categoriesData = array();
		// if (count($categoriesArray) > 0) {
		// 	foreach ($categoriesArray as $categoryID) {
		// 		$categoriesData[] = $this->getBlogCategoryByIDFn($categoryID);
		// 	}
		// }
		// unset($temp['categories']);
		// $temp['categories'] = $categoriesData;

		if ($isDeleted) {
			$temp['q_file'] = null;
			$temp['a_file'] = null;
		} else if ($fullPathImage) {
			$temp['q_file'] = IMG_BASE_URL . $temp['q_file'];
			$temp['a_file'] = IMG_BASE_URL . $temp['a_file'];
		}

		$temp['owner'] = $this->getAdminByIDFn($temp['owner']);
		$temp['isDeleted'] = $isDeleted;

		return $temp;
	}

	// public function getBlogCategoryByIDFn($id)
	// {
	// 	$querySelectorString = $this->getQuerySelector($this->querySelector);
	// 	$result = $this->queryResponse("select $querySelectorString from category where id='$id'");
	// 	if (!$result || count($result) === 0)
	// 		return null;

	// 	return $this->modelBlogCategoryData($result[0]);
	// }

	// public function modelBlogCategoryData($temp, $fullPathImage = true)
	// {
	// 	$isDeleted = +$temp['isDeleted'] === 1;
	// 	$temp['title'] = array(
	// 		'en' => $temp['title_en'],
	// 		'ar' => $temp['title_ar'],
	// 	);

	// 	$temp['description'] = array(
	// 		'en' => $temp['description_en'],
	// 		'ar' => $temp['description_ar'],
	// 	);

	// 	if ($isDeleted) {
	// 		$temp['image'] = array(
	// 			'desktop' => null,
	// 			'mobile' => null,
	// 		);
	// 	} else if ($fullPathImage) {
	// 		$temp['image'] = array(
	// 			'desktop' => IMG_BASE_URL . $temp['desktop_img'],
	// 			'mobile' => IMG_BASE_URL . $temp['mobile_img'],
	// 		);
	// 	} else {
	// 		$temp['image'] = array(
	// 			'desktop' => $temp['desktop_img'],
	// 			'mobile' => $temp['mobile_img'],
	// 		);
	// 	}

	// 	unset($temp['title_en']);
	// 	unset($temp['title_ar']);
	// 	unset($temp['description_en']);
	// 	unset($temp['description_ar']);
	// 	unset($temp['desktop_img']);
	// 	unset($temp['mobile_img']);

	// 	$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
	// 	$temp['isDeleted'] = $isDeleted;
	// 	$temp['owner'] = $this->getAdminByIDFn($temp['owner']);
	// 	return $temp;
	// }

	function modelAllData($temp, $fullPathImage = true)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelContentData($temp[$key], $fullPathImage);
		}
		return $data;
	}

}
?>