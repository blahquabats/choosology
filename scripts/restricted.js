/**
 * My Office → RESTRICTED admin hub.
 */
(function (window, $) {
	"use strict";

	var root = null;
	var state = null;

	function apiUrl() {
		if (typeof window.choosologyUrl === "function") {
			return window.choosologyUrl("ajax/restricted.php");
		}
		return "/ajax/restricted.php";
	}

	function post(action, payload) {
		return $.ajax({
			url: apiUrl(),
			method: "POST",
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: JSON.stringify($.extend({ action: action }, payload || {}))
		});
	}

	function value(id) {
		return String(root.find(id).val() || "").trim();
	}

	function status(message, isError) {
		root.find("#restricted_global_status")
			.toggleClass("is-error", !!isError)
			.text(message || "");
	}

	function errorText(xhr, fallback) {
		return (xhr && xhr.responseJSON && xhr.responseJSON.error) || fallback;
	}

	function fillOptions($select, rows, selected, emptyLabel) {
		$select.empty();
		if (emptyLabel !== undefined) {
			$("<option>").val("").text(emptyLabel).appendTo($select);
		}
		(rows || []).forEach(function (row) {
			$("<option>").val(row.value).text(row.label).appendTo($select);
		});
		if (selected !== undefined) {
			$select.val(selected);
		}
	}

	function linesFrom(text) {
		return String(text || "").split(/\r?\n/).map(function (line) {
			return line.trim();
		}).filter(Boolean);
	}

	function currentMascot() {
		return {
			key: value("#restricted_mascot_key"),
			display_name: value("#restricted_mascot_name"),
			enabled: root.find("#restricted_mascot_enabled").is(":checked"),
			is_default: root.find("#restricted_mascot_default").is(":checked"),
			sort_order: parseInt(value("#restricted_mascot_sort"), 10) || 0,
			theme: {
				accent: value("#restricted_theme_accent"),
				paper: value("#restricted_theme_paper"),
				ink: value("#restricted_theme_ink")
			},
			portraits: {
				idle: value("#restricted_portrait_idle"),
				offer: value("#restricted_portrait_offer"),
				explain: value("#restricted_portrait_explain"),
				done: value("#restricted_portrait_done")
			},
			lines: {
				offer_help: linesFrom(root.find("#restricted_lines_offer").val()),
				explain: linesFrom(root.find("#restricted_lines_explain").val()),
				done: linesFrom(root.find("#restricted_lines_done").val())
			},
			choices: {
				accept: value("#restricted_choice_accept"),
				decline: value("#restricted_choice_decline"),
				later: value("#restricted_choice_later"),
				done: value("#restricted_choice_done"),
				next: value("#restricted_choice_next")
			}
		};
	}

	function blankMascot() {
		return {
			key: "",
			display_name: "",
			enabled: true,
			is_default: false,
			sort_order: 100,
			theme: { accent: "#c4a35a", paper: "#f4ecd8", ink: "#1c1a16" },
			portraits: { idle: "", offer: "", explain: "", done: "" },
			lines: {
				offer_help: ["Looks like this is your first time with {feature}. Want a hand?"],
				explain: ["{body}"],
				done: ["That is the lot. You can keep going from here."]
			},
			choices: {
				accept: "Show me",
				decline: "I'll manage",
				later: "Later",
				done: "Got it",
				next: "Next"
			}
		};
	}

	function showMascot(mascot) {
		mascot = mascot || blankMascot();
		root.find("#restricted_mascot_key").val(mascot.key || "");
		root.find("#restricted_mascot_name").val(mascot.display_name || "");
		root.find("#restricted_mascot_sort").val(mascot.sort_order || 0);
		root.find("#restricted_mascot_enabled").prop("checked", !!mascot.enabled);
		root.find("#restricted_mascot_default").prop("checked", !!mascot.is_default);
		["accent", "paper", "ink"].forEach(function (token) {
			root.find("#restricted_theme_" + token).val((mascot.theme || {})[token] || "");
		});
		["idle", "offer", "explain", "done"].forEach(function (pose) {
			root.find("#restricted_portrait_" + pose).val((mascot.portraits || {})[pose] || "");
		});
		root.find("#restricted_lines_offer").val(((mascot.lines || {}).offer_help || []).join("\n"));
		root.find("#restricted_lines_explain").val(((mascot.lines || {}).explain || []).join("\n"));
		root.find("#restricted_lines_done").val(((mascot.lines || {}).done || []).join("\n"));
		["accept", "decline", "later", "done", "next"].forEach(function (choice) {
			root.find("#restricted_choice_" + choice).val((mascot.choices || {})[choice] || "");
		});
	}

	function beatRow(beat) {
		beat = beat || {
			id: "beat",
			role: "explain",
			pose: "explain",
			body: "",
			choices: ["done"]
		};
		var $row = $("<div>").addClass("restricted-beat");
		var $top = $("<div>").addClass("restricted-beat-top").appendTo($row);
		$("<label>").text("ID ").append($("<input>").addClass("restricted-beat-id").val(beat.id || "")).appendTo($top);
		var $role = $("<select>").addClass("restricted-beat-role");
		["offer_help", "explain", "done"].forEach(function (role) {
			$("<option>").val(role).text(role).appendTo($role);
		});
		$role.val(beat.role || "explain");
		$("<label>").text("Role ").append($role).appendTo($top);
		$("<label>").text("Pose ").append($("<input>").addClass("restricted-beat-pose").val(beat.pose || "")).appendTo($top);
		$("<button>").attr("type", "button").addClass("restricted-btn restricted-btn--danger restricted-beat-remove").text("Remove").appendTo($top);
		$("<label>").text("Body ").append(
			$("<textarea>").addClass("restricted-beat-body").attr("rows", 3).val(beat.body || "")
		).appendTo($row);
		$("<label>").text("Choices (comma separated) ").append(
			$("<input>").addClass("restricted-beat-choices").val((beat.choices || []).join(", "))
		).appendTo($row);
		return $row;
	}

	function renderBeats(beats) {
		var $wrap = root.find("#restricted_beats").empty();
		(beats || []).forEach(function (beat) {
			$wrap.append(beatRow(beat));
		});
		if (!$wrap.children().length) {
			$wrap.append(beatRow({
				id: "offer",
				role: "offer_help",
				pose: "offer",
				body: "",
				choices: ["accept", "later", "decline"]
			}));
		}
	}

	function currentInteraction() {
		var beats = [];
		root.find(".restricted-beat").each(function () {
			var $beat = $(this);
			beats.push({
				id: String($beat.find(".restricted-beat-id").val() || "").trim(),
				role: String($beat.find(".restricted-beat-role").val() || "").trim(),
				pose: String($beat.find(".restricted-beat-pose").val() || "").trim(),
				body: String($beat.find(".restricted-beat-body").val() || "").trim(),
				choices: String($beat.find(".restricted-beat-choices").val() || "")
					.split(",").map(function (choice) { return choice.trim(); }).filter(Boolean)
			});
		});
		return {
			key: value("#restricted_interaction_key"),
			title: value("#restricted_interaction_title"),
			event_key: value("#restricted_interaction_event"),
			feature_key: value("#restricted_interaction_feature"),
			surface: value("#restricted_interaction_surface"),
			audience: value("#restricted_interaction_audience"),
			mascot_key: value("#restricted_interaction_mascot"),
			priority: parseInt(value("#restricted_interaction_priority"), 10) || 100,
			enabled: root.find("#restricted_interaction_enabled").is(":checked"),
			beats: beats
		};
	}

	function blankInteraction() {
		return {
			key: "",
			title: "",
			event_key: "feature_first_seen",
			feature_key: "graph_editor",
			surface: "classic",
			audience: "both",
			mascot_key: state && state.mascots[0] ? state.mascots[0].key : "",
			priority: 100,
			enabled: true,
			beats: [
				{ id: "offer", role: "offer_help", pose: "offer", body: "", choices: ["accept", "later", "decline"] },
				{ id: "explain", role: "explain", pose: "explain", body: "", choices: ["done"] }
			]
		};
	}

	function showInteraction(interaction) {
		interaction = interaction || blankInteraction();
		root.find("#restricted_interaction_key").val(interaction.key || "");
		root.find("#restricted_interaction_title").val(interaction.title || "");
		root.find("#restricted_interaction_event").val(interaction.event_key || "");
		root.find("#restricted_interaction_feature").val(interaction.feature_key || "");
		root.find("#restricted_interaction_surface").val(interaction.surface || "classic");
		root.find("#restricted_interaction_audience").val(interaction.audience || "both");
		root.find("#restricted_interaction_mascot").val(interaction.mascot_key || "");
		root.find("#restricted_interaction_priority").val(interaction.priority || 100);
		root.find("#restricted_interaction_enabled").prop("checked", !!interaction.enabled);
		renderBeats(interaction.beats || []);
	}

	function findByKey(rows, key) {
		var found = null;
		(rows || []).some(function (row) {
			if (String(row.key) === String(key)) {
				found = row;
				return true;
			}
			return false;
		});
		return found;
	}

	function renderComments(comments) {
		var $wrap = root.find("#restricted_comments").empty();
		root.find("#restricted_comment_badge").text(comments.length ? "(" + comments.length + ")" : "");
		if (!comments.length) {
			$wrap.append($("<p>").addClass("restricted-empty").text("Review queue clear."));
			return;
		}
		comments.forEach(function (comment) {
			var $item = $("<article>").addClass("restricted-comment").attr("data-comment-id", comment.id);
			$("<p>").addClass("restricted-comment-meta").text(
				comment.author + " · " + comment.date + " · " + comment.board
			).appendTo($item);
			$("<p>").addClass("restricted-comment-text").text(comment.text).appendTo($item);
			var $actions = $("<div>").addClass("restricted-actions").appendTo($item);
			$("<button>").attr("type", "button").addClass("restricted-btn restricted-comment-approve").text("Approve").appendTo($actions);
			$("<button>").attr("type", "button").addClass("restricted-btn restricted-btn--danger restricted-comment-delete").text("Delete").appendTo($actions);
			$wrap.append($item);
		});
	}

	function renderState(next, selectedMascot, selectedInteraction) {
		state = next;
		var users = (state.users || []).map(function (name) {
			return { value: name, label: name };
		});
		var $users = root.find("#restricted_users").empty();
		users.forEach(function (user) {
			$("<option>").val(user.value).appendTo($users);
		});
		var mascotRows = (state.mascots || []).map(function (m) {
			return { value: m.key, label: m.display_name + " [" + m.key + "]" };
		});
		selectedMascot = selectedMascot || (state.mascots[0] && state.mascots[0].key) || "";
		fillOptions(root.find("#restricted_mascot_select"), mascotRows, selectedMascot);
		fillOptions(root.find("#restricted_interaction_mascot"), mascotRows);
		showMascot(findByKey(state.mascots, selectedMascot));

		var interactionRows = (state.interactions || []).map(function (i) {
			return { value: i.key, label: i.title + " [" + i.key + "]" };
		});
		selectedInteraction = selectedInteraction || (state.interactions[0] && state.interactions[0].key) || "";
		fillOptions(root.find("#restricted_interaction_select"), interactionRows, selectedInteraction);

		var eventRows = Object.keys(state.events || {}).map(function (key) {
			return { value: key, label: state.events[key].label + " [" + key + "]" };
		});
		fillOptions(root.find("#restricted_interaction_event"), eventRows);
		var featureRows = Object.keys(state.features || {}).map(function (key) {
			return { value: key, label: state.features[key].label + " [" + key + "]" };
		});
		fillOptions(root.find("#restricted_interaction_feature"), featureRows, undefined, "Any registered feature");
		showInteraction(findByKey(state.interactions, selectedInteraction));

		var degreeRows = Object.keys(state.degrees || {}).map(function (key) {
			return { value: key, label: state.degrees[key].label + " — " + state.degrees[key].blurb };
		});
		fillOptions(root.find("#restricted_degree_key"), degreeRows);
		renderComments(state.comments || []);
	}

	function reload(selectedMascot, selectedInteraction) {
		status("Loading authorization records…");
		return post("state").done(function (res) {
			if (!res || !res.ok) {
				status((res && res.error) || "Could not load restricted records.", true);
				return;
			}
			renderState(res, selectedMascot, selectedInteraction);
			status("");
		}).fail(function (xhr) {
			status(errorText(xhr, "Network error loading restricted records."), true);
		});
	}

	function mutate(action, payload, success, selection) {
		status("Saving…");
		post(action, payload).done(function (res) {
			if (!res || !res.ok) {
				status((res && res.error) || "Action failed.", true);
				return;
			}
			status(success || "Saved.");
			reload(selection && selection.mascot, selection && selection.interaction);
		}).fail(function (xhr) {
			status(errorText(xhr, "Network error."), true);
		});
	}

	function bind() {
		root.off(".restricted");
		root.on("click.restricted", "[data-restricted-view]", function () {
			var view = $(this).attr("data-restricted-view");
			root.find("[data-restricted-view]").removeClass("is-active");
			$(this).addClass("is-active");
			root.find("[data-restricted-panel]").removeClass("is-active").attr("hidden", true);
			root.find('[data-restricted-panel="' + view + '"]').addClass("is-active").removeAttr("hidden");
		});

		root.on("change.restricted", "#restricted_mascot_select", function () {
			showMascot(findByKey(state.mascots, $(this).val()));
		});
		root.on("click.restricted", "#restricted_mascot_new", function () {
			root.find("#restricted_mascot_select").val("");
			showMascot(blankMascot());
		});
		root.on("click.restricted", "#restricted_mascot_save", function () {
			var mascot = currentMascot();
			mutate("save_mascot", { mascot: mascot }, "Mascot saved.", { mascot: mascot.key });
		});
		root.on("click.restricted", "#restricted_mascot_reset", function () {
			var key = value("#restricted_mascot_key");
			if (key && window.confirm("Reset " + key + " to its built-in definition?")) {
				mutate("reset_mascot", { key: key }, "Mascot reset.", { mascot: key });
			}
		});
		root.on("click.restricted", "#restricted_mascot_delete", function () {
			var key = value("#restricted_mascot_key");
			if (key && window.confirm("Delete custom mascot " + key + "?")) {
				mutate("delete_mascot", { key: key }, "Mascot deleted.");
			}
		});

		root.on("change.restricted", "#restricted_interaction_select", function () {
			showInteraction(findByKey(state.interactions, $(this).val()));
		});
		root.on("click.restricted", "#restricted_interaction_new", function () {
			root.find("#restricted_interaction_select").val("");
			showInteraction(blankInteraction());
		});
		root.on("click.restricted", "#restricted_beat_add", function () {
			root.find("#restricted_beats").append(beatRow());
		});
		root.on("click.restricted", ".restricted-beat-remove", function () {
			$(this).closest(".restricted-beat").remove();
		});
		root.on("click.restricted", "#restricted_interaction_save", function () {
			var interaction = currentInteraction();
			mutate("save_interaction", { interaction: interaction }, "Interaction saved.", {
				interaction: interaction.key,
				mascot: value("#restricted_mascot_select")
			});
		});
		root.on("click.restricted", "#restricted_interaction_reset", function () {
			var key = value("#restricted_interaction_key");
			if (key && window.confirm("Reset " + key + " to its built-in definition?")) {
				mutate("reset_interaction", { key: key }, "Interaction reset.", { interaction: key });
			}
		});
		root.on("click.restricted", "#restricted_interaction_delete", function () {
			var key = value("#restricted_interaction_key");
			if (key && window.confirm("Delete custom interaction " + key + "?")) {
				mutate("delete_interaction", { key: key }, "Interaction deleted.");
			}
		});
		root.on("click.restricted", "#restricted_interaction_preview", function () {
			status("Rendering preview…");
			post("preview_interaction", { interaction: currentInteraction() }).done(function (res) {
				if (!res || !res.ok) {
					status((res && res.error) || "Preview failed.", true);
					return;
				}
				root.find("#restricted_preview").html(res.html || "");
				status("Preview does not change anyone's progress.");
			}).fail(function (xhr) {
				status(errorText(xhr, "Preview failed."), true);
			});
		});
		root.on("click.restricted", "#restricted_reset_progress", function () {
			if (window.confirm("Clear your own guide history so first-visit offers can run again?")) {
				mutate("reset_my_guide_progress", {}, "Your guide history was reset.");
			}
		});

		root.on("submit.restricted", "#restricted_degree_form", function () {
			var to = value("#restricted_degree_user");
			var degree = value("#restricted_degree_key");
			mutate("award_degree", { to: to, degree: degree }, "Degree granted to " + to + ".");
		});
		root.on("change.restricted", "input[name='restricted_scrip_scope']", function () {
			var all = root.find("input[name='restricted_scrip_scope']:checked").val() === "all";
			root.find("#restricted_scrip_user").prop("disabled", all);
		});
		root.on("submit.restricted", "#restricted_scrip_form", function () {
			var scope = root.find("input[name='restricted_scrip_scope']:checked").val() || "one";
			if (scope === "all" && !window.confirm("Award DataScrip to every account?")) {
				return;
			}
			mutate("award_scrip", {
				scope: scope,
				to: value("#restricted_scrip_user"),
				amount: parseInt(value("#restricted_scrip_amount"), 10) || 0,
				memo: value("#restricted_scrip_memo")
			}, "DataScrip awarded.");
		});

		root.on("click.restricted", ".restricted-comment-approve, .restricted-comment-delete", function () {
			var $item = $(this).closest("[data-comment-id]");
			var mode = $(this).hasClass("restricted-comment-delete") ? "delete" : "approve";
			if (mode === "delete" && !window.confirm("Delete this comment permanently?")) {
				return;
			}
			mutate("moderate_comment", {
				mode: mode,
				comment_id: parseInt($item.attr("data-comment-id"), 10) || 0
			}, mode === "delete" ? "Comment deleted." : "Comment approved.");
		});
		root.on("click.restricted", "#restricted_comments_approve_all", function () {
			if (window.confirm("Approve every comment currently awaiting review?")) {
				mutate("moderate_comment", { mode: "approve_all" }, "Review queue approved.");
			}
		});
	}

	function init(node) {
		root = $(node);
		if (!root.length) return;
		bind();
		reload();
	}

	window.ChoosologyRestricted = { init: init };
})(window, jQuery);
