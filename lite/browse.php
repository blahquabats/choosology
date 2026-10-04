<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();

global $db;

$which = strtolower(trim((string) ($_GET['which'] ?? 'rp')));
$allowed = array('rp' => 'Recently published', 'tr' => 'Top rated', 're' => 'Recently edited', 'fa' => 'From archives');
if (!isset($allowed[$which])) {
	$which = 'rp';
}
$titleQ = trim(choosology_undo_connect_string_mutation((string) ($_GET['q'] ?? '')));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "a.avail = 'public'";
$order = 'COALESCE(a.published, a.created) DESC';
if ($titleQ !== '') {
	$esc = mysqli_real_escape_string($db, $titleQ);
	$where .= " AND a.title LIKE '%$esc%'";
}
switch ($which) {
	case 'tr':
		$where .= " AND a.rating NOT IN ('NA','') AND a.rating IS NOT NULL";
		$order = 'CAST(a.rating AS DECIMAL(4,2)) DESC, a.id DESC';
		break;
	case 're':
		$order = 'a.edited DESC, a.id DESC';
		break;
	case 'fa':
		$where .= " AND (a.rating = 'NA' OR a.rating = '' OR a.rating IS NULL)";
		$order = 'COALESCE(a.published, a.created) DESC';
		break;
	case 'rp':
	default:
		$order = 'COALESCE(a.published, a.created) DESC, a.id DESC';
		break;
}

$countSql = "SELECT COUNT(*) AS c FROM advs a INNER JOIN advscreens s ON s.id = a.`begin` WHERE $where";
$countR = runquery_assoc($countSql);
$total = is_array($countR) ? (int) ($countR[0]['c'] ?? 0) : 0;
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
	$page = $totalPages;
	$offset = ($page - 1) * $perPage;
}

$listSql = "SELECT a.id AS aid, a.title, a.user, a.rating, a.description, a.play_starts,
	COALESCE(a.published, a.created) AS shown
	FROM advs a
	INNER JOIN advscreens s ON s.id = a.`begin`
	WHERE $where
	ORDER BY $order
	LIMIT $perPage OFFSET $offset";
$rows = runquery_assoc($listSql);
if (!is_array($rows)) {
	$rows = array();
}

choosology_lite_header(array('title' => 'Browse', 'active' => 'browse'));
?>
<fieldset class="lite-panel">
	<legend>Browse experiments</legend>
	<form class="lite-form" method="get" action="<?php echo htmlspecialchars(choosology_lite_url('browse.php'), ENT_QUOTES, 'UTF-8'); ?>">
		<label for="lite_which">Show</label>
		<select id="lite_which" name="which">
			<?php foreach ($allowed as $key => $label) { ?>
				<option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $which === $key ? ' selected' : ''; ?>>
					<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
				</option>
			<?php } ?>
		</select>
		<label for="lite_q">Title contains</label>
		<input type="search" id="lite_q" name="q" value="<?php echo htmlspecialchars($titleQ, ENT_QUOTES, 'UTF-8'); ?>" maxlength="100">
		<p style="margin-top:0.65rem;">
			<button type="submit" class="lite-btn">Search</button>
		</p>
	</form>
</fieldset>

<fieldset class="lite-panel">
	<legend>Results</legend>
	<p class="lite-meta"><?php echo (int) $total; ?> public experiment(s) · page <?php echo (int) $page; ?> of <?php echo (int) $totalPages; ?></p>
	<?php if (!$rows) { ?>
		<p class="lite-muted">No experiments matched.</p>
	<?php } else { ?>
		<ul class="lite-list">
			<?php foreach ($rows as $row) {
				$id = (int) ($row['aid'] ?? 0);
				$title = trim(decode((string) ($row['title'] ?? ''), 1));
				if ($title === '') {
					$title = 'Experiment #' . $id;
				}
				$by = htmlspecialchars((string) ($row['user'] ?? ''), ENT_QUOTES, 'UTF-8');
				$rating = htmlspecialchars((string) ($row['rating'] ?? 'NA'), ENT_QUOTES, 'UTF-8');
				$plays = (int) ($row['play_starts'] ?? 0);
				$href = htmlspecialchars(choosology_lite_url('view.php?id=' . $id), ENT_QUOTES, 'UTF-8');
				?>
				<li>
					<a href="<?php echo $href; ?>"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a>
					<div class="lite-muted">by <?php echo $by; ?> · rating <?php echo $rating; ?> · plays <?php echo $plays; ?></div>
				</li>
			<?php } ?>
		</ul>
		<?php if ($totalPages > 1) {
			$baseParams = array('which' => $which);
			if ($titleQ !== '') {
				$baseParams['q'] = $titleQ;
			}
			$mk = static function (int $p) use ($baseParams): string {
				$baseParams['page'] = $p;
				return choosology_lite_url('browse.php?' . http_build_query($baseParams));
			};
			?>
			<nav class="lite-pager" aria-label="Results pages">
				<?php if ($page > 1) { ?>
					<a class="lite-btn" href="<?php echo htmlspecialchars($mk($page - 1), ENT_QUOTES, 'UTF-8'); ?>">← Previous</a>
				<?php } ?>
				<span class="lite-muted">Page <?php echo (int) $page; ?> / <?php echo (int) $totalPages; ?></span>
				<?php if ($page < $totalPages) { ?>
					<a class="lite-btn" href="<?php echo htmlspecialchars($mk($page + 1), ENT_QUOTES, 'UTF-8'); ?>">Next →</a>
				<?php } ?>
			</nav>
		<?php } ?>
	<?php } ?>
</fieldset>
<?php
choosology_lite_footer();
