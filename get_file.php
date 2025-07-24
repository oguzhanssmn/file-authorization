<?php
session_start();
include('your/path/db.php');


function q($query, $params = [])
{
    global $pdo;
    $stmt = $pdo->prepare($query);
    if ($stmt->execute($params)) {
        return $stmt;
    }
    return false;
}

function r($result)
{
    if ($result) {
        return $result->fetch(PDO::FETCH_OBJ);
    }
    return null;
}
//My fav functions to make things easier


$auth_id   = $_SESSION['auth_id'] ?? null; //Get auth id in some way
$file_name = $_GET['file'] ?? '';
$type       = $_GET['type'] ?? '';

if ( !$file_name || !$type) {
    http_response_code(400);
    exit("Missing param.");
} //if file name or type is missing its a missing param. They both required

$auth_check = r(q("SELECT c.user_one_id, c.user_two_id
    FROM chat_files cf
    LEFT JOIN chats c ON c.id = cf.chat_id
    WHERE cf.file = ?", [$file_name])); //My imaginary query...
    //You can change it depending to your project.
    //In my query im getting chat id with file name
    //After than im getting id values of users in that chat
    

if (!$auth_check || ($auth_check->user_one_id != $auth_id && $auth_check->user_two_id != $auth_id)) {
    http_response_code(403);
    exit("You do not have permission to view thid file");
} //In here we are checking if auth_id matches with id values from our query

//From type to folder
$folder = match ($type) {
    'image' => 'images',
    'video' => 'videos',
    'file' => 'files',
    default => null
};
//Left side is type that we define in .htaccess
//Right side is folder names

if (!$folder) {
    http_response_code(400);
    exit("Undefined file type");
}

$file_path = __DIR__ . "/uploads/chats/{$folder}/{$file_name}";

if (!file_exists($file_path)) {
    http_response_code(404);
    exit("File not found");
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file_path);
finfo_close($finfo);

header("Content-Type: $mime");
header("Content-Disposition: inline; filename=\"" . basename($file_path) . "\"");
header("Content-Length: " . filesize($file_path));
readfile($file_path);
exit;