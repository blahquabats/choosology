<?php
require_once("connect.php");
require_once("auxfuncs.php");
require_once("labfeed.php");

$recentFeed = choosology_build_recent_feed($db, CHOOSOLOGY_HOME_FEED_LIMIT);
?>
<div class="home-splash">
    <section class="home-hero" aria-labelledby="home-hero-title">
        <div class="home-hero-lockup">
            <img class="home-hero-logo" src="images/logo_horizontal.png" alt="Choosology">
            <div class="home-hero-copy">
                <p class="home-hero-eyebrow">Interactive storytelling laboratory</p>
                <h1 id="home-hero-title" class="home-hero-title">Welcome to Choosology Labs</h1>
            </div>
        </div>
    </section>

    <section class="home-copy-full" aria-labelledby="home-copy-01-heading">
        <article class="home-copy-card home-copy-card--full">
            <span class="home-copy-code" aria-hidden="true">01</span>
            <h2 id="home-copy-01-heading" class="home-visually-hidden">About Choosology Labs</h2>
            <p>
                Here at Choosology Labs, we're at the forefront of interactive storytelling technology.
                Our ever-growing team of Choosologists is hard at work every day discovering new elements of fiction,
                exciting and efficient methods of exposition, and novel uses for the written word.
            </p>
        </article>
    </section>

    <section class="home-copy-grid" aria-label="Lab activity and next steps">
        <article class="home-copy-card home-copy-card--feed">
            <span class="home-copy-code" aria-hidden="true">02</span>
            <h2 class="home-copy-heading">Recent Updates</h2>
            <?php if (count($recentFeed) === 0) { ?>
                <p class="home-feed-empty">No recent lab notes or patch notes yet.</p>
            <?php } else { ?>
                <div class="lab-feed-list lab-feed-list--home">
                    <?php
                    foreach ($recentFeed as $item) {
                        choosology_echo_feed_item($item);
                    }
                    ?>
                </div>
                <div class="home-copy-actions">
                    <a class="home-copy-action" href="#/news">View all lab notes</a>
                </div>
            <?php } ?>
        </article>

        <article class="home-copy-card">
            <span class="home-copy-code" aria-hidden="true">03</span>
            <p>
                If you're already one of our Choosologists, sign in above to access your workstation.
                If you're new here, browse the currently-running experiments or read the latest lab notes.
            </p>
            <div class="home-copy-actions">
                <a class="home-copy-action" href="#/browse">Browse experiments</a>
                <a class="home-copy-action" href="#/news">Read lab notes</a>
            </div>
        </article>
    </section>
</div>
<h2 class="home-featured-title">Newly Featured</h2>
<div class='intabs' id = "featadvs" >


    <?php
    /* DISTINCT + explicit adv columns only: SELECT * from JOIN can overwrite advs.description with another table's column in mysqli_fetch_assoc */
    $query = "
        SELECT DISTINCT a.id AS aid, a.description AS adv_description
        FROM advs a
        INNER JOIN advscreens s ON s.id = a.`begin`
        WHERE a.avail = 'public'
          AND CHAR_LENGTH(TRIM(COALESCE(a.description, ''))) > 0
          AND (
                NOT EXISTS (SELECT 1 FROM ratings r WHERE r.adv = a.id)
             OR (SELECT AVG(r.rating) FROM ratings r WHERE r.adv = a.id) > 2.5
          )
        ORDER BY a.id DESC
        LIMIT 40";
    $res = runquery_assoc($query);
    if (is_array($res) && count($res) > 0) {
        $res = array_values(array_filter($res, function ($row) {
            $text = trim(decode($row['adv_description'] ?? '', 1));
            return $text !== '';
        }));
        if (count($res) > 0) {
            shuffle($res);
            $res = array_slice($res, 0, 10);
        }
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
                next: ".featuredslides .cycle-next",
                containerResize: 0,
                slideResize: 0
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
