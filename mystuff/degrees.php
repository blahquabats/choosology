<?php
/**
 * Degrees — earned lab achievements (tied to clipboard checklist + authoring milestones).
 */
require_once("../connect.php");
require_once("../auxfuncs.php");
require_once("../lib/clipboard-helpers.php");

if (empty($_SESSION['user'])) {
	echo "<div class='intabs'><p class='error'>Please sign in to view your degrees.</p></div>";
	return;
}

$user = (string) $_SESSION['user'];
choosology_clipboard_ensure_schema($db);
$sessionFlags = array(
	'visited_clipboard' => !empty($_SESSION['clipboard_visited']),
	'visited_results' => !empty($_SESSION['clipboard_results_visited']),
);
choosology_clipboard_sync_checklist($db, $user, $sessionFlags);
$achievements = choosology_user_achievements($db, $user);
$earnedCount = 0;
foreach ($achievements as $a) {
	if (!empty($a['earned_at'])) {
		$earnedCount++;
	}
}
$total = count($achievements);
?>
<div class="intabs ms-degrees-page">
	<div class="ms-degrees-paper">
		<div class="ms-degrees-head">
			<p class="ms-degrees-eyebrow">Credentials <span class="ms-degrees-eyebrow-tag">lab</span></p>
			<h2 class="ms-degrees-title">My Degrees</h2>
			<p class="ms-degrees-lede">Awards unlocked by checklist progress and authoring milestones.
				<strong><?php echo (int) $earnedCount; ?></strong> of <strong><?php echo (int) $total; ?></strong> earned.</p>
		</div>

		<ul class="ms-degrees-list">
			<?php foreach ($achievements as $a) {
				$earned = !empty($a['earned_at']);
				$when = '';
				if ($earned) {
					$ts = strtotime((string) $a['earned_at']);
					$when = $ts > 0 ? date('M j, Y', $ts) : (string) $a['earned_at'];
				}
				?>
			<li class="ms-degrees-item<?php echo $earned ? ' is-earned' : ' is-locked'; ?>">
				<div class="ms-degrees-item-main">
					<span class="ms-degrees-item-label"><?php echo htmlspecialchars($a['label'], ENT_QUOTES, 'UTF-8'); ?></span>
					<span class="ms-degrees-item-blurb"><?php echo htmlspecialchars($a['blurb'], ENT_QUOTES, 'UTF-8'); ?></span>
				</div>
				<div class="ms-degrees-item-meta">
					<span class="ms-degrees-group"><?php echo htmlspecialchars($a['group'], ENT_QUOTES, 'UTF-8'); ?></span>
					<?php if ($earned) { ?>
					<span class="ms-degrees-earned">Earned <?php echo htmlspecialchars($when, ENT_QUOTES, 'UTF-8'); ?></span>
					<?php } else { ?>
					<span class="ms-degrees-locked">Not yet</span>
					<?php } ?>
				</div>
			</li>
			<?php } ?>
		</ul>

		<p class="ms-degrees-foot">Tip: open the <strong>Clip</strong> button beside your login name for the new researcher checklist.</p>
	</div>
</div>
