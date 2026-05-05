<?php
$db = mysqli_connect("mysql.cyocyoa.com", "luser", "l_jU65TaA3", "choosology") or die("Could not connect.");
mysqli_query($db, "SET time_zone='-7:05'");
if (!$db)
	die("no db");

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
    if ($_GET['project_lazarus'] != "go") 
    {
        //echo "Not yet! :)<META http-equiv='refresh' content='0;URL=http://www.cyocyoa.com'>";
        //die();
    }
}
?>