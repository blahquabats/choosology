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
    $query = "
        SELECT *, a.id AS aid
        FROM advs a
        INNER JOIN advscreens s ON s.id = a.`begin`
        WHERE a.avail = 'public'
          AND CHAR_LENGTH(TRIM(COALESCE(a.description, ''))) > 0
          AND (
                NOT EXISTS (SELECT 1 FROM ratings r WHERE r.adv = a.id)
             OR (SELECT AVG(r.rating) FROM ratings r WHERE r.adv = a.id) > 2.5
          )
        ORDER BY a.id DESC
        LIMIT 10";
    $res = runquery_assoc($query);
    if (is_array($res) && count($res) > 0) {
        shuffle($res);
    }
    ?>
    <div class='featuredslides'>
        <?php if (is_array($res) && count($res) > 0) { ?>
        <div class='slidenav-left cycle-prev'><img src='images/icons/misc/slideleft2.png' alt="" /></div>
        <div class='featuredslides-cycle'>
        <?php
            foreach ($res as $r) {
                echo buildAdvFlag($r['aid'], $name);
            }
        ?>
        </div>
        <div class='slidenav-right cycle-next'>
            <img src='images/icons/misc/slideright2.png' alt="" />
        </div>
        <?php } elseif (is_array($res)) { ?>
        <p class='home-no-featured'>No public adventures to feature yet.</p>
        <?php } else {
            echo $res;
        } ?>
    </div>
</div>

<?php
echo "";
?>
<script>
    $(document).ready(function(){

        /* Cycle 3.x uses slideExpr, not "slides"; prev/next must be outside the cycle root or they become slides */
        var $cyc = $(".featuredslides-cycle");
        var slideCount = $cyc.children(".slidefolder").length;
        if ($cyc.length && slideCount >= 2) {
            $cyc.cycle({
                speed: 1000,
                manualSpeed: 1000,
                timeout: 8000,
                fx: "scrollHorz",
                pauseOnHover: true,
                prev: ".featuredslides .cycle-prev",
                next: ".featuredslides .cycle-next"
            });
        } else if (slideCount === 1) {
            $(".featuredslides .cycle-prev, .featuredslides .cycle-next").hide();
        }

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