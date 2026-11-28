/**
 * Hide images that fail to load so play/view never shows broken-image chrome or alt text.
 * (img error does not bubble, so each img is bound directly.)
 */
var commentsTimer = null;
var choiceExpandTimer = null;
var choiceFlairTimer = null;
var screenNavGen = 0;
var screenNavBusy = false;
/** Set by initTheaterMode so exitTheaterMode can clear animation cleanly. */
var theaterCtl = null;

function prefersReducedMotion()
{
    try {
        return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
    } catch (e) {
        return false;
    }
}

function clearChoiceRevealTimers()
{
    if (choiceExpandTimer) {
        clearTimeout(choiceExpandTimer);
        choiceExpandTimer = null;
    }
    if (choiceFlairTimer) {
        clearTimeout(choiceFlairTimer);
        choiceFlairTimer = null;
    }
}

function silenceBrokenImages(root)
{
    var $root = root ? $(root) : $(".advcanvas");
    $root.find("img").each(function () {
        var img = this;
        if (img.getAttribute("data-choosology-img-bound") === "1") {
            return;
        }
        img.setAttribute("data-choosology-img-bound", "1");
        function hideBroken() {
            img.removeAttribute("alt");
            img.removeAttribute("title");
            img.setAttribute("aria-hidden", "true");
            img.style.display = "none";
        }
        if (img.complete) {
            if (img.naturalWidth === 0) {
                hideBroken();
            }
            return;
        }
        img.addEventListener("error", hideBroken);
    });
}

function clearViewTimers()
{
    if (commentsTimer) {
        clearTimeout(commentsTimer);
        commentsTimer = null;
    }
    clearChoiceRevealTimers();
    screenNavGen++;
    screenNavBusy = false;
    clearSelectedChoicePending();
    document.body.classList.remove("choosology-screen-loading");
}

function choiceNavTargets()
{
    return $("#choicemeat .choice, #firstscreen, #lastscreen");
}

function markSelectedChoicePending(selected)
{
    choiceNavTargets().addClass("is-choice-pending").attr("aria-disabled", "true");
    document.body.classList.add("choosology-screen-loading");
    if (!selected || selected.nodeType !== 1) return;
    var $sel = $(selected);
    $sel.addClass("is-choice-selected");
    if (!$sel.children(".choice-spinner").length) {
        $sel.append("<span class='choice-spinner' aria-hidden='true'></span>");
    }
}

function clearSelectedChoicePending()
{
    $(".is-choice-pending").removeClass("is-choice-pending is-choice-selected").removeAttr("aria-disabled");
    $(".choice-spinner").remove();
}

function finishScreenNav()
{
    screenNavBusy = false;
    document.body.classList.remove("choosology-screen-loading");
}

function abortScreenNav()
{
    screenNavBusy = false;
    clearSelectedChoicePending();
    document.body.classList.remove("choosology-screen-loading");
}

function slideCurrentScreen(direction, complete)
{
    $(".text").toggle({
        effect: "slide",
        direction: direction,
        complete: function () {
            if (typeof complete === "function") complete();
        }
    });
}

function paintLoadedScreen(response, screenid, fromscreen)
{
    clearSelectedChoicePending();
    $("#lastscreen").off().show("fade");
    $("#lastscreen").on("click", function () {
        goToScreen(fromscreen, screenid, 1, this);
    });
    var beginId = $("#firstscreen").attr("data-begin");
    if (beginId !== undefined) {
        $("#firstscreen").toggleClass("is-opening-screen", String(screenid) === String(beginId));
    }
    $("#innards div").html(response.text);
    silenceBrokenImages(".advcanvas");
    $(".advcanvas").prop("style", response.bg);
    $(".realinnards,.viewcol1,.choices").prop("style", response.box + ";" + response.border);
    $("#choicemeat").html(response.choices);
    resetChoiceReveal($(".choicecontainer"));
}

function screenAjaxData(screenid, fromscreen)
{
    var data = {
        screen: screenid,
        project_lazarus: "go"
    };
    if (fromscreen !== undefined && fromscreen !== null) data.from = fromscreen;
    if ($("#try-draft").val() === "1") data.try = "1";
    return data;
}

function goToScreen(screenid, fromscreen, reverse, selected)
{
    if (screenNavBusy) return;
    screenNavBusy = true;
    markSelectedChoicePending(selected);
    var hideDirection = reverse ? "right" : "left";
    var showDirection = reverse ? "left" : "right";
    var gen = ++screenNavGen;
    $.ajax({
       url: "ajax/screenajax.php",
       data: screenAjaxData(screenid, fromscreen)
    })
    .done(function (text) {
        if (gen !== screenNavGen) {
            if (screenNavBusy) abortScreenNav();
            return;
        }
        var response;
        try {
            response = $.parseJSON(text);
        } catch (err) {
            abortScreenNav();
            return;
        }
        slideCurrentScreen(hideDirection, function () {
            if (gen !== screenNavGen) return;
            paintLoadedScreen(response, screenid, fromscreen);
            slideCurrentScreen(showDirection, function () {
                if (gen !== screenNavGen) return;
                finishScreenNav();
            });
        });
    })
    .fail(function () {
        if (gen !== screenNavGen) return;
        abortScreenNav();
    });
}

function reloadScreen(screenid)
{
    var gen = ++screenNavGen;
    var wasRevealed = $(".choicecontainer .choices").hasClass("is-revealed");
    $.ajax({
       url: "ajax/screenajax.php",
       data: screenAjaxData(screenid)
    })
    .done(function(text)
    {
        if (gen !== screenNavGen) return;
        var response = $.parseJSON(text);
        $("#choicemeat").html(response.choices);
        var $box = $(".choicecontainer");
        bindChoiceReveal($box);
        if (wasRevealed) {
            revealChoices($box, { animate: false, flair: false, force: true });
        } else {
            resetChoiceReveal($box);
        }
    });
}

function showComments(board, screen)
{
    var box = $("#CAcommentsholder"+board);
    //$("#CAcommentsholder"+board).html("blorp");
    //box.animate({ height: "400px", easing:"easeInCirc", queue: 0}, 400);
    $.ajax({
        url: "ajax/fetchcomments.php",
        dataType: "xml",
        data: {
           screen: screen,
           name: board,
           project_lazarus:'go'
           
        }
    })
    .done(loadCommentsResponse);
}
    
function checkComments(immediate)
{
    if (commentsTimer) {
        clearTimeout(commentsTimer);
        commentsTimer = null;
    }
    if($("#commentsexist").length)
    {
        var advid = $("#advid").val();
        var screenid = $("#screenid").val();
        if (immediate) {
            showComments("adv"+advid, screenid);
            return;
        }
        commentsTimer = window.setTimeout(function(){
            commentsTimer = null;
            showComments("adv"+advid, screenid);
        }, 500);
    }
}

function clearEndingFlair($box)
{
    if (!$box || !$box.length) return;
    $box.find(".ending-flair-burst, .ending-flair-sheen").remove();
    $box.find(".choices").removeClass("is-ending-flair");
}

function resetChoiceReveal($box)
{
    if (!$box || !$box.length) return;
    clearChoiceRevealTimers();
    clearEndingFlair($box);
    var $choices = $box.find(".choices").first();
    var $cover = $box.find(".choicecover").first();
    var $meat = $box.find("#choicemeat").first();
    $choices
        .off("transitionend.choiceExpand")
        .removeClass("is-revealed is-expanding is-ending-revealed is-ending-flair")
        .addClass("is-collapsed")
        .css({ height: "", overflow: "" });
    $cover.attr("aria-expanded", "false");
    $meat.attr("aria-hidden", "true");
    $meat.find(".commentsdiv").addClass("hidecomments");
}

function triggerEndingFlair($box, $choices)
{
    if (prefersReducedMotion()) return;
    clearEndingFlair($box);
    $choices.addClass("is-ending-flair");
    $choices.append("<span class='ending-flair-sheen' aria-hidden='true'></span>");

    var sparks = [
        { glyph: "\u2726", dx: "-132px", dy: "-34px", rot: "-28deg", delay: "0.02s", alt: false },
        { glyph: "\u25C6", dx: "-96px",  dy: "-48px", rot: "22deg",  delay: "0.07s", alt: true  },
        { glyph: "\u2022", dx: "-62px",  dy: "-30px", rot: "-15deg", delay: "0.04s", alt: true  },
        { glyph: "\u2726", dx: "-26px",  dy: "-52px", rot: "18deg",  delay: "0.09s", alt: false },
        { glyph: "\u25C6", dx: "0px",    dy: "-56px", rot: "45deg",  delay: "0.05s", alt: true  },
        { glyph: "\u2726", dx: "28px",   dy: "-50px", rot: "-20deg", delay: "0.08s", alt: false },
        { glyph: "\u2022", dx: "64px",   dy: "-32px", rot: "16deg",  delay: "0.03s", alt: true  },
        { glyph: "\u25C6", dx: "98px",   dy: "-46px", rot: "-24deg", delay: "0.06s", alt: true  },
        { glyph: "\u2726", dx: "134px",  dy: "-32px", rot: "30deg",  delay: "0.02s", alt: false }
    ];
    var burstHtml = "<div class='ending-flair-burst' aria-hidden='true'>";
    for (var i = 0; i < sparks.length; i++) {
        var s = sparks[i];
        var cls = "ending-flair-spark" + (s.alt ? " ending-flair-spark--alt" : "");
        burstHtml += "<span class='" + cls + "' style='--dx:" + s.dx + ";--dy:" + s.dy + ";--rot:" + s.rot + ";--spark-delay:" + s.delay + ";'>" + s.glyph + "</span>";
    }
    burstHtml += "</div>";
    $box.append(burstHtml);

    choiceFlairTimer = window.setTimeout(function () {
        choiceFlairTimer = null;
        clearEndingFlair($box);
    }, 950);
}

function revealChoices($box, opts)
{
    if (!$box || !$box.length) return;
    opts = opts || {};
    var animate = opts.animate !== false;
    var flair = opts.flair !== false;
    var $choices = $box.find(".choices").first();
    var $cover = $box.find(".choicecover").first();
    var $meat = $box.find("#choicemeat").first();
    if (!$choices.length) return;
    if ($choices.hasClass("is-revealed") && !opts.force) return;

    var isEnding = $meat.find(".ending-panel").length > 0;
    clearChoiceRevealTimers();
    clearEndingFlair($box);

    var startH = Math.round($choices[0].getBoundingClientRect().height);

    $cover.attr("aria-expanded", "true");
    $meat.attr("aria-hidden", "false");
    $meat.find(".commentsdiv").removeClass("hidecomments");
    $choices
        .off("transitionend.choiceExpand")
        .removeClass("is-collapsed is-expanding")
        .addClass("is-revealed")
        .toggleClass("is-ending-revealed", isEnding)
        .css({ height: "", overflow: "" });

    if (animate && !prefersReducedMotion()) {
        var targetH = Math.round($choices[0].getBoundingClientRect().height);
        if (targetH > 0 && Math.abs(targetH - startH) > 1) {
            var finishExpand = function () {
                if (choiceExpandTimer) {
                    clearTimeout(choiceExpandTimer);
                    choiceExpandTimer = null;
                }
                $choices
                    .off("transitionend.choiceExpand")
                    .removeClass("is-expanding")
                    .css({ height: "", overflow: "" });
            };
            $choices
                .addClass("is-expanding")
                .css({ height: startH + "px", overflow: "hidden" });
            void $choices[0].offsetHeight;
            $choices.css("height", targetH + "px");
            $choices.on("transitionend.choiceExpand", function (e) {
                if (e.target === $choices[0] && e.originalEvent && e.originalEvent.propertyName === "height") {
                    finishExpand();
                }
            });
            choiceExpandTimer = window.setTimeout(finishExpand, (isEnding ? 400 : 260) + 60);
        }
        if (isEnding && flair) {
            triggerEndingFlair($box, $choices);
        }
    }

    if (isEnding) {
        checkComments(true);
    }
}

function bindChoiceReveal($box)
{
    if (!$box || !$box.length) return;
    $box.off("click.choiceReveal").on("click.choiceReveal", ".choicecover", function (e) {
        e.preventDefault();
        revealChoices($box, { animate: true, flair: true });
    });
}

silenceBrokenImages(".advcanvas");
bindChoiceReveal($(".choicecontainer"));
initTheaterMode();

/**
 * Theater mode: expand the adventure play surface to fill the viewport.
 * Off by default; session preference only (does not force on for new visits).
 * Enter/exit use a short CSS animation unless the user prefers reduced motion.
 */
function initTheaterMode()
{
    var STORAGE_KEY = "choosology_theater_mode";
    var ANIM_MS = 320;
    var $toggle = $("#theater_toggle");
    var $exit = $("#theater_exit");
    var animTimer = null;
    var animating = false;
    if (!$toggle.length) {
        theaterCtl = null;
        return;
    }

    function isOn()
    {
        return document.body.classList.contains("choosology-theater");
    }

    function clearAnimTimer()
    {
        if (animTimer) {
            clearTimeout(animTimer);
            animTimer = null;
        }
        animating = false;
        document.body.classList.remove("choosology-theater-anim-in", "choosology-theater-anim-out");
    }

    function syncChrome(on, showExit)
    {
        $toggle.attr("aria-pressed", on ? "true" : "false");
        $toggle.text(on ? "Exit theater" : "Theater mode");
        if ($exit.length) {
            if (showExit) {
                $exit.removeAttr("hidden");
            } else {
                $exit.attr("hidden", "hidden");
            }
        }
        try {
            if (on) {
                sessionStorage.setItem(STORAGE_KEY, "1");
            } else {
                sessionStorage.removeItem(STORAGE_KEY);
            }
        } catch (e) { /* ignore */ }
    }

    function setTheater(on, opts)
    {
        on = !!on;
        opts = opts || {};
        var instant = !!opts.instant || prefersReducedMotion();
        if (animating && !instant) {
            return;
        }
        if (on === isOn() && !animating) {
            syncChrome(on, on);
            return;
        }
        clearAnimTimer();

        if (instant) {
            document.body.classList.toggle("choosology-theater", on);
            syncChrome(on, on);
            return;
        }

        if (on) {
            syncChrome(true, true);
            document.body.classList.add("choosology-theater");
            document.body.classList.add("choosology-theater-anim-in");
            animating = true;
            animTimer = setTimeout(function () {
                document.body.classList.remove("choosology-theater-anim-in");
                animating = false;
                animTimer = null;
            }, ANIM_MS);
            return;
        }

        // Keep Exit visible through the leave animation, then hide.
        syncChrome(false, true);
        document.body.classList.add("choosology-theater-anim-out");
        animating = true;
        animTimer = setTimeout(function () {
            document.body.classList.remove("choosology-theater", "choosology-theater-anim-out");
            if ($exit.length) {
                $exit.attr("hidden", "hidden");
            }
            animating = false;
            animTimer = null;
        }, ANIM_MS);
    }

    function toggleTheater()
    {
        setTheater(!isOn());
    }

    theaterCtl = {
        setTheater: setTheater,
        clearAnimTimer: clearAnimTimer
    };

    $toggle.off("click.theater").on("click.theater", function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleTheater();
    });
    $exit.off("click.theater").on("click.theater", function (e) {
        e.preventDefault();
        setTheater(false);
    });
    $(document).off("keydown.theater").on("keydown.theater", function (e) {
        if (e.key === "Escape" && isOn()) {
            setTheater(false);
        }
    });

    // Default off; only restore if user enabled earlier this session while viewing
    var preferOn = false;
    try {
        preferOn = sessionStorage.getItem(STORAGE_KEY) === "1";
    } catch (e) { /* ignore */ }
    setTheater(preferOn, { instant: true });
}

/** Leave theater when navigating away from the play view. */
function exitTheaterMode()
{
    clearViewTimers();
    if (theaterCtl && typeof theaterCtl.setTheater === "function") {
        theaterCtl.setTheater(false, { instant: true });
    } else {
        try {
            sessionStorage.removeItem("choosology_theater_mode");
        } catch (e) { /* ignore */ }
        document.body.classList.remove(
            "choosology-theater",
            "choosology-theater-anim-in",
            "choosology-theater-anim-out"
        );
    }
    $(document).off("keydown.theater");
    $("#theater_toggle").attr("aria-pressed", "false").text("Theater mode");
    $("#theater_exit").attr("hidden", "hidden");
}
