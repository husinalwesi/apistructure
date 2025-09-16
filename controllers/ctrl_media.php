<?php
class media extends mainController
{
	var $table = "media";
	var $querySelector = array('id', 'uploadeName', 'originalName', 'url', 'type', 'size', 'created_date');

	public function __construct()
	{
		$this->checkAuth();
		$this->callMethod($this);
	}

	public function uploadAPI()
	{
		$this->setAllowedMethod("POST");
		$medias = $this->uploadMedia('1');

		$ids = array();
		foreach ($medias as $key => $value) {
			if (!$ids[] = $this->queryInsert('media', $value))
				$this->getResponse(503);
		}

		$medias = $this->getMultipleMedias($ids);
		$this->dataArray = $medias;
		$this->getResponse(200, "Media Uploaded successfully.");
	}

	public function listAPI()
	{
		$this->setAllowedMethod("GET");
		$ids = $this->getPayload()->ids;
		$medias = $this->getMultipleMedias($ids);
		$this->dataArray = $medias;
		$this->getResponse(200);
	}

	public function itemAPI()
	{
		$this->setAllowedMethod("GET");
		$id = $this->getURLParameterByOrder();
		if (!$id)
			$this->getResponse(503, "`id` parameter is needed");

		$data = $this->getItemDetails($id);
		if (!$data)
			$this->getResponse(204);
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	function getItemDetails($id)
	{
		$querySelectorString = $this->getQuerySelector($this->querySelector);
		$data = $this->queryResponse("select $querySelectorString from $this->table where id=$id");
		if (!$data)
			return false;
		return $this->modelData($data[0]);
	}

	function getMultipleMedias($ids)
	{
		if (count($ids) == 0)
			$this->getResponse(204);

		$medias = array();
		foreach ($ids as $key => $value) {
			$item = $this->getItemDetails($value);
			if ($item)
				$medias[] = $item;
		}

		if (count($medias) == 0)
			$this->getResponse(204);

		return $medias;
	}

	function modelData($temp)
	{
		$data = array();
		$data['id'] = $temp['id'];

		$data['name'] = array();
		$data['name']['upload'] = $temp['uploadeName'];
		$data['name']['original'] = $temp['originalName'];

		$data['url'] = $temp['url'];
		$data['type'] = $temp['type'];
		$data['size'] = $temp['size'];
		$data['createdDate'] = $this->timeStampToDate($temp['created_date'], "dateTime");
		return $data;
	}

}
?>