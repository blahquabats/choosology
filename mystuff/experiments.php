<?php

require_once("../connect.php");
require_once("../auxfuncs.php");

if (empty($_SESSION['user'])) {
	echo "<div class='intabs'><p class='error'>Please sign in to view your experiments.</p></div>";
	return;
}
$name = (string) $_SESSION['user'];
$escName = mysqli_real_escape_string($db, $name);

?>
<div class='intabs ms-e-folder-page'>
<h2 class="ms-e-page-title">My Experiments</h2>
<div class='ms_e_experimentslist'>
<?php

// get all advs

$query = "select id, avail, edited from advs where user = '$escName' order by avail, edited desc";
$r = runquery_assoc($query);
if (!is_array($r)) {
    $r = array();
}
$first = 1;
// display list with links to preview/edit
foreach ($r as $adv)
{
    if($first)
    {
        // most recent
        echo "<div class='ms_e_recentexp'>
        <div class='ms_e_lastedited'>Return to your most recent work:</div>
        ".buildMiniFlag($adv['id'], $name, 1, true)."</div>
        ";
        if(count($r) > 1)
        {
            echo "<div class='ms-e-previous-label'>Or a previous experiment:</div>";
        }
        
        $first = 0;
    }
    else echo buildMiniFlag($adv['id'], $name, 1, true)."<br/>";
}
?>
</div>
<div class='ms_e_newexperiment'>
    <button type="button" class="ms-e-new-experiment-button" id="ms_e_newexperiment_submit">
        <span class="ms-e-new-experiment-code">NEW</span>
        <span class="ms-e-new-experiment-copy">
            <span class="ms-e-new-experiment-title">New experiment</span>
            <span class="ms-e-new-experiment-hint">Create a draft and open Experiment Settings</span>
        </span>
    </button>
</div>

<div class='ms_e_recentcomments'>
	<section class="ms-e-comments-panel" aria-labelledby="ms-e-comments-heading">
		<div class="ms-e-comments-head">
			<p class="ms-e-comments-eyebrow">Incoming field notes</p>
			<h3 id="ms-e-comments-heading">Recent comments</h3>
		</div>
		<div class="ms-e-comments-list">
			<?php
			$commentSql = "
				SELECT
					c.id,
					c.author,
					c.date,
					c.text,
					c.whichscreen,
					a.id AS advid,
					a.title AS advtitle
				FROM comments c
				INNER JOIN advs a ON c.whichboard = CONCAT('adv', a.id)
				WHERE a.user = '$escName'
				ORDER BY c.date DESC
				LIMIT 8";
			$recentComments = runquery_assoc($commentSql);
			if (!is_array($recentComments) || count($recentComments) === 0) {
				echo '<p class="ms-e-comments-empty">No comments on your experiments yet.</p>';
			} else {
				foreach ($recentComments as $comment) {
					$commentId = (int) ($comment['id'] ?? 0);
					$advid = (int) ($comment['advid'] ?? 0);
					$screen = (int) ($comment['whichscreen'] ?? 0);
					$author = htmlspecialchars((string) ($comment['author'] ?? 'Someone'), ENT_QUOTES, 'UTF-8');
					$title = htmlspecialchars(strip_tags(html_entity_decode((string) ($comment['advtitle'] ?? 'Untitled Experiment'))), ENT_QUOTES, 'UTF-8');
					$text = htmlspecialchars(stripslashes((string) ($comment['text'] ?? '')), ENT_QUOTES, 'UTF-8');
					$dateRaw = (string) ($comment['date'] ?? '');
					$dateLabel = $dateRaw;
					$dateIso = '';
					$ts = strtotime($dateRaw);
					if ($ts > 0) {
						$dateLabel = date('M j, Y g:ia', $ts);
						$dateIso = date('c', $ts);
					}
					echo '<article class="ms-e-comment-card" data-comment-id="' . $commentId . '">';
					echo '<div class="ms-e-comment-meta">';
					echo '<span class="ms-e-comment-author">' . $author . '</span>';
					if ($dateLabel !== '' && $dateIso !== '') {
						echo '<time datetime="' . htmlspecialchars($dateIso, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') . '</time>';
					}
					echo '</div>';
					echo '<p class="ms-e-comment-text">' . nl2br($text) . '</p>';
					echo '<a class="ms-e-comment-target" href="#/view/' . $advid . '">On ' . $title;
					if ($screen > 0) {
						echo ' &middot; screen #' . $screen;
					}
					echo '</a>';
					echo '</article>';
				}
			}
			?>
		</div>
	</section>
</div>
    
    
</div>


<script>
    $(".ms_e_newexperiment, .editadvbutton, .deleteadvbutton").off("click");
    
    $("#ms_e_newexperiment_submit").on("click", function(){
        makeNewExperiment("Untitled Experiment", { openSettings: true });
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