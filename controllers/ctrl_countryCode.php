<?php
class countrycode extends mainController
{
	var $table = "country_code";
	var $querySelector = array('id', 'name', 'code', 'capital', 'region', 'currency_code', 'currency_name', 'currency_symbol', 'language_code', 'language_name', 'flag', 'is_deleted');
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
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = $this->queryResponse("select $querySelectorString from $this->table where id=$id");
		if (!$data)
			$this->getResponse(204);
		$this->dataArray = $this->modelData($data[0]);
		$this->getResponse(200);
	}
	// 
	// 
	// 
	function modelData($temp)
	{
		$data = array();
		$data['id'] = +$temp['id'];
		$data['name'] = $temp['name'];
		$data['code'] = $temp['code'];
		$data['capital'] = $temp['capital'];
		$data['region'] = $temp['region'];
		$data['flag'] = $temp['flag'];
		$data['isDeleted'] = $this->toBoolean($temp['is_deleted']);
		// 
		$data['language'] = array();
		$data['language']['name'] = $temp['language_name'];
		$data['language']['code'] = $temp['language_code'];
		// 
		$data['currency'] = array();
		$data['currency']['code'] = $temp['currency_code'];
		$data['currency']['name'] = $temp['currency_name'];
		$data['currency']['symbol'] = $temp['currency_symbol'];
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

}
?>