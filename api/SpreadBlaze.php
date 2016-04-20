<?php
/* Put in your mysql connection information here*/
define("MYSQL_HOST", 'localhost');
define("MYSQL_USER", 'root');
define("MYSQL_PASS", '');
define("MYSQL_DBNAME", 'webdevpres');

/* Check if the parameter was given. If not, send back an error. All of these headers are used to make sure the packet
is recieved as expected, but could technically work without them. */
if (!isset($_GET['uid'])) {
    header('Content-Type: text/plain; charset=utf-8');
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('error' => 'The uid parameter was not specified.'));
    exit();
}

/* Check if the parameter recieved was in the format of a unique id. If it's not, send back an error. */
if (!(preg_match("/^[a-f\d]{8}-[a-f\d]{4}-[a-f\d]{4}-[a-f\d]{4}-[a-f\d]{12}$/i", $_GET['uid']) == 1)) {
    header('Content-Type: text/plain; charset=utf-8');
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('error' => 'The uid given was not valid.'));
    exit();
}

/* By this point, we know that the uid GET parameter both exists and is safe. So let's assign it a variable. */
$senderUid = $_GET['uid'];

/* Try to connect to the database. */
$databaseConnection = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DBNAME);
if ($databaseConnection->connect_errno != 0) {
    header('Content-Type: text/plain; charset=utf-8');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => $databaseConnection->connect_error));
    exit();
}

/* Query the database for the sender name. */
$getSenderNameResults = $databaseConnection->query('SELECT name FROM users WHERE uid=\'' . $senderUid . '\'');
if ($databaseConnection->errno != 0) {
    header('Content-Type: text/plain; charset=utf-8');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => $databaseConnection->error));
    exit();
}
$senderName = $getSenderNameResults->fetch_assoc()['name'];

/* Generate a UUID to use for this visitor. */
$getUuidResults = $databaseConnection->query('SELECT UUID() as uuid;');
if ($databaseConnection->errno != 0) {
    header('Content-Type: text/plain; charset=utf-8');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => $databaseConnection->error));
    exit();
}
$visitorUuid = $getUuidResults->fetch_assoc()['uuid'];

/* Create a new user for this visitor. */
$databaseConnection->query('INSERT INTO users (uid, parent_uid) VALUES (\'' . $visitorUuid . '\', \'' . $senderUid . '\')');
if ($databaseConnection->errno != 0) {
    header('Content-Type: text/plain; charset=utf-8');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => $databaseConnection->error));
    exit();
}

/* Recursive function to calculate the sender score. */
function CalculateSenderScore($dbConn, $uid, $level) {
    if ($level == 0) {
        return 0;
    }
    $allChildrenResults = $dbConn->query('SELECT uid FROM users WHERE parent_uid=\'' . $uid . '\'');
    if ($dbConn->errno != 0) {
        header('Content-Type: text/plain; charset=utf-8');
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(array('error' => $dbConn->error));
        exit();
    }
    $allChildren = $allChildrenResults->fetch_all();
    $senderSum = 0;
    foreach ($allChildren as $childUid) {
        $senderSum += $level + CalculateSenderScore($dbConn, $childUid[0], $level - 1);
    }
    return $senderSum;

}

/* Calculate the score of the sender. */
$senderScore = CalculateSenderScore($databaseConnection, $senderUid, 3);

header('Content-Type: text/plain; charset=utf-8');
header('HTTP/1.1 200 OK');
echo json_encode(array('name' => $senderName, 'score' => $senderScore, 'visitorUid' => $visitorUuid));
exit();
?>
