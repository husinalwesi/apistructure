<?php
class content extends mainController
{
	var $table = "content";
	var $querySelector = array('id', 'slug', 'title', 'body', 'created_date', 'owner', 'isDeleted');
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
		if ($params[0] === 'page' && $this->isAllowedMethod('GET')) {
			$this->getAllItemByID($params[1]);
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

	public function getItemByIDFn($id, $where = '')
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

	public function getAllItemByID($slug)
	{
		$this->checkAuth();
		$data = $this->getItemBySlugFn($slug, " and isDeleted='0'");//search in content pages

		if (!$data) {
			// search in blogs
			$result = $this->queryResponse("select * from blogs where slug='$slug' and isDeleted='0'");
			if ($result)
				$data = $this->modelBlogsData($result[0]);
		}

		if (!$data)
			$this->getResponse(404, "No data found for the given slug.");


		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function modelBlogsData($temp)
	{
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);

		// $temp['tags'] = $temp['tags'];
		$temp['cover_desktop_img'] = IMG_BASE_URL . $temp['cover_desktop_img'];
		$temp['cover_mobile_img'] = IMG_BASE_URL . $temp['cover_mobile_img'];
		$temp['main_desktop_img'] = IMG_BASE_URL . $temp['main_desktop_img'];
		$temp['main_mobile_img'] = IMG_BASE_URL . $temp['main_mobile_img'];


		$temp['contentType'] = 'blog';
		$temp['owner'] = $this->getAdminByIDFn($temp['owner']);
		$temp['isDeleted'] = +$temp['isDeleted'] === 1 ? true : false;

		return $temp;
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

		$this->checkRequiredFields(['slug', 'title', 'body', 'title']);

		$slug = $payload['fields']['slug'];

		$ifSlugAlreadyExistBlogs = $this->queryResponse("select * from blogs where slug='$slug'");
		$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$slug'");
		if ($ifSlugAlreadyExist || $ifSlugAlreadyExistBlogs)
			$this->getResponse(501, 'slug already exist, use another one');

		$params = array(
			'id' => '',
			'slug' => $slug,
			'title' => $payload['fields']['title'],
			'body' => $payload['fields']['body'],
			'created_date' => time(),
			'owner' => '11',//to get from token passed in header
			'isDeleted' => '0',
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
		if (!$slug && !$title && !$body)
			$this->getResponse(501, 'there is nothing to be updated!');

		$params = array();

		if ($slug) {
			$newSlug = $payload['fields']['slug'];
			$params['slug'] = $newSlug;

			$ifSlugAlreadyExistBlogs = $this->queryResponse("select * from blogs where slug='$newSlug' and id != '$slugID'");
			$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$newSlug' and id != '$slugID'");
			if ($ifSlugAlreadyExist || $ifSlugAlreadyExistBlogs)
				$this->getResponse(501, 'the new slug already exist, use another one');
		}

		if ($title)
			$params['title'] = $payload['fields']['title'];
		if ($body)
			$params['body'] = $payload['fields']['body'];

		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$slugID'"))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($slugID);

		$this->dataArray = $data;
		$this->getResponse(200, 'updated successfully..');
	}

	public function deleteItem($slugID)
	{
		$this->checkAuth();

		$data = $this->getItemByIDFn($slugID, " and isDeleted='0'");
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");


		$params = array();
		$params['isDeleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$slugID'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelContentData($temp)
	{
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['isDeleted'] = +$temp['isDeleted'] === 1 ? true : false;
		$temp['owner'] = $this->getAdminByIDFn($temp['owner']);
		$temp['contentType'] = 'content';
		return $temp;
	}

	function modelAllData($temp)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelContentData($temp[$key]);
		}
		return $data;
	}

}
?>