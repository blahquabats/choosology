<?php

require_once("../connect.php");
require_once("../auxfuncs.php");

if (empty($_SESSION['user'])) {
	echo "<div class='intabs'><p class='error'>Please sign in to view your experiments.</p></div>";
	return;
}
$name = (string) $_SESSION['user'];

?>
<div class='intabs'>
<h2>My Experiments</h2>
<div class='ms_e_experimentslist'>
<?php

// get all advs

$query = "select id, avail, edited from advs where user = '$name' order by avail, edited desc";
$r = runquery_assoc($query);
$first = 1;
// display list with links to preview/edit
foreach ($r as $adv)
{
    if($first)
    {
        // most recent
        echo "<div class='ms_e_recentexp'>
        <div class='ms_e_lastedited'>Return to your most recent work:</div>
        ".buildMiniFlag($adv['id'], $name)."</div>
        ";
        if(count($r) > 1)
        {
            echo "<br /><div>Or a previous experiment:</div>";
        }
        
        $first = 0;
    }
    else echo buildMiniFlag($adv['id'], $name)."<br/>";
}
?>
</div>
<div class='ms_e_newexperiment'>
    <h3>Create New Experiment</h3>
    <br />
    What will you call it?
    <br/>
    <input class='ms_e_launchinput' type ='text'  />

    <span class='fakebutton' id = 'ms_e_newexperiment_submit'>Launch! &rarr;</span>
</div>

<div class='ms_e_recentcomments'>
    
    <?php
    
            $comments=new commentArea("byuser".$alluserinfo['id'], true, true, 0, "width:100%", "Recent Comments");
        $comments->display();
        
        
        ?>
    
</div>
    
    
</div>


<script>
    $(".ms_e_newexperiment, .editadvbutton, .deleteadvbutton").off("click");
    $(".ms_e_newexperiment").on('click', function()
    {
       $(".ms_e_launchinput").focus(); 
    });
    
    $("#ms_e_newexperiment_submit").on("click", function(){
        var advname = $(".ms_e_launchinput").val();
        if(!advname) return false;
        makeNewExperiment($(".ms_e_launchinput").val());
    });
    
    $(".miniflag").on("click", function(e){ // setup click to view
        if (!$(e.target).is(".editadvbutton,.deleteadvbutton")) 
        {
            var $id = $(this).attr("data-viewid");
            location.href = "#/view/"+$id;
        }
        else
        {
            $(e.target).click();
            return false;
        }
    });
    listenToEdit();
</script>