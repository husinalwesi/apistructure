<?php
class blog_category extends mainController
{
	var $table = "blog_category";
	var $querySelector = array('id', 'title_en', 'title_ar', 'description_en', 'description_ar', 'desktop_img', 'mobile_img', 'owner', 'created_date', 'isDeleted');
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
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = array();
		// 
		$where = "";
		$show_deleted = $this->getSecureParams("show_deleted");
		if ($show_deleted === 'true')
			$where = "where isDeleted='1'";
		else if ($show_deleted === 'false')
			$where = "where isDeleted='0'";

		$data = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where"));
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

		$this->checkRequiredFields(['title_en', 'title_ar', 'description_en', 'description_ar']);

		$this->checkRequiredFiles(['desktop_img', 'mobile_img']);

		$filesToBeUploaded = array();
		$filesToBeUploaded['desktop_img'] = $payload['files']['desktop_img'];
		$filesToBeUploaded['mobile_img'] = $payload['files']['mobile_img'];

		$uploadedFilesPaths = $this->uploadMedia($filesToBeUploaded); // to upload files

		$params = array(
			'id' => '',
			'title_en' => $payload['fields']['title_en'],
			'title_ar' => $payload['fields']['title_ar'],
			'description_en' => $payload['fields']['description_en'],
			'description_ar' => $payload['fields']['description_ar'],
			'desktop_img' => $uploadedFilesPaths['desktop_img'],
			'mobile_img' => $uploadedFilesPaths['mobile_img'],
			'owner' => '11',//to get from token passed in header
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


		$title_en = $payload['fields']['title_en'];
		$title_ar = $payload['fields']['title_ar'];
		$description_en = $payload['fields']['description_en'];
		$description_ar = $payload['fields']['description_ar'];

		$filesToBeUploaded = array();
		if ($payload['files']['desktop_img'])
			$filesToBeUploaded['desktop_img'] = $payload['files']['desktop_img'];
		if ($payload['files']['mobile_img'])
			$filesToBeUploaded['mobile_img'] = $payload['files']['mobile_img'];


		if (!$title_en && !$title_ar && !$description_en && !$description_ar && !$filesToBeUploaded['desktop_img'] && !$filesToBeUploaded['mobile_img'])
			$this->getResponse(501, 'there is nothing to be updated!');

		$params = array();

		$uploadedFilesPaths = $this->uploadMediaPut($filesToBeUploaded); // to upload files		

		if ($title_en)
			$params['title_en'] = $payload['fields']['title_en'];
		if ($title_ar)
			$params['title_ar'] = $payload['fields']['title_ar'];
		if ($description_en)
			$params['description_en'] = $payload['fields']['description_en'];
		if ($description_ar)
			$params['description_ar'] = $payload['fields']['description_ar'];

		if ($uploadedFilesPaths['desktop_img'])
			$params['desktop_img'] = $uploadedFilesPaths['desktop_img'];
		if ($uploadedFilesPaths['mobile_img'])
			$params['mobile_img'] = $uploadedFilesPaths['mobile_img'];

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

		$this->deleteMedia($data['image']['desktop']);
		$this->deleteMedia($data['image']['mobile']);

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelBlogCategoryData($temp, $fullPathImage = true)
	{
		$isDeleted = +$temp['isDeleted'] === 1;
		$temp['title'] = array(
			'en' => $temp['title_en'],
			'ar' => $temp['title_ar'],
		);

		$temp['description'] = array(
			'en' => $temp['description_en'],
			'ar' => $temp['description_ar'],
		);

		if ($isDeleted) {
			$temp['image'] = array(
				'desktop' => null,
				'mobile' => null,
			);
		} else if ($fullPathImage) {
			$temp['image'] = array(
				'desktop' => IMG_BASE_URL . $temp['desktop_img'],
				'mobile' => IMG_BASE_URL . $temp['mobile_img'],
			);
		} else {
			$temp['image'] = array(
				'desktop' => $temp['desktop_img'],
				'mobile' => $temp['mobile_img'],
			);
		}

		unset($temp['title_en']);
		unset($temp['title_ar']);
		unset($temp['description_en']);
		unset($temp['description_ar']);
		unset($temp['desktop_img']);
		unset($temp['mobile_img']);

		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['isDeleted'] = $isDeleted;
		$temp['owner'] = $this->getAdminByIDFn($temp['owner']);
		return $temp;
	}

	function modelAllData($temp, $fullPathImage = true)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelBlogCategoryData($temp[$key], $fullPathImage);
		}
		return $data;
	}

}
?>