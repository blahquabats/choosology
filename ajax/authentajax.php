<?php
$_GET['project_lazarus'] = "go";
require_once("../connect.php");
require_once("../auxfuncs.php");

if (isset($_POST['loginsubmit']))
{
    $user = $_POST['logname'];
    //mysqli_real_escape_string($db, $_POST['login']);
    //$password = mysqli_real_escape_string($db, $_POST['logpass']);
    $password = $_POST['logpass'];
    $user = strip_tags($user);
    $encrypted_pswd = md5($sel . $password);

    $query = "select * from users where (name='$user' or email='$user') and pass='$encrypted_pswd'";
    $result = mysqli_query($db, $query);
    $result2 = $result ? mysqli_fetch_array($result) : false;

    if ($result2)
    {
        $uname = mysqli_real_escape_string($db, $result2['name']);
        $query = "update users set lastlogin=NOW() where name='$uname' and pass='$encrypted_pswd'";
        $result = @mysqli_query($db, $query);
        session_destroy();
        @session_start();
        if ($_POST['rememberlogin'])
        {
            eatCookies($result2['name']);
            makeCookies($result2['name']);
        }

        $alluserinfo = $result2;
        $_SESSION['user'] = $result2['name'];
        $_SESSION['usertype'] = $result2['usertype'];
        $alluserinfo['newmessages'] = getNewMessages();
        echo $result2['name'];
    }
    else
    {

        $error = "<br /><span style='color:red; font-weight:bold'>Wrong username or password.</style><br />";
        echo "2";
        $_SESSION['user'] = false;
        $_SESSION['usertype'] = false;

        //echo "{$_POST['login']}";
    }
    exit;
}


if (isset($_POST['logoutsubmit']))
{
	eatCookies($_SESSION['user']);
	$_SESSION['user'] = false;
	$_SESSION['usertype'] = false;
	session_destroy();
	echo "1";
}


?>