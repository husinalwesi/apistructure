<?php
class comments extends mainController
{
	var $table = "comments";
	var $querySelector = array('id', 'userID', 'username', 'comment', 'created_date', 'is_deleted');
	public function __construct()
	{
		$this->checkAuth();
		$this->callMethod($this);
	}


	public function listAPI()
	{
		$this->setAllowedMethod("GET");
		$handlePagination = $this->handlePagination();
		$where = "where is_deleted='0'";
		$data = array();
		$finalData = array();

		$data = $this->queryResponse("select username,comment,linked_username from comments_view $where $handlePagination");

		$finalData["data"] = array();
		for ($i = 0; $i < count(value: $data); $i++) {
			$finalData["data"][$i]['name'] = $data[$i]['linked_username'] ?? $data[$i]['username'];
			$finalData["data"][$i]["comment"] = $data[$i]['comment'];
		}

		$finalData["config"] = $this->getTotalWhere('comments_view', 'id', $where);

		$this->dataArray = $finalData;
		$this->getResponse(200);
	}

	public function addAPI()
	{
		$this->setAllowedMethod("POST");

		$params = array(
			'id' => '',
			'userID' => '',
			'username' => '',
			'comment' => '',
			'created_date' => '',
			'is_deleted' => ''
		);

		$payload = $this->getPayload();
		foreach ($params as $key => $value) {
			$params[$key] = $this->decrypt($payload->$key);
			// if (!$params[$key])
			// 	$this->getResponse(503);

			// && (($key === $this->encrypt('username') || $key === $this->encrypt('userID')) || $key === $this->encrypt('comment')))

		}

		$params['created_date'] = time();
		$params['is_deleted'] = 0;


		if (!$newComment = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");


		$this->getResponse(200, "Comment created successfully.");
	}


	// CREATE VIEW comments_view AS
	// SELECT 
	// 	c.id,
	// 	c.userID,
	// 	c.username,
	// 	c.comment,
	// 	c.created_date,
	// 	c.is_deleted,

	// 	u.username AS linked_username,
	// 	u.email
	// FROM 
	// 	comments c
	// LEFT JOIN 
	// 	user u ON u.id = c.userID
	// ORDER BY 
	// 	c.created_date DESC;

}
?>