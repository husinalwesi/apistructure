<?php
class mainController extends main
{
  var $msg = "";
  var $dataArray = null;
  var $time_zone = 'Asia/Amman';

  public function verifyAndGetAuth($data)
  {
    // $data->isPasswordEnabled
    $PHP_AUTH_USER = trim($_SERVER["PHP_AUTH_USER"]);
    $PHP_AUTH_PW = trim($_SERVER["PHP_AUTH_PW"]);
    if (!$PHP_AUTH_USER || !$PHP_AUTH_PW)
      $this->getResponse(401);

    $basicAuthAsIs = $this->getBasicAuthorization($PHP_AUTH_USER, $PHP_AUTH_PW);
    $basicAuthWithMD5 = $this->getBasicAuthorization($PHP_AUTH_USER, md5($PHP_AUTH_PW));
    $querySelector = $data->isVerify ? array('id') : array('id', 'full_name', 'username', 'email', 'img', 'status', 'is_deleted', 'created_date', 'password');
    $querySelectorString = $this->getQuerySelector($querySelector);
    $adminTableName = "admin_users";
    $data = $this->queryResponse("select $querySelectorString from $adminTableName where (basicAuth='$basicAuthAsIs' or basicAuth='$basicAuthWithMD5') and is_deleted='0' and status='1'");
    // NOTE:: It should match the basic auth and the user should be active and not deleted.
    return $data;
  }

  public function checkAuth()
  {
    // $param = $this->getSecureParams('param');
    return true;
    // 1730746016
    // + ' | ' + time()
    // $this->getResponse(200, $param.' | '.time());
    // return true;
    // if ($param !== time())
    //   $this->getResponse(503, $param + ' | ' + time());
    // return true;
    // echo $timestamp; // Outputs the current Unix timestamp

    // $data = $this->verifyAndGetAuth(array('isVerify' => true, 'isPasswordEnabled' => false));
    // if (!$data)
    //   $this->getResponse(401);

    // return true;
  }
  /**
   * check authentication for API call "the value of the token id"
   */

  public function extractUrlParams()
  {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', trim($uri, '/')); // remove leading/trailing slashes and split

    // find position of "api"
    $pos = array_search('api', $uri);

    if ($pos !== false) {
      $params = array_slice($uri, $pos + 2);

      $secureParams = [];
      foreach ($params as $param) {
        // pass each param to getSecureData
        $secureParams[] = $this->getSecureData($param);
      }

      return $secureParams;

    }
    return []; // return empty if "api" not found
  }

  public function extractUrlParamsFull()
  {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', trim($uri, '/')); // remove leading/trailing slashes and split
    $index = array_search('api', $uri);
    return [
      "CONTROLLER_INDEX" => $index + 2
    ];
  }

  public function callMethod($object)
  {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', $uri);
    // 
    $action = 'main';
    if ($action)
      $action .= 'API';
    if (method_exists($object, "$action")) {
      $object->$action();
    } else {
      $this->getResponse(400);
    }
  }
  /**
   * print api response as json string
   */
  public function getResponse($status, $msg = '')
  {
    ob_end_clean();
    header("Content-type: application/json; charset=utf-8");
    header("HTTP/1.1 " . $status);

    $defaultError = "Error";
    $default200 = "OK";
    $default201 = "Already Exists";
    $default204 = "No Content";
    $default400 = "Bad Request";
    $default401 = "Unauthorized";
    $default406 = "Not Acceptable";
    $default503 = "Service Unavailable";
    $default422 = "Unprocessable Entity";

    $dataMsg[200] = $status == "200" ? $msg ? $msg : $default200 : $default200;
    $dataMsg[201] = $status == "201" ? $msg ? $msg : $default201 : $default201;
    $dataMsg[204] = $status == "204" ? $msg ? $msg : $default204 : $default204;
    $dataMsg[400] = $status == "400" ? $msg ? $msg : $default400 : $default400;
    $dataMsg[401] = $status == "401" ? $msg ? $msg : $default401 : $default401;
    $dataMsg[406] = $status == "406" ? $msg ? $msg : $default406 : $default406;
    $dataMsg[503] = $status == "503" ? $msg ? $msg : $default503 : $default503;
    $dataMsg[422] = $status == "422" ? $msg ? $msg : $default422 : $default422;

    $msgTranslated = !empty($msg) ? $msg : $dataMsg[$status];

    $responseArray['message'] = $this->translate($msgTranslated);
    $responseArray['dataObject'] = $this->dataArray;

    echo json_encode($responseArray);
    exit(0);
  }

  public function getCurrentLang()
  {
    $headers = getallheaders();
    $langFromRequest = isset($headers['lang']) ? $headers['lang'] : DEFAULT_LANG;
    if (!$langFromRequest || ($langFromRequest && ($langFromRequest !== 'en' && $langFromRequest !== 'ar'))) {
      $langFromRequest = DEFAULT_LANG;
    }
    return $langFromRequest;
  }

  public function getTranslationFiles()
  {
    $fileEn = "./lang/en.php";
    $fileAr = "./lang/ar.php";
    $translations = array(
      "en" => null,
      "ar" => null
    );
    //  
    if (file_exists($fileEn))
      $translations['en'] = include $fileEn;
    if (file_exists($fileAr))
      $translations['ar'] = include $fileAr;
    return $translations;
  }

  public function translate($str)
  {
    $lang = $this->getCurrentLang();

    $translations = $this->getTranslationFiles();
    return $translations[$lang][$str] ?? $str;
  }

  public function queryResponse($sql, $flag = 1)
  {
    $db = new db();
    $db->setQuery($sql);
    if ($db->getQuery() !== false) {
      if (strPos($sql, "select") === 0) {
        $rows = array();
        if ($db->getRowCount()) {
          $rows = $db->getRowDataArray();
        }
        return $rows;
      } else if (strPos($sql, "insert") === 0) {
        return $db->getLastInsertedId();
      } else if (strPos($sql, "BEGIN")) {
        $this->getResponse(200);
      } else {
        if ($flag)
          $this->getResponse(200);
        return 1;
      }

    }
    $this->getResponse(503);
  }

  public function deleteMedia($file)
  {
    $file = "." . $file;
    // Assuming $file is the path to the file on your server
    if (file_exists($file)) {
      if (unlink($file)) {
        return true;
        // // File deleted successfully
        // $this->getResponse(200, "File deleted successfully.");
      } else {
        // Could not delete the file (permissions issue, etc.)
        // $this->getResponse(500, "Failed to delete the file.");
        return false;
      }
    } else {
      // File does not exist
      // $this->getResponse(404, "File not found.");
      return true;
    }
  }

  public function uploadMedia($files)
  {
    $data = array();
    foreach ($files as $key => $file) {
      $fileName = $file['name'];
      $fileName = str_replace(' ', '-', $file['name']);

      $mg = uniqid() . "_" . $fileName;
      $tData = strtotime("today");
      $uploadDir = "./uploads/$tData/";
      $originalImage = $uploadDir . $mg;

      // Check if folder exists
      if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
          $this->getResponse(500, "Cannot create folder $uploadDir");
          return;
        }
      }

      // Check if folder is writable
      if (!is_writable($uploadDir)) {
        $this->getResponse(500, "Folder $uploadDir is not writable");
        return;
      }

      // Attempt to move uploaded file
      if (move_uploaded_file($file['tmp_name'], $originalImage)) {
        $data[$key] = '/uploads/' . $tData . '/' . $mg;
      } else {
        $this->getResponse(500, "Failed to move uploaded file to $originalImage");
      }
    }

    return $data;
  }

  public function queryInsert($tableName, $params, $flag = 0)
  {
    $db = new db();
    $columns = array();
    $values = array();
    foreach ($params as $key => $value) {
      $columns[] = $key;
      $values[] = $value;
    }
    $columns = implode(",", $columns);
    $values = implode("','", $values);
    $sql = "INSERT INTO $tableName ($columns) VALUES ('$values')";
    // $this->getResponse(200,$sql);
    $db->setQuery($sql);
    if ($db->getQuery() !== false) {
      return $db->getLastInsertedId();
    }
    return 0;
  }

  public function queryUpdate($tableName, $params, $where = '')
  {
    $db = new db();
    $values = array();
    foreach ($params as $key => $value) {
      $values[] = "$key = '$value'";
    }
    $values = implode(",", $values);
    $sql = "UPDATE $tableName SET $values $where";
    // $this->getResponse(200,$sql);
    $db->setQuery($sql);
    if ($db->getQuery() !== false) {
      return 1;
    }
    return 0;
  }


  public function sendMail($email, $content)
  {
    require_once 'include/PHPMailer-master/PHPMailerAutoload.php';
    $mail = new PHPMailer;
    //$mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = Email_HOST; // Specify main and backup SMTP servers
    $mail->SMTPAuth = false; // Enable SMTP authentication
    $mail->SMTPAutoTLS = false;
    $mail->Username = Email_SEND_FROM; // SMTP username
    $mail->Password = Email_PASSWORD; // SMTP password
    $mail->SMTPSecure = Email_SMTPSECURE; // Enable TLS encryption, `ssl` also accepted
    $mail->Port = Email_PORT; //587 //465                                   // TCP port to connect to
    //$mail->SMTPDebug = 2;
    $mail->setFrom(Email_SEND_FROM, Email_TITLE);
    $mail->addAddress($email, Email_TITLE); // Add a recipient
    //$mail->addAddress('ellen@example.com');               // Name is optional
    //$mail->addReplyTo('info@example.com', 'Information');
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
    $mail->isHTML(true); // Set email format to HTML
    //http://bit.ly/2sJwLsF  this is a short link taken from this   52.34.144.49/easy-arab-dev/web/index.php?type=auth&action=verify
    $mail->Subject = Email_TITLE;
    $mail->Body = $content;

    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    if (!$mail->send()) {
      //$this->getResponse(200,"Error: ".$mail->ErrorInfo);
      return $mail->ErrorInfo;
    } else {
      $mail->ClearAllRecipients();
      // $this->getResponse(200,"Done");
      return 1;
    }
  }

  public function sendDesignedMail($email, $content)
  {
    $mail_structure = "<body style='font-family: Arial,Helvetica,sans-serif; background: #ebebeb; padding: 0;'>
     <table style='padding: 30px 15px;margin: 0 auto; border-radius:8px' cellpadding='0' cellspacing='0' width='640' align='center'>
       <tbody>
          <tr>
             <td valign='top' colspan='3' style='background: #fbfbfb;margin-top:20px;border-radius:5px'>
                <table cellpadding='0' cellspacing='0' width='640' align='center'>
                   <tbody>
                      <tr>
                         <td>
                            <div style='padding: 20px 20px 15px 15px;'>
                               <table>
                                  <tbody>
                                     <tr>
                                        <td style=''><a style='font-size: 18px; text-decoration: none; color: #323232; text-transform: uppercase; letter-spacing: 2px;' href='{siteUrl}' target='_blank'>" . Email_TITLE . "</a></td>
                                     </tr>
                                  </tbody>
                               </table>
                            </div>
                         </td>
                      </tr>
                      <tr>
                         <td valign='top' colspan='3'>
                            <div style='
                               background: rgb(30, 101, 221); /* Old browsers */
                               width: 675px; min-height: 6px;'></div>
                         </td>
                      </tr>
                        <td style='background: #ffffff; padding: 20px 15px; width:100%'>
                         $content
                        </td>
                   </tbody>
                 </table>
                 <table width='675' height='50' align='center' style='border-radius:0px 0px 5px 5px' bgcolor='#2E2E2E'>
                    <tbody>
                       <tr>
                          <td>
                             <a style='width:675px;display: block;text-align: center;color:#FFFFFF;text-decoration:none;font-size:12px;text-transform: uppercase; letter-spacing: 2px' href='javascript:void(0)' target='_blank'>" . Email_TITLE . "</a>
                          </td>
                       </tr>
                    </tbody>
                 </table>
               </td>
             </tr>
             <tr>
             <td style='color:rgb(169, 169, 169);font-size:12px; width:100%'>
                <br>
                <b>Disclaimer:</b> This e-mail and any attachments are confidential and may be protected by legal privilege. If you are not the intended recipient, be aware that any disclosure, copying, distribution or use of this e-mail or any attachment is prohibited. If you have received this e-mail in error, please notify us immediately by returning it to the sender and delete this copy from your system. Thank you for your cooperation.
             </td>
           </tr>
       </tbody>
     </table>
   </body>";
    $this->sendMail($email, $mail_structure);
  }

  function EXPORT_DATABASE($host, $user, $pass, $name, $tables = false, $backup_name = false)
  {
    set_time_limit(3000);
    $mysqli = new mysqli($host, $user, $pass, $name);
    $mysqli->select_db($name);
    $mysqli->query("SET NAMES 'utf8'");
    $queryTables = $mysqli->query('SHOW TABLES');
    while ($row = $queryTables->fetch_row()) {
      $target_tables[] = $row[0];
    }
    if ($tables !== false) {
      $target_tables = array_intersect($target_tables, $tables);
    }
    $content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `" . $name . "`\r\n--\r\n\r\n\r\n";
    foreach ($target_tables as $table) {
      if (empty($table)) {
        continue;
      }
      $result = $mysqli->query('SELECT * FROM `' . $table . '`');
      $fields_amount = $result->field_count;
      $rows_num = $mysqli->affected_rows;
      $res = $mysqli->query('SHOW CREATE TABLE ' . $table);
      $TableMLine = $res->fetch_row();
      $content .= "\n\n" . $TableMLine[1] . ";\n\n";
      $TableMLine[1] = str_ireplace('CREATE TABLE `', 'CREATE TABLE IF NOT EXISTS `', $TableMLine[1]);
      for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
        while ($row = $result->fetch_row()) { //when started (and every after 100 command cycle):
          if ($st_counter % 100 == 0 || $st_counter == 0) {
            $content .= "\nINSERT INTO " . $table . " VALUES";
          }
          $content .= "\n(";
          for ($j = 0; $j < $fields_amount; $j++) {
            $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
            if (isset($row[$j])) {
              $content .= '"' . $row[$j] . '"';
            } else {
              $content .= '""';
            }
            if ($j < ($fields_amount - 1)) {
              $content .= ',';
            }
          }
          $content .= ")";
          if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
            $content .= ";";
          } else {
            $content .= ",";
          }
          $st_counter = $st_counter + 1;
        }
      }
      $content .= "\n\n\n";
    }
    $content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
    $backup_name = $backup_name ? $backup_name : $name . '___(' . date('H-i-s') . '_' . date('d-m-Y') . ').sql';
    ob_get_clean();
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header('Content-Length: ' . (function_exists('mb_strlen') ? mb_strlen($content, '8bit') : strlen($content)));
    header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");
    echo $content;
    exit;
  }

}
?>