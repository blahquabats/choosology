/**
 * Choosology messaging UI — inbox center + slim recent-messages modal.
 */
(function (window, $) {
	"use strict";

	function apiUrl() {
		if (typeof choosologyUrlSafe === "function") {
			return choosologyUrlSafe("ajax/messages.php");
		}
		if (typeof choosologyUrl === "function") {
			return choosologyUrl("ajax/messages.php");
		}
		return "ajax/messages.php";
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

	var CLIC_THEMES = ["amber", "violet", "slate"];

	function getClicTheme() {
		try {
			var stored = localStorage.getItem("clicTheme");
			if (stored && CLIC_THEMES.indexOf(stored) >= 0) {
				return stored;
			}
		} catch (ignore) {}
		return "amber";
	}

	function applyClicTheme(theme) {
		theme = CLIC_THEMES.indexOf(theme) >= 0 ? theme : "amber";
		try {
			localStorage.setItem("clicTheme", theme);
		} catch (ignore) {}
		$("#msg_center, #msg_quick_modal, #msg_login_notify_btn").attr("data-clic-theme", theme);
		$(".clic-theme-opt").removeClass("is-active").filter('[data-clic-theme="' + theme + '"]').addClass("is-active");
	}

	function bindClicThemePicker(root) {
		if (!root) {
			return;
		}
		root.addEventListener("click", function (e) {
			var btn = e.target.closest(".clic-theme-opt");
			if (!btn) {
				return;
			}
			applyClicTheme(btn.getAttribute("data-clic-theme"));
		});
	}

	function updateBadges(unread) {
		var n = parseInt(unread, 10) || 0;
		var label = n > 0 ? ("CLIC (" + n + ")") : "CLIC";
		$("#mystuff-messages").text(label);
		var $badge = $("#msg_unread_badge");
		if (!$badge.length) {
			return;
		}
		if (n > 0) {
			$badge.text(n > 99 ? "99+" : String(n)).removeAttr("hidden").attr("aria-hidden", "false");
			$badge.closest(".msg-login-notify").attr("data-unread", String(n));
		} else {
			$badge.attr("hidden", "hidden").attr("aria-hidden", "true").text("");
			$badge.closest(".msg-login-notify").attr("data-unread", "0");
		}
	}

	function refreshUnread() {
		return post("unread").done(function (res) {
			if (res && res.ok) {
				updateBadges(res.unread);
			}
		});
	}

	/* ---------- Full Messages center (My Stuff tab) ---------- */
	function initCenter(root) {
		if (!root || root.getAttribute("data-msg-bound") === "1") {
			return;
		}
		root.setAttribute("data-msg-bound", "1");
		applyClicTheme(getClicTheme());
		bindClicThemePicker(root);
		var state = {
			folder: "inbox",
			page: 1,
			q: "",
			selectedId: 0,
			current: null
		};

		function loadList() {
			$("#msg_list_status").text("Loading…");
			post("list", { folder: state.folder, page: state.page, q: state.q }).done(function (res) {
				if (!res || !res.ok) {
					$("#msg_list_status").text((res && res.error) || "Failed to load.");
					return;
				}
				updateBadges(res.unread);
				var items = res.items || [];
				var $ul = $("#msg_list").empty();
				if (!items.length) {
					$("#msg_list_status").text(state.q ? "No matches." : "No messages here yet.");
				} else {
					var unreadN = items.filter(function (it) { return !it.seen && state.folder === "inbox"; }).length;
					$("#msg_list_status").text(
						(res.total || items.length) + " message" + ((res.total || items.length) === 1 ? "" : "s") +
						(state.folder === "inbox" && res.unread ? (" · " + res.unread + " unread") : "")
					);
					items.forEach(function (it) {
						var $li = $("<li/>")
							.addClass("msg-list-item" + (!it.seen && state.folder === "inbox" ? " is-unread" : "") + (it.id === state.selectedId ? " is-selected" : ""))
							.attr("data-id", it.id)
							.append($("<span class='msg-list-from'/>").text(state.folder === "sent" ? ("To " + it.to) : it.from))
							.append($("<span class='msg-list-title'/>").text(it.title || "(no subject)"))
							.append($("<span class='msg-list-preview'/>").text(it.preview || ""))
							.append($("<time class='msg-list-time'/>").text(it.sent || ""));
						$ul.append($li);
					});
				}
				var pages = Math.max(1, Math.ceil((res.total || 0) / 25));
				if (pages > 1) {
					$("#msg_pager").removeAttr("hidden");
					$("#msg_page_label").text("Page " + state.page + " / " + pages);
					$("#msg_prev").prop("disabled", state.page <= 1);
					$("#msg_next").prop("disabled", state.page >= pages);
				} else {
					$("#msg_pager").attr("hidden", "hidden");
				}
			}).fail(function () {
				$("#msg_list_status").text("Network error.");
			});
		}

		function showRead(msg) {
			state.current = msg;
			$("#msg_read_empty").attr("hidden", "hidden");
			$("#msg_read").removeAttr("hidden");
			$("#msg_read_meta").html(
				esc(msg.from) + " → " + esc(msg.to) + " · " + esc(msg.sent) +
				(msg.type && msg.type !== "normal" ? (" · <span class='msg-type-pill'>" + esc(msg.type) + "</span>") : "")
			);
			$("#msg_read_title").text(msg.title || "(no subject)");
			$("#msg_read_body").html(msg.body || "");
			$("#msg_reply_btn").toggle(!!msg.can_reply);
			$("#msg_report_btn").toggle(!!msg.can_report);
		}

		function openMessage(id) {
			state.selectedId = id;
			post("get", { id: id }).done(function (res) {
				if (!res || !res.ok) {
					if (typeof showAlert === "function") {
						showAlert((res && res.error) || "Could not open message.", "error");
					}
					return;
				}
				updateBadges(res.unread);
				showRead(res.message);
				loadList();
			});
		}

		function openCompose(opts) {
			opts = opts || {};
			$("#msg_reply_to").val(opts.replyTo || "");
			$("#msg_to").val(opts.to || "").prop("readonly", !!opts.replyTo);
			$("#msg_title").val(opts.title || "");
			$("#msg_body").val(opts.body || "");
			$("#msg_compose_status").text("");
			$("#msg_compose_title").text(opts.replyTo ? "Reply" : "Compose");
			$("#msg_compose").removeClass("msg-compose--hidden").attr("aria-hidden", "false");
		}

		function closeCompose() {
			$("#msg_compose").addClass("msg-compose--hidden").attr("aria-hidden", "true");
		}

		function openReport(id) {
			$("#msg_report_id").val(String(id));
			$("#msg_report_note").val("");
			$("#msg_report").removeClass("msg-report--hidden").attr("aria-hidden", "false");
		}

		function closeReport() {
			$("#msg_report").addClass("msg-report--hidden").attr("aria-hidden", "true");
		}

		$(".msg-folder", root).on("click", function () {
			$(".msg-folder", root).removeClass("is-active").attr("aria-selected", "false");
			$(this).addClass("is-active").attr("aria-selected", "true");
			state.folder = $(this).attr("data-folder") || "inbox";
			state.page = 1;
			loadList();
		});

		var searchTimer = null;
		$("#msg_search").on("input", function () {
			var v = String($(this).val() || "");
			clearTimeout(searchTimer);
			searchTimer = setTimeout(function () {
				state.q = v.trim();
				state.page = 1;
				loadList();
			}, 280);
		});

		$("#msg_list").on("click", ".msg-list-item", function () {
			var id = parseInt($(this).attr("data-id"), 10) || 0;
			if (id) {
				openMessage(id);
			}
		});

		$("#msg_prev").on("click", function () {
			if (state.page > 1) {
				state.page--;
				loadList();
			}
		});
		$("#msg_next").on("click", function () {
			state.page++;
			loadList();
		});

		$("#msg_compose_open").on("click", function () {
			openCompose({});
		});
		$("#msg_compose_close, #msg_compose_cancel, #msg_compose_backdrop").on("click", closeCompose);

		$("#msg_reply_btn").on("click", function () {
			if (!state.current) {
				return;
			}
			openCompose({
				replyTo: state.current.id,
				to: state.current.from,
				title: /^re:/i.test(state.current.title || "") ? state.current.title : ("Re: " + (state.current.title || ""))
			});
		});

		$("#msg_report_btn").on("click", function () {
			if (!state.current) {
				return;
			}
			openReport(state.current.id);
		});
		$("#msg_report_close, #msg_report_cancel, #msg_report_backdrop").on("click", closeReport);

		$("#msg_send").on("click", function () {
			var payload = {
				to: String($("#msg_to").val() || "").trim(),
				title: String($("#msg_title").val() || "").trim(),
				body: String($("#msg_body").val() || "").trim(),
				reply_to: parseInt($("#msg_reply_to").val(), 10) || 0
			};
			$("#msg_compose_status").text("Sending…");
			post("send", payload).done(function (res) {
				if (!res || !res.ok) {
					$("#msg_compose_status").text((res && res.error) || "Send failed.");
					return;
				}
				$("#msg_compose_status").text("Sent.");
				closeCompose();
				if (typeof showAlert === "function") {
					showAlert("Message sent.", "success");
				}
				state.folder = "sent";
				$(".msg-folder", root).removeClass("is-active").attr("aria-selected", "false");
				$(".msg-folder[data-folder='sent']", root).addClass("is-active").attr("aria-selected", "true");
				loadList();
			}).fail(function () {
				$("#msg_compose_status").text("Network error.");
			});
		});

		$("#msg_report_send").on("click", function () {
			var id = parseInt($("#msg_report_id").val(), 10) || 0;
			post("report", { id: id, note: String($("#msg_report_note").val() || "").trim() }).done(function (res) {
				if (!res || !res.ok) {
					if (typeof showAlert === "function") {
						showAlert((res && res.error) || "Report failed.", "error");
					}
					return;
				}
				closeReport();
				if (typeof showAlert === "function") {
					showAlert("Report sent to admins.", "success");
				}
			});
		});

		// Deep-link ?id= / hash query
		var m = (window.location.hash || "").match(/[?&]id=(\d+)/);
		if (m) {
			state.selectedId = parseInt(m[1], 10) || 0;
		}

		loadList();
		if (state.selectedId) {
			openMessage(state.selectedId);
		}
	}

	/* ---------- Slim modal ---------- */
	function ensureModal() {
		if ($("#msg_quick_modal").length) {
			return;
		}
		var html =
			'<div id="msg_quick_modal" class="msg-quick msg-quick--hidden" aria-hidden="true">' +
			'  <div class="msg-quick-backdrop" tabindex="-1"></div>' +
			'  <div class="msg-quick-panel" role="dialog" aria-modal="true" aria-labelledby="msg_quick_title">' +
			'    <div class="msg-quick-header">' +
			'      <div><p class="clic-legend clic-legend--compact">CLIC</p>' +
			'      <h2 class="msg-quick-title" id="msg_quick_title">Recent</h2></div>' +
			'      <button type="button" class="msg-compose-x" id="msg_quick_close" aria-label="Close">&times;</button>' +
			'    </div>' +
			'    <div class="msg-quick-body" id="msg_quick_body"></div>' +
			'    <div class="msg-quick-footer">' +
			'      <a class="msg-quick-link" id="msg_quick_inbox" href="#/mystuff/messages">Open full inbox</a>' +
			'    </div>' +
			'  </div>' +
			'</div>';
		$("body").append(html);
		$("#msg_quick_close, #msg_quick_modal .msg-quick-backdrop").on("click", closeModal);
		$(document).on("keydown.msgQuick", function (e) {
			if (e.key === "Escape" && !$("#msg_quick_modal").hasClass("msg-quick--hidden")) {
				closeModal();
			}
		});
	}

	function openModal() {
		ensureModal();
		applyClicTheme(getClicTheme());
		$("#msg_quick_body").html("<p class='msg-hint'>Loading…</p>");
		$("#msg_quick_modal").removeClass("msg-quick--hidden").attr("aria-hidden", "false");
		$("body").addClass("msg-quick-open");
		post("recent", { limit: 8 }).done(function (res) {
			if (!res || !res.ok) {
				$("#msg_quick_body").html("<p class='msg-hint'>" + esc((res && res.error) || "Could not load.") + "</p>");
				return;
			}
			updateBadges(res.unread);
			var items = res.items || [];
			if (!items.length) {
				$("#msg_quick_body").html("<p class='msg-hint'>No messages yet.</p>");
				return;
			}
			var $list = $("<ul class='msg-quick-list'/>");
			items.forEach(function (it) {
				var $li = $("<li class='msg-quick-item" + (it.seen ? "" : " is-unread") + "'/>");
				$li.append($("<div class='msg-quick-item-top'/>")
					.append($("<strong/>").text(it.from))
					.append($("<time/>").text(it.sent || "")));
				$li.append($("<div class='msg-quick-item-title'/>").text(it.title || "(no subject)"));
				$li.append($("<div class='msg-quick-item-preview'/>").text(it.preview || ""));
				var $actions = $("<div class='msg-quick-item-actions'/>");
				$actions.append(
					$("<a class='msg-quick-link'/>")
						.attr("href", "#/mystuff/messages?id=" + it.id)
						.text("Open / reply")
						.on("click", closeModal)
				);
				$actions.append($("<span class='msg-quick-sep'/>").text("·"));
				$actions.append(
					$("<a class='msg-quick-link msg-quick-link--danger'/>")
						.attr("href", "#/mystuff/messages?id=" + it.id)
						.text("Report")
						.on("click", closeModal)
				);
				$li.append($actions);
				$list.append($li);
			});
			$("#msg_quick_body").empty().append($list);
		}).fail(function () {
			$("#msg_quick_body").html("<p class='msg-hint'>Network error.</p>");
		});
	}

	function closeModal() {
		$("#msg_quick_modal").addClass("msg-quick--hidden").attr("aria-hidden", "true");
		$("body").removeClass("msg-quick-open");
	}

	window.ChoosologyMessages = {
		initCenter: initCenter,
		openModal: openModal,
		closeModal: closeModal,
		refreshUnread: refreshUnread,
		updateBadges: updateBadges,
		post: post
	};

	$(function () {
		applyClicTheme(getClicTheme());
		$(document).on("click", "#msg_unread_badge, #msg_login_notify_btn", function (e) {
			e.preventDefault();
			e.stopPropagation();
			openModal();
		});
		if ($("body").attr("data-logged-in") === "1") {
			refreshUnread();
		}
	});
})(window, jQuery);
