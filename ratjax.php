<?php

header('Content-Type: text/xml');
include ('connect.php');
if (!isset($_SESSION['user']))
{
	session_start();
}
if (isset($_SESSION['user']))
{

	$user = $_SESSION['user'];
}
else
{
	exit;
}

function assembleRating($which)
{
	global $db;
	$q = "select rating from ratings where adv=$which";
	$r = mysqli_query($db, $q);
	if ($r && $res = mysqli_fetch_array($r))
	{
		$count = 0;
		$sum = 0;
		do {
			$sum += $res['rating'];
			$count++;
		} while ($res = mysqli_fetch_array($r));
		$rat = round($sum / $count, 1);
		$perc = intval(($rat / 5) * 100);

		if ($count < 1)
			return false;
		return $rat;
	}
	else
		return false;
}


$id = intval($_GET['adv']);
$sid = intval($_GET['screen']);
$rating = intval($_GET['rating']);
echo "<response>";

$q = "select * from ratings where who=\"$user\" and adv=$id";
$r = mysqli_query($db, $q);
if ($r && mysqli_fetch_array($r))
{
	$ratquery = "update ratings set rating=$rating where adv=$id and `who`=\"$user\"";
}
else
{
    $q = "select user from advs where id=$id";
    $r = mysqli_query($db, $q);
    $res = mysqli_fetch_array($r);
    $owner = $res['user'];
	$ratquery = "insert into ratings (adv,who,rating, screen, owner) values ($id,\"$user\",$rating, $sid, \"$owner\")";
}
if (mysqli_query($db, $ratquery))
{
	echo "<success>1</success>";
	echo "<advid>$id</advid>";
	$avgrate = assembleRating($id);
	if (!$avgrate)
		$avgrate = 0;
	$ratquery = "update advs set rating=\"$avgrate\" where id='$id'";
  mysqli_query($db, $ratquery);
	$perc = intval(($avgrate / 5) * 100);
	echo "<rating>$avgrate</rating>";
	echo "<number>$perc</number>";
	echo "<myrating>$rating</myrating>";
}
else
	echo "<success>0</success>";


echo "</response>";

?>