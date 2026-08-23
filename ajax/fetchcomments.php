<?php
header('Content-Type: text/xml');
include ('../connect.php');
include ('../messagesfunc.php');

$user = '';
if (isset($_SESSION['user']))
{
	$user = $_SESSION['user'];
}

$error = '0';

  $board = isset($_GET['name']) ? mysqli_real_escape_string($db, $_GET['name']) : '';
  $screen = isset($_GET['screen']) ? mysqli_real_escape_string($db, $_GET['screen']) : '0';
  if(!$screen) $screen=0;
  if(isset($_GET['delete']) && $_GET['delete']==1) $delete = mysqli_real_escape_string($db, $_GET['cid']);
  else $delete = 0;
  $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
  if($page < 1) $page=1;
  if(!$board) exit;
  if(substr($board, 0, 6) == "byuser") $pagesize = 5;
  else $pagesize = 20;
  echo "<response>";
  $comments="";
   // first any inserting
   if(!empty($_POST['text']) && $user)
   {
       if($_POST['text'] == "Enter your comment here...") 
       {
           echo "<insertion>-1</insertion>
           ";
           $error = "Nothing entered!";
       }
       else 
       {
          $text=mysqli_real_escape_string($db, $_POST['text']);
          // Use ABS(TIMESTAMPDIFF) so timezone skew (UTC storage vs session -07:00) cannot
          // make older rows look "in the future" and trip the 1-second flood guard forever.
          $iq="insert into comments (`author`, `text`, `date`, `whichboard`, `whichscreen`) select '$user',\"$text\",NOW(),\"$board\", \"$screen\" from dual where not exists (select * from comments where author='$user' and whichboard='$board' and (`text`=\"$text\" or ABS(TIMESTAMPDIFF(SECOND, `date`, NOW())) < 1))";
          if(@mysqli_query($db, $iq))
          {
            $rows=mysqli_affected_rows($db);
            if($rows==1)
            {
                echo "<insertion>1</insertion>";
                // now alert a user if applicable
                checkSendMessage($user, $board, $screen);
            }
            else 
              {
              echo "<insertion>-1</insertion>
              ";
              $error="Comment blocked: Too quick!";
              }
          }
          else 
          {
             echo "<insertion>-1</insertion>";
             $error="Something went awry saving your comment!";
          }
       }
      //echo "<iq>$iq</iq>";
   
   }
   else echo "<insertion>0</insertion>";

   if ($delete)
   {
       $dq = "delete from comments where id = '$delete' and (author = '$user' OR '$user' = 'The Grasssmith') limit 1";
       if(@mysqli_query($db, $dq))
       {
           $rows=mysqli_affected_rows($db);
           if($rows==1)echo "<deletion>1</deletion>";
           else
           {
               echo "<deletion>-1</deletion>
          ";
               $error="Post not found!";
           }
       }
       else
       {
           echo "<deletion>-1</deletion>";
           $error="Something went awry deleting your comment!";
       }

   }
   else echo "<deletion>0</deletion>";
   
  
  if($board=="review" && $_GET['approvealldem']==1)
   {
       $aq="update comments set reviewed=1 where reviewed=0";
       mysqli_query($db, $aq);
   }
  
  $num=0;
  $begin=(($page-1)*$pagesize);
  if(substr($board, 0, 6) == "byuser")
  {
      $q="select name from users where id = '".substr($board,6)."'";
      $res = mysqli_query($db, $q);
      $result = mysqli_fetch_array($res);
      $username = $result['name'];
      $wheres = "c.author='$username' and (LEFT(c.whichboard, 3)!='adv' OR (SELECT avail from advs where advs.id = SUBSTRING(c.whichboard,4))='public')";

  }
  else $wheres="c.whichboard='$board'";
  if ($screen > 0 && substr($board, 0, 3) === 'adv') {
      $wheres .= " AND (c.whichscreen = '$screen' OR c.whichscreen = 0)";
  }
  if($board=="review") $wheres="c.reviewed=0";
  $q = "select SQL_CALC_FOUND_ROWS u.name as id, u.name as name, u.pic as pic, c.id as commentid, c.text, DATE_FORMAT(c.date,\"%b %e, %Y\") as timestamp, c.whichboard from comments as c
  left join users as u on u.name=c.author where $wheres order by c.date desc limit $begin,$pagesize";
echo "<page>$page</page>";
  
 //echo $q;
$bres = @mysqli_query($db, $q);


  $cq="select FOUND_ROWS() as total";
  $cres= mysqli_query($db, $cq);
echo "<id>$board</id>";

if ($bresult = @mysqli_fetch_array($bres))
{



  $first =1;
  $count=$begin+1;
    $rememberstars= array();
	do {
        $commentauthor = $bresult['name'];
        if(!isset($rememberstars[$commentauthor]))
        {
            $sq = "select * from ratings where who = '$commentauthor' or owner = '$commentauthor'";
            $sr = mysqli_query($db, $sq);
            $stars = 0;
            while($sres = mysqli_fetch_array($sr))
            {
                if($sres['who'] == $commentauthor)
                {
                 $stars++;
                }
                else if ($sres['owner'] == $commentauthor)
                {
                 $stars += .1*$sres['rating'];
                }
            }
            $stars = round($stars);
            $rememberstars[$commentauthor] = $stars;
        }
        else $stars = $rememberstars[$commentauthor];
        $comments = "";
        echo "<comments>";
        if($first)
        {
            if($board=="review" && $_SESSION['usertype']==1)
            {
                echo "&lt;a onclick='approveAll();return false;'&gt;Approve all&lt;/a&gt;";
            }
            $first=0;
        }
        $nameEsc = htmlspecialchars((string)$bresult['name'], ENT_QUOTES, 'UTF-8');
        $idEsc = htmlspecialchars((string)$bresult['id'], ENT_QUOTES, 'UTF-8');
        $dateEsc = htmlspecialchars((string)$bresult['timestamp'], ENT_QUOTES, 'UTF-8');
        $picId = isset($bresult['pic']) ? (int)$bresult['pic'] : 0;
        if ($picId > 0) {
            $avatar = "&lt;img class='CAavatar' src='ajax/pic.php?id={$picId}&amp;thumb=1' alt='' width='24' height='24' /&gt;";
        } else {
            $initial = $nameEsc !== '' ? strtoupper(substr($nameEsc, 0, 1)) : '?';
            $avatar = "&lt;span class='CAavatar CAavatar--fallback' aria-hidden='true'&gt;{$initial}&lt;/span&gt;";
        }
        $extrainfo = "{$avatar}&lt;a class='CAusername' href='profile.php?user={$idEsc}'&gt;{$nameEsc}&lt;/a&gt; (&lt;div class='mstar'&gt;&lt;/div&gt; $stars)";
        if(substr($board, 0, 6) == "byuser") $extrainfo = "";
	  $comments.="&lt;div class='CAbyline'&gt;
    &lt;span class='CAbyline-main'&gt;{$count}. {$extrainfo} &lt;span class='CAdate'&gt;{$dateEsc}&lt;/span&gt;";
    if($board=="review" or substr($board, 0, 6) == "byuser") $comments .= " on ".convertBoardName($bresult['whichboard']);
    $comments .= "&lt;/span&gt;";
    if($user == $bresult['name'] || $user == "The Grasssmith")
    {
        $comments .= "&lt;div class='CAbylineopts'&gt;";
        if(substr($board, 0, 6) !== "byuser") $comments .= "&lt;a onclick=\"deleteCAComment('{$bresult['commentid']}', '{$bresult['whichboard']}')\"&gt;delete&lt;/a&gt;";
        $comments .= "&lt;/div&gt;";
    }
    $comments.="
    
    &lt;/div&gt;";
    $body = htmlspecialchars(stripslashes((string)$bresult['text']), ENT_QUOTES, 'UTF-8');
    $body = str_replace(array('&lt;br /&gt;', '&lt;br/&gt;', '&lt;br&gt;'), '&lt;br /&gt;', $body);
	  $comments.="&lt;div class='CAcomment'&gt;
    ".$body."    
    &lt;/div&gt;";
		$num++;
		$count++;
        echo $comments."</comments>";
	} while ($bresult = mysqli_fetch_array($bres));

	$total = mysqli_fetch_array($cres);
  $total=$total['total'];
}
else echo "<comments>No comments yet</comments>";
  if(!$total) $total=0;     
  echo "<num>$total</num>";
  echo "<pagesize>$pagesize</pagesize>";
  if(!$error) $error="0";
  echo "<error>$error</error>";
  echo "</response>";

?>
