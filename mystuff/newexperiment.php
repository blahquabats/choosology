<?php

require_once("../connect.php");
require_once("../auxfuncs.php");

// get all relevant images to populate chooser
$pics = getUserPics($name);
$mydir = playerDir($name);
$unidir = playerDir("&everyone");
$picsholder = "<div class='imagepicker'>
            <input type='hidden' name='newform_image' value='' />
            <div class='imagepicker_selected'>
            </div>
            <div class='imagepicker_menu'>
";
foreach ($pics as $pic)
{
    if ($pic['user'] == "&everyone") $dir = $unidir."/thumbs/".$pic['filename'];
    else $dir = $mydir."/thumbs/".$pic['filename'];
    $picsholder .= "<div class='imagepicker_result' data-attribute='".$pic['id']."'>
    <img src='$dir'>
    <div class='imagepicker_caption'>
    ".$pics['imagename']."
    </div>
    </div>";
}
$picsholder .= "</div>
</div>";

?>
<div class='intabs'>
<h2>Create New Experiment</h2>
<br />
<br />
    <div class='ms_ne_newform'>
        <div class='ms_ne_header'>
            FORM NE-1
        </div>
        <div class='ms_ne_instructions'>
            Please fill out the form completely and submit to the relevant lab supervisors.
        </div>
        <div class="ms_ne_formline">
         <div class='ms_ne_prompt'>
          Experiment Name:  
        </div>    
        <div class='ms_ne_input' id ="newform_name"> 
          <?php
            echo $_GET['newname'];
          ?>  
        </div>    
        </div>
        
        <div class="ms_ne_formline">
         <div class='ms_ne_prompt'>
          Experiment Image:  
        </div>    
        <div class='ms_ne_notinput'>
            <?php echo $picsholder; ?>
        </div>
        </div>
        
        <div class="ms_ne_formline">
         <div class='ms_ne_prompt'>
          Experiment Description:  
        </div>    
        <div class='ms_ne_input textarea' id ="newform_description">
            
        </div>    
        <div class='ms_ne_inputhelp'>100/250 characters</div>
        </div>
        
        <div class="ms_ne_formline">
         <div class='ms_ne_prompt'>
          Experiment Background:  
        </div>    
        <div class='ms_ne_notinput'>
            <input type='radio' name='newform_bgswitch' checked> Color: <input class='ms_ne_colors' id='newform_bgcolor' value='#ffffff' /> <br/>
            <input type='radio' name='newform_bgswitch'> Image: <select disabled><option>Choose...</option></select>
        </div>    
        </div>
        
        <div class="ms_ne_formline">
         <div class='ms_ne_prompt'>
          Experiment Foreground:  
        </div>    
            <div class='ms_ne_notinput'>
            Main Color: <input class='ms_ne_colors' id='newform_bgcolor' value='#ccddff' /><br />
            Border Color: <input class='ms_ne_colors' id='newform_bordercolor' value='#8899aa' /><br />
            Border Width: <select id='newform_borderwidth' value='#ccddff' >
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
                <option>5</option>
            </select><br />
            
        </div>       
        </div>
        
        <div class='ms_ne_preview_container'>
        <div class='ms_ne_preview_side'>    
            
            </div>
            <div class='ms_ne_preview_container'>
        </div>

        <button class='ms_ne_button'>Submit to Relevant Lab Supervisors</button>
    </div>

</div>

<script>
    $(".ms_ne_input").attr("contentEditable", "true");
     $(".ms_ne_colors").minicolors({
            change: function (hex,opacity)
            {
               // $("#editscreenwindow").css("background-color", hex);
            }
        });
        
    $(".imagepicker").on("click", function(){
       alert("hi"); 
    });
</script>