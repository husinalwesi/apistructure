<?php
class analytics extends mainController
{
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
		// $params = $this->extractUrlParams();
		if ($this->isAllowedMethod('GET')) {
			$this->getItems();
		}

		$this->getResponse(422, "Method not supported");
	}

	public function getItems()
	{
		$this->checkAuth();
		$totalCategories = $this->queryResponse("select count(id) as 'totalCategories' from category where isDeleted='0'");

		$totalMainCategories = $this->queryResponse("select count(distinct groupCategory) as 'totalMainCategories' from category where isDeleted='0'");		

		$totalQuestions = $this->queryResponse("select count(id) as 'totalQuestions' from questions where isDeleted='0'");		

		$data = array();
		$data["totalCategories"] = $totalCategories[0]['totalCategories'];		
		$data["totalMainCategories"] = $totalMainCategories[0]['totalMainCategories'];	


		$data["totalQuestions"] = $totalMainCategories[0]['totalQuestions'];			
		$this->dataArray = $data;
		$this->getResponse(200);
	}

}
?>