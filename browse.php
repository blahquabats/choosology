<?php
require_once("connect.php");
require_once("auxfuncs.php");

?>
<h1><span style='font-family: Linecons'></span> Search Experiments</h1>


<div id="wholepage">
    <div class='searchbit' style='text-align: right'>
        Quick Search by Title:<br />
        <?php echo icon("search"); ?>&nbsp;<input type="text" id='experimentsearch' />
        <div id='results'>

        </div>
    </div>
<div class='homep' style='margin-right: 40%;'>
    We are always looking for new subjects on whom to experiment. After you've completed your experience, please rate
    the experiment and perhaps even leave a comment for the Choosologist in charge.
    <br> <br>
    You may browse the offerings below or search for a particular experiment.
    <br />
    <br />
    <b>View:</b>
    <label class='sel'>
        <select style='width:60%' id='viewselect'>
            <option value='gs'>General Survey</option>
            <option value='tr'>Top Rated All-Time</option>
            <option value='rp'>Recently Published</option>
            <option value='re'>Recently Edited</option>
            <option value='fa'>From The Archives (random unrated experiments)</option>
            <option value='as'>Advanced Search...</option>
        </select>
    </label>
    <div id="advsearch">
    	Title search: <input name='titlesearch' id='titlesearch'></input>
    	<button id='advsearchbutton'>Search</button>
    </div>
</div>


<div id='otherexperiments'>
    <div id='oecont'>
    <?php
    echo "<div class='advcolumn'></div>";
    echo "<div class='advcolumn'></div>";
    echo "<div class='advcolumn'></div>";
    ?>
    </div>

</div>
    <br /><br />
    <div id ='searchloading'>
    Fetching Experiments...
    </div>


</div>
<script>
    $(document).ready(function(){
       /* $("#searchbutt").click(function() {
            $("#wholepage").hide("clip", 500);
        });
        $(".seemore").click(function(){
            $(".advcolumn").hide("blind", 500);
        });*/
        $("#viewselect").on("change", function() {
            var val = $("#viewselect option:selected").val();
            if (!val) return false;
            if(val == "as") //open advanced search
            {
                $("#advsearch").show("blind", 400);
                return false;
            }
            $("#advsearch").hide("blind", 400);
            triggerSearch(val, 4, 1);
        });
       /* $(".miniflag").hover(function(){
            $(this).css("background-color", "#fafafa");
        }, function(){
            $(this).css("background-color", "transparent");
        });*/

        $("#advsearchbutton").click(function(){
        		triggerSearch("as", 4, 1);
        });
        $("#experimentsearch").keyup(function() {
           var val = $(this).val();
            if (val.length < 3)
            {
                $("#results").html('').css("display", "none");
                return true;
            }
            $.ajax({
                type: "POST",
                url: "searchadv.php",
                data: {
                    limit: 4,
                    searchval: val
                }
            }).done(function(response) {
                    if (!response) return true;
                    if(response != 2)
                    {
                       $("#results").html(response).css("display", "block");
                        $("#wholepage").click(function(){
                            $("#results").html('').css("display", "none");
                            $("#wholepage").off("click");
                        });

                    }
                  /*  else
                    {
                        var topbox = $("#topbox");
                        topbox.append("<span style='color:red; font-weight:bold' class='wrongpass'><br />Wrong username or password.</style><br />");
                        topbox.effect("shake", {distance: 5, times: 2});
                        $(".wrongpass").fadeOut(2000, "easeInExpo", function(){
                            this.remove();
                        });
                        $('#loginsubmit').prop('disabled', false);
                        $("#loginpass").val("").focus();
                    }*/
                });
        });
        
        

    });
    


    function triggerSearch(which, limit, allthree, page)
    {
        $("#viewselect").val(which);
        if(!$("#viewselect").val()) $("#viewselect").val("as");

        $("#oecont").hide("blind", 400);
        $("#searchloading").show();
        
        var params = "";
        if(!page) page = 1;
		if(which == "as") 
		{	// collect parameters
			//var tagval = $("#tagsearch").val();
			var titleval = $("#titlesearch").val();
			params = {title: titleval};
		}
		else if(which.substring(0,7) == "a.title")
		{
		    var parts = which.split(";;");
		    parts[1]
		    $("#titlesearch").val(parts[1]);
		    var titleval = $("#titlesearch").val();
		    params = {title: titleval};
		}
        $.ajax({
            type: "POST",
            url: "searchadv.php",
            data: {
                which: which,
                limit: limit,
                allthree: allthree,
                page: page,
                params: params
            }
        }).done(function(response) {
                if (!response) return true;
                if(response != 2) // or whatever error code...
                {
                    var ponse = response.split("!@!@!");
                    $(".advcolumn").get(0).innerHTML = ponse[0];
                    $(".advcolumn").get(1).innerHTML = ponse[1];
                    $(".advcolumn").get(2).innerHTML = ponse[2];
                    $("#searchloading").hide();
                    $("#oecont").show("blind", 400);
                    /*
                    $(".advcolumn .miniflag").hover(function(){ // SETUP HOVER
                        $(this).css("background-color", "#fafafa");
                    }, function(){
                        $(this).css("background-color", "transparent");
                    });*/
                    
                    $(".advcolumn .miniflag").on("click", function(){ // setup click to view
                    var $id = $(this).attr("data-viewid");
                    location.href = "#/view/"+$id;
                    });
                    listenToEdit();
                }

            });

    }
    triggerSearch("gs",4,1);
</script>