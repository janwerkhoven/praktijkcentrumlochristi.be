<?php

/**
 * @version		$Id: example.php 10497 2008-07-03 16:36:12Z ircmaxell $
 * @package		Joomla
 * @subpackage	Content
 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is included in Joomla!

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Example Content Plugin
 *
 * @package		Joomla
 * @subpackage	Content
 * @since 		1.5
 */

class plgContentCrosscontent extends JPlugin

{

	var $myArticles = array();

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */

	function plgContentCrosscontent( $subject, $params )
	{
		parent::__construct( $subject, $params );

	}

	/**
	 * Example prepare content method
	 *
	 * Method is called by the view
	 *
	 * @param 	object		The article object.  Note $article->text is also available
	 * @param 	object		The article params
	 * @param 	int			The 'page' number
	 */

	function onPrepareContent( $article, $params, $limitstart )
	{
		global $mainframe;
		$this->myArticles[] = $article->id;
		if(!$this->checkRules($article)){
			return;
		}

		if(JRequest::getInt('crossContentSave', 0) > 0){
			if(!class_exists('ContentHelper')){
				require_once(JPATH_SITE . '/administrator/components/com_content/helper.php');
			}

			// cleanup fulltext if no readmore was given

			$post = stripslashes($_POST['text']);
			if(0 == preg_match("/<hr id=\"system-readmore\" \/>/i", $post)){
				$article->fulltext = '';
			}

			$this->saveContentPrep($article);

			$db = JFactory::getDBO();
			$db->setQuery("Update 

								#__content 

							Set 

								title       = ".$db->Quote(JRequest::getVar('title','')).",
								introtext   = ".$db->Quote($article->introtext).",
								`fulltext`  = ".$db->Quote($article->fulltext).",
								modified    = '".date('Y-m-d H:i:s')."',
								modified_by = '".JFactory::getUser()->get('id')."'

							Where 

								id = '".JRequest::getInt('crossContentSave', 0)."'

							");

			$db->query();

			$article->title = JRequest::getVar('title','');
		}

		$text = '<div id="crosscontent-text">' . $article->introtext . $article->fulltext . '</div>';

		$article->text = $text;

	}

	/**
	 * Example after display title method
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param 	object		The article object.  Note $article->text is also available
	 * @param 	object		The article params
	 * @param 	int			The 'page' number
	 * @return	string
	 */

	function onAfterDisplayTitle( $article, $params, $limitstart )

	{
		global $mainframe;
		return '';
	}

	/**
	 * Example before display content method
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param 	object		The article object.  Note $article->text is also available
	 * @param 	object		The article params
	 * @param 	int			The 'page' number
	 * @return	string
	 */

	function onBeforeDisplayContent( $article, $params, $limitstart )

	{
		global $mainframe;
	}

	/**
	 * Example after display content method
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param 	object		The article object.  Note $article->text is also available
	 * @param 	object		The article params
	 * @param 	int			The 'page' number
	 * @return	string
	 */

	function onAfterDisplayContent( $article, $params, $limitstart )

	{
		global $mainframe;
		if(!$this->checkRules($article)){
			return '';
		}

		$editor = JFactory::getEditor($this->params->get('editorName', 'tinymce'));

		$readMore = '';

		if(trim($article->fulltext) != ''){

			$readMore = htmlentities('<hr id="system-readmore" />' . $article->fulltext, 0, 'UTF-8');

		}

		

		$introEdit = $editor->display( 'text',  htmlentities($article->introtext, 0, 'UTF-8') . $readMore , '100%', intval($this->params->get('editorHeight')), '75', '20' ) ;

		

		$js = '

		
			<script type="text/javascript">

			if(typeof testHowMany == "undefined"){
				testHowMany = new Array();
			} else {
				testHowMany.push('.$article->id.');
			}

			if(testHowMany.length > 0){
				alert("CrossContent ERROR: More than one article detected (ID: '.$article->id.', TITLE \"'.$article->title.'\")! Please add the articles you do not want to edit into the exclude list of the CrossContent plugin! DO NOT TRY TO EDIT AND SAVE THE DATA, YOU COULD CORRUPT IT!");
			}

			var dragObj = null;
			var dragx = 0;
			var dragy = 0;
			var posx = 0;
			var posy = 0;

			function draginit() {
			  document.onmousemove = drag;
			  document.onmouseup = dragstop;
			}

			function dragstart(element) {
			  dragObj = element;
			  dragx = posx - dragObj.offsetLeft;
			  dragy = posy - dragObj.offsetTop;
			}

			function dragstop() {
			  dragObj=null;
			}

			function drag(event) {
			  posx = document.all ? window.event.clientX : event.pageX;
			  posy = document.all ? window.event.clientY : event.pageY;
			  if(dragObj != null) {
			    dragObj.style.left = (posx - dragx) + "px";
			    dragObj.style.top = (posy - dragy) + "px";
			  }
			}

			draginit();

			/*
			function updateIntroText(){
			
			var theText = tinyMCE.getContent("text");
				//alert(theText);
				// quickfix for seo problem
				theText = theText.replace(eval("/src=\"images/gi"),"src=\"'.JURI::root().'images"); 
				//alert(theText);
				document.getElementById("crosscontent-text").innerHTML
					= theText;
			}
			
			setInterval("updateIntroText()",'.intval($this->params->get('refreshInterval', 100)).');
			*/

			function addPosition(){
				document.getElementById("dragPositionX").value = document.getElementById("crosscontent-wrapper").style.left;
				document.getElementById("dragPositionY").value = document.getElementById("crosscontent-wrapper").style.top;
				document.crossContentForm.submit();
			}

			</script>

		

		';

		

		// JURI::getQuery() doesnt work here (buildQuery is assigned to this class, which is wrong, as of Joomla 1.5.7)

		$reqUri = $_SERVER['REQUEST_URI'];

		

		$xCall = '';
		$x = intval($this->params->get('editorX')) . "px";

		if(JRequest::getVar('dragPositionX', '') != ''){
			$x = JRequest::getVar('dragPositionX', '');
			$xCall = '<script>
    					document.getElementById("dragPositionX").value = "'.$x.'";
    					document.getElementById("crosscontent-wrapper").style.left = "'.$x.'";
    					</script>';
		}



		$yCall = '';
		$y = intval($this->params->get('editorY')) . "px";

		if(JRequest::getVar('dragPositionY', '') != ''){
			$y = JRequest::getVar('dragPositionY', '');
			$yCall = '<script>
    					document.getElementById("dragPositionY").value = "'.$y.'";
    					document.getElementById("crosscontent-wrapper").style.top = "'.$y.'";
    					</script>';
		}

		

		$edOpen = '';

		if(JRequest::getVar('editorOpen') != 'true'){
			$edOpen = '<script>
    					javascript:showHide("crosscontent-container","crosscontent-top");
    					document.getElementById("editorOpen").value = "false";
    					</script>';

		} else {

			$edOpen = '<script>
	   					javascript:showHide("crosscontent-container","crosscontent-top");
    					javascript:showHide("crosscontent-container","crosscontent-top");
    					document.getElementById("editorOpen").value = "true";
    					</script>';

		}

		





		$editorWidth = $this->params->get('editorWidth');
		$editTitle = $this->params->get('editTitle');
		$kleurTekst = $this->params->get('kleurTekst');
		$kleurVlak = $this->params->get('kleurVlak');
		$kleurRand = $this->params->get('kleurRand');
		$kleurHover = $this->params->get('kleurHover');
		$kleurHoverRand = $this->params->get('kleurHoverRand');
		$dragable = $this->params->get('dragable');
		if ($dragable == "nothing") {
				$dragCursor = "auto";
		} else {
				$dragCursor = "move";
		}
		

		$result = '



<script language="JavaScript">

		<!--
		function showHide(elementid){
			if (document.getElementById(elementid).style.display == "none"){
				document.getElementById(elementid).style.display = "";
				document.getElementById("editorOpen").value = "true";
				document.getElementById("crosscontent-top").style.display = "none";
				document.getElementById("crosscontent-container").style.display = "";
			} else {
				document.getElementById(elementid).style.display = "none";
				document.getElementById("editorOpen").value = "false";
				document.getElementById("crosscontent-top").style.display = "";
				document.getElementById("crosscontent-container").style.display = "none";
			}
		}
		//-->

</script>



<style type="text/css">

#crosscontent-wrapper {
	position: absolute;
	top: '.$y.';
	left: '.$x.';
	height: auto;
	width: '.$editorWidth.'px;
	z-index: 1000;
	padding: 10px;
	background: '.$kleurVlak.';
	border: 1px solid '.$kleurRand.';
	cursor: '.$dragCursor.';
	color: '.$kleurTekst.';
	font-size: 16px;
	font-weight: bold;
	text-align: left;
	line-height: 150%;
}

#crosscontent-container {
	text-align: right;
}

#crosscontent-top {
	margin: 0 0 0 40px;	
}

#crosscontent-inner-top {
	margin: 0 0 10px 40px;
	text-align: left;
}

#crosscontent-wrapper .input {
	display: '.$editTitle.';
	margin: 10px 0 0 0;
	font-size: 12px;
	font-weight: normal;
}

#crosscontent-wrapper a {
	text-decoration: none;
}

#crosscontent-wrapper a:link, #crosscontent-wrapper a:visited {
	color: inherit;
} 

#crosscontent-wrapper a:hover {
	color: '.$kleurHover.';
} 

.crosscontent-btn-save {
	background: '.$kleurVlak.';
	border: solid 1px '.$kleurRand.';
	color: #888;
	margin: 0;
	font-size: 15px;
	font-weight: bold;
	text-align: left;
	line-height: 150%;
}

.crosscontent-btn-save:hover {
	color: '.$kleurHover.';
	border: solid 1px '.$kleurHoverRand.';
	cursor:pointer;
}

.paginatitel {
	font-size: 12px;
	font-weight: normal;
}

.potlood {
	position: absolute;
	top: 10px;
	left: 8px;"	
}

</style>





<div id="crosscontent-wrapper" onmousedown="'.$dragable.'">
  <form name="crossContentForm" onsubmit="addPosition(); return false;" action="'.$reqUri.'" method="post">
    ' . '
	<div id="crosscontent-top">
		<a id="crosscontent-open" href="javascript:showHide(\'crosscontent-container\')">
		<img src="plugins/content/crosscontent/potlood.png" height="24px" width="35px" class="potlood" />
		Deze pagina bewerken <span class="paginatitel">('.htmlentities($article->title, 0, 'UTF-8').')</span></a>
    </div>
	<div id="crosscontent-container">
		<div id="crosscontent-inner-top">
			<a href="javascript:showHide(\'crosscontent-container\')" class="bewerken"><img src="plugins/content/crosscontent/potlood.png" height="24px" width="35px" class="potlood" />Tekstverwerker sluiten</a>
    	<p class="input">Paginatitel aanpassen: <input type="text" name="title" value="'.htmlentities($article->title, 0, 'UTF-8').'"/></p>
		</div>
		'.$introEdit.'
      	<input type="submit" class="crosscontent-btn-save" value="Opslaan"/>
      	<input type="hidden" name="dragPositionX" id="dragPositionX" value=""/>
      	<input type="hidden" name="dragPositionY" id="dragPositionY" value=""/>
      	<input type="hidden" name="editorOpen" id="editorOpen" value=""/>
      	<input type="hidden" name="crossContentSave" value="'.$article->id.'"/>
    </div>
  </form>
</div>





	'.$xCall.$yCall.$edOpen.$js;
	return $result;


	}



	/**
	 * Example before save content method
	 *
	 * Method is called right before content is saved into the database.
	 * Article object is passed by reference, so any changes will be saved!
	 * NOTE:  Returning false will abort the save with an error.  
	 * 	You can set the error by calling $article->setError($message)
	 *
	 * @param 	object		A JTableContent object
	 * @param 	bool		If the content is just about to be created
	 * @return	bool		If false, abort the save
	 */


	function onBeforeContentSave( $article, $isNew )

	{
		global $mainframe;
		return true;
	}

	/**
	 * Example after save content method
	 * Article is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved
	 *
	 *
	 * @param 	object		A JTableContent object
	 * @param 	bool		If the content is just about to be created
	 * @return	void		
	 */

	function onAfterContentSave( $article, $isNew )

	{
		global $mainframe;
		return true;
	}

	function checkRules($article){

		$user = JFactory::getUser();

		if (!$user->authorize('com_content', 'edit', 'content', 'all')) {
			return false;;
		}

		if(count($this->myArticles) != 1){
			return false;
		}

		// only want the details of each article as of the settings
		// some rules

		if($this->params->get('onlyDetails', 1) == 1 && (JRequest::getVar('option','') != 'com_content' || JRequest::getVar('view','') != 'article')){

			return false;

		}

		if($this->params->get('onlyDetails', 1) == 1 && (JRequest::getVar('option','') != 'com_content' || JRequest::getVar('view','') != 'article')){
			return false;
		}

		if(JRequest::getVar('task','') == 'edit'){
			return false;
		}

		$excludeIds = explode(',',$this->params->get('excludeIds',''));

		if(in_array($article->id, $excludeIds)){
			return false;
		}

		if($this->params->get('sections','') != ''){

			$sectCats = explode(',',$this->params->get('sections',''));
			$sectCatsSize = count($sectCats);
			$found = false;

			for($i = 0;$i < $sectCatsSize;$i++){
				$ex = explode(':',$sectCats[$i]);
				$sectionId = $ex[0];
				$catId = $ex[1];

				if($article->sectionid == $sectionId && $article->catid == $catId){
					$found = true;
					break;
				}
			}

			if(!$found){
				return false;
			}
		}

		// rules end

		return true;
	}

	function saveContentPrep( $row )

	{

		// Get submitted text from the request variables

		$text = JRequest::getVar( 'text', '', 'post', 'string', JREQUEST_ALLOWRAW );



		// Clean text for xhtml transitional compliance

		$text		= str_replace( '<br>', '<br />', $text );



		// Search for the {readmore} tag and split the text up accordingly.

		$pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';

		$tagPos	= preg_match($pattern, $text);



		if ( $tagPos == 0 )

		{

			$row->introtext	= $text;

		} else

		{

			list($row->introtext, $row->fulltext) = preg_split($pattern, $text, 2);

		}



		// Filter settings

		jimport( 'joomla.application.component.helper' );

		$config	= JComponentHelper::getParams( 'com_content' );

		$user	= JFactory::getUser();

		$gid	= $user->get( 'gid' );



		$filterGroups	= (array) $config->get( 'filter_groups' );

		if (in_array( $gid, $filterGroups ))

		{

			$filterType		= $config->get( 'filter_type' );

			$filterTags		= preg_split( '#[,\s]+#', trim( $config->get( 'filter_tags' ) ) );

			$filterAttrs	= preg_split( '#[,\s]+#', trim( $config->get( 'filter_attritbutes' ) ) );

			switch ($filterType)

			{

				case 'NH':

					$filter	= new JFilterInput();

					break;

				case 'WL':

					$filter	= new JFilterInput( $filterTags, $filterAttrs, 0, 0 );

					break;

				case 'BL':

				default:

					$filter	= new JFilterInput( $filterTags, $filterAttrs, 1, 1 );

					break;

			}

			$row->introtext	= $filter->clean( $row->introtext );

			$row->fulltext	= $filter->clean( $row->fulltext );

		}



		return true;

	}



}

