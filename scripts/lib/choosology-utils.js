/**
 * Pure Choosology JS utilities — usable in the browser and in Jest.
 * Browser: attaches to window.ChoosologyUtils and mirrors legacy globals.
 * Node: module.exports.
 */
(function (root, factory) {
  if (typeof module === "object" && module.exports) {
    module.exports = factory();
  } else {
    var api = factory();
    root.ChoosologyUtils = api;
    // Legacy global aliases used by scripts/choosology.js callers
    if (typeof root.count !== "function") {
      root.count = api.count;
    }
    if (typeof root.strip_tags !== "function") {
      root.strip_tags = api.stripTags;
    }
    if (typeof root.degreesToRadians !== "function") {
      root.degreesToRadians = api.degreesToRadians;
    }
    if (typeof root.choosologyUrlSafeGlobal !== "function") {
      root.choosologyUrlSafeGlobal = api.urlSafe;
    }
  }
})(typeof globalThis !== "undefined" ? globalThis : this, function () {
  "use strict";

  var CLIC_THEMES = ["amber", "violet", "slate"];

  function count(array) {
    var c = 0;
    var i;
    for (i in array) {
      if (Object.prototype.hasOwnProperty.call(array, i) && array[i] != null) {
        c++;
      }
    }
    return c;
  }

  function stripTags(input, allowed) {
    allowed = (((allowed || "") + "")
      .toLowerCase()
      .match(/<[a-z][a-z0-9]*>/g) || [])
      .join("");
    var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
    var commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
    return String(input || "")
      .replace(commentsAndPhpTags, "")
      .replace(tags, function ($0, $1) {
        return allowed.indexOf("<" + $1.toLowerCase() + ">") > -1 ? $0 : "";
      });
  }

  function degreesToRadians(degrees) {
    return (Math.PI / 180) * Number(degrees);
  }

  function urlSafe(path, choosologyUrlFn) {
    if (typeof choosologyUrlFn === "function") {
      return choosologyUrlFn(path);
    }
    if (typeof choosologyUrl === "function") {
      return choosologyUrl(path);
    }
    path = String(path || "").replace(/^\//, "");
    return path ? "/" + path : "/";
  }

  function normalizeClicTheme(theme) {
    return CLIC_THEMES.indexOf(theme) >= 0 ? theme : "amber";
  }

  function formatUnreadBadge(unread) {
    var n = parseInt(unread, 10) || 0;
    return {
      count: n,
      label: n > 0 ? "CLIC (" + n + ")" : "CLIC",
      badgeText: n > 99 ? "99+" : String(n),
      showBadge: n > 0
    };
  }

  function isValidExperimentTitle(title) {
    return String(title || "").trim().length > 0;
  }

  return {
    CLIC_THEMES: CLIC_THEMES.slice(),
    count: count,
    stripTags: stripTags,
    strip_tags: stripTags,
    degreesToRadians: degreesToRadians,
    urlSafe: urlSafe,
    choosologyUrlSafeGlobal: urlSafe,
    normalizeClicTheme: normalizeClicTheme,
    formatUnreadBadge: formatUnreadBadge,
    isValidExperimentTitle: isValidExperimentTitle
  };
});
