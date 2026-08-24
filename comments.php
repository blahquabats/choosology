<?php
   class commentArea 
   {
      var $which="",
      $ispublic=true,
      $readonly=false,
      $html="",
      $script="";
      /*
      which = adv123, 
      
      */
      
      function __construct($which, $public=true,$readonly=false, $screenid=0, $style="", $commentstext="Comments")
      {
          $this->commentstext = $commentstext;
          $this->which=$which;
          $this->ispublic=$public;
          $this->readonly=$readonly;
          $this->screenid=$screenid;
          $this->style=$style;
          if (empty($_SESSION['user']) && !$public) return false;
          if(!empty($_SESSION['user']) && !$readonly) $this->buildCommenter();
          if($public) $this->buildComments();
          
      }
   
   
      function buildCommenter()  // textarea, ajax submit stuff
      {
          $html="<div class='CAentercomment'>
          <div class='CAentercommentloader' id='CAloader".$this->which."'>
          </div>
          <span id='CAmessage".$this->which."'></span>
          <textarea id='CAtext".$this->which."' class='CAtextarea empty' onfocus=\"checkCATextArea(this, 'f')\" onblur=\"checkCATextArea(this, 'b')\" maxlength='500'>Enter your comment here...</textarea>
          <div class='CAentercomment-actions'>
          <span class='CAentercomment-hint'>(500 characters max)</span>
          ".makeFakeButton("subcombutton", "submitCAComment('".$this->which."', '".$this->screenid."')",false, "say", "<span id='CAsubmit".$this->which."'>Submit</span>", "green")."
          </div>
          </div>";                                    
          $this->html.=$html;
      }
      

      
      function buildComments()
      {
          
           $html="<br><div class='CAcomments' data-ca-board='{$this->which}' data-screen='".(int)$this->screenid."' ";
           if($this->style) $html .= "style='{$this->style}'";
           $html.=">
           <h4>
           <div class='CAcommentslastpage' id='CAcommentslastpage{$this->which}'></div>
           <div class='CAcommentsheaderholder'>
           {$this->commentstext} <br>
           <span id='CAcommentscountbegin{$this->which}'></span>
           <span id='CAcommentscountend{$this->which}'></span>
           <span id='CAcommentscountfull{$this->which}'></span>
           </div>
           <div class='CAcommentsnextpage' id='CAcommentsnextpage{$this->which}'></div>
           </h4>
           <br><br>
           <div class='CAcommentsholder' id='CAcommentsholder{$this->which}'>Loading comments...</div>
           </div>
           ";
           $this->html.=$html;
           $this->script.="if(typeof loadComments==='function'){loadComments('{$this->which}',1,".(int)$this->screenid.");}";
      }
      
      function display($return=false)
      {
      if(!$return)
       {
       echo $this->html;
       if($this->script !== '') echo "<script>".$this->script."</script>";
       }
       else 
       {
       $out = $this->html;
       if($this->script !== '') $out .= "<script>".$this->script."</script>";
       return $out;
      }
      }
      function displayScript()
      {
         echo "<script>".$this->script."</script>";
      }
   }
?>