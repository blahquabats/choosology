<?php
function icon($name, $size="medium", $color= "inherit")
{
 switch ($name)
 {
    case "search":
        $icon = "&#xe009;";
        break;
    case "beaker":
        $icon = "&#xe025;";
        break;
    case "mail":
        $icon = "&#xe019;";
        break;
    case "settings":
        $icon = "&#xe01f;";
        break;
    case "picture":
    case "pic":
        $icon = "&#xe01b;";
        break;
    case "trash":
        $icon = "&#xe006;";
        break;
    case "options":
        $icon = "&#xe00a;";
        break;
    case "star":
        $icon = "&#xe002;";
        break;
    case "pencil":
    case "edit":
        $icon = "&#xe00f;";
        break;
    case "person":
    case "say":
     $icon = "&#xe007;";
        break;
    case "world":
         $icon = "&#xe02f;";
        break;
    case "heart":
         $icon = "&#xe000;";
         break;
    case "lock":
         $icon = "&#xe00d;";
         break;
    case "video":
         $icon = "&#xe005;";
         break;
    case "news":
         $icon = "&#xe018;";
         break;
 }
    return "<span style=\"font-family:Linecons; font-size:$size; color: $color\">$icon</span>";
}
?>