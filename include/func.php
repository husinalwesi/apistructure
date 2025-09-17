<?php
class main
{
  /**
   * class construct
   */
  public function __construct()
  {

  }
  /**
   * check header request parameters and return its secure value and strip
   * the tags
   */

  public function isHeaderParamExist($var)
  {
    $data = getallheaders();
    return isset($data[$var]);
  }

  public function prepareMultipleWhereForSameItem($array, $keyName)
  {
    $where = "";
    foreach ($array as $key => $value) {
      $orSign = $key ? "||" : "";
      $where .= "$orSign $keyName='$value' ";
    }
    return $where;
  }


  public function getURLParameterByOrder($order = 1)
  {
    
    // Get slash parameter in the URL.
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', $uri);
    return $uri[$this->extractUrlParamsFull()['METHOD_INDEX'] + $order];
  }

  public function getHeaderParams($var)
  {
    $data = getallheaders();
    return $data[$var];
  }
  /**
   * check post and get request parameters and return its secure value and strip
   * the tags
   */
  public function getSecureParams($var, $default = 0)
  {
    $data = 0;
    $flag_par = 0;

    if (isset($_POST[$var])) {
      $data = strip_tags($_POST[$var]);
      $flag_par = 1;
    } else if (isset($_GET[$var])) {
      $data = strip_tags($_GET[$var]);
      $flag_par = 1;
    }
    if ($flag_par) {
      $default = $data;
    }
    $default = str_replace("'", "", $default);
    $default = str_replace('"', "", $default);
    return $default;
  }

      public function getSecureParamsPut()
  {
    $putData = file_get_contents("php://input");
		$parsed = [];
		parse_str($putData, $parsed);

		foreach ($parsed as $key => $value) {
			$parsed[$key] = $this->getSecureData($value);
		}

    return $parsed;
  }

    public function getSecureParamsBody($var, $default = 0)
  {
    if (isset($_POST[$var])) return strip_tags($_POST[$var]);
    return $default;
  }

    public function getSecureData($var, $default = '')
  {
    return !empty($var) ? strip_tags($var) : $default;
  }

  public function getPayload()
  {
    // Converts it into a PHP object
    $json_data = file_get_contents('php://input');
    // Decode JSON data into PHP array
    $response_data = json_decode($json_data);
    return $response_data;
  }

  public function setAllowedMethod($method)
  {
    $requestMethod = $_SERVER["REQUEST_METHOD"];
    if (strtoupper($requestMethod) != $method)
      $this->getResponse(422, "Method not supported");
  }

  public function isAllowedMethod($method)
  {
    $requestMethod = $_SERVER["REQUEST_METHOD"];
    return strtoupper($requestMethod) === strtoupper($method);
  }


  public function notAllowedUrlParams()
  {
    $this->getResponse(400, "Invalid request URL.");
  }  

  public function getQuerySelector($array)
  {
    // return implode(",", $array);
    return "*";
  }

  public function toBoolean($str)
  {
    $str = +$str;
    return $str ? true : false;
  }

  public function getIP()
  {
    $key = "85c3aef9a2d34fc3b3117b78ad59e237";
    $url = "https://api.bigdatacloud.net/data/ip-geolocation?key=$key";
    $data = file_get_contents($url);
    if ($data) {
      $data = json_decode($data);
      return $data->ip;
    }
    return "0";
  }

  public function setIPLog($user_id)
  {
    $params = array(
      'id' => '',
      'user_id' => $user_id,
      'ip' => $this->getIP(),
      "created_date" => time()
    );
    // 
    $this->queryInsert('users_log', $params);
    //
  }


  /**
   * check post and get request parameters and return its secure value
   */
  public function getParams($var, $default = 0)
  {
    if (isset($_POST[$var])) {
      $default = $_POST[$var];
    } else if (isset($_GET[$var])) {
      $default = $_POST[$var];
    }
    return $default;
  }
  public function getMutliParams($stringFlag)
  {
    $data = array();
    foreach ($_POST as $key => $value) {

      if (strpos($key, $stringFlag) !== false) {
        if (!$value)
          $value = 0;
        $data[$key] = $value;
      }
    }
    return $data;
  }
  /**
   * check controller name and include it
   */
  public function getControllerHandler($controller_name)
  {
    $controller_name = "ctrl_" . $controller_name;
    $dirname = dirname(dirname(__FILE__));
    $file_name = $dirname . "/controllers/$controller_name.php";
    if (!file_exists($file_name)) {
      $controller_name = "controllers/ctrl_error.php";
    } else {
      $controller_name = "controllers/$controller_name.php";
    }
    include($controller_name);
  }
  /**
   * create object from requested controller
   */
  public function newClassObject($controller_name)
  {
    $newObject = null;
    if (class_exists($controller_name)) {
      $newObject = new $controller_name();
    }
    return $newObject;
  }

  public function handleIsDeleted()
  {
    $is_deleted = $this->getSecureParams("is_deleted");
    if (!$is_deleted)
      $is_deleted = "0";
    return "is_deleted='$is_deleted'";
  }

  public function handleQuery()
  {
    $query = $this->getSecureParams("query");
    if (!$query)
      $query = "*";
    return $query;
  }

  public function getTotal($table_name, $param)
  {
    $total = "0";
    $handleIsDeleted = $this->handleIsDeleted();
    $total_obj = $this->queryResponse("select count($param) as '$param' from $table_name where $handleIsDeleted");
    if ($total_obj)
      $total = $total_obj[0][$param];
    // 
    return array(
      "total" => +$total,
      "pages" => +$this->calculatePages($total),
      "limit" => $this->getSecureParams("limit") ? +$this->getSecureParams("limit") : 10
    );
  }

  public function getTotalWhere($table_name, $param, $where)
  {
    $total = "0";
    // $this->getResponse(200,"select count($param) as '$param' from $table_name $where");
    $total_obj = $this->queryResponse("select count($param) as '$param' from $table_name $where");
    if ($total_obj)
      $total = $total_obj[0][$param];
    // 
    return array(
      $this->encrypt("total") => $this->encrypt(+$total),
      $this->encrypt("pages") => $this->encrypt(+$this->calculatePages($total)),
      $this->encrypt("limit") => $this->encrypt($this->getSecureParams("limit") ? +$this->getSecureParams("limit") : 10)
    );
  }

  public function encrypt($data){
    // return base64_encode(urldecode($data));
    // return base64_encode($data);
    return $data;
  }

  public function decrypt($data){
    // return base64_decode($data);
    // return base64_decode(urldecode($data));
    return $data;
  }

  public function calculatePages($total)
  {
    $limit = $this->getSecureParams("limit");
    if (!$limit)
      $limit = "10";
    // 
    $pages = ceil($total / $limit);
    if (!$pages)
      $pages = "0";
    return $pages;
  }

  public function handlePagination()
  {
    $limit = $this->getSecureParams("limit");
    $page = $this->getSecureParams("page");
    if (!$limit)
      $limit = "100";
    if (!$page)
      $page = "1";
    // 
    $offset = ($limit * $page) - $limit;
    // 
    return "limit $limit OFFSET $offset";
  }

  public function timeStampToDate($val, $type)
  {
    $result = "";
    if ($type == "dateTime") {
      $result = date('d.m.Y g:i a', $val);
    }
    // 
    return $result;
  }

  public function deleteDbRow($table, $colomn_id_name, $id)
  {
    if (!$table || !$colomn_id_name || !$id)
      $this->getResponse(503, "parameter needed");
    $sql = "select $colomn_id_name from $table where $colomn_id_name='$id'";
    $data = $this->queryResponse($sql);
    if (!$data)
      $this->getResponse(503, "row not found");
    $params_to_edit = array('is_deleted' => '1');
    if (!$this->queryUpdate($table, $params_to_edit, "where $colomn_id_name='$id'"))
      $this->getResponse(503);
    return true;
  }

  public function restoreDbRow($table, $colomn_id_name, $id)
  {
    if (!$table || !$colomn_id_name || !$id)
      $this->getResponse(503, "parameter needed");
    $sql = "select $colomn_id_name from $table where $colomn_id_name='$id'";
    $data = $this->queryResponse($sql);
    if (!$data)
      $this->getResponse(503, "row not found");
    $params_to_edit = array('is_deleted' => '0');
    if (!$this->queryUpdate($table, $params_to_edit, "where $colomn_id_name='$id'"))
      $this->getResponse(503);
    return true;
  }

  public function changeStatus($table, $colomn_id_name, $id, $status)
  {
    if (!$table || !$colomn_id_name || !$id)
      $this->getResponse(503, "parameter needed");
    $sql = "select $colomn_id_name from $table where $colomn_id_name='$id'";
    $data = $this->queryResponse($sql);
    if (!$data)
      $this->getResponse(503, "row not found");
    $params_to_edit = array('status' => $status);
    if (!$this->queryUpdate($table, $params_to_edit, "where $colomn_id_name='$id'"))
      $this->getResponse(503);
    return true;
  }

  public function isStringContainsStr($str, $sub)
  {
    return strpos($str, $sub) !== false;
  }

  public function getBasicAuthorization($username, $password)
  {
    return base64_encode("$username:$password");
  }

}
?>