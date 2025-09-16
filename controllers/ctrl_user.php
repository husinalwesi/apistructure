<?php
class user extends mainController
{
	var $table = "user";
	var $querySelector = array('id', 'username', 'email', 'password', 'created_date');

	public function __construct()
	{
		$this->checkAuth();
		$this->callMethod($this);
	}

	public function loginAPI()
	{
		$this->setAllowedMethod("POST");
		$payload = $this->getPayload();
		$username = $payload->username;
		$password = $payload->password;
		if (!$username || !$password)
			$this->getResponse(503);


		$data = $this->queryResponse("select id,username,email,created_date from $this->table where (username='$username' or email='$username') and password='$password'");

		$topHit = $this->getTopHit($data[0]['id']);
		if (!$topHit)
			$topHit = 0;
		$data[0]['top_hit'] = $topHit;
		$data[0]['rank'] = $this->getRank($topHit);

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

		// 
		$this->dataArray = $data[0];
		$this->getResponse(200);
	}

	public function signupAPI()
	{
		$this->setAllowedMethod("POST");
		$payload = $this->getPayload();
		$username = $payload->username;
		$email = $payload->email;
		$password = $payload->password;
		if (!$username || !$password || !$email)
			$this->getResponse(503);

		$data = $this->queryResponse("select * from $this->table where username='$username' or email='$username'");
		if (!$data[0]) {
			// if (true) {
			$params = array(
				'id' => '',
				'username' => $username,
				'email' => $email,
				'password' => $password,
				'created_date' => time()
			);

			if (!$newUserID = $this->queryInsert($this->table, $params))
				$this->getResponse(503, "An Error Occure.");

			$newUserData = $this->queryResponse("select id,username,email,created_date from $this->table where id='$newUserID'");

			$newUserData[0]['top_hit'] = 0;
			$newUserData[0]['rank'] = 0;

			// $newUserDataTemp = array();
			// $newUserDataTemp[0] = $newUserData;
			$encodedData = [];
			foreach ($newUserData as $item) {
				// // Create an associative array to store encoded values
				$encodedItem = [];
				foreach ($item as $key => $value) {
					// Base64 encode both the key and value
					$encodedKey = $this->encrypt($key);
					$encodedValue = $this->encrypt($value);
					$encodedItem[$encodedKey] = $encodedValue;
				}
				// Add the encoded item to the array
				$encodedData = $encodedItem;
			}

			$this->dataArray = $encodedData;
			$this->getResponse(200);
		}
		//  else {
		// 	$this->dataArray = $data[0];
		// 	$this->getResponse(200);
		// }
		$this->getResponse(503);
	}

	public function socialAPI()
	{
		$this->getResponse(200);
	}

	// 

	function getTopHit($userID)
	{
		$result = $this->queryResponse("select MAX(score) AS max_score from rank where userid = $userID");
		return $result[0]['max_score'];
	}

	function getRank($score)
	{
		$result = $this->queryResponse("select count(*) + 1 as calculated_rank from rank where score >= $score");
		return $result[0]['calculated_rank'];
	}

}
?>