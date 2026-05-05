<?php
require_once("connect.php");
require_once("auxfuncs.php");
?>
<h1>Welcome to Choosology Labs!</h1>
<div class='homep'>
    Here at Choosology Labs, we're at the forefront of interactive storytelling technology. Our ever-growing team of Choosologists is hard at work every day
    discovering new elements of fiction, exciting and efficient methods of exposition, and novel uses for the written word.

</div>
<br />
<div class='homep'>
    If you're already one of our Choosologists, go ahead and sign in above to access your workstation. If you're new here, feel free to <a>browse</a> the
    currently-running experiments or read <a>more about the site</a>.
</div>
<br />
<h2>Newly Featured</h2>
<div class='intabs' id = "featadvs" >


    <?php
    //<span id='tinyshowhide'>&uarr;hide&uarr;</span>
    $query = "select *, a.id as aid from advs a, advscreens s where avail='public' and s.id = a.begin order by aid desc limit 3";
    $res = runquery_assoc($query);
    ?>
    <div class='featuredslides'>
        <div class='slidenav-left cycle-prev'>

        </div>
        <?php
        if(is_array($res))
        {
        foreach ($res as $r)
        {
            echo buildAdvFlag($r['aid'], $name);
        }
        ?>
        <div class='slidenav-right cycle-next'>
            <img src='images/icons/misc/slideright2.png' />
        </div>
        <?php 
        } 
        else echo $res;
        ?>
    </div>
</div>

<?php
echo "";
?>
<script>
    $(document).ready(function(){

        $(".featuredslides").cycle({
            speed: 1000,
            manualSpeed: 1000,
            timeout: 8000,
            fx: "scrollHorz",
            pauseOnHover: true,
            //fx: "fade",
            //hideNonActive: false,
            slides: "> div.slidefolder"
        });
        $(".overoneslide").mouseenter(function() {
            $(this).hide("fade", 500);
        });
        $(".oneslide").mouseleave(function() {
            $(this).find(".overoneslide").show("fade", 500);
        });

        $("#tinyshowhide").click(function() {
            var feats = $("#featadvs");
            if(feats.css("display") != "none")
            {
                feats.css("display", "none");
                $("#tinyshowhide").html("&darr;show&darr;");
            }
            else
            {
                feats.css("display", "block");
                $("#tinyshowhide").html("&uarr;hide&uarr;");
            }
        });
    });
</script>