<?php
class content extends mainController
{
	var $table = "content";
	var $querySelector = array('key_item', 'value_en', 'value_ar');
	public function __construct()
	{
		$this->checkAuth();
		$this->callMethod($this);
	}

	public function contactlistAPI()
	{
		$this->setAllowedMethod("GET");
		$this->dataArray = $this->getContactlist();
		$this->getResponse(200);
	}

	public function itemAPI()
	{
		$this->setAllowedMethod("GET");
		$key = $this->getURLParameterByOrder();
		if (!$key)
			$this->getResponse(503, "`key` parameter is needed");

		$data = $this->getItemDetails($key);
		if (!$data)
			$this->getResponse(204);
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function createUpdateAPI()
	{
		$this->setAllowedMethod("POST");
		$params = array('key_item' => '', 'value_en' => '', 'value_ar' => '');
		$payload = $this->getPayload();

		foreach ($params as $key => $value) {
			if (!$payload->$key)
				$this->getResponse(201, "Missing Parameter `$key`");
			$params[$key] = $payload->$key;
		}

		$key = $params['key_item'];

		$oldData = $this->getItemDetails($key);
		if ($oldData) {
			// update
			if (!$this->queryUpdate($this->table, $params, "where key_item='$key'"))
				$this->getResponse(503, "An Error Occure.");
		} else {
			// create
			if (!$this->queryInsert($this->table, $params))
				$this->getResponse(503, "An Error Occure.");
		}

		$this->dataArray = $this->getItemDetails($key);
		$this->getResponse(200, "Content successfully updated.");
	}

	public function updateContactAPI()
	{
		$this->setAllowedMethod("PATCH");
		$payload = $this->getPayload();

		$data = array();
		$data[] = array('key_item' => 'phone1', 'value_en' => $payload->phone->primary);
		$data[] = array('key_item' => 'phone2', 'value_en' => $payload->phone->secondary);

		$data[] = array('key_item' => 'info_email', 'value_en' => $payload->email->info);
		$data[] = array('key_item' => 'support_email', 'value_en' => $payload->email->support);

		$data[] = array('key_item' => 'youtube', 'value_en' => $payload->socialNetwork->youtube);
		$data[] = array('key_item' => 'facebook', 'value_en' => $payload->socialNetwork->facebook);
		$data[] = array('key_item' => 'linkedin', 'value_en' => $payload->socialNetwork->linkedin);
		$data[] = array('key_item' => 'instagram', 'value_en' => $payload->socialNetwork->instagram);
		$data[] = array('key_item' => 'snapchat', 'value_en' => $payload->socialNetwork->snapchat);

		$data[] = array('key_item' => 'location_lat', 'value_en' => $payload->location->lat);
		$data[] = array('key_item' => 'location_lng', 'value_en' => $payload->location->lng);
		$data[] = array('key_item' => 'location_title', 'value_en' => $payload->location->title);

		foreach ($data as $key => $value) {
			$itemKey = $data[$key]['key_item'];
			if (!$this->queryUpdate($this->table, $data[$key], "where key_item='$itemKey'"))
				$this->getResponse(503, "An Error Occure.");
		}

		$this->dataArray = $this->getContactlist();
		$this->getResponse(200, "Contact us successfully updated.");
	}

	// 
	function modelData($temp)
	{
		$data = array();
		$data['key'] = $temp['key_item'];
		$data['value'] = array();
		$data['value']['en'] = $temp['value_en'];
		$data['value']['ar'] = $temp['value_ar'];
		return $data;
	}

	function getItemDetails($key)
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = $this->queryResponse("select $querySelectorString from $this->table where key_item='$key'");
		if (!$data)
			return false;
		return $this->modelData($data[0]);
	}

	function modelContactData($temp)
	{
		$data = array();

		$data["phone"] = array();
		$data["phone"]['primary'] = $this->getItemValueByKey($temp, "phone1");
		$data["phone"]['secondary'] = $this->getItemValueByKey($temp, "phone2");

		$data["email"] = array();
		$data["email"]['info'] = $this->getItemValueByKey($temp, "info_email");
		$data["email"]['support'] = $this->getItemValueByKey($temp, "support_email");

		$locationLat = $this->getItemValueByKey($temp, "location_lat");
		$locationLng = $this->getItemValueByKey($temp, "location_lng");
		$locationTitle = $this->getItemValueByKey($temp, "location_title");
		$isLocationEnabled = $locationLat && $locationLng;

		$data["location"] = $isLocationEnabled ? array() : false;
		if ($isLocationEnabled) {
			$data["location"]['lat'] = $locationLat;
			$data["location"]['lng'] = $locationLng;
			$data["location"]['title'] = $locationTitle;
		}

		$data["socialNetwork"] = array();
		$data["socialNetwork"]["youtube"] = $this->getItemValueByKey($temp, "youtube");
		$data["socialNetwork"]["facebook"] = $this->getItemValueByKey($temp, "facebook");
		$data["socialNetwork"]["linkedin"] = $this->getItemValueByKey($temp, "linkedin");
		$data["socialNetwork"]["instagram"] = $this->getItemValueByKey($temp, "instagram");
		$data["socialNetwork"]["snapchat"] = $this->getItemValueByKey($temp, "snapchat");

		return $data;
	}

	function getItemValueByKey($values, $itemKey)
	{
		foreach ($values as $key => $value) {
			if ($value['key_item'] == $itemKey)
				return $value['value_en'];
		}
		return "";
	}

	public function getContactlist()
	{
		$querySelectorString = $this->getQuerySelector(array_splice($this->querySelector, 0, 2));
		$whereItems = array(
			"phone1",
			"phone2",
			"info_email",
			"support_email",
			"youtube",
			"facebook",
			"linkedin",
			"instagram",
			"snapchat",
			"location_lat",
			"location_lng",
			"location_title"
		);
		$where = $this->prepareMultipleWhereForSameItem($whereItems, "key_item");
		$data = $this->modelContactData($this->queryResponse("select $querySelectorString from $this->table where $where"));
		return $data;
	}

}
?>