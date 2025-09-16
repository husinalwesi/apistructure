<?php
class rank extends mainController
{
	var $table = "rank";
	var $querySelector = array('id', 'userid', 'rank', 'created_date', 'score');
	public function __construct()
	{
		$this->checkAuth();
		$this->callMethod($this);
	}


	public function topTinAPI()
	{
		$this->setAllowedMethod("GET");
		$data = array();
		// $data = $this->queryResponse("select userid, max(score) AS score from $this->table where score is not null group by userid order by score desc limit 10");



		$data = $this->queryResponse("select username,score from user_top_scores limit 10");

		// for ($i = 0; $i < count($data); $i++) {
		// 	$userID = $data[$i]['userid'];
		// 	$userDetails = $this->queryResponse("select id,username,email,created_date from user where id='$userID'");
		// 	$data[$i]['userDetails'] = $userDetails[0];
		// }

		// $encodedData = [];
		// foreach ($data as $item) {
		// 	// Create an associative array to store encoded values
		// 	$encodedItem = [];
		// 	foreach ($item as $key => $value) {
		// 		// Base64 encode both the key and value
		// 		$encodedKey = $this->encrypt($key);
		// 		$encodedValue = $this->encrypt($value);
		// 		$encodedItem[$encodedKey] = $encodedValue;
		// 	}
		// 	// Add the encoded item to the array
		// 	$encodedData[] = $encodedItem;
		// }

		// $finalData[$this->encrypt('data')] = $encodedData;
		// $data["config"] = $this->getTotalWhere($this->table, 'id', $where);
		// $finalData[$this->encrypt('config')] = $data["config"];

		// $this->dataArray = $finalData;


		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function getrankAPI()
	{
		$this->setAllowedMethod("GET");
		$score = $this->getSecureParams('score');
		$userid = $this->getSecureParams('userid');
		// if (!$score)
		// 	$this->getResponse(503);
		$data = array($this->encrypt('rank') => $this->encrypt($this->getRank($score, $userid)));
		$this->dataArray = $data;
		$this->getResponse(200);
	}

	public function addAPI()
	{
		$this->setAllowedMethod("POST");

		$params = array(
			'id' => '',
			'userid' => '',
			'rank' => '',
			'created_date' => '',
			'score' => ''
		);

		$payload = $this->getPayload();
		foreach ($params as $key => $value) {
			$params[$key] = $payload->$key;
			// if (!$params[$key] && ($key === 'userid' || $key === 'score'))
			// 	$this->getResponse(503);

			if (!$params[$key] && ($key === 'userid'))
				$this->getResponse(503);

		}

		$params['created_date'] = time();
		// $params['rank'] = $this->getRank($params['score']);

		if (!$newRank = $this->queryInsert($this->table, $params))
			$this->getResponse(503, "An Error Occure.");


		$this->getResponse(200, "Rank created successfully.");
	}
	// 
	function getRank($score, $userid)
	{
		if ($userid) {
			$prevHit = $this->getTopHitRank($userid);
			if ($prevHit > $score)
				$score = $prevHit;
			$result = $this->queryResponse("select count(*) + 1 as calculated_rank from user_top_scores where score >= $score and userid <> $userid");
		} else {
			$result = $this->queryResponse("select count(*) + 1 as calculated_rank from user_top_scores where score >= $score");
		}
		return $result[0]['calculated_rank'];
	}



	function getTopHitRank($userID)
	{
		$result = $this->queryResponse("select MAX(score) AS max_score from rank where userid = $userID");
		return $result[0]['max_score'];
	}


	// CREATE VIEW user_top_scores AS
	// SELECT 
	// 	r.id AS rank_id,
	// 	MAX(r.score) AS score,
	// 	r.created_date,
	// 	r.userid,
	// 	u.username,
	// 	u.email
	// FROM 
	// 	rank r
	// JOIN 
	// 	user u ON u.id = r.userid
	// GROUP BY 
	// 	r.userid
	// ORDER BY 
	// 	score DESC, r.created_date ASC;

}
?>