<?php
class search extends mainController
{
	public function __construct()
	{
		$this->callMethod($this);
	}

	public function mainAPI()
	{
		$params = $this->extractUrlParams();
		if (count($params) === 0 && $this->isAllowedMethod('GET')) {
			$this->getItems();
		}

		$this->getResponse(422, "Method not supported");
	}

	public function getItems()
	{
		$q = $this->getSecureParams("q");	
		$limit = $this->getSecureParams("limit");
		$page = $this->getSecureParams("page");	
		if (!$limit) $limit = "100";
		if (!$page) $page = "1";

		if(!$q) $this->getResponse(200, 'Missing q..');	
		$search = "'%$q%'";
		$data = array();
		// 
		$data["books"] = $this->queryResponse("select slug, title, short_desc as 'desc',img from books_with_avg_rate where is_deleted = '0' and (
        slug like $search
        or title like $search
        or short_desc like $search
        or long_desc like $search
        or international_number like $search
        or publisher like $search
        or author like $search
        or specialization like $search
        or publish_year like $search
			)
		");
		
		foreach ($data["books"] as $key => $value) {
			$data["books"][$key]['category'] = 'book';
			$data["books"][$key] = $this->modelData($data["books"][$key]);
		}
		// 
		// 
		$data["categories"] = $this->queryResponse("select slug, title, description as 'desc', img from category where isDeleted = '0' and (
        slug like $search
        or title like $search
        or description like $search
		)
		");
		
		foreach ($data["categories"] as $key => $value) {
			$data["categories"][$key]['category'] = 'category';
			$data["categories"][$key] = $this->modelData($data["categories"][$key]);			
		}				
		// 
		// 
		$data["events"] = $this->queryResponse("select id as 'slug', description as 'desc', img from events where is_deleted = '0' and (
        description like $search
		)
		");
		
		foreach ($data["events"] as $key => $value) {
			$data["events"][$key]['category'] = 'event';
			$data["events"][$key]['title'] = $data["events"][$key]['desc'];			
			$data["events"][$key]['desc'] = "";
			$data["events"][$key] = $this->modelData($data["events"][$key]);						
		}
		// 
		// 

		$data["content"]['about'] = $this->queryResponse("select slug, title, body as 'desc' from content where (
        (slug ='short_info' or slug ='about')
        and (title like $search or body like $search)
		)
		")[0];
		$data["content"]['about']['img'] = "";
		$data["content"]['about']['category'] = "aboutus";
		// $data["content"]['about'] = $this->modelData($data["content"]['about']);
		// 
		// 
		$data["content"]['privacy-policy'] = $this->queryResponse("select slug, title, body as 'desc' from content where (
        (slug ='privacy-policy')
        and (title like $search or body like $search)
		)
		")[0];		
		$data["content"]['privacy-policy']['img'] = "";
		$data["content"]['privacy-policy']['category'] = "privacy-policy";		
		// $data["content"]['privacy-policy'] = $this->modelData($data["content"]['privacy-policy']);		
		// 
		// 
		$data["content"]['orders-payments-delivery'] = $this->queryResponse("select slug, title, body as 'desc' from content where (
        (slug ='orders-payments-delivery')
        and (title like $search or body like $search)
		)
		")[0];		
		$data["content"]['orders-payments-delivery']['img'] = "";
		$data["content"]['orders-payments-delivery']['category'] = "orders-payments-delivery";
		// $data["content"]['orders-payments-delivery'] = $this->modelData($data["content"]['orders-payments-delivery']);				
		// 
		// 
		$data["content"]['refund-returns-policy'] = $this->queryResponse("select slug, title, body as 'desc' from content where (
        (slug ='refund-returns-policy')
        and (title like $search or body like $search)
		)
		")[0];						
		$data["content"]['refund-returns-policy']['img'] = "";
		$data["content"]['refund-returns-policy']['category'] = "refund-returns-policy";		
		// $data["content"]['refund-returns-policy'] = $this->modelData($data["content"]['refund-returns-policy']);
		// 
		// 		
		$all = array_merge(
			$data["books"],
			$data["categories"],
			$data["events"],
			array_values($data["content"])
		);


		$total = count($all);
		$totalPages = ceil($total / $limit);
		$offset = ($page - 1) * $limit;
		$paginatedItems = array_slice($all, $offset, $limit);



		$result = array(
			"config" => array(
				'total' => $total,
				'pages' => $totalPages,
				'limit' => $limit
			),
			"data" => $paginatedItems
		);

		$this->dataArray = $result;
		$this->getResponse(200);
	}	


	public function modelData($temp, $fullPathImage = true)
	{
		if($temp['img']) $temp['img'] = IMG_BASE_URL . $temp['img'];
		return $temp;
	}

}
?>