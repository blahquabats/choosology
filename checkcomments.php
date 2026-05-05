<?php
require_once ("wrapper.php");
if ($alluserinfo['usertype'] != 1)
	die();
	echo "<script>
            function approveAll()
            {
               if(cyoXML.readyState==4 || cyoXML.readyState==0)
    	              {
                    
                   	cyoXML.open('GET','fetchcomments.php?approvealldem=1&name=review',true);
    			          cyoXML.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    			          cyoXML.onreadystatechange=loadCommentsResponse;
    			          cyoXML.send();  
    		            }
    		            else 
    		            {
    		             
    			           setTimeout('approveAll()',1000);
    		            }
            }  
  
  </script>";
	
echo "<div class='body'>";
    $comments=new commentArea("review", true, true);
    $comments->display();

echo "</div>";


require_once ("footer.php");
?>
