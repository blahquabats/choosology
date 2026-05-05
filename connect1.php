<?php
$db = mysqli_connect("mysql.cyocyoa.com", "luser", "l_jU65TaA3", "cyocyoa") or die("Could not connect.");
if (!$db)
	die("no db");

foreach ($_POST as & $post)
{
	$post = htmlspecialchars(mysqli_real_escape_string($db, $post));
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
if (!isset($_SESSION['user']))
{
	session_start();
}
?>