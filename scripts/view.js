/**
 * Hide images that fail to load so play/view never shows broken-image chrome or alt text.
 * (img error does not bubble, so each img is bound directly.)
 */
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
    $.ajax({
       url: "ajax/screenajax.php",
       data: {screen: screenid,
       project_lazarus:'go'}
    })
    .done(function(text)
    {
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
    $.ajax({
       url: "ajax/screenajax.php",
       data: {screen: screenid,
       from: fromscreen,
       project_lazarus:'go'}
    })
    .done(function(text)
    {
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
                complete: checkComments
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
    /*.done(function(text)
    {
        var response = text;
        
        var xml = $( response );
        
        var id = xml.find("id").text(),
        comments = xml.find("comments").text(),
        pagesize = xml.find("pagesize").text(),
        error = xml.find("error").text();
        $("#CAcommentsholder"+id).html(comments);
        /*
        $(".starsrating"+id).val(rating.text());
        
        $(".starsloading"+id).hide();
        showAvgStars(id);
        var rr=$(".rateresponse"+id);

    });   */
}
    
function checkComments()
{
    if($("#commentsexist").length)
    {
        var advid = $("#advid").val();
        var screenid = $("#screenid").val();
        window.setTimeout(function(){showComments("adv"+advid, screenid)}, 500) ;
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
 */
function initTheaterMode()
{
    var STORAGE_KEY = "choosology_theater_mode";
    var $toggle = $("#theater_toggle");
    var $exit = $("#theater_exit");
    if (!$toggle.length) {
        return;
    }

    function isOn()
    {
        return document.body.classList.contains("choosology-theater");
    }

    function setTheater(on)
    {
        on = !!on;
        document.body.classList.toggle("choosology-theater", on);
        $toggle.attr("aria-pressed", on ? "true" : "false");
        $toggle.text(on ? "Exit theater" : "Theater mode");
        if ($exit.length) {
            if (on) {
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

    function toggleTheater()
    {
        setTheater(!isOn());
    }

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
    setTheater(preferOn);
}

/** Leave theater when navigating away from the play view. */
function exitTheaterMode()
{
    try {
        sessionStorage.removeItem("choosology_theater_mode");
    } catch (e) { /* ignore */ }
    document.body.classList.remove("choosology-theater");
    $(document).off("keydown.theater");
}
