<?php
class blogs extends mainController
{
	var $table = "blogs";
	var $querySelector = array('id', 'slug', 'title', 'body', 'tags', 'cover_desktop_img', 'cover_mobile_img', 'main_desktop_img', 'main_mobile_img', 'short_title', 'short_desc', 'related_blog', 'created_date', 'owner', 'isDeleted');
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
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from $this->table $where $handlePagination"));
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function getItemBySlugFn($slug, $where = '')
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from $this->table where slug='$slug' $where");
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

	public function getItemByID($slug)
	{
		$this->checkAuth();
		$data = $this->getItemBySlugFn($slug);
		if (!$data)
			$this->getResponse(404, "No data found for the given slug.");
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function createItem()
	{
		$this->checkAuth();
		$payload = $this->getRequestData();

		$this->checkRequiredFields(['slug', 'title', 'body', 'title', 'tags', 'short_title', 'short_desc', 'related_blog']);
		$this->checkRequiredFiles(['cover_desktop_img', 'cover_mobile_img', 'main_desktop_img', 'main_mobile_img']);

		$slug = $payload['fields']['slug'];

		$ifSlugAlreadyExistContent = $this->queryResponse("select * from content where slug='$slug'");
		$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$slug'");
		if ($ifSlugAlreadyExist || $ifSlugAlreadyExistContent)
			$this->getResponse(501, 'slug already exist, use another one');


		$filesToBeUploaded = array();
		$filesToBeUploaded['cover_desktop_img'] = $payload['files']['cover_desktop_img'];
		$filesToBeUploaded['cover_mobile_img'] = $payload['files']['cover_mobile_img'];
		$filesToBeUploaded['main_desktop_img'] = $payload['files']['main_desktop_img'];
		$filesToBeUploaded['main_mobile_img'] = $payload['files']['main_mobile_img'];


		// $this->dataArray = $filesToBeUploaded;		
		// $this->getResponse(200);

		$uploadedFilesPaths = $this->uploadMedia($filesToBeUploaded); // to upload files

		$params = array(
			'id' => '',
			'slug' => $slug,
			'title' => $payload['fields']['title'],
			'body' => $payload['fields']['body'],
			'created_date' => time(),
			'owner' => '11',//to get from token passed in header
			'isDeleted' => '0',
			'tags' => $payload['fields']['tags'],
			'cover_desktop_img' => $uploadedFilesPaths['cover_desktop_img'],
			'cover_mobile_img' => $uploadedFilesPaths['cover_mobile_img'],
			'main_desktop_img' => $uploadedFilesPaths['main_desktop_img'],
			'main_mobile_img' => $uploadedFilesPaths['main_mobile_img'],
			'short_title' => $payload['fields']['short_title'],
			'short_desc' => $payload['fields']['short_desc'],
			'related_blog' => $payload['fields']['related_blog'],
		);

		if (!$newSlugID = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($newSlugID);

		$this->dataArray = $data;
		$this->getResponse(200, 'created successfully..');
	}

	public function updateItem($slugID)
	{
		$this->checkAuth();

		$payload = $this->getRequestData();

		$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where id='$slugID'");
		if (!$ifSlugAlreadyExist)
			$this->getResponse(501, 'there is no content with this id');


		$slug = $payload['fields']['slug'];
		$title = $payload['fields']['title'];
		$body = $payload['fields']['body'];

		$tags = $payload['fields']['tags'];
		$short_title = $payload['fields']['short_title'];
		$short_desc = $payload['fields']['short_desc'];
		$related_blog = $payload['fields']['related_blog'];

		$filesToBeUploaded = array();
		if ($payload['files']['cover_desktop_img'])
			$filesToBeUploaded['cover_desktop_img'] = $payload['files']['cover_desktop_img'];
		if ($payload['files']['cover_mobile_img'])
			$filesToBeUploaded['cover_mobile_img'] = $payload['files']['cover_mobile_img'];
		if ($payload['files']['main_desktop_img'])
			$filesToBeUploaded['main_desktop_img'] = $payload['files']['main_desktop_img'];
		if ($payload['files']['main_mobile_img'])
			$filesToBeUploaded['main_mobile_img'] = $payload['files']['main_mobile_img'];

		if (!$slug && !$title && !$body && !$tags && !$short_title && !$short_desc && !$related_blog && !$filesToBeUploaded['cover_desktop_img'] && !$filesToBeUploaded['cover_mobile_img'] && !$filesToBeUploaded['main_desktop_img'] && !$filesToBeUploaded['main_mobile_img'])
			$this->getResponse(501, 'there is nothing to be updated!');

		$params = array();

		if ($slug) {
			$newSlug = $payload['fields']['slug'];
			$params['slug'] = $newSlug;

			$ifSlugAlreadyExistContent = $this->queryResponse("select * from content where slug='$newSlug' and id != '$slugID'");
			$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$newSlug' and id != '$slugID'");
			if ($ifSlugAlreadyExist || $ifSlugAlreadyExistContent)
				$this->getResponse(501, 'the new slug already exist, use another one');
		}
		// 
		$uploadedFilesPaths = $this->uploadMediaPut($filesToBeUploaded); // to upload files

		if ($title)
			$params['title'] = $payload['fields']['title'];
		if ($body)
			$params['body'] = $payload['fields']['body'];
		if ($tags)
			$params['tags'] = $payload['fields']['tags'];
		if ($short_title)
			$params['short_title'] = $payload['fields']['short_title'];
		if ($short_desc)
			$params['short_desc'] = $payload['fields']['short_desc'];
		if ($related_blog)
			$params['related_blog'] = $payload['fields']['related_blog'];

		if ($uploadedFilesPaths['cover_desktop_img'])
			$params['cover_desktop_img'] = $uploadedFilesPaths['cover_desktop_img'];
		if ($uploadedFilesPaths['cover_mobile_img'])
			$params['cover_mobile_img'] = $uploadedFilesPaths['cover_mobile_img'];
		if ($uploadedFilesPaths['main_desktop_img'])
			$params['main_desktop_img'] = $uploadedFilesPaths['main_desktop_img'];
		if ($uploadedFilesPaths['main_mobile_img'])
			$params['main_mobile_img'] = $uploadedFilesPaths['main_mobile_img'];

		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$slugID'"))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($slugID);

		$this->dataArray = $data;
		$this->getResponse(200, 'updated successfully..');
	}

	public function deleteItem($slugID)
	{
		$this->checkAuth();

		$data = $this->getItemByIDFn($slugID, " and isDeleted='0'", false);
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");

		$params = array();
		$params['isDeleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$slugID'"))
			$this->getResponse(503, "An Error Occure.");


		$this->deleteMedia($data['cover_desktop_img']);
		$this->deleteMedia($data['cover_mobile_img']);
		$this->deleteMedia($data['main_desktop_img']);
		$this->deleteMedia($data['main_mobile_img']);

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelContentData($temp, $fullPathImage = true)
	{
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);

		// $temp['tags'] = $temp['tags'];
		if ($fullPathImage) {
			$temp['cover_desktop_img'] = IMG_BASE_URL . $temp['cover_desktop_img'];
			$temp['cover_mobile_img'] = IMG_BASE_URL . $temp['cover_mobile_img'];
			$temp['main_desktop_img'] = IMG_BASE_URL . $temp['main_desktop_img'];
			$temp['main_mobile_img'] = IMG_BASE_URL . $temp['main_mobile_img'];
		}

		$temp['contentType'] = 'blog';
		$temp['owner'] = $this->getAdminByIDFn($temp['owner']);
		$temp['isDeleted'] = +$temp['isDeleted'] === 1 ? true : false;

		return $temp;
	}

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