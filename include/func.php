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

  public function getAdminByIDFn($userID, $where = '')
  {
    $querySelector = array('id', 'username', 'password', 'createdDate', 'isDeleted');
    $querySelectorString = $this->getQuerySelector($querySelector);
    $result = $this->queryResponse("select $querySelectorString from admin where CAST(id AS CHAR)='$userID' $where");
    if (!$result || count($result) === 0)
      return null;
    $data = array();
    $data = $this->modelAdminData($result[0]);
    if (!$data || count($data) === 0)
      return null;
    return $data;
  }

  public function modelAdminData($temp)
  {
    unset($temp['password']);
    $temp['createdDate'] = $this->timeStampToDate($temp['createdDate']);
    $temp['isDeleted'] = +$temp['isDeleted'] === 1 ? true : false;
    return $temp;
  }

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

  public function getRequestData()
  {
    $method = $_SERVER['REQUEST_METHOD'];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    $result = [
      "fields" => [],
      "files" => []
    ];

    // Raw body for PUT/POST/others
    $raw = file_get_contents("php://input");

    // Handle multipart/form-data (files + fields)
    if (stripos($contentType, 'multipart/form-data') === 0) {
      if ($method === 'POST') {
        $result["fields"] = $_POST;
        $result["files"] = $_FILES;
      } else {
        $raw = file_get_contents("php://input");
        $boundary = substr($raw, 0, strpos($raw, "\r\n"));
        $parts = array_slice(explode($boundary, $raw), 1);

        $fields = [];
        $files = [];

        foreach ($parts as $part) {
          $part = trim($part);
          if ($part === "--")
            continue; // End marker

          // Split headers & body
          list($rawHeaders, $body) = explode("\r\n\r\n", $part, 2);
          $body = rtrim($body, "\r\n");

          // Parse headers
          $headers = [];
          foreach (explode("\r\n", $rawHeaders) as $header) {
            if (strpos($header, ':') !== false) {
              list($hName, $hValue) = explode(':', $header, 2);
              $headers[strtolower(trim($hName))] = trim($hValue);
            }
          }

          // Find the Content-Disposition header
          if (!isset($headers['content-disposition'])) {
            continue;
          }

          if (preg_match('/name="([^"]+)"/', $headers['content-disposition'], $matchName)) {
            $name = $matchName[1];
          } else {
            continue;
          }

          // Check if it's a file (has filename=)
          if (preg_match('/filename="([^"]*)"/', $headers['content-disposition'], $matchFile)) {
            $filename = $matchFile[1];
            if ($filename !== '') {
              $tmpPath = tempnam(sys_get_temp_dir(), 'php_put_');
              file_put_contents($tmpPath, $body);

              $files[$name] = [
                'name' => $filename,
                'type' => $headers['content-type'] ?? 'application/octet-stream',
                'tmp_name' => $tmpPath,
                'error' => 0,
                'size' => strlen($body)
              ];
            }
          } else {
            // Normal field
            $fields[$name] = $body;
          }
        }
        $result['fields'] = $fields;
        $result['files'] = $files;
      }
    } else if (stripos($contentType, 'application/json') === 0) {
      $decoded = json_decode($raw, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        $result["fields"] = $decoded;
      }
    } elseif (stripos($contentType, 'application/x-www-form-urlencoded') === 0) {
      parse_str($raw, $parsed);
      $result["fields"] = $parsed;
    } elseif (stripos($contentType, 'text/plain') === 0) {
      // Plain text -> wrap into a single field
      // parse_str($raw, $parsed);
      // $decoded = json_decode($raw, true);       
      $result["fields"] = json_decode($raw, true);
    } else {
      // Fallback: try query-style parsing
      parse_str($raw, $parsed);
      if (!empty($parsed)) {
        $result["fields"] = $parsed;
      } else {
        $result["fields"] = ["raw" => $raw];
      }
    }


    foreach ($result['fields'] as $key => $value) {
      $result['fields'][$key] = $this->getSecureData($value);
    }

    return $result;

  }

  public function checkRequiredFields(array $requiredFields)
  {
    $payload = $this->getRequestData();
    $missingFields = [];

    // Loop through required fields and check if any are missing or empty
    foreach ($requiredFields as $field) {
      if (empty($payload['fields'][$field] ?? null)) {
        $missingFields[] = $field;
      }
    }

    // If there are missing fields, return a formatted response and stop execution
    if (!empty($missingFields)) {
      $missingList = '`' . implode('`, `', $missingFields) . '`';
      $this->getResponse(501, "Missing required field(s): {$missingList}");
    }
    return true;
  }

  public function checkRequiredFiles(array $requiredFields)
  {
    $payload = $this->getRequestData();
    $missingFields = [];

    // Loop through required fields and check if any are missing or empty
    foreach ($requiredFields as $field) {
      if (empty($payload['files'][$field] ?? null)) {
        $missingFields[] = $field;
      }
    }

    // If there are missing fields, return a formatted response and stop execution
    if (!empty($missingFields)) {
      $missingList = '`' . implode('`, `', $missingFields) . '`';
      $this->getResponse(501, "Missing required field(s): {$missingList}");
    }
    return true;
  }  


  // public function checkRequiredFields()
  // {
  //   $payload = $this->getRequestData();

  //   // Safely extract fields from payload
  //   $username = $payload['fields']['username'] ?? null;
  //   $password = $payload['fields']['password'] ?? null;

  //   // Track missing fields
  //   $missingFields = [];

  //   if (empty($username)) {
  //     $missingFields[] = 'username';
  //   }

  //   if (empty($password)) {
  //     $missingFields[] = 'password';
  //   }

  //   // Respond with clear error message if any field is missing
  //   if (!empty($missingFields)) {
  //     $missingList = '`' . implode('`, `', $missingFields) . '`';
  //     $this->getResponse(501, "Missing required field(s): {$missingList}");
  //   }
  // }

  public function getSecureData($var, $default = '')
  {
    return !empty($var) ? strip_tags($var) : $default;
  }

  public function isAllowedMethod($method)
  {
    $requestMethod = $_SERVER["REQUEST_METHOD"];
    return strtoupper($requestMethod) === strtoupper($method);
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
    return "isdeleted='$is_deleted'";
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
      "total" => +$total,
      "pages" => +$this->calculatePages($total),
      "limit" => $this->getSecureParams("limit") ? +$this->getSecureParams("limit") : 10
    );
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

  public function timeStampToDate($val, $type = 'dateTime')
  {
    $result = "";
    if ($type == "dateTime") {
      $result = date('d.m.Y g:i a', $val);
    }
    // 
    return $result;
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