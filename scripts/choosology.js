function count(array)
{
    var c = 0;
    for(i in array) // in returns key, not object
        if(array[i] != undefined)
            c++;

return c;
}

function strip_tags(input, allowed) {
  allowed = (((allowed || '') + '')
    .toLowerCase()
    .match(/<[a-z][a-z0-9]*>/g) || [])
    .join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
  var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
    commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
  return input.replace(commentsAndPhpTags, '')
    .replace(tags, function($0, $1) {
      return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
    });
}
var decodeEntities = (function () {
        //create a new html document (doesn't execute script tags in child elements)
        var doc = document.implementation.createHTMLDocument("");
        var element = doc.createElement('div');

        function getText(str) {
            element.innerHTML = str;
            str = element.textContent;
            element.textContent = '';
            return str;
        }

        function decodeHTMLEntities(str) {
            if (str && typeof str === 'string') {
                var x = getText(str);
                while (str !== x) {
                    str = x;
                    x = getText(x);
                }
                return x;
            }
        }
        return decodeHTMLEntities;
    })();

function nicedatetime(datetime)
{
    return $.format.date(datetime, "h:mma on MM/dd/yyyy").toLowerCase();
}

function degreesToRadians(degrees) 
{
    return (Math.PI/180) * degrees;
}

function highlightStars(num, id)
{
    var c=1;

    while (c<=5) //clear all
    {
        $(".avgstar"+c+"#stavg"+c+"-"+id).css({'width' : "0%"});
        
        c++;
    }
    c=1;
    while (c<=num)
    {
        $(".ratingstar"+c+"#st"+c+"-"+id).attr("src","images/icons/ratings/star-o.png");
        c++;
    }


}
function showAvgStars(id)
{
    var avg=$(".starsrating"+id).val();
    var c=1;
    while (c<=5) //clear all
    {
        $(".ratingstar"+c+"#st"+c+"-"+id).attr("src","images/icons/ratings/star-n.png");
        c++;
    }
    var oavg=avg;
    avg=parseFloat(avg);
    c=1;
    if(avg===0)
    {
        $(".rateresponse"+id).html("Not enough ratings");
        return false;
    }
    while (c<=parseInt(avg)) //show avg amount
    {
        $(".avgstar"+c+"#stavg"+c+"-"+id).css({'width' : "100%"});
        c++;
    }
    c--;
    avg=avg-parseFloat(c);
    avg=Math.round(avg*100.00);
    if(c<5)
    {
        $(".avgstar"+(c+1)+"#stavg"+(c+1)+"-"+id).css({'width' : avg+"%"});
    }
    $(".rateresponse"+id).html("Average rating: <b>"+oavg+"</b> stars");
}

function sendRating(stid,id,sid)
{
   
	$(".starsloading"+id).show();
	var rating=stid;
	if(id>0 && stid>0)
	{
	$.ajax({
       url: "ratjax.php",
       dataType: "xml",
       data: {rating: rating,
       adv: id,
       screen: sid,
       project_lazarus:'go'}
    })
    .done(function(text)
    {
        
        var response = text;
        
        var xml = $( response );
        var id = xml.find("advid"),
        success = xml.find("success"),
        rating = xml.find("rating"),
        myrating = xml.find("myrating"),
        num = xml.find("number");
        id = id.text();
        $(".starsrating"+id).val(rating.text());
        
        $(".starsloading"+id).hide();
        showAvgStars(id);
        var rr=$(".rateresponse"+id);
		var ry=$(".rateyours"+id);
		
		if(success.text()=="1") 
        {
            rr.html("Thanks for rating!");
            ry.html("Your rating: <b>"+myrating.text()+"</b> stars");
        }
			else 
        {
            rr.html("Rating failed...");
            ry.html("");
        } 
    });   
	}
	else showAlert("There was an error trying to send the rating. What are you doing?", "error");

	}


function showAlert(message, type)
{
    if(!type) type='info';
    var ab = $("#alertbox");
    switch (type)
    {
    case "error":
        ab.css("background-color","#ff6666");
        break;
    case "success":
        ab.css("background-color","#66ff66");
        break;
    case "warning":
        ab.css("background-color","#ffff66");
        break;
    case "info":
    case "default":
    break;
    }
    ab.html(message);
    ab.slideDown("slow", function() {
        setTimeout(function(){
            ab.slideUp("slow");
            }, 2000);
        });
}

function checkCATextArea(element, forb)
{
    if(element.value=='Enter your comment here...' && forb=='f') 
    {
      element.value='';
      element.className = 'CAtextarea';
      //removeClass(element,'empty');
    }
    else if(element.value==='' && forb=='b')
    {
      element.value='Enter your comment here...';
      element.className = 'CAtextarea empty';
      //addClass(element,'empty');
    }
}

function submitCAComment(name, screen)
{
   if(!screen) screen = 0;
   //document.getElementById('CAloader'+name).style.display='block';
   var area=$('#CAtext'+name);
   var desc=area.val().replace(/^\s\s*/, '').replace(/\s\s*$/, '').replace(/(\\r\\n|\\n|\\r)/gm,'<br />');
   var post='text='+desc;
   
   $.ajax({
        url: 'ajax/fetchcomments.php?name='+name+'&screen='+screen+'&page=1',
        type: "post",
        dataType: 'xml',
        data: {
            text: desc,
            project_lazarus:'go'
            }
    }).done(loadCommentsResponse);
   
}


function deleteCAComment(cid, which)
{
   if (!confirm('Are you sure you want to delete this?')) return false;
   document.getElementById('CAloader'+which).style.display='block';
    $.ajax({
        url: 'ajax/fetchcomments.php',
        type: 'get',
        dataType: 'xml',
        data: {
            name: which,
            delete: 1,
            cid: cid,
            project_lazarus:'go'
            }
    }).done(loadCommentsResponse);
}

function loadCommentsResponse(response)
{
        xmlResponse=response;
         xmlDocEl=xmlResponse.documentElement;
         var comments=xmlDocEl.getElementsByTagName('comments');
         //comments=comments[0].firstChild.data;
         var id=xmlDocEl.getElementsByTagName('id');
         if(id) id=id[0].firstChild.data;
    	 var num=xmlDocEl.getElementsByTagName('num');
         if(num) num=num[0].firstChild.data;
         var page=xmlDocEl.getElementsByTagName('page');
         if(page) page=parseInt(page[0].firstChild.data);
         var errmes=xmlDocEl.getElementsByTagName('error');
         if(errmes) errmes=errmes[0].firstChild.data;
         var pagesize=xmlDocEl.getElementsByTagName('pagesize');
         if(pagesize) pagesize=pagesize[0].firstChild.data;
         
        if(xmlDocEl.getElementsByTagName('insertion')) 
        {
            var insertion=xmlDocEl.getElementsByTagName('insertion');
            insertion=insertion[0].firstChild.data;
        }
          else
        {
            var insertion=0;
        }

         if(xmlDocEl.getElementsByTagName('deletion'))
        {
         var deletion=xmlDocEl.getElementsByTagName('deletion');
         deletion=deletion[0].firstChild.data;
        }
          else
        {
            var deletion=0;
        }

        if(deletion != 0)
        {
            if(deletion==-1)
            {
                showAlert(errmes, "error");
            }
            else
            {
                showAlert("Comment deleted!", "success");
            }
        }

       if(insertion != 0) 
       {
    
            if(insertion==-1) 
            {
              showAlert(errmes, "error");
            }
            else 
            {
              showAlert("Comment posted!");
            }
            document.getElementById('CAtext'+id).value='';
            document.getElementById('CAtext'+id).focus();
            document.getElementById('CAtext'+id).blur();
        }
         document.getElementById('CAcommentsnextpage'+id).innerHTML='';
         document.getElementById('CAcommentslastpage'+id).innerHTML='';
         document.getElementById('CAcommentscountfull'+id).innerHTML='';
         document.getElementById('CAcommentsholder'+id).innerHTML='';
         for (var i in comments)
         {
            if(!comments[i].firstChild) continue;
            document.getElementById('CAcommentsholder'+id).innerHTML += comments[i].firstChild.data;
         }
         
          if(num !== 0) document.getElementById('CAcommentscountbegin'+id).innerHTML=1+((page-1)*pagesize)+' to ';
          if(page > 1) var _sc=(document.querySelector(".CAcomments[data-ca-board='"+id+"']")||{getAttribute:function(){return 0;}}).getAttribute('data-screen')||0;
          document.getElementById('CAcommentslastpage'+id).innerHTML='<small><a onclick="loadComments(\''+id+'\', '+(page-1)+', '+_sc+')"><- Newer</a></small>';

     if(num == 0)
      {
        document.getElementById('CAcommentscountbegin'+id).innerHTML='';
        document.getElementById('CAcommentscountend'+id).innerHTML='';
        document.getElementById('CAcommentscountfull'+id).innerHTML='';
        document.getElementById('CAcommentsnextpage'+id).innerHTML='';
      }

     if(num>0)
     { 
      if(page*pagesize<num) // if end of the page is less than the total
      {
         document.getElementById('CAcommentscountend'+id).innerHTML=page*pagesize;
         var _sc2=(document.querySelector(".CAcomments[data-ca-board='"+id+"']")||{getAttribute:function(){return 0;}}).getAttribute('data-screen')||0;
         document.getElementById('CAcommentsnextpage'+id).innerHTML='<small><a onclick="loadComments(\''+id+'\', '+(page+1)+', '+_sc2+')">Older -></a></small>';
      }
      else
      {
         document.getElementById('CAcommentscountend'+id).innerHTML=num;
      }
           }
         if(num > pagesize)
         {
      document.getElementById('CAcommentscountfull'+id).innerHTML=' of '+num;
      
   }
   if(document.getElementById('CAloader'+id)) document.getElementById('CAloader'+id).style.display='none';
}
         
function loadComments(name, page, screen)
{
    if(!page) page=1;
    if(typeof screen === 'undefined' || screen === null || screen === '') {
        var el = document.querySelector(".CAcomments[data-ca-board='"+name+"']");
        screen = el ? (el.getAttribute('data-screen') || 0) : 0;
    }
    $.ajax({
    url: 'ajax/fetchcomments.php',
    type: 'GET',
    dataType: 'xml',
    data: {
        name: name,
        page: page,
        screen: screen,
        project_lazarus:'go'
        }
}).done(loadCommentsResponse);

}

function decodeEntities(input) 
{
    var y = document.createElement('textarea');
    y.innerHTML = input;
    y.innerHTML = y.value;
    return y.value.replace(/(<([^>]+)>)/ig,"");
}

function choosologyUrlSafeGlobal(path) {
    if (typeof choosologyUrl === "function") {
        return choosologyUrl(path);
    }
    path = String(path || "").replace(/^\//, "");
    return path ? ("/" + path) : "/";
}

/**
 * Create a new experiment from My Stuff (or elsewhere) and open the graph editor.
 */
function makeNewExperiment(title, options) {
    options = options || {};
    title = String(title || "").trim();
    if (!title) {
        if (typeof showAlert === "function") {
            showAlert("Please enter a name for the experiment.", "error");
        }
        return;
    }
    var $btn = $("#ms_e_newexperiment_submit");
    if ($btn.length) {
        $btn.css("pointer-events", "none").addClass("fgreen");
    }
    $.ajax({
        type: "POST",
        url: choosologyUrlSafeGlobal("ajax/newadventure.php"),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        data: JSON.stringify({ title: title })
    }).done(function (res) {
        if ($btn.length) {
            $btn.css("pointer-events", "").removeClass("fgreen");
        }
        if (res && res.ok && res.id) {
            if (typeof showAlert === "function") {
                showAlert(options.openSettings ? "Draft created. Opening settings..." : "Experiment created. Opening editor...", "success");
            }
            if (options.openSettings && window.sessionStorage) {
                try {
                    window.sessionStorage.setItem("choosologyOpenSettingsForAdvid", String(res.id));
                } catch (err) {
                    /* Ignore private browsing / storage failures; the editor still opens. */
                }
            }
            window.location.href = "#/edit/" + res.id;
            return;
        }
        if (typeof showAlert === "function") {
            showAlert((res && res.error) ? res.error : "Could not create experiment.", "error");
        }
    }).fail(function (xhr) {
        if ($btn.length) {
            $btn.css("pointer-events", "").removeClass("fgreen");
        }
        var msg = "Could not create experiment.";
        if (xhr.responseJSON && xhr.responseJSON.error) {
            msg = xhr.responseJSON.error;
        }
        if (typeof showAlert === "function") {
            showAlert(msg, "error");
        }
    });
}

function listenToEdit()
{
    $(function() {
       $(".editadvbutton").off("click.choosologyEdit").on("click.choosologyEdit", function(e){
           
           var advid = $(this).attr("data-advid");
            location.href='#/edit/'+advid;
            e.stopPropagation();
       });
       $(".deleteadvbutton").off("click.choosologyDelete").on("click.choosologyDelete", function(e){
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var advid = $btn.attr("data-advid");
            var title = $.trim($btn.closest(".miniflag").find(".miniflag-title").text());
            var prompt = title ? "Delete \"" + title + "\"? This cannot be undone." : "Delete this experiment? This cannot be undone.";
            if (!advid || !window.confirm(prompt)) {
                return false;
            }
            $btn.css("pointer-events", "none").text("...");
            $.ajax({
                type: "POST",
                url: choosologyUrlSafeGlobal("ajax/deleteadventure.php"),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                data: JSON.stringify({ advid: advid })
            }).done(function(res) {
                if (res && res.ok) {
                    if (typeof showAlert === "function") {
                        showAlert("Experiment deleted.", "success");
                    }
                    var $panel = $btn.closest(".intabs").parent();
                    if ($panel.length) {
                        $panel.load(choosologyUrlSafeGlobal("mystuff/experiments.php"));
                    } else {
                        $btn.closest(".miniflag").fadeOut(200, function() {
                            $(this).remove();
                        });
                    }
                    return;
                }
                $btn.css("pointer-events", "").text("X");
                if (typeof showAlert === "function") {
                    showAlert((res && res.error) ? res.error : "Could not delete experiment.", "error");
                }
            }).fail(function(xhr) {
                $btn.css("pointer-events", "").text("X");
                var msg = "Could not delete experiment.";
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                if (typeof showAlert === "function") {
                    showAlert(msg, "error");
                }
            });
            return false;
       });
    });
}

$(window).scroll(function () { 
  window.scrollTo(0,0);
});