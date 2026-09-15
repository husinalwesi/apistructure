<?php
class book extends mainController
{
	var $table = "book";
	var $querySelector = array(
		'id',
		'slug',
		'title',
		'short_desc',
		'long_desc',
		'img',
		'inner_img',
		'price',
		'international_number',
		'publisher',
		'author',
		'specialization',
		'publish_year',
		'page_no',
		'category',
		'created_date',
		'owner_id',
		'is_deleted',
		'index_file',
		'file'
	);
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
			$this->getItemBySlug($params[0]);
		} elseif (count($params) === 2 && $this->isAllowedMethod('GET')) {
			$this->getItemFileBySlug($params[1]);
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
		// $this->checkAuth();
		$handlePagination = $this->handlePagination();
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = array();
		// 
		$where = "";
		$show_deleted = $this->getSecureParams("show_deleted");
		if ($show_deleted === 'true') $where = "where is_deleted='1'";
		else if ($show_deleted === 'false') $where = "where is_deleted='0'";

		$category = $this->getSecureParams("category");
		if($category) $where.=" and category = '$category'";
		// category
		$related_book_id = $this->getSecureParams("related_book_id");
		if($related_book_id) $where.=" and id != '$related_book_id'";

		$orderby = $this->getSecureParams("orderby");
		$order = "";
		if($orderby === 'rating' || $orderby === 'date' || $orderby === 'pricelowtohigh' || $orderby === 'pricehightolow'){
			// 
			if($orderby === 'rating'){
				$order = "order by avg_rate desc";
			}
			// 
			if($orderby === 'pricelowtohigh' || $orderby === 'pricehightolow'){
				$orderAscDesc = $orderby === 'pricelowtohigh' ? "asc" : "desc";
				$order = "order by price $orderAscDesc";
			}
			// 
			if($orderby === 'date'){
				$order = "order by created_date desc";
			}
		}

		$data["config"] = $this->getTotalWhere("books_with_avg_rate", 'id', $where, "");
		$data["data"] = $this->modelAllData($this->queryResponse("select $querySelectorString from books_with_avg_rate $where $order $handlePagination"));
		$this->dataArray = $data;
		$this->getResponse(200);
	}


	public function getItemByIDFn($id, $where = '')
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from books_with_avg_rate where id='$id' $where");
		if (!$result || count($result) === 0)
			return null;
		$data = array();
		$data = $this->modelAllData($result);
		if (!$data || count($data) === 0)
			return null;
		return $data[0];
	}

	public function getItemBySlugFn($slug, $where = '', $specialCase = false)
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$result = $this->queryResponse("select $querySelectorString from books_with_avg_rate where slug='$slug' $where");

		if (!$result || count($result) === 0)
			return null;
		$data = array();
		$data = $this->modelAllData($result, $specialCase);
		if (!$data || count($data) === 0)
			return null;
		return $data[0];
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
		$temp['is_deleted'] = +$temp['is_deleted'] === 1 ? true : false;

		return $temp;
	}

	public function getItemFileBySlug($slug)
	{
		$this->checkAuth();
	    $userID = $this->getUserID();		
		$result = $this->getItemBySlugFn($slug,'', true);
		if (!$result) $this->getResponse(404, "No data found for the given Slug.");

		// user is logged in now after checkAuth.
		// but we need to make sure that the user already buy this book.

		
		$data = $this->queryResponse("select cart from orders where owner_id = '$userID'");		
		// later on when payment is ready, we need to add another condition to check the status of the payment also.
		if(!$data) $this->getResponse(201, 'You have to buy the book first..');

		$booksAlreadyBuyied = array();
		foreach ($data as $key => $value) {
			$data[$key]['cart'] = json_decode($data[$key]['cart']);
			foreach ($data[$key]['cart'] as $key2 => $value2) {			
				$booksAlreadyBuyied[] = $data[$key]['cart'][$key2]->slug;
			}
		}

		if (in_array($slug, $booksAlreadyBuyied, true)) {
			$this->dataArray = $result['file'];
			$this->getResponse(200);
		}
		
		$this->getResponse(201, 'You have to buy the book first..');
	}		

	public function getItemBySlug($slug)
	{
		// $this->checkAuth();
		$data = $this->getItemBySlugFn($slug);
		if (!$data) $this->getResponse(404, "No data found for the given Slug.");
		$this->dataArray = $data;
		$this->getResponse(200);
	}	

	// public function getItemByID($id)
	// {
	// 	// $this->checkAuth();
	// 	$data = $this->getItemByIDFn($id);
	// 	if (!$data)
	// 		$this->getResponse(404, "No data found for the given ID.");
	// 	$this->dataArray = $data;
	// 	$this->getResponse(200);
	// }

	public function createItem()
	{
		$this->checkAuth();
		$payload = $this->getRequestData();

		$this->checkRequiredFields([
			'slug',
			'title',
			'short_desc',
			'long_desc',
			'price',
			'international_number',
			'publisher',
			'author',
			'specialization',
			'publish_year',
			'page_no',
			'category'
		]);

		$slug = $payload['fields']['slug'];
		$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$slug'");
		if ($ifSlugAlreadyExist) $this->getResponse(501, 'slug already exist, use another one');		

		// $this->checkRequiredFiles(['img', 'inner_img']);				
		$this->checkRequiredFiles(['img', 'file', 'index_file']);
		
		$filesToBeUploaded = array();
		$filesToBeUploaded['img'] = $payload['files']['img'];
		$filesToBeUploaded['inner_img'] = $payload['files']['inner_img'];

		$filesToBeUploaded['file'] = $payload['files']['file'];
		$filesToBeUploaded['index_file'] = $payload['files']['index_file'];		

		$uploadedFilesPaths = $this->uploadMedia($filesToBeUploaded); // to upload files		

		$params = array(
			'id' => '',

			'slug' => $payload['fields']['slug'],			
			'title' => $payload['fields']['title'],
			'short_desc' => $payload['fields']['short_desc'],
			'long_desc' => $payload['fields']['long_desc'],
			'img' => $uploadedFilesPaths['img'],
			'inner_img' => $uploadedFilesPaths['inner_img'],
			'price' => $payload['fields']['price'],
			'international_number' => $payload['fields']['international_number'],
			'publisher' => $payload['fields']['publisher'],
			'author' => $payload['fields']['author'],
			'specialization' => $payload['fields']['specialization'],
			'publish_year' => $payload['fields']['publish_year'],
			'page_no' => $payload['fields']['page_no'],
			'category' => $payload['fields']['category'],
			
			'created_date' => time(),
			'owner_id' => $this->getUserID(),
			'is_deleted' => '0',

			'file' => $uploadedFilesPaths['file'],
			'index_file' => $uploadedFilesPaths['index_file'],			
		);

		if (!$newSlugID = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($newSlugID);

		$this->dataArray = $data;
		$this->getResponse(200, 'created successfully..');
	}


	public function updateItem($id)
	{
		$this->checkAuth();

		$payload = $this->getRequestData();

		$ifIDAlreadyExist = $this->queryResponse("select * from $this->table where id='$id'");
		if (!$ifIDAlreadyExist) $this->getResponse(501, 'there is no content with this ID');


		$slug = $payload['fields']['slug'];		
		$title = $payload['fields']['title'];
		$short_desc = $payload['fields']['short_desc'];
		$long_desc = $payload['fields']['long_desc'];
		$price = $payload['fields']['price'];
		$international_number = $payload['fields']['international_number'];
		$publisher = $payload['fields']['publisher'];
		$author = $payload['fields']['author'];
		$specialization = $payload['fields']['specialization'];
		$publish_year = $payload['fields']['publish_year'];
		$page_no = $payload['fields']['page_no'];
		$category = $payload['fields']['category'];

		$filesToBeUploaded = array();
		if ($payload['files']['img'])
			$filesToBeUploaded['img'] = $payload['files']['img'];
		if ($payload['files']['inner_img'])
			$filesToBeUploaded['inner_img'] = $payload['files']['inner_img'];

		if ($payload['files']['file'])
			$filesToBeUploaded['file'] = $payload['files']['file'];
		if ($payload['files']['index_file'])
			$filesToBeUploaded['index_file'] = $payload['files']['index_file'];		



		if (
			!$filesToBeUploaded['img'] &&
			!$filesToBeUploaded['inner_img'] &&
			!$filesToBeUploaded['file'] &&
			!$filesToBeUploaded['index_file'] &&			
			!$slug &&
			!$title &&
			!$short_desc &&
			!$long_desc &&
			!$price &&
			!$international_number &&
			!$publisher &&
			!$author &&
			!$specialization &&
			!$publish_year &&
			!$page_no &&
			!$category
		)
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
		
		if ($uploadedFilesPaths['img'])
			$params['img'] = $uploadedFilesPaths['img'];
		if ($uploadedFilesPaths['inner_img'])
			$params['inner_img'] = $uploadedFilesPaths['inner_img'];	
		
		if ($uploadedFilesPaths['file'])
			$params['file'] = $uploadedFilesPaths['file'];	

		if ($uploadedFilesPaths['index_file'])
			$params['index_file'] = $uploadedFilesPaths['index_file'];	

		if($uploadedFilesPaths['file'] && $ifIDAlreadyExist[0]['file']){
			// if there is a new image, also there is an old image, so delete the old image file.
			$this->deleteMedia($ifIDAlreadyExist[0]['file']);
		}

		if($uploadedFilesPaths['index_file'] && $ifIDAlreadyExist[0]['index_file']){
			// if there is a new image, also there is an old image, so delete the old image file.
			$this->deleteMedia($ifIDAlreadyExist[0]['index_file']);
		}

		if($uploadedFilesPaths['img'] && $ifIDAlreadyExist[0]['img']){
			// if there is a new image, also there is an old image, so delete the old image file.
			$this->deleteMedia($ifIDAlreadyExist[0]['img']);
		}

		if($uploadedFilesPaths['inner_img'] && $ifIDAlreadyExist[0]['inner_img']){
			// if there is a new image, also there is an old image, so delete the old image file.
			$this->deleteMedia($ifIDAlreadyExist[0]['inner_img']);
		}


		if ($slug)
			$params['slug'] = $payload['fields']['slug'];		
		if ($title)
			$params['title'] = $payload['fields']['title'];
		if ($short_desc)
			$params['short_desc'] = $payload['fields']['short_desc'];
		if ($long_desc)
			$params['long_desc'] = $payload['fields']['long_desc'];
		if ($price)
			$params['price'] = $payload['fields']['price'];
		if ($international_number)
			$params['international_number'] = $payload['fields']['international_number'];
		if ($publisher)
			$params['publisher'] = $payload['fields']['publisher'];
		if ($author)
			$params['author'] = $payload['fields']['author'];
		if ($specialization)
			$params['specialization'] = $payload['fields']['specialization'];
		if ($publish_year)
			$params['publish_year'] = $payload['fields']['publish_year'];
		if ($page_no)
			$params['page_no'] = $payload['fields']['page_no'];
		if ($category)
			$params['category'] = $payload['fields']['category'];

		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$id'"))
			$this->getResponse(503, "An Error Occure.");

		$data = $this->getItemByIDFn($id);

		$this->dataArray = $data;
		$this->getResponse(200, 'updated successfully..');
	}

	public function deleteItem($slugID)
	{
		$this->checkAuth();

		$data = $this->getItemByIDFn($slugID, " and is_deleted='0'");
		if (!$data)
			$this->getResponse(404, "No data found for the given ID.");


		$params = array();
		$params['is_deleted'] = 1;
		if (!$this->queryUpdate($this->table, $params, "where CAST(id AS CHAR)='$slugID'"))
			$this->getResponse(503, "An Error Occure.");

		$this->getResponse(200, 'deleted successfully..');
	}

	public function modelContentData($temp, $specialCase = false)
	{

		$temp['img'] = IMG_BASE_URL . $temp['img'];
		$temp['inner_img'] = IMG_BASE_URL . $temp['inner_img'];			


		$temp['index_file'] = IMG_BASE_URL . $temp['index_file'];			
		$temp['file'] = IMG_BASE_URL . $temp['file'];					

		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['is_deleted'] = +$temp['is_deleted'] === 1 ? true : false;
		$temp['owner'] = $this->getAdminByIDFn($temp['owner_id']);

		$temp['category'] = $this->getCategoryByIDFn($temp['category']);		

		$temp['prices'] = $this->queryResponse("select * from book_prices where is_deleted = '0' and bookid='".$temp['id']."'");

		foreach ($temp['prices'] as $key => $value) {
			$temp['prices'][$key] = $this->modelPricesData($temp['prices'][$key]);
		}
		if(!$specialCase) unset($temp['file']);
		return $temp;
	}

	public function modelPricesData($temp, $fullPathImage = true)
	{
		$temp['created_date'] = $this->timeStampToDate($temp['created_date']);
		$temp['is_deleted'] = +$temp['is_deleted'] === 1;
		$temp['owner'] = $this->getAdminByIDFn($temp['owner']);

		
		// $result = $this->queryResponse("select * from book where id='" . $temp['bookid'] . "'");
		// $temp['book'] = $this->modelBookData($result[0]);
		unset($temp['bookid']);

		return $temp;
	}	

	function modelAllData($temp, $specialCase = false)
	{
		$data = array();
		foreach ($temp as $key => $value) {
			$data[] = $this->modelContentData($temp[$key], $specialCase);
		}
		return $data;
	}

}
?>