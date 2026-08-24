<?php
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';

if (empty($_SESSION['user'])) {
	echo '<div class="error">Please sign in to view messages.</div>';
	return;
}

$unread = (int) getNewMessages();
$userEsc = htmlspecialchars((string) $_SESSION['user'], ENT_QUOTES, 'UTF-8');
?>
<div class="msg-center" id="msg_center" data-user="<?php echo $userEsc; ?>">
	<header class="msg-center-head">
		<div class="msg-center-heading clic-brand-block">
			<p class="clic-legend">Choosology Labs Internal Communications</p>
			<h2 class="msg-center-title clic-wordmark">CLIC</h2>
		</div>
		<div class="msg-center-toolbar">
			<div class="clic-theme-picker" title="Preview CLIC visual themes">
				<span class="clic-theme-picker-label">Skin preview</span>
				<button type="button" class="clic-theme-opt is-active" data-clic-theme="amber">Amber ledger</button>
				<button type="button" class="clic-theme-opt" data-clic-theme="violet">Violet wire</button>
				<button type="button" class="clic-theme-opt" data-clic-theme="slate">Slate ticker</button>
			</div>
			<label class="msg-search-wrap">
				<span class="msg-search-label">Search</span>
				<input type="search" id="msg_search" class="msg-input" placeholder="Title, body, or handle…" autocomplete="off" />
			</label>
			<div class="msg-folder-rail" role="tablist" aria-label="Folder">
				<button type="button" class="msg-folder is-active" data-folder="inbox" role="tab" aria-selected="true">Inbox</button>
				<button type="button" class="msg-folder" data-folder="sent" role="tab" aria-selected="false">Sent</button>
			</div>
			<button type="button" class="msg-btn msg-btn--primary" id="msg_compose_open">Compose</button>
		</div>
	</header>

	<div class="msg-center-grid">
		<aside class="msg-list-pane" aria-label="Message list">
			<div class="msg-list-status" id="msg_list_status" aria-live="polite"><?php echo $unread > 0 ? $unread . ' unread' : 'All caught up'; ?></div>
			<ul class="msg-list" id="msg_list"></ul>
			<div class="msg-list-pager" id="msg_pager" hidden>
				<button type="button" class="msg-btn" id="msg_prev">Newer</button>
				<span id="msg_page_label"></span>
				<button type="button" class="msg-btn" id="msg_next">Older</button>
			</div>
		</aside>

		<section class="msg-read-pane" id="msg_read_pane" aria-live="polite">
			<div class="msg-read-empty" id="msg_read_empty">
				<p class="msg-read-empty-eyebrow">No selection</p>
				<p>Pick a message from the list, or compose a new one.</p>
			</div>
			<article class="msg-read" id="msg_read" hidden>
				<header class="msg-read-head">
					<p class="msg-read-meta" id="msg_read_meta"></p>
					<h3 class="msg-read-title" id="msg_read_title"></h3>
				</header>
				<div class="msg-read-body" id="msg_read_body"></div>
				<footer class="msg-read-actions">
					<button type="button" class="msg-btn msg-btn--primary" id="msg_reply_btn">Reply</button>
					<button type="button" class="msg-btn msg-btn--danger" id="msg_report_btn">Report</button>
				</footer>
			</article>
		</section>
	</div>

	<div class="msg-compose msg-compose--hidden" id="msg_compose" aria-hidden="true">
		<div class="msg-compose-backdrop" id="msg_compose_backdrop"></div>
		<div class="msg-compose-panel" role="dialog" aria-modal="true" aria-labelledby="msg_compose_title">
			<header class="msg-compose-header">
				<div>
					<p class="clic-legend clic-legend--compact">CLIC · Outbound</p>
					<h3 class="msg-compose-title" id="msg_compose_title">Compose</h3>
				</div>
				<button type="button" class="msg-compose-x" id="msg_compose_close" aria-label="Close">&times;</button>
			</header>
			<form id="msg_compose_form" class="msg-compose-form" onsubmit="return false;">
				<input type="hidden" id="msg_reply_to" value="" />
				<label class="msg-label" for="msg_to">To</label>
				<input type="text" id="msg_to" class="msg-input" maxlength="45" autocomplete="off" required />
				<label class="msg-label" for="msg_title">Subject</label>
				<input type="text" id="msg_title" class="msg-input" maxlength="55" />
				<label class="msg-label" for="msg_body">Message</label>
				<textarea id="msg_body" class="msg-textarea" rows="7" maxlength="4000" required></textarea>
				<div class="msg-compose-actions">
					<button type="button" class="msg-btn msg-btn--primary" id="msg_send">Send</button>
					<button type="button" class="msg-btn" id="msg_compose_cancel">Cancel</button>
					<span class="msg-compose-status" id="msg_compose_status" aria-live="polite"></span>
				</div>
			</form>
		</div>
	</div>

	<div class="msg-report msg-report--hidden" id="msg_report" aria-hidden="true">
		<div class="msg-compose-backdrop" id="msg_report_backdrop"></div>
		<div class="msg-compose-panel msg-compose-panel--narrow" role="dialog" aria-modal="true" aria-labelledby="msg_report_title">
			<header class="msg-compose-header">
				<div>
					<p class="clic-legend clic-legend--compact">CLIC · Escalation</p>
					<h3 class="msg-compose-title" id="msg_report_title">Report message</h3>
				</div>
				<button type="button" class="msg-compose-x" id="msg_report_close" aria-label="Close">&times;</button>
			</header>
			<form id="msg_report_form" onsubmit="return false;">
				<input type="hidden" id="msg_report_id" value="" />
				<p class="msg-hint">This sends the original message plus your note to Lab admins (usertype ≥ 1).</p>
				<label class="msg-label" for="msg_report_note">Why are you reporting?</label>
				<textarea id="msg_report_note" class="msg-textarea" rows="4" maxlength="1000"></textarea>
				<div class="msg-compose-actions">
					<button type="button" class="msg-btn msg-btn--danger" id="msg_report_send">Send report</button>
					<button type="button" class="msg-btn" id="msg_report_cancel">Cancel</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
(function () {
	function boot() {
		if (typeof ChoosologyMessages !== "undefined" && ChoosologyMessages.initCenter) {
			ChoosologyMessages.initCenter(document.getElementById("msg_center"));
			return true;
		}
		return false;
	}
	if (!boot()) {
		var s = document.createElement("script");
		s.src = (typeof choosologyUrl === "function") ? choosologyUrl("scripts/messages.js") : "scripts/messages.js";
		s.onload = boot;
		document.body.appendChild(s);
	}
})();
</script>
