<?php
/**
 * My Office — experiment results (detailed metrics).
 * Personal clipboard lives in the floating overlay (Clip button).
 */
require_once("../connect.php");
require_once("../auxfuncs.php");
require_once("../lib/clipboard-helpers.php");

if (empty($_SESSION['user'])) {
	echo "<div class='intabs'><p class='error'>Please sign in to open your office.</p></div>";
	return;
}

$user = (string) $_SESSION['user'];
choosology_clipboard_ensure_schema($db);

$_SESSION['clipboard_results_visited'] = 1;
choosology_award_achievement($db, $user, 'results_analyst');
choosology_clipboard_set_checklist_flag($db, $user, 'review_results', 'completed', 1);
choosology_clipboard_sync_checklist($db, $user, array(
	'visited_clipboard' => !empty($_SESSION['clipboard_visited']),
	'visited_results' => true,
));

$metrics = choosology_clipboard_experiment_metrics($db, $user);
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$availFilter = isset($_GET['avail']) ? trim((string) $_GET['avail']) : '';
if ($availFilter !== '' && !in_array($availFilter, array('public', 'private', 'none', 'all'), true)) {
	$availFilter = '';
}

$filtered = array();
foreach ($metrics as $m) {
	if ($availFilter !== '' && (string) ($m['avail'] ?? '') !== $availFilter) {
		continue;
	}
	if ($q !== '') {
		$hay = strtolower((string) ($m['title'] ?? ''));
		if (strpos($hay, strtolower($q)) === false) {
			continue;
		}
	}
	$filtered[] = $m;
}
?>
<div class="intabs ms-office-page" data-office-panel="results">
	<div class="ms-office-paper">
		<div class="ms-office-head">
			<p class="ms-office-eyebrow">Lab analytics <span class="ms-office-eyebrow-tag">results</span></p>
			<h2 class="ms-office-title">Experiment Results</h2>
			<p class="ms-office-lede">Usage signals for experiments you own. Everyday notes and checklist live on your
				<button type="button" class="ms-office-linkish" id="ms_office_open_clipboard">clipboard</button>.</p>
		</div>

		<form class="ms-office-filters" id="ms_office_filters" onsubmit="return false;">
			<label class="ms-office-filter">
				<span>Search title</span>
				<input type="search" id="ms_office_q" class="ms-office-input" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Filter by name…" autocomplete="off">
			</label>
			<label class="ms-office-filter">
				<span>Availability</span>
				<select id="ms_office_avail" class="ms-office-input">
					<option value="">Any</option>
					<option value="public"<?php echo $availFilter === 'public' ? ' selected' : ''; ?>>public</option>
					<option value="private"<?php echo $availFilter === 'private' ? ' selected' : ''; ?>>private</option>
					<option value="none"<?php echo $availFilter === 'none' ? ' selected' : ''; ?>>none</option>
					<option value="all"<?php echo $availFilter === 'all' ? ' selected' : ''; ?>>all</option>
				</select>
			</label>
			<button type="button" class="ms-office-btn" id="ms_office_filter_apply">Apply</button>
		</form>

		<section class="ms-office-card" aria-labelledby="ms-office-results-h">
			<h3 id="ms-office-results-h">Detailed metrics</h3>
			<p class="ms-office-hint">Plays, comments, ratings, ending finds, and structure size.</p>
			<?php if (count($filtered) === 0) { ?>
			<p class="ms-office-empty"><?php echo count($metrics) === 0 ? 'No experiments to measure yet.' : 'No experiments match this filter.'; ?></p>
			<?php } else { ?>
			<div class="ms-office-metrics-wrap">
				<table class="ms-office-metrics">
					<thead>
						<tr>
							<th scope="col">Experiment</th>
							<th scope="col">Status</th>
							<th scope="col">Plays</th>
							<th scope="col">Comments</th>
							<th scope="col">Ratings</th>
							<th scope="col">Avg</th>
							<th scope="col">Endings found</th>
							<th scope="col">Screens</th>
							<th scope="col">Last played</th>
							<th scope="col">Links</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($filtered as $m) {
						$title = htmlspecialchars(strip_tags(html_entity_decode((string) $m['title'])), ENT_QUOTES, 'UTF-8');
						$avail = htmlspecialchars((string) $m['avail'], ENT_QUOTES, 'UTF-8');
						$id = (int) $m['id'];
						$lastPlayed = '';
						if (!empty($m['last_played'])) {
							$ts = strtotime((string) $m['last_played']);
							$lastPlayed = $ts > 0 ? date('M j, Y', $ts) : htmlspecialchars((string) $m['last_played'], ENT_QUOTES, 'UTF-8');
						}
						?>
						<tr>
							<td><?php echo $title; ?></td>
							<td><?php echo $avail; ?></td>
							<td><?php echo (int) $m['play_starts']; ?></td>
							<td><?php echo (int) $m['comments']; ?></td>
							<td><?php echo (int) $m['rating_count']; ?></td>
							<td><?php echo htmlspecialchars((string) $m['rating'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php echo (int) $m['ending_finds']; ?></td>
							<td><?php echo (int) $m['screens']; ?></td>
							<td><?php echo $lastPlayed !== '' ? $lastPlayed : '—'; ?></td>
							<td class="ms-office-metrics-links">
								<a class="ms-office-link" href="#/view/<?php echo $id; ?>">View</a>
								<a class="ms-office-link" href="#/edit/<?php echo $id; ?>">Edit</a>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
			<?php } ?>
		</section>
	</div>
</div>

<script>
(function ($) {
	function reloadOffice() {
		var q = $.trim($("#ms_office_q").val() || "");
		var avail = $("#ms_office_avail").val() || "";
		var url = "mystuff/office.php?panel=results";
		if (q) url += "&q=" + encodeURIComponent(q);
		if (avail) url += "&avail=" + encodeURIComponent(avail);
		var $panel = $(".ms-office-page").closest(".ui-tabs-panel");
		if ($panel.length) {
			$panel.load(url);
		} else {
			window.location.hash = "#/mystuff/office";
		}
	}
	$("#ms_office_filter_apply").off("click").on("click", reloadOffice);
	$("#ms_office_q").off("keypress").on("keypress", function (e) {
		if (e.which === 13) {
			e.preventDefault();
			reloadOffice();
		}
	});
	$("#ms_office_open_clipboard").off("click").on("click", function (e) {
		e.preventDefault();
		if (window.ChoosologyClipboard && typeof ChoosologyClipboard.open === "function") {
			ChoosologyClipboard.open({ auto: false });
		}
	});
})(jQuery);
</script>
