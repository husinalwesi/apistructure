<?php

function extractUrlParamsFull(){
		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$uri = explode('/', trim($uri, '/')); // remove leading/trailing slashes and split
    $index = array_search('api', $uri);
    return ["CONTROLLER_INDEX" => $index + 2, "METHOD_INDEX" => $index + 3];
	}  

// echo json_encode($_SERVER);
// exit(0);

// if (!($_SERVER['HTTP_SEC_FETCH_SITE'] === "same-origin" && $_SERVER['HTTP_SEC_FETCH_MODE'] === "cors")) {
//     http_response_code(403);
//     exit;
// }

// Handle preflight requests
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit(0);
// }


// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

// echo "You have CORS!";

/* Handle CORS */

// Specify domains from which requests are allowed
// header('Access-Control-Allow-Origin: *');

// // Specify which request methods are allowed
// header('Access-Control-Allow-Methods: PUT, GET, POST, PATCH, DELETE, OPTIONS');

// // Additional headers which may be sent along with the CORS request
// header('Access-Control-Allow-Headers: X-Requested-With,Authorization,Content-Type');

// // Set the age to 1 day to improve speed/caching.
// header('Access-Control-Max-Age: 86400');

// // Exit early so the page isn't fully loaded for options requests
// if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
//     exit();
// }

//  if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
//     $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//     header('HTTP/1.1 301 Moved Permanently');
//     header('Location: ' . $redirect);
//     exit();
// }
date_default_timezone_set('Asia/Amman');
include("config.php");
include("include/lib.php");
include("include/func.php");
include("include/mainController.php");
$main = new main();
// 
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);



		// $this->dataArray = $this->extractUrlParamsFull();
		// $this->getResponse(200, 'from start');


$controller = $uri[extractUrlParamsFull()['CONTROLLER_INDEX']];
// $method = $uri[extractUrlParamsFull()['METHOD_INDEX']];


// echo json_encode($method);
// exit();


if (
    (isset($controller) &&
        !in_array(
            $controller,
            array(
                // 'countrycode',
                'admins',
                // 'comments',
                // 'rank',
                // 'user'
                // 'content',
                // 'category',
                // 'media',
                // 'blog'
            )
        )
    )
) {
    // echo json_encode('1');
    // exit();

    header("HTTP/1.1 400 Not Found");
    header("Content-type: application/json; charset=utf-8");
    $responseArray['message'] = "Bad Request";
    // echo json_encode($responseArray);
    // exit();
}
// else{
//     echo json_encode('2');
//     exit();
// }

// 
$main->getControllerHandler($controller);
$main->newClassObject($controller);
?>