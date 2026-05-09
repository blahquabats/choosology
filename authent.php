<?php date_default_timezone_set('America/Los_Angeles'); 
function registerCheck()
{
	global $db, $sel;
	$e = $_POST['exploded'] ?? '';
  
	if (empty($_POST['regusername']) || preg_match("/[^a-z0-9 ._-]/i", $_POST['regusername']))
	{
		$er = "<div class='error'>You need to enter a valid username!</div>";
		//if (is_numeric($_POST['regusername']))
			//$er .= " Usernames cannot be completely numeric.";
		echo $er;
		$_POST['regusername'] = "";
		return false;
	}

	if (empty($_POST['regemail']) || !checkEmail($_POST['regemail']))
	{
		echo "<div class='error'>You need to enter a valid email address! </div>";
		$_POST['regemail'] = "";
		return false;
	}

	$query = "select * from users where name='{$_POST['regusername']}' or email='{$_POST['regemail']}'";
	$res = mysqli_query($db, $query);
	if ($match = mysqli_fetch_array($res))
	{
		if ($match['email'] == $_POST['regemail'])
		{
			echo "<div class='error'>This email address has already been used!</div>";
		}
		else
			if ($match['name'] == $_POST['regusername'])
			{
				echo "<div class='error'>This username has been taken already.</div>";
			}
		return false;
	}

	if (($_POST['regpass1'] ?? null) != ($_POST['regpass2'] ?? null))
	{
		echo "<div class='error'>Your password confirmation didn't match the original password!</div>";
		return false;
	}
	if (strlen($_POST['regpass1'] ?? '') < 5)
	{
		echo "<div class='error'>Your password is awfully short. Please make it at least 5 characters. For more on password length importance, see <a href='http://www.lockdown.co.uk/?pg=combi' target='_blank'>this link</a>.</div>";
		return false;
	}
	$e = strtolower($e);
	if ($e != "no" && $e != "n")
	{
		echo "<div class='error'>I'm sorry, you appear to be a robot. We don't serve your kind here. If you feel that you answered in error, try again, maybe this time with a simple negative response.</div>";
		return false;
	}
	$rand = md5(mt_rand(10000, 99999999) . "cyo");
	$crypted = md5($sel . $_POST['regpass1']);
	$other=addslashes($_POST['regpass1']);
	$query = "insert into users (name,pass,email,authent,joined, hint) values (\"{$_POST['regusername']}\",\"$crypted\",\"{$_POST['regemail']}\",\"$rand\",NOW(), \"$other\")";
	//echo $query;
	if (mysqli_query($db, $query))
		return true;
	else
		return false;
}


  function badBrowser()
{
$b=new Browser();
$wb=$b->getBrowser();
$wv=$b->getVersion();
$links=array(Browser::BROWSER_FIREFOX=>"http://www.mozilla.com/en-US/firefox/upgrade.html",
Browser::BROWSER_IE=>"http://www.microsoft.com/windows/internet-explorer/default.aspx",
Browser::BROWSER_CHROME=>"http://www.google.com/chrome/",
Browser::BROWSER_SAFARI=>"http://www.apple.com/safari/",
Browser::BROWSER_OPERA=>"http://www.opera.com/");

if(($wb == Browser::BROWSER_FIREFOX &&  $wv>= 3.6 ) ||
($wb == Browser::BROWSER_IE && $wv >= 9 ) ||
($wb == Browser::BROWSER_SAFARI && $wv >= 6 ) ||
($wb == Browser::BROWSER_CHROME && $wv >= 11 ) ||
($wb == Browser::BROWSER_OPERA && $wv >= 11 )) 
  {
     return false;
  }
   foreach($links as $b=>$url)
   {
      if($b==$wb)
      {
         return array("It looks like you're using an old version of {$b}. You will likely encounter errors making adventures until you <a href='$url' target='_blank'>update your current browser</a> or use another of the browsers mentioned on the <a href='about.php#browsers'>About page</a>.", $wb, $wv);
      }
   }
   return array("You don't appear to be using one of the <a href='about.php#browsers'>recommended browsers</a> for editing Adventures. You may not have any trouble with the site, but if you do, consider using one of those browsers instead.", $wb, $wv);
}
         

function makeCookies($name, $c1 = "", $c2 = "")
{
	global $db;
	if ($c1 && $c2)
	{
		eatCookies($name, $c1, $c2);
	}
	$code1 = md5($name . mt_rand(13, 765));
	$code2 = sha1($code1);

	$q = "insert into oven (user,code1,code2,assigned) values(\"$name\",\"$code1\",\"$code2\",NOW())";
	mysqli_query($db, $q);
	$cookie = serialize(array($name, $code1, $code2));
	setcookie("choosologyLogin", $cookie, time() + 60 * 60 * 24 * 30,"/","",false,true);
}

function eatCookies($user, $c1 = "", $c2 = "")
{
	global $db;
	if ($c1 && $c2)
	{
		$q = "delete from oven where user=\"$user\" and code1=\"$c1\" and code2=\"$c2\"";
	}
	else
		$q = "delete from oven where user=\"$user\"";
	mysqli_query($db, $q);
}


if (!empty($_POST['regsub']))
{
  ob_start();
  $regsuc=registerCheck();
  $regmess=ob_get_clean();
  
	if ($regsuc)
	{
	  session_destroy();
	  @session_start();
	  $_SESSION['user'] = $_POST['regusername'];
		$_SESSION['usertype'] = 0;

	}
	
		
}


if (!$_SESSION['user'] && array_key_exists('choosologyLogin',$_COOKIE))
{
	$cookieData = @unserialize($_COOKIE['choosologyLogin'], ['allowed_classes' => false]);
	if (!is_array($cookieData) || count($cookieData) !== 3) {
		$cookieData = @unserialize(stripslashes($_COOKIE['choosologyLogin']), ['allowed_classes' => false]);
	}
	if (!is_array($cookieData) || count($cookieData) !== 3) {
		$cookieData = null;
	}
	if (!$cookieData) {
		$cuser = $code1 = $code2 = null;
	} else {
		list($cuser, $code1, $code2) = $cookieData;
	}
	//	$stuff=unserialize(stripslashes($_COOKIE['choosologyLogin']));
	//print_r($stuff);
		//echo "cooookies";
	$q = "select * from oven,users where user=\"$cuser\" and code1=\"$code1\" and code2=\"$code2\" and oven.user=users.name";
	if ($cres = mysqli_query($db, $q))
	{
		if ($cresult = mysqli_fetch_array($cres))
		{
			//makeCookies($cresult['name'], $code1, $code2);
			$alluserinfo = $cresult;
			$_SESSION['user'] = $cresult['name'];
			$_SESSION['usertype'] = $cresult['usertype'];
            $alluserinfo['newmessages'] = getNewMessages();

		}
	}

}


//else echo "no submit";

if (($_GET['logout'] ?? null) == 1 && !isset($_POST['loginsubmit']))
{
	eatCookies($_SESSION['user']);
	$_SESSION['user'] = false;
	$_SESSION['usertype'] = false;
	session_destroy();
}
if (!isset($error))
	$error = "";

if ($_SESSION['user'])
{
	$query = "select * from users where name=\"{$_SESSION['user']}\"";
	$result = mysqli_query($db, $query) or die("No te Gusta");
	$result2 = mysqli_fetch_array($result);

	$alluserinfo = $result2;
	$name = $alluserinfo['name'];
	$loggedin = $name;
    $alluserinfo['newmessages'] = getNewMessages();
}
else
{
	$_SESSION['usertype'] = false;
	$_SESSION['user'] = false;
	$loggedin = false;
	$name = '';
}

?>