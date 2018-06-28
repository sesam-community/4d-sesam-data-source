<?php

ini_set('memory_limit', '2048M');

$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');
$allowed_paths = json_decode(getenv("ALLOWED_TABLES"));



$parsed_uri = parse_url($_SERVER['REQUEST_URI']);
$path = ltrim($parsed_uri['path'], "/");


if (!in_array($path, $allowed_paths)) {
    error_log("Path $path is not allowed, you may want to add it to ALLOWED_PATH");
    http_response_code(404);
    die();
}
//since in ISO8601 format
$since = filter_input(INPUT_GET, 'since');
if (is_string($since)) {
    error_log("Get since $since");
}
$since_date_obj = DateTimeImmutable::createFromFormat(DateTime::ISO8601, $since . "+0000");

$dsn = "4D:host=$db_host;port=$db_port;charset=UTF-16";
$db = new PDO($dsn, $db_user, $db_password);

$since_query_str = "";

if ($since_date_obj) {
    error_log("Since presented " . $since_query_str . " and parsed successfully");
    $since_query_str = "WHERE Modified_On >= '" . $since_date_obj->format("Ymdhis") . "'";
}
$query = "SELECT * FROM $path $since_query_str";
error_log("Executing query $query");
$result = $db->query($query);
if ($result) {
    header('Content-Type: application/json');
} else {
    error_log("result = " . $result);
}


$rows = $result->fetchAll(PDO::FETCH_ASSOC);
unset($db);

if (!is_array($rows) || !count($rows)) {
    error_log("No data found");
    die();
}

$res_count = count($rows);
error_log("Got $res_count rows from database");

for ($i = 0; $i < count($rows); $i++) {
    $encoding_arr = [];
    foreach ($rows[$i] as $key => &$record_element) {
        //check if field has UTF-16 and convert if needed
        preg_match_all('/\x00/', $record_element, $count);
        if (strlen($record_element) && (count($count[0]) / strlen($record_element) > 0.4)) {
            $rows[$i][$key] = iconv('UTF-16LE', 'UTF-8//IGNORE', $record_element);
        }
        if ($key === 'Key') {
            $rows[$i]['_id'] = $rows[$i][$key];
        } else if (strpos($key, 'Email_Address') !== false && strpos($rows[$i][$key], " ")) {
            error_log("Found spaces in email address - strip");
            $rows[$i][$key] = str_replace(" ", "", $rows[$i][$key]);
        } else if ($key === 'Modified_On' && strlen($rows[$i][$key]) === 14) {//yyyymmddhhiiss
            error_log("Found last modified timestamp");
            $updated = DateTimeImmutable::createFromFormat("Ymdhis", $rows[$i][$key]);
            if ($updated) {
                $rows[$i]["_updated"] = "~t" . $updated->format(DateTime::ISO8601);
            }
        }
    }
}
$result_json_str = json_encode($rows, JSON_PARTIAL_OUTPUT_ON_ERROR);
echo $result_json_str;
if (json_last_error()) {
    error_log(json_last_error_msg());
}



