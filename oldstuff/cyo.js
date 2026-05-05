	var screensrc=new Array();
	var screensrcfull=new Array();
	var advssrc=new Array();
// AJAX THINGS
var cyoXML=createXHRObj();
var svgDoc=null;
function createXHRObj()
{
	var xmlHttp;
	if(window.ActiveXObject)
	{
		try
		{
			xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		}	
		catch (e)
		{
			xmlHttp=false;
		}
	}
	else {	
		try
		{
			xmlHttp=new XMLHttpRequest();
		}
		catch(e)
		{
			xmlHttp=false;
		}
	}
	if(!xmlHttp)
	{
		alert("AJAX error.");
	}
	else {
		
		return xmlHttp;
	}
	}
	
	    function makeGetRequest(url,responsefunction) 
    {
 	    if(cyoXML.readyState==4 || cyoXML.readyState==0)
    	{
          callback=responsefunction;
         
          cyoXML.open('GET', url, true);
          
          cyoXML.onreadystatechange = callback;
	  cyoXML.send(null);
    	}
   	else
    	{
	    setTimeout('makeGetRequest("'+url+'","'+responsefunction+'")',250);
    	}
    }
    // ajax screen saving
    function saveScreen(id)
    {
     // gather screen info first
     // need name/title, text, choices
     Encoder.EncoderType='entity';
     var screenarray=new Array();
     var desc="";
     var choice="";
     var choicetarget="";
     if(!document.getElementById("scname")) 
     {
        alert("Problem?");
        return false;
      }
     screenarray['id']=id;
     screenarray['name']=document.getElementById("scname").value;
     //screenarray['title']=document.getElementById("sctitle").value;
     for (var x=1; x<9; x++)
     {
       if(choice=nicEditors.findEditor("choicelabel"+x))
       {
         choice=choice.getContent().trim();
        // choice=choice.replace(/^\s\s*/, '').replace(/\s\s*$/, '').replace(/<br \/><br \/><br \/>/gm, "<br /><br />").replace(/(\r\n|\n|\r)/gm,"<br />");
            
       }
       else if(document.getElementById('choicelabel'+x))
       {
         choice=document.getElementById('choicelabel'+x).value.trim();
       }
        else break;
        choice=Encoder.htmlEncode(choice, true);
         choice=encodeURIComponent(choice);
         choicetarget=document.getElementById('choicenum'+x).value;
         screenarray['choice'+x]=choice+"|Q-D-|"+choicetarget;
     }
     //desc=document.getElementById("scdesc").innerHTML.replace(/^\s\s*/, '').replace(/\s\s*$/, '').replace(/(\r\n|\n|\r)/gm,"<br />");
     if(desc=nicEditors.findEditor("scdesc"))
     {
       desc=desc.getContent().trim();
       //alert(desc);
       //desc=desc.replace(/^\s\s*/, '').replace(/\s\s*$/, '').replace(/<br \/><br \/><br \/>/gm, "<br /><br />").replace(/(\r\n|\n|\r)/gm,"<br />");
       
       //alert(desc); 

     }
     else
     {
       desc=document.getElementById('scdesc').value.trim();

     }
     desc=Encoder.htmlEncode(desc, true);
     desc=encodeURIComponent(desc);
     var more=false;

     screenarray['text']=desc;
     updateScreenContent(screenarray);
     return false;
    }
    
    function toBin(str){
 var st,i,j,d;
 var arr = [];
 var len = str.length;
 for (i = 1; i<=len; i++){
                //reverse so its like a stack
  d = str.charCodeAt(len-i);
  for (j = 0; j < 8; j++) {
   st = d%2 == '0' ? "class='zero'" : "" 
   arr.push(d%2);
   d = Math.floor(d/2);
  }
 }
        //reverse all bits again.
 return arr.reverse().join("");
}

    	function updateScreenContent(screenarray)
	{
	  if(top.advavail=="public") 
	  {
      alert("You cannot edit this screen while the adventure is available to the public.");
      return false;
    }
     // closeDivUp();
      addClass(document.getElementById('savescreen'),"fdisable");
      document.getElementById('savescreen').innerHTML="Working...";
	  //	svgDoc.getElementById("loadingoverlay").style.display="block";
   // 	svgDoc.getElementById("loadingoverlaytext").style.display="block";
	 // 	svgDoc.getElementById("throbgroup").style.display="block";
	  	
		if(screenarray)
		{
		if(cyoXML.readyState==4 || cyoXML.readyState==0)
		{
		 var post="";
		  for (var x in screenarray)
		  {
       // for (var y in screenarray[x])
        //{
            post+=screenarray['id']+"_"+x+"="+screenarray[x]+"&";
       // }
      }
      post+="advid="+top.advid;
      post+="&justscreen=1";
     //alert(post);
     // return false;
      
  			cyoXML.open("POST","updateadv.php",true);
			cyoXML.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			cyoXML.setRequestHeader("Content-length",post.length);
			//cyoXML.setRequestHeader("Connection","close");
			cyoXML.onreadystatechange=saveScreenResponse;
			cyoXML.send(post);  
		}
		else 
		{
			setTimeout('updateScreenContent('+screenarray+')',1000);
		}
		}
		else return false;
		
		
   }
   
   	function saveScreenResponse()
	{     
       	if(cyoXML.readyState==4)
		{
			if(cyoXML.status==200)
			{
			
//      var svgDoc = document.getElementById('canvas').contentDocument;
	  //	svgDoc.getElementById("loadingoverlay").style.display="none";
   // 	svgDoc.getElementById("loadingoverlaytext").style.display="none";
	 // 	svgDoc.getElementById("throbgroup").style.display="none";
      makeClick(svgDoc.getElementById('toadvresponse'), 'toadvresponse'); 		  
      if(top.document.getElementById("miniback").style.display=="none") {
      makeClick(svgDoc.getElementById('menuop1fake'), 'menuop1fake');
      }
			}
			else {
				alert("There was a problem accessing the server: "+cyoXML.statusText);
			}
		}	
  }



	function sendRating(stid,id,sid)
	{
		document.getElementById("starsloading"+id).style.display="block";
		var rating=stid;
		if(id>0 && stid>0)
		{
		if(cyoXML.readyState==4 || cyoXML.readyState==0)
		{
			cyoXML.open("GET","ratjax.php?rating="+rating+"&adv="+id+"&screen="+sid,true);
			cyoXML.onreadystatechange=ratingResponse;
			cyoXML.send(null);
		}
		else 
		{
			setTimeout('sendRating()',1000);
		}
		}
		else alert("There was an error trying to send the rating. What are you doing?");
	}
	
	
	function ratingResponse()
	{
		if(cyoXML.readyState==4)
		{
			if(cyoXML.status==200)
			{
			xmlResponse=cyoXML.responseXML;
			xmlDocEl=xmlResponse.documentElement;
			var id=xmlDocEl.getElementsByTagName("advid");
			id=id[0].firstChild.data;
      var succ=xmlDocEl.getElementsByTagName("success");
			var rating=xmlDocEl.getElementsByTagName("rating");
			rating=rating[0].firstChild.data;
			var myrating=xmlDocEl.getElementsByTagName("myrating");
			myrating=myrating[0].firstChild.data;
			document.getElementById("starsrating"+id).value=rating;
			showAvgStars(id);
			
			var sh=document.getElementById("starsloading"+id);
			sh.style.display="none";
			var num=xmlDocEl.getElementsByTagName("number");
			num=num[0].firstChild.data;
		//		if(rating==0) sh.innerHTML="<div class='ratingholder'><img class='understar' src='icons/normal/stars.png'><div class='overstar' style='width:"+num+"%'><img src='icons/over/stars.png'></div></div> <div><small>Not enough ratings</small></div><br><br>";
		//	else sh.innerHTML="<div class='ratingholder'><img class='understar' src='icons/normal/stars.png'><div class='overstar' style='width:"+num+"%'><img src='icons/over/stars.png'></div></div> <div><small>Average: "+rating+" stars</small></div><br>";
			var rr=document.getElementById("rateresponse"+id);
			var ry=document.getElementById("rateyours"+id);
			if(succ[0].firstChild.data=="1") 
      {
         rr.innerHTML="Thanks for rating!";
         ry.innerHTML="Your rating: <b>"+myrating+"</b> stars";
      }
			else 
      {
         rr.innerHTML="Rating failed...";
         ry.innerHTML="";
      } 
			}
			else {
				alert("There was a problem accessing the server: "+cyoXML.statusText);
			}
		}	
	}
	var ids=new Array();
    var searchnames = new Array();
	function searchDisplay()
	{
		if(cyoXML.readyState==4)
		{
			if(cyoXML.status==200)
			{
			xmlResponse=cyoXML.responseXML;
			xmlDocEl=xmlResponse.documentElement;
			var results=new Array();

			var r;
			var i;
            var ns;
   			var listing="";
			for (var count=1;count<11;count++)
			{
			if(xmlDocEl.getElementsByTagName("result"+count))
			{
				r=xmlDocEl.getElementsByTagName("result"+count);
				i=xmlDocEl.getElementsByTagName("id"+count);
                ns=xmlDocEl.getElementsByTagName("nosubmit"+count);
			if(r[0]) results[count]=r[0].firstChild.data;
			else break;
			ids[count]=i[0].firstChild.data;
            searchnames[count]=r[0].firstChild.data;
            nsval = ns[0].firstChild.data;
			}
			else break;
			}
			if(results[1]==0) return false;
			for(count=1;results[count];count++)
			{

                if(nsval==1) var listonclick = "$('#usersearchbox').val('"+results[count]+"')";
                else var listonclick = "window.location='profile.php?user="+ids[count]+"'";

			    listing+="<div class='userlisting' onmouseover=\"userListSelect("+count+")\" onmouseout=\"userListUnselect("+count+")\" onclick=\""+listonclick+"\" id=\"userselect"+count+"\">"+results[count]+" <small>(#"+ids[count]+")</small></div>";
			}
			if(listing=="") listing="No results.";
			document.getElementById("usersearchresults").innerHTML=listing;
			
			}
			else {
				alert("There was a problem accessing the server: "+cyoXML.statusText);
			}
		}	
	}

	function imgNameReturn()
	{
if(cyoXML.readyState==4)
		{
			if(cyoXML.status==200)
			{
			xmlResponse=cyoXML.responseXML;
			xmlDocEl=xmlResponse.documentElement;
		
			var s=xmlDocEl.getElementsByTagName("success");
			var success=s[0].firstChild.data;

			if(success)
			{
							var sn=document.getElementById("showname"+success);
			var nn=document.getElementById("namechange"+success).value;
			nn=nn.substring(0,25);
				sn.innerHTML=nn;
				editName(success);	
				document.getElementById("namechange"+success).value=nn;
			}
			else
			{
				
			}
		}
		else {
				alert("There was a problem accessing the server: "+cyoXML.statusText);
			}
		}
		}
			
			function imgAssignReturn()
	{
if(cyoXML.readyState==4)
		{
			if(cyoXML.status==200)
			{
			xmlResponse=cyoXML.responseXML;
			xmlDocEl=xmlResponse.documentElement;
		
			var s=xmlDocEl.getElementsByTagName("success");
			var success=s[0].firstChild.data;

			if(success)
			{
				document.getElementById("ppoll"+success).style.display="none";	
			}
			else
			{
				document.getElementById("ppoll"+success).innerHTML="Error! Please reload the page and try again.";
			}
			IPMenu(success);
		}
		else {
				alert("There was a problem accessing the server: "+cyoXML.statusText);
			}
		}
		}
// DONE WITH AJAX
var catchangeopen=0;
var namechangeopen=0;

     function addClass(element, value) {
      if (!element.className) {
      element.className = value;
      } else {
      var newClassName = element.className;
      newClassName += " ";
      newClassName += value;
      element.className = newClassName;
      }
      }
	function removeClass(ele,cls) 
	{
	if (ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'))) {
	var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
	ele.className=ele.className.replace(reg,' ');
	}
	}
function showlogin()
{
	var lin=document.getElementById("loginbit");
	if(lin.style.display!='block') 
	{
		lin.style.display="block";
		document.getElementById("loginname").focus();
	}
	else lin.style.display='none';
}


      function getElementsByClassName(classname, node) {
      if(!node) node = document.getElementsByTagName("body")[0];
      var a = [];
      var re = new RegExp('\\b' + classname + '\\b');
      var els = node.getElementsByTagName("*");
      for(var i=0,j=els.length; i<j; i++)
      if(re.test(els[i].className))a.push(els[i]);
      return a;
      }

function checkScreenFields()
{
	if(document.getElementById('scname').value=="")
	{
		alert("You need to enter a screen name for this screen to be saved!");
		return false;
	}
	return true;
}

function catChange(val)
{
 document.getElementById("whichcat").value=val;
}

function deletePic(id)
{
	if(!confirm("Truly delete this picture? It will be removed from any screens you may have which use it.")) return false;
	document.getElementById("actioninput").value="delete";
	document.getElementById("whichinput").value=id;
	document.getElementById("actionform").submit();
}

function viewPic(id, w, h)
{
   document.getElementById("lb").innerHTML="<img src='"+screensrcfull[id]+"' />";
   var light=document.getElementById("light");
   var hopew=parseInt(w);
   var hopeh=parseInt(h);
   var winw, winh;
   if( typeof( window.innerWidth ) == 'number' ) {
    //Non-IE
    winw = window.innerWidth;
    winh = window.innerHeight;
  } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
    //IE 6+ in 'standards compliant mode'
    winw = document.documentElement.clientWidth;
    winh = document.documentElement.clientHeight;
  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
    //IE 4 compatible
    winw= document.body.clientWidth;
    winh = document.body.clientHeight;
  }
   var rwinw=winw-100;
   var rwinh=winh-50;
   
   if(hopew>rwinw) hopew=parseInt(rwinw);
   if(hopeh>rwinh) hopeh=parseInt(rwinh);
   
   var percw=parseInt(50*((winw-hopew-50)/winw));
   var perch=parseInt(50*((winh-hopeh-50)/winh));
   light.style.width=hopew+"px";
   light.style.height=hopeh+"px";
   light.style.top=perch+"%";
   light.style.left=percw+"%";
   light.style.display="block";
   
   document.getElementById("fade").style.display="block";
   
}

	function switchThumb(from,to)
{
	
	var newid=document.getElementById(from).options[document.getElementById(from).selectedIndex].value;
	if(newid==0)
	{
		document.getElementById(to).innerHTML="";
		return true;
	}
	var path=screensrc[newid];

	document.getElementById(to).innerHTML="<img src=\""+path+"\" />";
}

function editChosen(which,id,parent)
{
	var screen=document.getElementById('choice'+which+'id').options[document.getElementById('choice'+which+'id').selectedIndex].value;
	if(screen=="0") screen="new&parent="+parent;
	newScreenEdit(id,screen);
	
}
function newScreenEdit(id,screen)
{
window.open("members.php?loc=adv&id="+id+"&screen="+screen,"Edit","location=1,status=1,scrollbars=1,menubar=1,resizable=1,directories=1,width=1000,height=600");
}
function newAdvEdit(id)
{
window.open("members.php?loc=adv&id="+id,"Edit","location=1,status=1,scrollbars=1,menubar=1,resizable=1,directories=1,width=1000,height=600");
}
function checkAvail(which, ignorecheck)
{
	var ae=document.getElementById("availexp");
    if (!ignorecheck) document.getElementById("pubconfirm").checked = false;
	if(svgDoc)
  {
  var ai=svgDoc.getElementById("avail");
	ai.setAttribute("fill", "#ccccff");
	}
	if(which=="private")
	{
	    document.getElementById("passholder").style.display="block";
        document.getElementById("pubconfirmholder").style.display = "block";
        document.getElementById("cantpubholder").style.display = "none";
	    ae.innerHTML="Private adventures are only visible to people<br /> with the password you enter below:";
	}
	else 
	{
		document.getElementById("passholder").style.display="none";
		if(which=="none")
		{
            document.getElementById("pubconfirmholder").style.display = "none";
            document.getElementById("cantpubholder").style.display = "none";
			ae.innerHTML="Unavailable adventures are only visible to you.";
		}
		if(which=="restricted")
		{
            if (top.document.usedscreens < 1)
            {
                document.getElementById("cantpubholder").style.display = "block";
                document.getElementById("pubconfirmholder").style.display = "none";
            }
            else
            {
                document.getElementById("pubconfirmholder").style.display = "block";
                document.getElementById("cantpubholder").style.display = "none";
            }
			ae.innerHTML="Restricted adventures contain mature content<br />and are only visible to users who have chosen<br />to be able to see such content.";
		}
		if(which=="public")
		{
            if (top.document.usedscreens < 1)
            {
                document.getElementById("pubconfirmholder").style.display = "none";
                document.getElementById("cantpubholder").style.display = "block";
            }
            else
            {
                document.getElementById("pubconfirmholder").style.display = "block";
                document.getElementById("cantpubholder").style.display = "none";
            }
			ae.innerHTML="Publicly available adventures are visible to anyone<br/>on the internet and must be family-friendly.";
			if(ai) ai.setAttribute("fill", "#ff0000");
		}
	}
	
	
}

function fontchange()
{
	
	var sel=document.getElementById("fontselect");
	var tsel=document.getElementById("titlefontselect");
	var font=sel.options[sel.selectedIndex].value;
	var tfont=tsel.options[tsel.selectedIndex].value;
	document.getElementById("advdesc").style.fontFamily="\""+font+"\"";
	document.getElementById("advtitle").style.fontFamily="\""+tfont+"\"";
	
}
  
  function saveAdvOpts(id)
  {
     document.getElementById("advoptload").style.display="block";
    // document.getElementById("advoptload").innerHTML="<br /><br /><br /><br /><div class='success' style='margin-left:auto;margin-right:auto;width:25%'>Loading...</div>";
     var sendarray=new Array();
     var titlestyles=getTextStyles("title");
     sendarray['titlestyle']="font-weight:"+titlestyles['bold']+"; font-style:"+titlestyles['italic']+"; text-align:"+titlestyles['justify']+"; color:"+titlestyles['fontcol']+"; font-family:\""+titlestyles['font']+"\"";    
     var ts=getTextStyles("text");
     sendarray['textstyle']="font-weight:"+ts['bold']+"; font-style:"+ts['italic']+"; text-align:"+ts['justify']+"; color:"+ts['fontcol']+"; font-family:\""+ts['font']+"\"";
     ts=getTextStyles("link");
     sendarray['linkstyle']="font-weight:"+ts['bold']+"; font-style:"+ts['italic']+"; text-align:"+ts['justify']+"; color:"+ts['fontcol']+"; font-family:\""+ts['font']+"\"";    
     
     sendarray['box'] = document.getElementById("forecol").value;
     sendarray['border'] = document.getElementById("borcol").value;
     sendarray['borderwidth'] = document.getElementById("borderwidth").value;
     sendarray['bg'] = document.getElementById("backcol").value;
     sendarray['bgpic'] = document.getElementById("hideybgpic").value;
     //sendarray['usesimplestyling'] = (document.getElementById("usesimplestyle").checked) ? 1 : 0;
     sendarray['title']=nicEditors.findEditor("advtitle").getContent();
     sendarray['description']=nicEditors.findEditor("advdesc").getContent();
     var avail=document.getElementById("avail");
     var pubconfirm = document.getElementById("pubconfirm").checked;
     if(avail.options[avail.selectedIndex].value != "none" && !pubconfirm)
     {
         var text = "You must confirm that you're really ready to publish this adventure.";
         if(avail.options[avail.selectedIndex].value == "public") text += "\nAnyone will be able to see it, rate it, and comment on it!";
         if (top.document.usedscreens < 1) text = "You don't have enough screens to publish yet!";
         alert(text);
         document.getElementById("advoptload").style.display="none";
         return false;
     }
     sendarray['pubconfirm'] = pubconfirm;
     sendarray['avail']=avail.options[avail.selectedIndex].value;
     var begin=document.getElementById("beginid");
     sendarray['begin']=begin.options[begin.selectedIndex].value;
     sendarray['pass'] = document.getElementById("privatepass").value;
     sendarray['pic'] = picchoiceid[id+'-0'];
     sendarray['id']=id;
		
		if(sendarray)
		{
		if(cyoXML.readyState==4 || cyoXML.readyState==0)
		{
		 var post="";
		  for (var x in sendarray)
		  {
		       if(sendarray[x].replace) sendarray[x]=sendarray[x].replace(/^\s\s*/, '').replace(/\s\s*$/, '').replace(/(\r\n|\n|\r)/gm,"<br />");
           sendarray[x]=encodeURIComponent(sendarray[x]);
           post+=x+"="+sendarray[x]+"&";       
      }
          
			cyoXML.open("POST","saveadvopts.php",true);
			cyoXML.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			cyoXML.setRequestHeader("Content-length",post.length);
			cyoXML.onreadystatechange=advOptResponse;
			cyoXML.send(post);
		}
		else 
		{
			setTimeout('saveAdvOpts('+id+')',500);
		}
		}
		else 
    {
    document.getElementById("advoptload").style.display="none";
    return false;
    }
  
  }
  
  function advOptResponse()
  {
    if(cyoXML.readyState==4)
		{
			if(cyoXML.status==200)
			{
          xmlResponse=cyoXML.responseXML;
    			xmlDocEl=xmlResponse.documentElement;
    			var id=xmlDocEl.getElementsByTagName("id");
    			id=id[0].firstChild.data;
    			var avail=xmlDocEl.getElementsByTagName("avail");

    			if(!isNaN(id) && parseInt(id)==id)
                {
                    document.getElementById("advoptload").innerHTML="<br /><br /><br /><br /><div class='success' style='width:25%;margin-left:auto;margin-right:auto;font-weight:bold;'>Adventure Saved!</div>";
                    advavail=avail[0].firstChild.data;
                }
    			else
                {
                    document.getElementById("advoptload").innerHTML="<br /><br /><br /><br /><div class='error' style='width:25%;margin-left:auto;margin-right:auto;font-weight:bold;'>"+id+"</div>";
                }

    		  
    		  setTimeout("document.getElementById('advoptload').style.display='none'",1500);
			}
			else {
				alert("There was a problem accessing the server: "+cyoXML.statusText);
			}
		}
  
  }
  
function hlText(el)
{
    el.style.borderColor='#ff0000';
}

function dlText(el)
{
    el.style.borderColor='#ff9999';
}

function switchTo(which)
{
   var switchfrom=document.getElementById(document.getElementById('whichstuff').value);
   if(switchfrom=="nothing" || !switchfrom) 
   {
     which="textstuff";
   }
   
    if(which=="nothing")
   {
     if(document.getElementById('textf').style.visibility=="hidden")
     {
       document.getElementById('textf').style.visibility="visible";
       document.getElementById('textb').style.visibility="visible";
       which="textstuff";
     }
     else 
     {
       document.getElementById('textb').style.visibility="hidden";
       document.getElementById('textf').style.visibility="hidden";
       which="backgroundstuff";       
     }
   }
   
   var switchto=document.getElementById(which);
   document.getElementById('whichstuff').value=which;
   
   if(which!="nothing") addClass(switchto, "fakeinlineblock");
   if(switchfrom && switchfrom!="nothing") removeClass(switchfrom, "fakeinlineblock");
   
}

function switchSec(which)
{
   // var SVGdoc=document.getElementById('canvas').getSVGDocument();
    var box = (which=="vised") ? "overdiv" : "miniback";
     if(which != 'vised' && document.getElementById('overdiv').style.visibility!="hidden") 
        {
           
           document.getElementById('overdiv').style.visibility="hidden";
           removeClass(document.getElementById('vised'),"fdisable");
           if(document.getElementById('divup1').style.display=='block') makeClick(document.getElementById('savescreen'), 'savescreen'); 
           else makeClick(svgDoc.getElementById('savebutton'), 'savebutton');
           document.getElementById('overdiv').style.height="0px";
           document.getElementById("divup1").style.height="0px";
           document.getElementById(box).style.display="block";
           addClass(document.getElementById(which),"fdisable"); 
           
        }
     if(which != 'advop' && document.getElementById('miniback').style.display!="none") 
        {
           document.getElementById('miniback').style.display="none";
           document.getElementById('overdiv').style.visibility="visible";
           removeClass(document.getElementById('advop'),"fdisable");
           makeClick(svgDoc.getElementById('menuop1fake'), 'menuop1fake');
          //document.getElementById(box).style.height="500px";
            resizeOverDiv();
            makeClick(svgDoc.getElementById('getallscreensfake'), 'getallscreensfake');
            addClass(document.getElementById(which),"fdisable");
            
        }
}

function selectText(which)
{
    var val=document.getElementById('textbuttonswhich').value;
    if(val!="") 
    {
       document.getElementById('textbuttons'+val).style.display='none';
       if (document.getElementById("tb"+val).filters) document.getElementById("tb"+val).filters.alpha.opacity=60;
       else document.getElementById("tb"+val).style.opacity=.6;
    }
    if (document.getElementById("tb"+which).filters) document.getElementById("tb"+which).filters.alpha.opacity=100;
    else document.getElementById("tb"+which).style.opacity=1;
    document.getElementById('textbuttonswhich').value=which;
    document.getElementById('textbuttons'+which).style.display='block';
}

function getTextStyles(which)
{
    var returns= new Array();
    returns['bold']= document.getElementById("screenfont-weight"+which).checked ? "bold" : "normal";
    returns['italic']= document.getElementById("screenfont-style"+which).checked ? "italic" : "normal";
    
    var fontface=document.getElementById("advform").elements["screenfontface"+which];
    returns['font']=fontface.options[fontface.selectedIndex].value;
    
    returns['fontcol']=document.getElementById(which+"col").value;
    
    var justifyrad=document.getElementById("advform").elements["screentext"+which+"r"];
    var radioLength=justifyrad.length;
        for(var i = 0; i < radioLength; i++) 
        {
      		if(justifyrad[i].checked) {
      			returns['justify']=justifyrad[i].value;
      			break;
      		}
       	}
      return returns;
}

function updateText(which)
{
    var styles=getTextStyles(which); 
    var el=document.getElementById("tb"+which);
    el.style.color=styles['fontcol'];
    el.style.fontWeight=styles['bold'];
    el.style.fontStyle=styles['italic'];
    el.style.textAlign=styles['justify'];
    el.style.fontFamily=styles['font'];
}

function updateStyles()
{

    var forefill = document.getElementById("forecol").value;
    var foreborder = document.getElementById("borcol").value;
    var borderwidth = document.getElementById("borderwidth").value;
    var backcol = document.getElementById("backcol").value;
    var bgpic = document.getElementById("hideybgpic").value;
    
    var r=parseInt((forefill.substring(1,3)), 16);
    var g=parseInt((forefill.substring(3,5)), 16);
    var b=parseInt((forefill.substring(5,7)), 16);
    
    var brightness = Math.sqrt(Math.pow((r * .299), 2)+Math.pow((g * .587), 2)+Math.pow((b * .114), 2));
    if(brightness < 110) var textcolor="white";
    else var textcolor="black"; 
    
    document.getElementById('fgchoice').value='#'+forefill;
    document.getElementById('fgbchoice').value='#'+foreborder;
    
    var fg=document.getElementById('minifore');
    var bg=document.getElementById('miniback');
    
    fg.style.backgroundColor=forefill;
    fg.style.color=textcolor;
    fg.style.borderColor=foreborder;
    fg.style.borderWidth=borderwidth+"px";
    
    bg.style.backgroundColor=backcol;
    if(bgpic) bg.style.backgroundImage="url('"+screensrcfull[bgpic]+"')";

}

function cssBodyChange(bg,box,border,borderwidth,textcolor,linkcolor,bgpic, boxpic)
{

  if(!bg) bg=document.getElementById("backcol").value;
	if(!box) box=document.getElementById("fgchoice").value;
	if(!border) border=document.getElementById("fgbchoice").value;
	if(!borderwidth) borderwidth=document.getElementById("borderwidth").options[document.getElementById("borderwidth").selectedIndex].value;
	if(!textcolor) try {textcolor=document.getElementById("textcolor").value;}  catch(err){}
	     var r=parseInt((box.substring(1,3)), 16);
    var g=parseInt((box.substring(3,5)), 16);
    var b=parseInt((box.substring(5,7)), 16);
    
    var brightness = Math.sqrt(Math.pow((r * .299), 2)+Math.pow((g * .587), 2)+Math.pow((b * .114), 2));
    if(brightness < 110) var textcolor="white";
    else var textcolor="black";
	if(!linkcolor) try {linkcolor=document.getElementById("linkcolor").value;}  catch(err){}
	if(!bgpic && document.getElementById("hideybgpic")) bgpic=document.getElementById("hideybgpic").value;
    if(!boxpic && document.getElementById("hideyboxpic")) bgpic=document.getElementById("hideyboxpic").value;
	//alert (box+" "+bg+" "+border+" "+borderwidth);
	//document.getElementById("thebody").style.="box"+box;
	var allPageTags=document.getElementsByTagName("*"); 
 //Cycle through the tags using a for loop 
 for (i=0; i<allPageTags.length; i++) { 
 //Pick out the tags with our class name 
 if (allPageTags[i].className.substr(0,4)=="body") { 
 //Manipulate this in whatever way you want 
 allPageTags[i].style.backgroundColor=box;
 allPageTags[i].style.border=borderwidth+"px solid "+border; 
if(textcolor) allPageTags[i].style.color=textcolor;  
 } 
 } 
	document.getElementById("thebody").style.backgroundColor=bg;
	//document.getElementById("thebody").style.backgroundImage="url('"+screensrcfull[bgpic]+"')";
    if(bgpic) $("#thebody").css("background-image","url('"+screensrcfull[bgpic]+"')");
    if(bgpic) $(".body").css("background-image","url('"+screensrcfull[boxpic]+"')");
	if(linkcolor && false)
	{
	var allas=document.getElementsByTagName("a");
	for (a=0;a<allas.length;a++)
	{
		allas[a].style.color=linkcolor;
	} 
}
	var allbutts=document.getElementsByClassName("ftrans");
	for (a=0;a<allbutts.length;a++)
	{
		allbutts[a].style.color=textcolor;
	} 
}

    var timeOuts = new Array();
// for smooth open/closing
       function clearAllTimeouts(id){  
   for(key in top.timeOuts[id] ){  
     clearTimeout(top.timeOuts[id][key]);  
   }  
 }  

function togcats(which)
{
	if(document.getElementById("cate"+which).style.display!="none")
	{
		document.getElementById("cate"+which).style.display="none";
		//smoothClose(which);
	}
	else 
	{
	      document.getElementById("cate"+which).style.display="block";
				//smoothOpen(which);
		

		}
}

function togTop()
{
	if(document.getElementById("topbanner").style.display!="block")
	{
		document.getElementById("topbanner").style.display="block";
		document.getElementById("collapseholder").innerHTML="Hide banner";
	}
	else 
	{
		document.getElementById("topbanner").style.display="none";
		document.getElementById("collapseholder").innerHTML="Show banner";
		}
}

     function smoothOpen(id)
     {
         clearAllTimeouts(id);
         top.timeOuts[id]=new Array();
         var el = top.document.getElementById("cate"+id); //how large do we need to get?
         var elh=el.offsetHeight+3;
         var v=top.document.getElementById("cat"+id);
         var vh=v.offsetHeight;
         var th=0;
         if(vh<elh+th)
         {
         var time=0;
         for(var count=6;count-20<elh;count=count+20)
         {  // count is total extra pixels; should go 20 per 1/40 of a second or until it hits elh
               if(count>elh) count=elh;
               top.timeOuts[id][count]=setTimeout("top.document.getElementById('"+v.id+"').style.height='"+eval(th+count)+"px'",time);            
               time+=25;
               
         }
         //top.timeOuts[id][count]=setTimeout("document.getElementById('cate"+id+"').style.visibility='visible';top.document.getElementById('"+v.id+"').style.height='"+eval(th+elh)+"px'",time);
         top.timeOuts[id][count]=setTimeout("document.getElementById('cate"+id+"').style.visibility='visible';top.document.getElementById('"+v.id+"').style.height='auto'",time);
         }
     } 
     function smoothClose(id)
     {
         clearAllTimeouts(id);
         top.timeOuts[id]=new Array();
         var elh=0;
         var v=top.document.getElementById("cat"+id);
         var vh=v.offsetHeight;
         
         if(vh>elh)
         {
         var time=50;
         for(var count=10;count-24<(vh-elh);count=count+24)
         {  // count is total extra pixels; should go 24 per 1/40 of a second or until it hits 0
               if(count>(vh-elh)) count=(vh-elh);
               top.timeOuts[id][count]=setTimeout("top.document.getElementById('"+v.id+"').style.height='"+eval(vh-count)+"px'",time);         
               time+=25;
               
         }
         }
     }
     
     function expandall()
     {
     	var count=1;
     	while(document.getElementById("cat"+count))
     	{
     		document.getElementById("cat"+count).style.height=document.getElementById("cate"+count).offsetHeight+"px";
     		//document.getElementById("cate"+count).style.visibility="visible";
     		document.getElementById("cate"+count).style.display="block";
     		count++;
     	}
     	
     }
     
          function collapseall()
     {
     	var count=1;
     	while(document.getElementById("cat"+count))
     	{
     		document.getElementById("cat"+count).style.height="0px";
     		//document.getElementById("cate"+count).style.visibility="hidden";
     		document.getElementById("cate"+count).style.display="none";
     		count++;
     	}
     }
     
     function indexResize()
     {
         //return false;
     	var ah=document.getElementById('ish').offsetHeight; 
                                var sb=document.getElementById('indexbody');
                                if(sb.style.height<(ah+60)) sb.style.minHeight=(ah+60)+'px';
     }

     
     function switchNews(which)
     {
     	var sb=document.getElementById('indexbody').style.height="auto";
     	     	var count=1;
     	     	while(document.getElementById("update"+count) && document.getElementById("headline"+count))
     	{
     		document.getElementById("update"+count).style.display="none";
            document.getElementById("headline"+count).style.backgroundColor="#ccddff";
     		count++;
     	}
     		document.getElementById("update"+which).style.display="block";
            document.getElementById("headline"+which).style.backgroundColor="#eeeeff";
     		indexResize();
     	
     }

	function checkFloat(val)
	{
		if(val=="center") document.getElementById("floatornot").disabled=true;
		else document.getElementById("floatornot").disabled=false;
	}
	
	function editCat(which,ignore)
	{
		var inp=document.getElementById('catchange'+which);
		var sub=document.getElementById('catsub'+which);
		var box=document.getElementById('catchangebox'+which);
		if(box.style.display!="block")
		{
			if(!ignore)
			{
			if(namechangeopen>0) editName(namechangeopen,1);
			if(catchangeopen>0) editCat(catchangeopen,1);
		}
		catchangeopen=which;
		box.style.display="block";
		inp.focus();
		inp.select();
		
		}
		else
		{
	//	inp.style.visibility="hidden";
		//	sub.style.visibility="hidden";
		box.style.display="none";
				inp.value="Enter category...";
		inp.style.color='#666';
			catchangeopen=0;	
		}
		
	}
	function editName(which,ignore)
	{
		var inp=document.getElementById('namechange'+which);
		var box=document.getElementById('namechangebox'+which);
		
		if(box.style.display!="block")
		{
		if(!ignore)
			{
			if(namechangeopen>0) editName(namechangeopen,1);
			if(catchangeopen>0) editCat(catchangeopen,1);
			}
			namechangeopen=which;
		box.style.display="block";
		inp.focus();
		inp.select();
		
		}
		else
		{
			var orig=document.getElementById('origname'+which);
		box.style.display="none";
				inp.value=orig.value;
			namechangeopen=0;	
		}
		
	}
	
	function depopCatchange(which)
	{
		var inp=document.getElementById('catchange'+which);
		if(inp.style.color!='black')
		{
		inp.value="";
		inp.style.color='black';
		}
	}
	
	function highlightStars(num, id)
	{
		var c=1;
	  while (c<=5) //clear all
		{
  	document.getElementById("stavg"+c+"-"+id).style.width="0%";
  	c++;
  	}
  	c=1;
    while (c<=num)
		{
			document.getElementById("st"+c+"-"+id).src="icons/over/star.png";  
			c++;
		}
		
		
	}
    function showAvgStars(id)
	{
	  var avg=document.getElementById("starsrating"+id).value;
		var c=1;
		while (c<=5) //clear all
		{
			document.getElementById("st"+c+"-"+id).src="icons/normal/star.png";
			c++;
		}
		 var oavg=avg;
		 avg=parseFloat(avg);
		 c=1;
		 if(avg===0)
		 {
       document.getElementById("rateresponse"+id).innerHTML="Not enough ratings";
       return false;
     }
		while (c<=parseInt(avg)) //show avg amount
		{
			document.getElementById("stavg"+c+"-"+id).style.width='100%';
			c++;
		}
		c--;
		avg=avg-parseFloat(c);
		avg=Math.round(avg*100.00);
		if(c<5) 
    {
		document.getElementById("stavg"+(c+1)+"-"+id).style.width=avg+"%";
		}
		       document.getElementById("rateresponse"+id).innerHTML="Average rating: <b>"+oavg+"</b> stars";
	}
	
	var searchTO;
	function searchCheck(e, nosubmit)
	{
        if(!nosubmit) nosubmit=false;
		clearTimeout(searchTO);
		 var keyid = (window.event) ? event.keyCode : e.keyCode;
		 var selected=document.getElementById("userselected").value;

            if(keyid===13 || keyid ===9) // enter or tab
		 {
		 	if(selected==0) return true;
            if(nosubmit)
            {
                 $('#usersearchbox').val(searchnames[selected]);
                var box2=$("#usersearchresults");
                box2.css('display', "none");
                return true;
            }
		 	if(keyid === 13) window.location="profile.php?user="+ids[selected];
		 	return false;
		 }
		  selected=parseInt(selected);
		 if(keyid==38) //up arrow
		 {
		 	if(selected==0) userListSelect(1);
		 	else // figure out where to move.. 
		 	{
		 		if(selected==1)
		 		{
		 			for(count=2;count<11;count++)
		 			{
		 				if(!document.getElementById('userselect'+count)) break;
		 			}
		 			var move=count-1;
		 		}
		 		else var move=selected-1;
		 		userListSelect(move);
		 	}
		 	return false;
		 }
		 if (keyid==40) // down arrow
		 {
		 	
		 	if(selected==0) userListSelect(1);
		 	else // figure out where to move.. 
		 	{
		 		var move=selected+1;
		 		if(!document.getElementById("userselect"+move)) move=1;
		 		userListSelect(move);
		 	}
		 	return false;
		 }

		var box=document.getElementById("usersearchbox");
		var box2=document.getElementById("usersearchresults");
		var val=box.value;

		if(val.length>2)
		{
		if(cyoXML.readyState==4 || cyoXML.readyState==0)
		{
            val = encodeURIComponent(val);
            if(nosubmit==1) val += "&nosubmit=1";
			cyoXML.open("GET","searchusers.php?user="+val,true);
			cyoXML.onreadystatechange=searchDisplay;
			cyoXML.send(null);
		}
		else 
		{
			setTimeout('searchCheck(e, nosubmit)',1000);
		}
		box2.style.display="block";
		searchTO=setTimeout("document.getElementById('usersearchresults').style.display='none'",4000);
		}
		else
		box2.style.display="none";
		return true;
		
	}
	
	function userListSelect(id)
	{
		clearTimeout(searchTO);
		var item=document.getElementById("userselect"+id);
		if(!item) return false;
		var selected=document.getElementById("userselected");
		if(selected.value>0)
		{
			userListUnselect(selected.value);
		}
		item.style.backgroundColor='#ccddff';
		selected.value=id;
		
	}

	function userListUnselect(id)
	{
		var item=document.getElementById("userselect"+id);
		var selected=document.getElementById("userselected");
		item.style.backgroundColor='white';
		selected.value=0;
		searchTO=setTimeout("document.getElementById('usersearchresults').style.display='none'",4000);
	}
	
		function imgcheckpress(e)
	{
		 var keyid = (window.event) ? event.keyCode : e.keyCode;
   		 if(keyid==13) return true;
		 return false;
    }
    
    function sendEditName(id)
    {
    	var inp=document.getElementById('namechange'+id);
    		if(cyoXML.readyState==4 || cyoXML.readyState==0)
		{
			cyoXML.open("GET","upimgname.php?id="+id+"&name="+inp.value,true);
			cyoXML.onreadystatechange=imgNameReturn;
			cyoXML.send(null);
		}
		else 
		{
			setTimeout('sendEditName('+id+')',1000);
		}
    }
    
    // pic choosing module
    var picchoice=new Array();
    var picchoicen=new Array();
    var picchoiceid=new Array();
    function rolloverpic(which,id,name)
    {
    	var preview=document.getElementById("ppocp"+which);
   		var prename=document.getElementById("ppocn"+which);
   		var pic=document.getElementById("pic"+which+"-"+id);
  			if(id=="0") preview.innerHTML="";
   		else preview.innerHTML="<img src='"+screensrc[id]+"' />";
   		prename.innerHTML=name;
   		pic.style.backgroundColor="#ccddff";
   		pic.style.margin="0px";
   		pic.style.border="1px solid black";
    }
    
        function rolloutpic(which,id)
    {
    	var preview=document.getElementById("ppocp"+which);
   		var prename=document.getElementById("ppocn"+which);
   		var pic=document.getElementById("pic"+which+"-"+id);
   		if(picchoiceid[which]=="0") preview.innerHTML="";
		   else preview.innerHTML="<img src='"+picchoice[which]+"' />";
   		prename.innerHTML=picchoicen[which];
   		pic.style.backgroundColor="white";
   		pic.style.margin="1px";
   		pic.style.border="0px solid black";
    }
    
    function selectpic(which,id,name,what,row, submit, onclick)
    {
   
  
      if(!onclick) onclick=cssBodyChange;    
    	var preview=document.getElementById("ppocp"+which);
   		var prename=document.getElementById("ppocn"+which);
   		var pic=document.getElementById("pic"+which+"-"+id);
   		var realwhich=which.split("-");
   		realwhich=realwhich[0];
   		if(which!="new" && submit==true)
   		{
   			document.getElementById("ppoll"+which).style.display="block";
   		    		if(cyoXML.readyState==4 || cyoXML.readyState==0)
		{
			cyoXML.open("GET","upimgassign.php?pid="+id+"&what="+what+"&which="+which+"&realwhich="+realwhich+"&row="+row,true);
			cyoXML.onreadystatechange=imgAssignReturn;
			cyoXML.send(null);
		}
		else 
		{
			setTimeout('selectpic("'+which+'",'+id+',"'+name+'","'+what+'","'+row+'")',500);
		}
		}
		else 
		{
			IPMenu(which);
		}
		if(document.getElementById('hidey'+row)) document.getElementById("hidey"+row).value=id;
		var pcw=(picchoiceid[which]) ? picchoiceid[which] : "0";
		var oldpic=document.getElementById("pic"+which+"-"+pcw);
		if(oldpic) removeClass(oldpic,"selected");
		picchoiceid[which]=id;
   		picchoicen[which]=name;
   		picchoice[which]=screensrc[id];
   		addClass(pic,"selected");
   		if(onclick=="cssBodyChange" && document.getElementById("hideybgpic"))
   		{
   		//document.getElementById("hideybgpic").value=id;
   		 eval(onclick+"();");
   		}
   		else if(onclick && onclick!="0") eval(onclick+"();");
   		
    }
    
    function switchpics(which)
    {
      var spl=document.getElementById("sitepicslink"+which);
      var sitepics=document.getElementById("sitepics"+which);
      var userpics=document.getElementById("userpics"+which);
      if(spl.innerHTML=="Site Images") 
      {
      sitepics.style.display='inline';
      userpics.style.display='none';
      spl.innerHTML="My Images";
      }
    	else 
      {
      sitepics.style.display='none';
      userpics.style.display='inline';
      spl.innerHTML="Site Images";
      }
    
    }
    
    function IPMenu(which)
    {
    	var box=document.getElementById("ppo"+which);
    	var spl=document.getElementById("sitepicslink"+which);
    	if(box.style.display!="block") 
      {
      box.style.display="block";
      spl.style.display="block";
      }
    	else 
      {
      box.style.display="none";
      spl.style.display="none";
      }
    }
    
    function insertAtCursor(myField, myValue) {
//IE support
if (document.selection) {
myField.focus();
sel = document.selection.createRange();
sel.text = myValue;
}
//MOZILLA/NETSCAPE support
else if (myField.selectionStart || myField.selectionStart == '0') {
var startPos = myField.selectionStart;
var endPos = myField.selectionEnd;
myField.value = myField.value.substring(0, startPos)
+ myValue
+ myField.value.substring(endPos, myField.value.length);
} else {
myField.value += myValue;
}
}

function startImageInsert(picid, element)
{
if(!picid || picid=="0") return false;
  if(!element) element=document.documentElement;
    if(document.getElementById('imageinserttext').innerHTML=='Click in Text to Insert!')
    {
    element.onclick=null;
    resetCursor(element);
    document.getElementById('imageinserttext').innerHTML='Insert Picture';
    return false;
    }
  
   
   setCursor(picid, element);
   document.getElementById('imageinserttext').innerHTML='Click in Text to Insert!';
   var html="<img src='"+screensrcfull[picid]+"' />";
   element.onclick=function () { 
   insertHtmlAtCursor(html);
   element.onclick=null;
   resetCursor(element);
   document.getElementById('imageinserttext').innerHTML='Insert Into Text';
   };
}

function startVideoInsert(element)
{
  if(!element) element=document.documentElement;
    if(document.getElementById('videoinserttext').innerHTML=='Click in Text to Insert!')
    {
    element.onclick=null;
    //resetCursor(element);
    document.getElementById('videoinserttext').innerHTML='Insert Video';
    return false;
    }   
    var vidid=prompt('Paste a YouTube video URL or ID here:')
if(!vidid)  return false;
   var realid="";
   // figure out what the id really is
   if(vidid.split("v=")!=vidid)
   {
      var ar= vidid.split("v=");
      realid=ar[1].substr(0,11);
   }
   else if(vidid.split("youtu")!=vidid)
   {
        var ar=vidid.split("\/");
        realid=ar[(ar.length-1)]
   }
   else if(vidid.length==11)
   {
      realid=vidid;
   }
   else  alert('Could find no YouTube ID in what you pasted. Please try again.');
   document.getElementById('videoinserttext').innerHTML='Click in Text to Insert!';
   var html="<br/><iframe width=\"560\" height=\"349\" src=\"http://www.youtube.com/embed/"+realid+"?rel=0&showinfo=0&autohide=1\" frameborder=\"0\" allowfullscreen></iframe>";
   element.onclick=function () { 
   insertHtmlAtCursor(html);
   document.getElementById('videoinserttext').innerHTML='Insert Video';
   element.onclick=null;
  // resetCursor(element);
   };
}

function resetCursor(element)
{
   element.style.cursor="text";
   
}

function setCursor(picid, element)
{              
    element.style.cursor="url("+screensrc[picid]+"),url(icons/pic.cur),default";
}

function insertHtmlAtCursor(html) {
    var range, node;
    
    if (window.getSelection && window.getSelection().getRangeAt) {
        range = window.getSelection().getRangeAt(0);
        if(range.createContextualFragment)
        {
          node = range.createContextualFragment(html)
          range.insertNode(node);
        }
        else if (document.selection && document.selection.createRange) {
        document.selection.createRange().pasteHTML(html);
        }
        
    } else if (document.selection && document.selection.createRange) {
        document.selection.createRange().pasteHTML(html);
    }
}

window.onload = function() { 
  var txts = document.getElementsByTagName('TEXTAREA') 

  for(var i = 0, l = txts.length; i < l; i++) {
    if(/^[0-9]+$/.test(txts[i].getAttribute("maxlength"))) { 
      var func = function() { 
        var len = parseInt(this.getAttribute("maxlength"), 10); 

        if(this.value.length > len) {  
          this.value = this.value.substr(0, len); 
          return false; 
        } 
      }

      txts[i].onkeyup = func;
      txts[i].onblur = func;
    } 
  } 
}


function setChoiceText()
{
  for(var x=1 ;x<=8; x++)
  {
    if(document.getElementById("choicelabel"+x))
    {
    new top.nicEditor({maxHeight : 50, buttonList : ['bold','italic','underline','strikethrough','subscript','superscript','left','center','right','forecolor','bgcolor']}).panelInstance('choicelabel'+x)
    }
  }
}

function respondToMessage(title, to)
{
    document.getElementById("usersearchbox").value=to;
    document.getElementById("newmessagetitle").value=title;
    document.getElementById("usersearchbox").focus();
    document.getElementById("newmessagebody").focus();
}

function deleteMessages(id)
{
    var boxen = $(".messageselect:checked")
    if (boxen.length < 1) return false;
    if(confirm("Really delete the selected messages?"))
    {
        var messids = new Array();
        boxen.each(function (i)
            {
                messids[i] = this.value;
            }
        )
        var deleteids = messids.join(",");
        $("#deletionids").val(deleteids);
        $("#deletionform").submit();
    }
    else return false;
}

function sendMessage()
{
    if (1) document.getElementById("composemessage").submit();
}

function selectDeselect()
{
    var boxen = $(".messageselect");
    if($(".messageselect:checked").length === boxen.length) boxen.prop('checked', false);
    else boxen.prop('checked', true);

}

function removeFBreturn()
{
    if(cyoXML.readyState==4)
    {
        if(cyoXML.status==200)
        {

            xmlResponse=cyoXML.responseXML;
            xmlDocEl=xmlResponse.documentElement;
            var answer=xmlDocEl.getElementsByTagName('answer');
            answer=answer[0].firstChild.data;
            if(answer == 0)
            {
                $("#fbbutton").css("display", "none");
                $(".indexsideholder").css("top", "10px");
            }
        }
    }

    else
    {
        //alert('There was a problem accessing the server: '+cyoXML.statusText);
    }
}

function removeFB()
{
    cyoXML.open("GET","checkfb.php",true);
    cyoXML.onreadystatechange=removeFBreturn;
    cyoXML.send(null);
}

    function strip_tags (input, allowed) {
        allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
        var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
            commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
        return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
            return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
        });
    }
