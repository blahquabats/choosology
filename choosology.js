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
    return $.format.date(datetime, "h:mma on MM/dd/yyyy");
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
        url: 'ajax/fetchcomments.php?name='+name+'&screen='+screen,
        type: "post",
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
                showAlert(errmess, "error");
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
          showAlert(errmess, "error");
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
          if(page > 1) document.getElementById('CAcommentslastpage'+id).innerHTML='<small><a onclick="loadComments(\''+id+'\', '+(page-1)+')"><- Newer</a></small>';

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
         document.getElementById('CAcommentsnextpage'+id).innerHTML='<small><a onclick="loadComments(\''+id+'\', '+(page+1)+')">Older -></a></small>';
      }
      else
      {
         document.getElementById('CAcommentscountend'+id).innerHTML=num;
      }
           }
         if(num > 10)
         {
      document.getElementById('CAcommentscountfull'+id).innerHTML=' of '+num;
      
   }
   if(document.getElementById('CAloader'+id)) document.getElementById('CAloader'+id).style.display='none';
}
         
function loadComments(name, page)
{
     if(!page) page=1;
    $.ajax({
    url: 'ajax/fetchcomments.php',
    type: 'GET',
    data: {
        name: name,
        page: page,
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
