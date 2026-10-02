<?php
/**
 * My Office — lab clipboard + experiment results.
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

$panel = isset($_GET['panel']) ? trim((string) $_GET['panel']) : 'clipboard';
if ($panel !== 'results') {
	$panel = 'clipboard';
}

$_SESSION['clipboard_visited'] = 1;
if ($panel === 'results') {
	$_SESSION['clipboard_results_visited'] = 1;
	choosology_award_achievement($db, $user, 'results_analyst');
	choosology_clipboard_set_checklist_flag($db, $user, 'review_results', 'completed', 1);
} else {
	choosology_award_achievement($db, $user, 'clipboard_clerk');
	choosology_clipboard_set_checklist_flag($db, $user, 'open_clipboard', 'completed', 1);
}

$sessionFlags = array(
	'visited_clipboard' => true,
	'visited_results' => !empty($_SESSION['clipboard_results_visited']),
);
choosology_clipboard_sync_checklist($db, $user, $sessionFlags);

$pad = choosology_clipboard_get_pad($db, $user);
$todos = choosology_clipboard_list_todos($db, $user);
$last = choosology_clipboard_last_edited_adv($db, $user);
$metrics = choosology_clipboard_experiment_metrics($db, $user);

$escU = mysqli_real_escape_string($db, $user);
$checkState = array();
$cr = mysqli_query($db, "SELECT item_key, dismissed, completed FROM clipboard_checklist WHERE uname = '$escU'");
if ($cr) {
	while ($row = mysqli_fetch_assoc($cr)) {
		$checkState[(string) $row['item_key']] = array(
			'dismissed' => (int) ($row['dismissed'] ?? 0) === 1,
			'completed' => (int) ($row['completed'] ?? 0) === 1,
		);
	}
}
$checklist = array();
foreach (choosology_clipboard_checklist_defs() as $def) {
	$st = $checkState[$def['key']] ?? array('dismissed' => false, 'completed' => false);
	if ($st['dismissed']) {
		continue;
	}
	$checklist[] = array_merge($def, $st);
}

$catalog = choosology_achievement_catalog();
?>
<div class="intabs ms-office-page" data-office-panel="<?php echo htmlspecialchars($panel, ENT_QUOTES, 'UTF-8'); ?>">
	<div class="ms-office-paper">
		<div class="ms-office-head">
			<p class="ms-office-eyebrow">Desk set <span class="ms-office-eyebrow-tag">personal</span></p>
			<h2 class="ms-office-title">My Office</h2>
			<p class="ms-office-lede">Clipboard notes, onboarding checklist, recent work, and experiment results.</p>
		</div>

		<nav class="ms-office-subnav" aria-label="Office panels">
			<button type="button" class="ms-office-subnav-btn<?php echo $panel === 'clipboard' ? ' is-active' : ''; ?>" data-office-panel="clipboard">Clipboard</button>
			<button type="button" class="ms-office-subnav-btn<?php echo $panel === 'results' ? ' is-active' : ''; ?>" data-office-panel="results">Experiment Results</button>
		</nav>

		<div class="ms-office-panel<?php echo $panel === 'clipboard' ? ' is-active' : ''; ?>" id="ms_office_clipboard" data-panel="clipboard"<?php echo $panel === 'clipboard' ? '' : ' hidden'; ?>>
			<section class="ms-office-card" aria-labelledby="ms-office-activity-h">
				<h3 id="ms-office-activity-h">Recent activity</h3>
				<?php if ($last && $last['id'] > 0) {
					$title = htmlspecialchars(strip_tags(html_entity_decode($last['title'])), ENT_QUOTES, 'UTF-8');
					$edited = $last['edited'] !== '' ? nicedatetime($last['edited']) : '';
					?>
				<p class="ms-office-activity-line">
					<a class="ms-office-link" href="#/edit/<?php echo (int) $last['id']; ?>">Return to last edited experiment</a>
					<span class="ms-office-muted">— <?php echo $title; ?><?php echo $edited !== '' ? ' · ' . htmlspecialchars($edited, ENT_QUOTES, 'UTF-8') : ''; ?></span>
				</p>
				<p class="ms-office-activity-line">
					<a class="ms-office-link" href="#/view/<?php echo (int) $last['id']; ?>">Preview it</a>
					<span class="ms-office-sep">·</span>
					<a class="ms-office-link" href="#/mystuff/experiments">All experiments</a>
					<span class="ms-office-sep">·</span>
					<button type="button" class="ms-office-linkish" data-office-panel="results">Open results</button>
				</p>
				<?php } else { ?>
				<p class="ms-office-empty">No experiments yet. <a class="ms-office-link" href="#/mystuff/experiments">Create one</a> to pin recent work here.</p>
				<?php } ?>
			</section>

			<section class="ms-office-card" aria-labelledby="ms-office-check-h">
				<h3 id="ms-office-check-h">New researcher checklist</h3>
				<p class="ms-office-hint">Dismiss items you do not need. Completing an item (automatically or manually) unlocks a Degree.</p>
				<?php if (count($checklist) === 0) { ?>
				<p class="ms-office-empty">Checklist clear — nice work. See Degrees for awards.</p>
				<?php } else { ?>
				<ul class="ms-office-checklist" id="ms_office_checklist">
					<?php foreach ($checklist as $item) {
						$done = !empty($item['completed']);
						$achLabel = isset($catalog[$item['achievement']]) ? $catalog[$item['achievement']]['label'] : $item['achievement'];
						?>
					<li class="ms-office-check-item<?php echo $done ? ' is-done' : ''; ?>" data-item-key="<?php echo htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8'); ?>">
						<div class="ms-office-check-main">
							<span class="ms-office-check-label"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
							<span class="ms-office-check-hint"><?php echo htmlspecialchars($item['hint'], ENT_QUOTES, 'UTF-8'); ?> · Degree: <?php echo htmlspecialchars($achLabel, ENT_QUOTES, 'UTF-8'); ?></span>
						</div>
						<div class="ms-office-check-actions">
							<?php if (!$done) { ?>
							<button type="button" class="ms-office-btn ms-office-btn--small" data-check-complete>Done</button>
							<?php } else { ?>
							<span class="ms-office-badge">Complete</span>
							<?php } ?>
							<button type="button" class="ms-office-btn ms-office-btn--ghost ms-office-btn--small" data-check-dismiss title="Hide without awarding">Dismiss</button>
						</div>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
			</section>

			<section class="ms-office-card" aria-labelledby="ms-office-pad-h">
				<h3 id="ms-office-pad-h">Personal notepad</h3>
				<p class="ms-office-hint">Free-form scratch space — lab notes, reminders, plot scraps.</p>
				<textarea id="ms_office_pad" class="ms-office-pad" rows="8" maxlength="50000"><?php echo htmlspecialchars($pad['body'], ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="ms-office-row">
					<button type="button" class="ms-office-btn" id="ms_office_pad_save">Save note</button>
					<span id="ms_office_pad_status" class="ms-office-status" aria-live="polite"></span>
				</div>
			</section>

			<section class="ms-office-card" aria-labelledby="ms-office-todo-h">
				<h3 id="ms-office-todo-h">To-do list</h3>
				<form id="ms_office_todo_form" class="ms-office-todo-form" onsubmit="return false;">
					<input type="text" id="ms_office_todo_input" class="ms-office-input" maxlength="500" placeholder="Add a lab task…" autocomplete="off">
					<button type="submit" class="ms-office-btn" id="ms_office_todo_add">Add</button>
				</form>
				<ul class="ms-office-todos" id="ms_office_todos">
					<?php foreach ($todos as $todo) { ?>
					<li class="ms-office-todo<?php echo !empty($todo['done']) ? ' is-done' : ''; ?>" data-todo-id="<?php echo (int) $todo['id']; ?>">
						<label class="ms-office-todo-label">
							<input type="checkbox" class="ms-office-todo-check"<?php echo !empty($todo['done']) ? ' checked' : ''; ?>>
							<span><?php echo htmlspecialchars($todo['body'], ENT_QUOTES, 'UTF-8'); ?></span>
						</label>
						<button type="button" class="ms-office-btn ms-office-btn--ghost ms-office-btn--small" data-todo-delete>Remove</button>
					</li>
					<?php } ?>
				</ul>
				<?php if (count($todos) === 0) { ?>
				<p class="ms-office-empty" id="ms_office_todos_empty">No to-dos yet.</p>
				<?php } ?>
			</section>
		</div>

		<div class="ms-office-panel<?php echo $panel === 'results' ? ' is-active' : ''; ?>" id="ms_office_results" data-panel="results"<?php echo $panel === 'results' ? '' : ' hidden'; ?>>
			<section class="ms-office-card" aria-labelledby="ms-office-results-h">
				<h3 id="ms-office-results-h">Experiment results</h3>
				<p class="ms-office-hint">Usage signals for experiments you own: plays, comments, ratings, ending finds, and structure size.</p>
				<?php if (count($metrics) === 0) { ?>
				<p class="ms-office-empty">No experiments to measure yet.</p>
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
								<th scope="col">Links</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($metrics as $m) {
							$title = htmlspecialchars(strip_tags(html_entity_decode((string) $m['title'])), ENT_QUOTES, 'UTF-8');
							$avail = htmlspecialchars((string) $m['avail'], ENT_QUOTES, 'UTF-8');
							$id = (int) $m['id'];
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
</div>

<script>
(function ($) {
	function apiUrl() {
		if (typeof choosologyUrlSafeGlobal === "function") {
			return choosologyUrlSafeGlobal("ajax/clipboard.php");
		}
		return "ajax/clipboard.php";
	}
	function post(action, payload) {
		payload = payload || {};
		payload.action = action;
		return $.ajax({
			type: "POST",
			url: apiUrl(),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: JSON.stringify(payload)
		});
	}
	function showPanel(name) {
		var $root = $(".ms-office-page");
		if (!$root.length) return;
		$root.attr("data-office-panel", name);
		$root.find(".ms-office-subnav-btn").removeClass("is-active")
			.filter('[data-office-panel="' + name + '"]').addClass("is-active");
		$root.find(".ms-office-panel").attr("hidden", "hidden").removeClass("is-active");
		$root.find('.ms-office-panel[data-panel="' + name + '"]').removeAttr("hidden").addClass("is-active");
		if (name === "results") {
			try { sessionStorage.setItem("choosologyOfficePanel", "results"); } catch (err) {}
			post("visit_results");
		} else {
			try { sessionStorage.removeItem("choosologyOfficePanel"); } catch (err2) {}
			post("visit_clipboard");
		}
	}

	$(document).off("click.msOffice").on("click.msOffice", ".ms-office-subnav-btn, [data-office-panel].ms-office-linkish", function (e) {
		e.preventDefault();
		var name = $(this).attr("data-office-panel") || "clipboard";
		showPanel(name);
	});

	$("#ms_office_pad_save").off("click").on("click", function () {
		var $status = $("#ms_office_pad_status").text("Saving…");
		post("save_pad", { body: $("#ms_office_pad").val() }).done(function (res) {
			$status.text(res && res.ok ? "Saved." : ((res && res.error) || "Could not save."));
		}).fail(function () {
			$status.text("Could not save.");
		});
	});

	$("#ms_office_todo_form").off("submit").on("submit", function (e) {
		e.preventDefault();
		var body = $.trim($("#ms_office_todo_input").val());
		if (!body) return;
		post("add_todo", { body: body }).done(function (res) {
			if (res && res.ok) {
				$("#ms_office_todo_input").val("");
				renderTodos(res.todos || []);
			}
		});
	});

	function renderTodos(todos) {
		var $ul = $("#ms_office_todos").empty();
		$("#ms_office_todos_empty").remove();
		if (!todos.length) {
			$ul.after('<p class="ms-office-empty" id="ms_office_todos_empty">No to-dos yet.</p>');
			return;
		}
		todos.forEach(function (t) {
			var $li = $('<li class="ms-office-todo"></li>').attr("data-todo-id", t.id);
			if (t.done) $li.addClass("is-done");
			$li.append(
				$('<label class="ms-office-todo-label"></label>')
					.append($('<input type="checkbox" class="ms-office-todo-check">').prop("checked", !!t.done))
					.append($("<span></span>").text(t.body))
			);
			$li.append('<button type="button" class="ms-office-btn ms-office-btn--ghost ms-office-btn--small" data-todo-delete>Remove</button>');
			$ul.append($li);
		});
	}

	$(document).off("change.msOfficeTodo").on("change.msOfficeTodo", ".ms-office-todo-check", function () {
		var id = parseInt($(this).closest(".ms-office-todo").attr("data-todo-id"), 10) || 0;
		post("toggle_todo", { id: id, done: $(this).is(":checked") ? 1 : 0 }).done(function (res) {
			if (res && res.todos) renderTodos(res.todos);
		});
	});
	$(document).off("click.msOfficeTodoDel").on("click.msOfficeTodoDel", "[data-todo-delete]", function () {
		var id = parseInt($(this).closest(".ms-office-todo").attr("data-todo-id"), 10) || 0;
		post("delete_todo", { id: id }).done(function (res) {
			if (res && res.todos) renderTodos(res.todos);
		});
	});

	$(document).off("click.msOfficeCheck").on("click.msOfficeCheck", "[data-check-dismiss]", function () {
		var $item = $(this).closest(".ms-office-check-item");
		var key = $item.attr("data-item-key");
		post("dismiss_checklist", { item_key: key }).done(function (res) {
			if (res && res.ok) $item.slideUp(150, function () { $(this).remove(); });
		});
	});
	$(document).off("click.msOfficeCheckDone").on("click.msOfficeCheckDone", "[data-check-complete]", function () {
		var $item = $(this).closest(".ms-office-check-item");
		var key = $item.attr("data-item-key");
		post("complete_checklist", { item_key: key }).done(function (res) {
			if (res && res.ok) {
				$item.addClass("is-done");
				$item.find("[data-check-complete]").replaceWith('<span class="ms-office-badge">Complete</span>');
			}
		});
	});

	post("visit_clipboard");
	<?php if ($panel === 'results') { ?>
	post("visit_results");
	<?php } ?>
})(jQuery);
</script>
