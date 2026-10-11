/**
 * Floating lab clipboard — overlay in front of the current screen.
 * Auto-opens after login / return after CLIPBOARD_AUTO_HOURS.
 */
(function (window, $) {
	"use strict";

	var CLIPBOARD_AUTO_HOURS = 8;
	var STORAGE_DISMISS = "choosologyClipboardDismissedAt";
	var STORAGE_SKIP_NEXT_AUTO = "choosologyClipboardSkipNextAuto";

	function apiUrl() {
		if (typeof choosologyUrlSafeGlobal === "function") {
			return choosologyUrlSafeGlobal("ajax/clipboard.php");
		}
		if (typeof choosologyUrl === "function") {
			return choosologyUrl("ajax/clipboard.php");
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

	function esc(s) {
		return $("<div/>").text(String(s == null ? "" : s)).html();
	}

	function isLoggedIn() {
		return $("body").attr("data-logged-in") === "1" || $("#clipboard_office_btn").length > 0;
	}

	function prefersReducedMotion() {
		try {
			return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
		} catch (e) {
			return false;
		}
	}

	function ensureModal() {
		if ($("#clipboard_modal").length) {
			return;
		}
		var html =
			'<div id="clipboard_modal" class="clip-modal clip-modal--hidden" aria-hidden="true">' +
			'  <div class="clip-modal-backdrop" tabindex="-1"></div>' +
			'  <div class="clip-modal-panel" role="dialog" aria-modal="true" aria-labelledby="clipboard_modal_title">' +
			'    <div class="clip-modal-header">' +
			'      <div class="clip-modal-heading">' +
			'        <p class="clip-modal-eyebrow">Desk clipboard <span class="clip-modal-eyebrow-tag">personal</span></p>' +
			'        <h2 class="clip-modal-title" id="clipboard_modal_title">Clipboard</h2>' +
			'      </div>' +
			'      <button type="button" class="clip-modal-x" id="clipboard_modal_close" aria-label="Close clipboard">&times;</button>' +
			'    </div>' +
			'    <div class="clip-modal-body" id="clipboard_modal_body"></div>' +
			'  </div>' +
			'</div>';
		$("body").append(html);
		$("#clipboard_modal_close, #clipboard_modal .clip-modal-backdrop").on("click", close);
		$(document).on("keydown.clipboardModal", function (e) {
			if (e.key === "Escape" && !$("#clipboard_modal").hasClass("clip-modal--hidden")) {
				close();
			}
		});
	}

	function showLoadingState() {
		var $panel = $("#clipboard_modal .clip-modal-panel");
		$panel
			.addClass("clip-modal-panel--loading")
			.off("transitionend.clipExpand")
			.css("max-height", "");
		$("#clipboard_modal_body")
			.addClass("clip-modal-body--loading")
			.html('<div class="ajaxloader" role="status" aria-label="Loading"></div>');
	}

	/** After content is in the DOM, grow the panel from the compact loading height. */
	function expandPanelAfterLoad() {
		var $panel = $("#clipboard_modal .clip-modal-panel");
		var $body = $("#clipboard_modal_body");
		$body.removeClass("clip-modal-body--loading");
		if (!$panel.length) {
			return;
		}
		if (prefersReducedMotion()) {
			$panel.removeClass("clip-modal-panel--loading").css("max-height", "");
			return;
		}
		var startH = $panel.outerHeight();
		$panel.css("max-height", startH + "px");
		$panel.removeClass("clip-modal-panel--loading");
		var targetH = Math.min($panel.prop("scrollHeight") + 2, window.innerHeight - 80);
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				$panel.css("max-height", Math.max(startH, targetH) + "px");
			});
		});
		$panel.off("transitionend.clipExpand").on("transitionend.clipExpand", function (e) {
			if (e.target !== $panel[0]) {
				return;
			}
			$panel.off("transitionend.clipExpand").css("max-height", "");
		});
	}

	function renderTodos(todos) {
		if (!todos || !todos.length) {
			return '<p class="clip-empty" id="clip_todos_empty">No to-dos yet.</p><ul class="clip-todos" id="clip_todos"></ul>';
		}
		var html = '<ul class="clip-todos" id="clip_todos">';
		todos.forEach(function (t) {
			html +=
				'<li class="clip-todo' + (t.done ? " is-done" : "") + '" data-todo-id="' + esc(String(t.id)) + '">' +
				'<label class="clip-todo-label">' +
				'<input type="checkbox" class="clip-todo-check"' + (t.done ? " checked" : "") + ">" +
				"<span>" + esc(t.body) + "</span></label>" +
				'<button type="button" class="clip-btn clip-btn--ghost clip-btn--small" data-todo-delete>Remove</button>' +
				"</li>";
		});
		html += "</ul>";
		return html;
	}

	function renderChecklist(items) {
		var visible = (items || []).filter(function (it) {
			return !it.dismissed;
		});
		if (!visible.length) {
			return '<p class="clip-empty">Checklist clear. See Degrees for awards.</p>';
		}
		var html = '<ul class="clip-checklist" id="clip_checklist">';
		visible.forEach(function (it) {
			html +=
				'<li class="clip-check-item' + (it.completed ? " is-done" : "") + '" data-item-key="' + esc(it.key) + '">' +
				'<div class="clip-check-main">' +
				'<span class="clip-check-label">' + esc(it.label) + "</span>" +
				'<span class="clip-check-hint">' + esc(it.hint) + "</span>" +
				'</div><div class="clip-check-actions">';
			if (it.completed) {
				html += '<span class="clip-badge">Complete</span>';
			}
			html +=
				'<button type="button" class="clip-btn clip-btn--ghost clip-btn--small" data-check-dismiss>Dismiss</button>' +
				"</div></li>";
		});
		html += "</ul>";
		return html;
	}

	function renderHighLevelResults(metrics) {
		var rows = metrics || [];
		if (!rows.length) {
			return (
				'<p class="clip-empty">No experiment results yet. ' +
				'<a class="clip-link" href="#/mystuff/office">Open My Office</a> when you have published work.</p>'
			);
		}
		var top = rows.slice(0, 3);
		var html = '<ul class="clip-results-list">';
		top.forEach(function (m) {
			html +=
				'<li class="clip-results-item">' +
				'<div class="clip-results-title">' + esc(m.title || "Untitled") + "</div>" +
				'<div class="clip-results-meta">' +
				esc(String(m.play_starts || 0)) + " plays · " +
				esc(String(m.comments || 0)) + " comments · avg " +
				esc(String(m.rating || "NA")) +
				' · <a class="clip-link" href="#/view/' + esc(String(m.id)) + '">View</a>' +
				"</div></li>";
		});
		html += "</ul>";
		return html;
	}

	function datascripSignUrl(ds) {
		if (ds && ds.sign_url) {
			return String(ds.sign_url);
		}
		if (typeof choosologyUrlSafeGlobal === "function") {
			return choosologyUrlSafeGlobal("images/datascrip.png");
		}
		if (typeof choosologyUrl === "function") {
			return choosologyUrl("images/datascrip.png");
		}
		return "/images/datascrip.png";
	}

	function datascripIconHtml(ds) {
		return (
			'<img class="datascrip-sign" src="' + esc(datascripSignUrl(ds)) +
			'" alt="DataScrip" width="14" height="12" decoding="async">'
		);
	}

	function datascripAmountHtml(ds, amount) {
		var n = Math.abs(parseInt(amount, 10) || 0);
		return datascripIconHtml(ds) + "&nbsp;" + esc(String(n));
	}

	function renderLedger(datascrip) {
		var ds = datascrip || {};
		var balHtml = ds.formatted_html
			? String(ds.formatted_html)
			: datascripAmountHtml(ds, ds.balance || 0);
		var recent = ds.recent || [];
		var html =
			'<p class="clip-scrip-balance">Balance: <strong>' + balHtml + "</strong></p>";
		if (!recent.length) {
			html +=
				'<p class="clip-empty">No transactions yet. Daily check-ins, Degrees, and play earn DataScrip.</p>';
		} else {
			html += '<ul class="clip-ledger-list">';
			recent.forEach(function (row) {
				var amt = parseInt(row.amount, 10) || 0;
				var sign = amt >= 0 ? "+" : "−";
				html +=
					'<li class="clip-ledger-item">' +
					'<span class="clip-ledger-amt' + (amt >= 0 ? " is-credit" : " is-debit") + '">' +
					sign + datascripAmountHtml(ds, amt) + "</span>" +
					'<span class="clip-ledger-memo">' + esc(row.label || row.memo || row.reason_key || "") + "</span>" +
					"</li>";
			});
			html += "</ul>";
		}
		return html;
	}

	function renderBody(data) {
		var last = data.last_edited;
		var activityHtml;
		if (last && last.id) {
			activityHtml =
				'<p class="clip-activity-line">' +
				'<a class="clip-link" href="#/edit/' + esc(String(last.id)) + '">Return to last edited experiment</a>' +
				'<span class="clip-muted"> — ' + esc(last.title || "Untitled") + "</span></p>" +
				'<p class="clip-activity-line">' +
				'<a class="clip-link" href="#/view/' + esc(String(last.id)) + '">Preview</a>' +
				'<span class="clip-sep">·</span>' +
				'<a class="clip-link" href="#/mystuff/experiments">All experiments</a></p>';
		} else {
			activityHtml =
				'<p class="clip-empty">No experiments yet. ' +
				'<a class="clip-link" href="#/mystuff/experiments">Create one</a>.</p>';
		}

		var padBody = (data.pad && data.pad.body) ? data.pad.body : "";
		var html =
			'<section class="clip-section" aria-labelledby="clip_activity_h">' +
			'<h3 id="clip_activity_h">Recent activity</h3>' + activityHtml + "</section>" +
			'<section class="clip-section" aria-labelledby="clip_ledger_h">' +
			'<h3 id="clip_ledger_h">Ledger</h3>' +
			'<p class="clip-hint">Recent DataScrip movements.</p>' +
			renderLedger(data.datascrip) + "</section>" +
			'<section class="clip-section" aria-labelledby="clip_check_h">' +
			'<h3 id="clip_check_h">New researcher checklist</h3>' +
			'<p class="clip-hint">Complete each item by doing the task. Dismiss anything you skip.</p>' +
			renderChecklist(data.checklist) + "</section>" +
			'<section class="clip-section" aria-labelledby="clip_pad_h">' +
			'<h3 id="clip_pad_h">Personal notepad</h3>' +
			'<textarea id="clip_pad" class="clip-pad" rows="5" maxlength="50000">' + esc(padBody) + "</textarea>" +
			'<div class="clip-row clip-row--pad-save">' +
			'<span id="clip_pad_status" class="clip-status" aria-live="polite"></span>' +
			'<button type="button" class="clip-btn" id="clip_pad_save">Save note</button></div></section>' +
			'<section class="clip-section" aria-labelledby="clip_todo_h">' +
			'<h3 id="clip_todo_h">To-do list</h3>' +
			'<form id="clip_todo_form" class="clip-todo-form" onsubmit="return false;">' +
			'<input type="text" id="clip_todo_input" class="clip-input" maxlength="500" placeholder="Add a lab task…" autocomplete="off">' +
			'<button type="submit" class="clip-btn" id="clip_todo_add">Add</button></form>' +
			renderTodos(data.todos) + "</section>" +
			'<section class="clip-section" aria-labelledby="clip_results_h">' +
			'<h3 id="clip_results_h">Recent results</h3>' +
			renderHighLevelResults(data.metrics) + "</section>";

		$("#clipboard_modal_body").html(html);
		bindBodyHandlers();
	}

	function refreshTodos(todos) {
		var $section = $("#clip_todo_h").closest(".clip-section");
		$section.find("#clip_todos, #clip_todos_empty").remove();
		$section.append(renderTodos(todos));
	}

	function bindBodyHandlers() {
		$("#clip_pad_save").off("click").on("click", function () {
			var $status = $("#clip_pad_status").text("Saving…");
			post("save_pad", { body: $("#clip_pad").val() }).done(function (res) {
				$status.text(res && res.ok ? "Saved." : ((res && res.error) || "Could not save."));
			}).fail(function () {
				$status.text("Could not save.");
			});
		});

		$("#clip_todo_form").off("submit").on("submit", function (e) {
			e.preventDefault();
			var body = $.trim($("#clip_todo_input").val());
			if (!body) {
				return;
			}
			post("add_todo", { body: body }).done(function (res) {
				if (res && res.ok) {
					$("#clip_todo_input").val("");
					refreshTodos(res.todos || []);
				}
			});
		});

		$("#clipboard_modal_body").off("change.clipTodo").on("change.clipTodo", ".clip-todo-check", function () {
			var id = parseInt($(this).closest(".clip-todo").attr("data-todo-id"), 10) || 0;
			post("toggle_todo", { id: id, done: $(this).is(":checked") ? 1 : 0 }).done(function (res) {
				if (res && res.todos) {
					refreshTodos(res.todos);
				}
			});
		});
		$("#clipboard_modal_body").off("click.clipTodoDel").on("click.clipTodoDel", "[data-todo-delete]", function () {
			var id = parseInt($(this).closest(".clip-todo").attr("data-todo-id"), 10) || 0;
			post("delete_todo", { id: id }).done(function (res) {
				if (res && res.todos) {
					refreshTodos(res.todos);
				}
			});
		});
		$("#clipboard_modal_body").off("click.clipCheck").on("click.clipCheck", "[data-check-dismiss]", function () {
			var $item = $(this).closest(".clip-check-item");
			post("dismiss_checklist", { item_key: $item.attr("data-item-key") }).done(function (res) {
				if (res && res.ok) {
					$item.slideUp(120, function () {
						$(this).remove();
					});
				}
			});
		});
		$("#clipboard_modal_body").off("click.clipCheckDone");
		$("#clipboard_modal_body").off("click.clipNav").on("click.clipNav", "a.clip-link", function () {
			/* allow navigation; close overlay so destination is visible */
			close();
		});
	}

	function open(opts) {
		opts = opts || {};
		if (!isLoggedIn()) {
			return;
		}
		ensureModal();
		showLoadingState();
		$("#clipboard_modal").removeClass("clip-modal--hidden").attr("aria-hidden", "false");
		$("body").addClass("clipboard-modal-open");
		var openedAt = Date.now();
		/* Keep the spinner visible briefly so the expand animation can read on fast local loads. */
		var MIN_LOAD_MS = prefersReducedMotion() ? 0 : 320;
		function finishOpen(renderFn) {
			var wait = Math.max(0, MIN_LOAD_MS - (Date.now() - openedAt));
			window.setTimeout(function () {
				if ($("#clipboard_modal").hasClass("clip-modal--hidden")) {
					return;
				}
				renderFn();
				expandPanelAfterLoad();
			}, wait);
		}
		post("summary").done(function (res) {
			finishOpen(function () {
				if (!res || !res.ok) {
					$("#clipboard_modal_body").html(
						'<p class="clip-hint">' + esc((res && res.error) || "Could not load clipboard.") + "</p>"
					);
					return;
				}
				renderBody(res);
				post("visit_clipboard");
			});
		}).fail(function () {
			finishOpen(function () {
				$("#clipboard_modal_body").html('<p class="clip-hint">Network error loading clipboard.</p>');
			});
		});
		if (!opts.auto) {
			try {
				sessionStorage.setItem(STORAGE_SKIP_NEXT_AUTO, "1");
			} catch (e) {}
		}
	}

	function close() {
		var $panel = $("#clipboard_modal .clip-modal-panel");
		$panel.off("transitionend.clipExpand").removeClass("clip-modal-panel--loading").css("max-height", "");
		$("#clipboard_modal").addClass("clip-modal--hidden").attr("aria-hidden", "true");
		$("body").removeClass("clipboard-modal-open");
		try {
			localStorage.setItem(STORAGE_DISMISS, String(Date.now()));
		} catch (e) {}
	}

	function shouldAutoOpen() {
		if (!isLoggedIn()) {
			return false;
		}
		try {
			if (sessionStorage.getItem(STORAGE_SKIP_NEXT_AUTO) === "1") {
				sessionStorage.removeItem(STORAGE_SKIP_NEXT_AUTO);
				return false;
			}
		} catch (e1) {}
		try {
			var raw = localStorage.getItem(STORAGE_DISMISS);
			if (!raw) {
				return true;
			}
			var ts = parseInt(raw, 10);
			if (!ts) {
				return true;
			}
			var hours = (Date.now() - ts) / (1000 * 60 * 60);
			return hours >= CLIPBOARD_AUTO_HOURS;
		} catch (e2) {
			return true;
		}
	}

	function maybeAutoOpen() {
		if (!shouldAutoOpen()) {
			return;
		}
		window.setTimeout(function () {
			open({ auto: true });
		}, 450);
	}

	/** Call after a successful login before/while reloading. */
	function markForceAutoOpenOnNextLoad() {
		try {
			localStorage.removeItem(STORAGE_DISMISS);
			sessionStorage.removeItem(STORAGE_SKIP_NEXT_AUTO);
		} catch (e) {}
	}

	window.ChoosologyClipboard = {
		open: open,
		close: close,
		maybeAutoOpen: maybeAutoOpen,
		markForceAutoOpenOnNextLoad: markForceAutoOpenOnNextLoad,
		shouldAutoOpen: shouldAutoOpen
	};

	$(function () {
		$(document).on("click", "#clipboard_office_btn", function (e) {
			e.preventDefault();
			e.stopPropagation();
			open({ auto: false });
		});
		maybeAutoOpen();
	});
})(window, jQuery);
