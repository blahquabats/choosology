<?php
require_once __DIR__ . '/db-config.php';

$s = choosology_db_settings('choosology');
$db = mysqli_connect($s['host'], $s['user'], $s['password'], $s['database']) or die("Could not connect.");
if (!$db)
	die("no db");

mysqli_set_charset($db, 'utf8mb4');
mysqli_query($db, "SET time_zone='-07:00'");

foreach ($_POST as & $post)
{
	if(!is_array($post)) $post = htmlspecialchars(mysqli_real_escape_string($db, $post));
}
foreach ($_COOKIE as & $cookie)
{
	//$cookie = htmlspecialchars(mysqli_real_escape_string($db, $cookie));
}
foreach ($_GET as & $get)
{
	$get = htmlspecialchars(mysqli_real_escape_string($db, $get));
}

$sel = "cYo";
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user'] == "")
{
    if (($_GET['project_lazarus'] ?? null) != "go") 
    {
        //echo "Not yet! :)<META http-equiv='refresh' content='0;URL=http://www.cyocyoa.com'>";
        //die();
    }
}
?>