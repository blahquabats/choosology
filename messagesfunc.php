<?php

function sendMessage($touser, $fromuser, $messagetitle, $messagebody, $type="normal")
{
    global $db;
    $q = "select name from users where id='$touser' or name='$touser'";
    $result = mysqli_query($db, $q);
    if($result->num_rows != 1)
    {
        echo "<div class='error'>Couldn't figure out which user to send to!</div>";
        return false;
    }
    else
    {
        $res = mysqli_fetch_array($result);
        $touser = $res[0];
        $q = "insert into messages (to_user, from_user, sent_date, body, message_type, title) values ('$touser', '$fromuser', NOW(), '$messagebody', '$type', '$messagetitle')";
        if(mysqli_query($db, $q)) {
            echo "<div class='success'>Message sent!</div>";
            return true;
        }
        else echo "<div class='error'>Message sending failed!</div>";
        return false;
    }
}

function checkSendMessage($user, $board, $screen = 0)
{
    global $db;
    if(substr($board,0,4) == "user")
    {
        $userid = substr($board, 4);
        $q = "select name from users where id='$userid'";
        $result = mysqli_query($db, $q);
        if($result->num_rows !==1) return false;
        $res = mysqli_fetch_array($result);
        if($res[0] == $user) return false;
        $messagetitle = "$user has commented on your profile!";
        $messagebody = "See the new comment <a href=\"profile.php?user={$res[0]}\" target=\"_blank\">here</a>.";
        sendMessage($res[0], "The Grasssmith",$messagetitle, $messagebody, "system");
    }
    if(substr($board,0,3) == "adv")
    {
        if ($screen) $screen = "&screen=$screen";
        else $screen = "";
        $advid = substr($board, 3);
        $q = "select user from advs where id='$advid'";
        $result = mysqli_query($db, $q);
        if($result->num_rows !==1) return false;
        $res = mysqli_fetch_array($result);
        if($res[0] == $user) return false;
        $messagetitle = "$user has commented on your adventure!";
        $messagebody = "See the new comment <a href=\"view.php?id=$advid$screen\" target=\"_blank\">here</a>.";
        sendMessage($res[0], "The Grasssmith",$messagetitle, $messagebody, "system");
    }
}

function convertBoardName($name)
{
    global $db;
    switch (substr($name, 0, 3))
    {
        case "use":
        $user = substr($name, 4);
        $q = "select name from users where id = '$user'";
        $after = "'s profile";
        $href = "profile.php?user=$user";
        break;
        case "new":
        $newsitem = substr($name, 4);
        $q = "select headline from news where id = '$newsitem'";
        $after = " (News)";
        $href = "index.php?newsitem=$newsitem";
        break;
        case "adv":
        $adv = substr($name, 3);
        $q = "select title from advs where id = '$adv'";
        $after = "";
        $href = "#/view/$adv";
        break;
    }
    $result = mysqli_query($db, $q);
    if (!$result) return false;
    $res = mysqli_fetch_array($result);
    $text = $res[0];
    if (!$text) return false;
    $text = strip_tags(html_entity_decode($text));
    if ($href) $text = "&lt;a class=\"link\" onclick=\"location.href='$href'\" &gt;$text&lt;/a&gt;";
    if ($after) $text = $text.$after;
    return $text;
}
?>