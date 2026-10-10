/**
 * Hide images that fail to load so play/view never shows broken-image chrome or alt text.
 * (img error does not bubble, so each img is bound directly.)
 */
var commentsTimer = null;
var screenNavGen = 0;
/** Set by initTheaterMode so exitTheaterMode can clear animation cleanly. */
var theaterCtl = null;

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
    screenNavGen++;
}

function goToScreen(screenid, fromscreen, reverse)
{
    if(reverse) direction = "right";
    else direction = "left";
    $(".text").toggle({
        effect: "slide",
        direction: direction,
        complete: function()
        {
            $(".choicecover").show();
            bindEndingReveal($(".choicecontainer"));
            replaceScreen(screenid, fromscreen, reverse);
        }
    });
}

function reloadScreen(screenid)
{
    var gen = ++screenNavGen;
    $.ajax({
       url: "ajax/screenajax.php",
       data: {screen: screenid,
       project_lazarus:'go'}
    })
    .done(function(text)
    {
        if (gen !== screenNavGen) return;
        var response = $.parseJSON(text);
        $("#choicemeat").html(response.choices);
        bindEndingReveal($(".choicecontainer"));
        checkComments();
    });
}

function replaceScreen(screenid, fromscreen, reverse)
{
    if(reverse) direction = "left";
    else direction = "right";
    var gen = ++screenNavGen;
    $.ajax({
       url: "ajax/screenajax.php",
       data: {screen: screenid,
       from: fromscreen,
       project_lazarus:'go'}
    })
    .done(function(text)
    {
        if (gen !== screenNavGen) return;
        var response = $.parseJSON(text);
        
        $("#lastscreen").off().show("fade");
        $("#lastscreen").on("click",function(){
            goToScreen(fromscreen,screenid,1);
        });
        $("#innards div").html(response.text);
        silenceBrokenImages(".advcanvas");
        $(".advcanvas").prop("style", response.bg);
        $(".realinnards,.viewcol1,.choices").prop("style", response.box+";"+response.border);
        $(".choicecover").prop("style", response.box+";"+response.border+";"+response.offset);
        $("#choicemeat").html(response.choices);
        $(".text").toggle({
                effect: "slide",
                direction: direction,
                complete: function () {
                    if (gen !== screenNavGen) return;
                    checkComments();
                }
            });

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
    
function checkComments()
{
    if (commentsTimer) {
        clearTimeout(commentsTimer);
        commentsTimer = null;
    }
    if($("#commentsexist").length)
    {
        var advid = $("#advid").val();
        var screenid = $("#screenid").val();
        commentsTimer = window.setTimeout(function(){
            commentsTimer = null;
            showComments("adv"+advid, screenid);
        }, 500);
    }
}

    function bindEndingReveal($box) {
        $box.off("mouseenter.ending mouseleave.ending click.ending");
        $box.on("mouseenter.ending", function(){
            $(".commentsdiv").removeClass("hidecomments");
            $(".choicecover").fadeOut("fast");
        });
        $box.on("mouseleave.ending", function(){
            if (!$box.data("commentsPinned")) {
                $(".commentsdiv").addClass("hidecomments");
            }
            $(".choicecover").fadeIn("fast");
        });
        $box.on("click.ending", function(){
            $box.data("commentsPinned", true);
            $(".commentsdiv").removeClass("hidecomments");
            $(".choicecover").fadeOut("fast");
            $box.off("mouseenter.ending mouseleave.ending");
        });
    }
    silenceBrokenImages(".advcanvas");
    bindEndingReveal($(".choicecontainer"));
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

    function prefersReducedMotion()
    {
        try {
            return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
        } catch (e) {
            return false;
        }
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
