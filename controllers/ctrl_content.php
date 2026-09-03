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
	 	if ($params[0] === 'info' && $this->isAllowedMethod('PUT')) {
			$this->updateFull();
		}else if ($params[0] === 'info' && $this->isAllowedMethod('GET')) {
			$this->getInfo();
		}else if ($params[0] === 'contact' && $this->isAllowedMethod('GET')) {
			$this->getContact();
		}else if ($params[0] === 'social' && $this->isAllowedMethod('GET')) {
			$this->getSocial();
		}else if ($params[0] === 'page' && $this->isAllowedMethod('GET')) {
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

	public function getInfo($msg = ''){
		$this->checkAuth();
		// 
		$data3 = array();

		$temp = array('short_info', 'about', 'copyright','privacy-policy','orders-payments-delivery', 'refund-returns-policy');

		foreach ($temp as $key => $value) {			
			$data3[$temp[$key]] = $this->getItemBySlugFn($temp[$key], " and isDeleted='0'");
		}
		// 

		$data = array();

		$temp = array('phone','mobile','email', 'map');

		foreach ($temp as $key => $value) {			
			$data[$temp[$key]] = $this->getItemBySlugFn($temp[$key], " and isDeleted='0'");
			$data[$temp[$key]] = $data[$temp[$key]]['title'];			
		}

		$map = $data['map'];

		$data['map'] = array();
		$data['map']['latlng'] = $map;
		$mapSplitted = explode(',', $map);
		$data['map']['lat'] = $mapSplitted[0];
		$data['map']['lng'] = $mapSplitted[1];		
		// 
		// 
		// 
		// $this->checkAuth();
		$data2 = array();

		$temp = array('google','x','whatsapp','instagram','linkedin','facebook');

		foreach ($temp as $key => $value) {			
			$data2[$temp[$key]] = $this->getItemBySlugFn($temp[$key], " and isDeleted='0'");
			$data2[$temp[$key]] = $data2[$temp[$key]]['title'];			
		}

		// $this->dataArray = $data2;
		// $this->getResponse(200);			

		// $this->dataArray = $data;
		// $this->getResponse(200);			

		$result = array();
		$result['contact'] = $data;
		$result['social'] = $data2;	

		$result['general'] = array();
		$result['general']['short_info'] = array();
		$result['general']['short_info']['title'] = $data3['short_info']['title'];
		$result['general']['short_info']['body'] = $data3['short_info']['body'];		
		// 
		$result['general']['about'] = array();
		$result['general']['about']['title'] = $data3['about']['title'];
		$result['general']['about']['body'] = $data3['about']['body'];				
		// 
		$result['general']['copyright'] = array();
		$result['general']['copyright'] = $data3['copyright']['title'];
		// 
		$result['general']['privacy-policy'] = array();
		$result['general']['privacy-policy']['title'] = $data3['privacy-policy']['title'];
		$result['general']['privacy-policy']['body'] = $data3['privacy-policy']['body'];				
		//
		$result['general']['orders-payments-delivery'] = array();
		$result['general']['orders-payments-delivery']['title'] = $data3['orders-payments-delivery']['title'];
		$result['general']['orders-payments-delivery']['body'] = $data3['orders-payments-delivery']['body'];				 
		// 
		$result['general']['refund-returns-policy'] = array();
		$result['general']['refund-returns-policy']['title'] = $data3['refund-returns-policy']['title'];
		$result['general']['refund-returns-policy']['body'] = $data3['refund-returns-policy']['body'];				 		

		$this->dataArray = $result;
		$this->getResponse(200);					
	}

	public function getContact(){
		$this->checkAuth();
		$data = array();

		$temp = array('phone','mobile','email', 'map');

		foreach ($temp as $key => $value) {			
			$data[$temp[$key]] = $this->getItemBySlugFn($temp[$key], " and isDeleted='0'");
			$data[$temp[$key]] = $data[$temp[$key]]['title'];			
		}

		$map = $data['map'];

		$data['map'] = array();
		$data['map']['latlng'] = $map;
		$mapSplitted = explode(',', $map);
		$data['map']['lat'] = $mapSplitted[0];
		$data['map']['lng'] = $mapSplitted[1];		

		$this->dataArray = $data;
		$this->getResponse(200);			
	}

	public function getSocial(){
		$this->checkAuth();
		$data = array();

		$temp = array('google','x','whatsapp','instagram','linkedin','facebook');

		foreach ($temp as $key => $value) {			
			$data[$temp[$key]] = $this->getItemBySlugFn($temp[$key], " and isDeleted='0'");
			$data[$temp[$key]] = $data[$temp[$key]]['title'];			
		}

		$this->dataArray = $data;
		$this->getResponse(200);		
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

		$this->checkRequiredFields(['slug', 'title', 'body']);

		$slug = $payload['fields']['slug'];

		// $ifSlugAlreadyExistBlogs = $this->queryResponse("select * from blogs where slug='$slug'");
		$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$slug'");
		// if ($ifSlugAlreadyExist || $ifSlugAlreadyExistBlogs)
		if ($ifSlugAlreadyExist)		
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

	public function updateFull()
	{
		$this->checkAuth();
		$payload = $this->getRequestData();

		$phone = $payload['fields']['phone'];
		$mobile = $payload['fields']['mobile'];
		$email = $payload['fields']['email'];
		$maplatlng = $payload['fields']['maplatlng'];
		$google = $payload['fields']['google'];
		$x = $payload['fields']['x'];
		$whatsapp = $payload['fields']['whatsapp'];
		$instagram = $payload['fields']['instagram'];
		$linkedin = $payload['fields']['linkedin'];
		$facebook = $payload['fields']['facebook'];

		$short_info_title = $payload['fields']['short_info_title'];
		$short_info_desc = $payload['fields']['short_info_desc'];

		$about_title = $payload['fields']['about_title'];
		$about_desc = $payload['fields']['about_desc'];

		$copyright = $payload['fields']['copyright'];

		$privacy_title = $payload['fields']['privacy_title'];
		$privacy_desc = $payload['fields']['privacy_desc'];

		$order_payment_title = $payload['fields']['order_payment_title'];
		$order_payment_desc = $payload['fields']['order_payment_desc'];

		$refund_title = $payload['fields']['refund_title'];
		$refund_desc = $payload['fields']['refund_desc'];

		if (
			!$phone &&
			!$mobile &&
			!$email &&
			!$maplatlng &&
			!$google &&
			!$x &&
			!$whatsapp &&
			!$instagram &&
			!$linkedin &&
			!$facebook &&
			!$short_info_title &&
			!$short_info_desc &&
			!$about_title &&
			!$about_desc &&
			!$copyright &&
			!$privacy_title &&
			!$privacy_desc &&
			!$order_payment_title &&
			!$order_payment_desc &&
			!$refund_title &&
			!$refund_desc
		) {
			$this->getResponse(203, "No data provided.");
		}

		if($phone){
			$params = array();		
			$params['title'] = $phone;
			if (!$this->queryUpdate($this->table, $params, "where slug='phone'")) $this->getResponse(503, "An Error Occure.");			
		}

		if ($mobile) {
			$params = array();
			$params['title'] = $mobile;
			if (!$this->queryUpdate($this->table, $params, "where slug='mobile'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($email) {
			$params = array();
			$params['title'] = $email;
			if (!$this->queryUpdate($this->table, $params, "where slug='email'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($maplatlng) {
			$params = array();
			$params['title'] = $maplatlng;
			if (!$this->queryUpdate($this->table, $params, "where slug='map'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($google) {
			$params = array();
			$params['title'] = $google;
			if (!$this->queryUpdate($this->table, $params, "where slug='google'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($x) {
			$params = array();
			$params['title'] = $x;
			if (!$this->queryUpdate($this->table, $params, "where slug='x'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($whatsapp) {
			$params = array();
			$params['title'] = $whatsapp;
			if (!$this->queryUpdate($this->table, $params, "where slug='whatsapp'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($instagram) {
			$params = array();
			$params['title'] = $instagram;
			if (!$this->queryUpdate($this->table, $params, "where slug='instagram'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($linkedin) {
			$params = array();
			$params['title'] = $linkedin;
			if (!$this->queryUpdate($this->table, $params, "where slug='linkedin'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($facebook) {
			$params = array();
			$params['title'] = $facebook;
			if (!$this->queryUpdate($this->table, $params, "where slug='facebook'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($short_info_title || $short_info_desc) {
			$params = array();
			if($short_info_title) $params['title'] = $short_info_title;
			if($short_info_desc) $params['body'] = $short_info_desc;
			if (!$this->queryUpdate($this->table, $params, "where slug='short_info'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($about_title || $about_desc) {
			$params = array();
			if($about_title) $params['title'] = $about_title;
			if($about_desc) $params['body'] = $about_desc;
			if (!$this->queryUpdate($this->table, $params, "where slug='about'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($copyright) {
			$params = array();
			$params['title'] = $copyright;
			if (!$this->queryUpdate($this->table, $params, "where slug='copyright'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($privacy_title || $privacy_desc) {
			$params = array();
			if($privacy_title) $params['title'] = $privacy_title;
			if($privacy_desc) $params['body'] = $privacy_desc;
			if (!$this->queryUpdate($this->table, $params, "where slug='privacy-policy'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($order_payment_title || $order_payment_desc) {
			$params = array();
			if($order_payment_title) $params['title'] = $order_payment_title;
			if($order_payment_desc) $params['body'] = $order_payment_desc;
			if (!$this->queryUpdate($this->table, $params, "where slug='orders-payments-delivery'")) $this->getResponse(503, "An Error Occure.");
		}

		if ($refund_title || $refund_desc) {
			$params = array();
			if($refund_title) $params['title'] = $refund_title;
			if($refund_desc) $params['body'] = $refund_desc;
			if (!$this->queryUpdate($this->table, $params, "where slug='refund-returns-policy'")) $this->getResponse(503, "An Error Occure.");
		}

		// return updated data with update message..
		$this->getInfo('Updated Successfully..');
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

			// $ifSlugAlreadyExistBlogs = $this->queryResponse("select * from blogs where slug='$newSlug' and id != '$slugID'");
			$ifSlugAlreadyExist = $this->queryResponse("select * from $this->table where slug='$newSlug' and id != '$slugID'");
			// if ($ifSlugAlreadyExist || $ifSlugAlreadyExistBlogs)			
			if ($ifSlugAlreadyExist)
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