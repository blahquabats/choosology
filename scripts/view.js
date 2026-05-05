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
            $(".choicecontainer").hover(function(){
                $(".commentsdiv").removeClass("hidecomments");
                $(".choicecover").fadeOut("fast");
                },function(){
                $(".choicecover").fadeIn("fast");
                });
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
        $(".commentsdiv").removeClass("hidecomments");
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

    $(".choicecontainer").hover(function(){
        $(".commentsdiv").removeClass("hidecomments");
        $(".choicecover").fadeOut("fast");
        },function(){
        $(".choicecover").fadeIn("fast");
        });
    $(".choicecontainer").click(function(){
        $(".commentsdiv").removeClass("hidecomments");
        $(".choicecover").fadeOut("fast");
        $(".choicecontainer").off("mouseenter mouseleave");
        });
