<?php
require_once("connect.php");
require_once("auxfuncs.php");

    $comments=new commentArea("adv1507", true, false);
    $comments->display();


?>