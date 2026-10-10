/**
 * Classic My Office hub — room layers, shop/inventory, hotspots, trophy room.
 */
(function (window, $) {
  "use strict";

  var HOTSPOT_TITLES = {
    experiments: "Experiments",
    messages: "CLIC",
    resources: "Resources",
    ledger: "Ledger",
    degrees: "Degrees",
    account: "My Information",
    results: "Experiment Results",
    shop: "Office Shop",
    inventory: "Inventory"
  };

  var HOTSPOT_URLS = {
    experiments: "mystuff/experiments.php",
    messages: "mystuff/messages.php",
    resources: "mystuff/resources.php",
    ledger: "mystuff/ledger.php",
    degrees: "mystuff/degrees.php",
    account: "mystuff/account.php",
    results: "mystuff/results.php"
  };

  var state = null;
  var $root = null;
  var pendingHotspot = null;

  function apiUrl() {
    if (typeof window.choosologyUrl === "function") {
      return window.choosologyUrl("ajax/office.php");
    }
    return "/ajax/office.php";
  }

  function post(action, payload) {
    var body = $.extend({ action: action }, payload || {});
    return $.ajax({
      url: apiUrl(),
      method: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      data: JSON.stringify(body)
    });
  }

  function syncHeaderBalance(balanceHtml) {
    if (!balanceHtml) return;
    $(".msg-login-scrip-amt").html(balanceHtml);
    $("#ms_room_balance").html(balanceHtml);
  }

  function applyLayers(layers) {
    if (!$root || !layers) return;
    $root.find(".ms-room-layer[data-slot]").each(function () {
      var slot = $(this).attr("data-slot");
      var layer = layers[slot];
      var $img = $(this).find("img");
      if (layer && layer.asset_url) {
        $img.attr("src", layer.asset_url).attr("alt", layer.label || "");
      }
    });
  }

  function applyPedestals(pedestals) {
    if (!$root || !pedestals) return;
    pedestals.forEach(function (ped) {
      var $p = $root.find('.ms-room-pedestal[data-pedestal="' + ped.index + '"]');
      if (!$p.length) return;
      if (ped.item_key && ped.asset_url) {
        $p.addClass("is-filled").attr("title", ped.label || "Trinket");
        $p.html(
          '<img src="' +
            String(ped.asset_url).replace(/"/g, "&quot;") +
            '" alt="' +
            String(ped.label || "").replace(/"/g, "&quot;") +
            '" draggable="false">'
        );
      } else {
        $p.removeClass("is-filled").attr("title", "Empty pedestal");
        $p.html('<span class="ms-room-pedestal-empty">+</span>');
      }
    });
  }

  function applyState(next) {
    if (!next || !next.ok) return;
    state = next;
    applyLayers(next.layers);
    applyPedestals(next.pedestals);
    syncHeaderBalance(next.balance_html);
    if (!$root.find("#ms_trophy_view").hasClass("ms-trophy-view--hidden")) {
      renderTrophyStrip();
    }
  }

  function refreshState() {
    return post("state").done(function (res) {
      applyState(res);
    });
  }

  function closeOverlay() {
    if (!$root) return;
    var $ov = $root.find("#ms_room_overlay");
    $ov.addClass("ms-room-overlay--hidden").attr("aria-hidden", "true");
    $root.find("#ms_room_panel_body").empty();
  }

  function openOverlay(title, htmlOrUrl, isUrl) {
    if (!$root) return;
    var $ov = $root.find("#ms_room_overlay");
    var $body = $root.find("#ms_room_panel_body");
    $root.find("#ms_room_overlay_title").text(title || "Panel");
    $ov.removeClass("ms-room-overlay--hidden").attr("aria-hidden", "false");
    if (isUrl) {
      $body.html('<div class="ajaxloader"></div>');
      $body.load(htmlOrUrl, function (response, status) {
        if (status === "error") {
          $body.html("<p class='error'>Could not load this panel.</p>");
        }
      });
    } else {
      $body.html(htmlOrUrl);
    }
  }

  function closeTrophy() {
    if (!$root) return;
    $root.find("#ms_trophy_view").addClass("ms-trophy-view--hidden").attr("aria-hidden", "true");
    $root.find(".ms-room-stage-wrap, .ms-room-chrome").show();
  }

  function renderTrophyStrip() {
    if (!$root || !state) return;
    var $strip = $root.find("#ms_trophy_strip");
    var trinkets = state.trinkets || [];
    var bay = state.trophy_bay_url || "";
    if (bay) {
      $strip.css("background-image", "url('" + bay + "')");
    }
    var html = "";
    var placed = {};
    (state.pedestals || []).forEach(function (p) {
      if (p.item_key) placed[p.item_key] = p.index;
    });

    if (!trinkets.length) {
      html =
        '<div class="ms-trophy-bay ms-trophy-bay--empty"><p>No trinkets yet. Earn Degrees and catalogue endings to fill the hall.</p></div>';
    } else {
      trinkets.forEach(function (t, i) {
        var onDisplay = Object.prototype.hasOwnProperty.call(placed, t.item_key);
        html +=
          '<div class="ms-trophy-bay" data-item-key="' +
          String(t.item_key).replace(/"/g, "&quot;") +
          '">' +
          '<button type="button" class="ms-trophy-item' +
          (onDisplay ? " is-displayed" : "") +
          '" data-item-key="' +
          String(t.item_key).replace(/"/g, "&quot;") +
          '">' +
          '<img src="' +
          String(t.asset_url).replace(/"/g, "&quot;") +
          '" alt="">' +
          '<span class="ms-trophy-item-label">' +
          $("<div>").text(t.label || "").html() +
          "</span>" +
          (onDisplay
            ? '<span class="ms-trophy-item-badge">In office</span>'
            : '<span class="ms-trophy-item-badge ms-trophy-item-badge--action">Display</span>') +
          "</button></div>";
        if ((i + 1) % 3 === 0) {
          /* keep dense rhythm; bays are CSS flex children */
        }
      });
      /* Mild room-to-grow end bay */
      html +=
        '<div class="ms-trophy-bay ms-trophy-bay--grow" aria-hidden="true"><span>…</span></div>';
    }
    $strip.html(html);
  }

  function openTrophy() {
    if (!$root) return;
    closeOverlay();
    $root.find(".ms-room-stage-wrap, .ms-room-chrome").hide();
    $root.find("#ms_trophy_view").removeClass("ms-trophy-view--hidden").attr("aria-hidden", "false");
    var done = function () {
      renderTrophyStrip();
      var el = $root.find("#ms_trophy_scroll")[0];
      if (el) el.scrollLeft = 0;
    };
    if (!state) {
      refreshState().always(done);
    } else {
      done();
    }
  }

  function renderShopOrInventory(mode) {
    if (!state) {
      refreshState().done(function () {
        renderShopOrInventory(mode);
      });
      return;
    }
    var items = (state.catalog || []).filter(function (c) {
      if (mode === "shop") {
        return c.kind === "decor" && c.unlock_rule === "shop";
      }
      return !!c.owned;
    });
    var html = '<div class="ms-room-grid">';
    if (!items.length) {
      html +=
        mode === "shop"
          ? "<p class='ms-room-empty'>No décor in the catalog.</p>"
          : "<p class='ms-room-empty'>Inventory is empty.</p>";
    }
    items.forEach(function (item) {
      var owned = !!item.owned;
      var equipped =
        item.kind === "decor" &&
        state.layers &&
        state.layers[item.slot] &&
        state.layers[item.slot].item_key === item.item_key;
      html += '<div class="ms-room-tile' + (equipped ? " is-equipped" : "") + '">';
      html +=
        '<img class="ms-room-tile-img" src="' +
        String(item.asset_url).replace(/"/g, "&quot;") +
        '" alt="">';
      html +=
        '<div class="ms-room-tile-meta"><strong>' +
        $("<div>").text(item.label).html() +
        "</strong>";
      html +=
        '<span class="ms-room-tile-blurb">' +
        $("<div>").text(item.blurb || "").html() +
        "</span>";
      if (item.kind === "decor") {
        html +=
          '<span class="ms-room-tile-slot">' +
          $("<div>").text(item.slot || "").html() +
          "</span>";
      }
      html += "</div><div class='ms-room-tile-actions'>";
      if (mode === "shop") {
        if (owned) {
          html +=
            '<button type="button" class="ms-room-btn" data-equip-key="' +
            String(item.item_key).replace(/"/g, "&quot;") +
            '" data-equip-slot="' +
            String(item.slot || "").replace(/"/g, "&quot;") +
            '">' +
            (equipped ? "Equipped" : "Equip") +
            "</button>";
        } else {
          html +=
            '<button type="button" class="ms-room-btn ms-room-btn--accent" data-buy-key="' +
            String(item.item_key).replace(/"/g, "&quot;") +
            '">Buy ' +
            (item.price || 0) +
            "</button>";
        }
      } else if (item.kind === "decor") {
        html +=
          '<button type="button" class="ms-room-btn" data-equip-key="' +
          String(item.item_key).replace(/"/g, "&quot;") +
          '" data-equip-slot="' +
          String(item.slot || "").replace(/"/g, "&quot;") +
          '">' +
          (equipped ? "Equipped" : "Equip") +
          "</button>";
      } else {
        html +=
          '<button type="button" class="ms-room-btn" data-display-key="' +
          String(item.item_key).replace(/"/g, "&quot;") +
          '">Display in office</button>';
      }
      html += "</div></div>";
    });
    html += "</div>";
    openOverlay(mode === "shop" ? HOTSPOT_TITLES.shop : HOTSPOT_TITLES.inventory, html, false);
  }

  function firstFreePedestal() {
    if (!state || !state.pedestals) return 0;
    for (var i = 0; i < state.pedestals.length; i++) {
      if (!state.pedestals[i].item_key) return i;
    }
    return -1;
  }

  function placeTrinketInteractive(itemKey) {
    if (!itemKey) return;
    var free = firstFreePedestal();
    var idx = free;
    if (idx < 0) {
      var choice = window.prompt(
        "All 3 office pedestals are filled. Enter pedestal to replace (1–3), or Cancel.",
        "1"
      );
      if (choice === null) return;
      idx = parseInt(choice, 10) - 1;
      if (isNaN(idx) || idx < 0 || idx > 2) {
        window.alert("Choose 1, 2, or 3.");
        return;
      }
    }
    post("place_trinket", { pedestal_index: idx, item_key: itemKey }).done(function (res) {
      if (!res || !res.ok) {
        window.alert((res && res.error) || "Could not place trinket.");
        return;
      }
      applyState(res.state);
      if ($root.find("#ms_room_overlay").hasClass("ms-room-overlay--hidden") === false) {
        var title = $root.find("#ms_room_overlay_title").text();
        if (title === HOTSPOT_TITLES.inventory) {
          renderShopOrInventory("inventory");
        }
      }
    });
  }

  function openHotspot(name) {
    if (!name) return;
    if (name === "office" || name === "") {
      closeOverlay();
      closeTrophy();
      return;
    }
    if (name === "trophy") {
      openTrophy();
      return;
    }
    if (name === "shop") {
      closeTrophy();
      renderShopOrInventory("shop");
      return;
    }
    if (name === "inventory") {
      closeTrophy();
      renderShopOrInventory("inventory");
      return;
    }
    var url = HOTSPOT_URLS[name];
    if (!url) {
      closeOverlay();
      closeTrophy();
      return;
    }
    closeTrophy();
    openOverlay(HOTSPOT_TITLES[name] || name, url, true);
    if (name === "results" && window.ChoosologyClipboard) {
      /* results_analyst awarded by results.php itself */
    }
  }

  function bind() {
    if (!$root || $root.data("officeBound")) return;
    $root.data("officeBound", 1);

    $root.on("click", "[data-hotspot]", function (e) {
      e.preventDefault();
      openHotspot($(this).attr("data-hotspot"));
    });
    $root.on("click", "[data-office-panel]", function (e) {
      e.preventDefault();
      openHotspot($(this).attr("data-office-panel"));
    });
    $root.on("click", "[data-office-close]", function (e) {
      e.preventDefault();
      closeOverlay();
    });
    $root.on("click", "#ms_trophy_back", function (e) {
      e.preventDefault();
      closeTrophy();
    });
    $root.on("click", ".ms-room-pedestal", function (e) {
      e.preventDefault();
      var idx = parseInt($(this).attr("data-pedestal"), 10);
      if ($(this).hasClass("is-filled")) {
        if (!window.confirm("Clear this pedestal?")) return;
        post("clear_trinket", { pedestal_index: idx }).done(function (res) {
          if (res && res.ok) applyState(res.state);
        });
      } else {
        openHotspot("trophy");
      }
    });
    $root.on("click", "[data-buy-key]", function (e) {
      e.preventDefault();
      var key = $(this).attr("data-buy-key");
      post("buy", { item_key: key }).done(function (res) {
        if (!res || !res.ok) {
          window.alert((res && res.error) || "Purchase failed.");
          return;
        }
        applyState(res.state);
        renderShopOrInventory("shop");
      });
    });
    $root.on("click", "[data-equip-key]", function (e) {
      e.preventDefault();
      var key = $(this).attr("data-equip-key");
      var slot = $(this).attr("data-equip-slot");
      post("equip_decor", { item_key: key, slot: slot }).done(function (res) {
        if (!res || !res.ok) {
          window.alert((res && res.error) || "Could not equip.");
          return;
        }
        applyState(res.state);
        var title = $root.find("#ms_room_overlay_title").text();
        if (title === HOTSPOT_TITLES.shop) renderShopOrInventory("shop");
        else if (title === HOTSPOT_TITLES.inventory) renderShopOrInventory("inventory");
      });
    });
    $root.on("click", "[data-display-key]", function (e) {
      e.preventDefault();
      placeTrinketInteractive($(this).attr("data-display-key"));
    });
    $root.on("click", ".ms-trophy-item", function (e) {
      e.preventDefault();
      placeTrinketInteractive($(this).attr("data-item-key"));
    });

    $(document)
      .off("keydown.choosologyOffice")
      .on("keydown.choosologyOffice", function (e) {
        if (e.key === "Escape" || e.keyCode === 27) {
          if ($root && !$root.find("#ms_trophy_view").hasClass("ms-trophy-view--hidden")) {
            closeTrophy();
          } else {
            closeOverlay();
          }
        }
      });
  }

  function mount(el) {
    $root = el ? $(el) : $("#ms_room_page");
    if (!$root.length) return;
    bind();
    refreshState().always(function () {
      var pending =
        pendingHotspot ||
        window.__choosologyOfficeHotspot ||
        null;
      pendingHotspot = null;
      window.__choosologyOfficeHotspot = null;
      if (pending && pending !== "office") {
        openHotspot(pending);
      }
    });
  }

  window.ChoosologyOffice = {
    mount: mount,
    openHotspot: openHotspot,
    closeOverlay: closeOverlay,
    setPendingHotspot: function (name) {
      pendingHotspot = name || null;
      window.__choosologyOfficeHotspot = pendingHotspot;
    },
    refresh: refreshState
  };
})(window, jQuery);
