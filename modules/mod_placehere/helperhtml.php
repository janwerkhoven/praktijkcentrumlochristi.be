<?php

/**
* @version		mod_placehere 
* @package		Joomla
* @copyright	Copyright (C) 2007 Eike Pierstorff eike@diebesteallerzeiten.de
* @license		GNU/GPL, see LICENSE.php
*
* File last changed 17/07/08
*/

class modPlaceHereHTML
{
 function buildTablefromArray($rows,$params) {
  // ****************** Output **********************/
   
	 if(count($rows)<1) { return; }   

	 $moduleclass_sfx			= $params->get("moduleclass_sfx","");
	 $leading					= $params->get("leading",1);
	 $num_of_cols				= $params->get("columns",2);
	 $w							= $params->get("containerwidth",false);
	 $width = "";
	 if($w) {
	  $width = ' width="' . $w . '"'; 
	 }
   echo '<div class="mod_placehere' . $moduleclass_sfx . '">';
	 echo '<table ' . $width . '>';
	 for($i=0;$i<$leading;$i++)  {
	  if($rows[$i]) {
	  	
		if($i%2 == 0) {
			$class = "even";			
		} else {
			$class = "odd";			
		}
					
	  echo '<tr class="' . $class . '">';
	   echo '<td valign="top" colspan="' . $num_of_cols . '" class="mod_placehere_leading">' . $rows[$i] . '</td>';
	  echo '</tr>';		
		}
	 }
	 // following paragraphs
	 // How many table rows ?
	 $num_of_trs = count($rows);
	 $width = 100/$num_of_cols;
	 
	 /* Fix by n7Epsilon: Don't run the loop if there are
	 no more paragraphs to show other than the leading one */
	 if ($num_of_trs > (int)$leading)
	 {   
		 for($i=$leading;$i<$num_of_trs;$i++) {
			if($i%2 == 0) {
				$class = "even";			
			} else {
				$class = "odd";			
			}
	  	echo '<tr class="' . $class . '">';
			for($z=0;$z<$num_of_cols;$z++) {
				if(isset($rows[$i])) {
		     echo '<td valign="top" width="' . $width . '%" class="mod_placehere_following">' . $rows[$i] . '</td>'; 			
				} else {
				 echo '<td width="' . $width . '%" class="mod_placehere_following">&nbsp;</td>';
				}
			$i++;			
			}
		  echo '</tr>';
			$i--;		
		 }
	 }
	 echo '</table>';
	 echo '</div>';	 
 // ****************** Output **********************/ 

 }
 
function buildDivsfromArray($rows,$params) {
  // ****************** Output **********************/
   
     // no content
	 if(count($rows)<1)
	 	return;
     
	 $moduleclass_sfx			= $params->get("moduleclass_sfx","");	 
	 $leading					= $params->get("leading",1);
	 $num_of_cols				= $params->get("columns",2);
	 $w							= $params->get("containerwidth",false);
	
	 $width = "";
	 if($w) {
	  $width = ' width="' . $w . '"'; 
	 }
	 echo '<div ' . $width . ' class="mod_placehere' . $moduleclass_sfx . '">';
	 for($i=0;$i<$leading;$i++)  {
	  if($rows[$i]) {
		if($i%2 == 0) {
			$class = "even";			
		} else {
			$class = "odd";			
		}	  	
		
	   echo '<div style="overflow:auto;" class="mod_placehere_leading ' . $class . '">' . $rows[$i] . '</div>';
		}
	 }
	 // following paragraphs
	 // How many table rows ?
	 $num_of_trs = count($rows);
	 $width = 100/$num_of_cols;
   
   
   	if(!isset($class)) {
			$class = "even";			
	}
     /* Fix by n7Epsilon: Don't run the loop if there are
	 no more paragraphs to show other than the leading one */
	 if ($num_of_trs > (int)$leading)
	 {
		 for($i=$leading;$i<$num_of_trs;$i++) {
			for($z=0;$z<$num_of_cols;$z++) {						
				if(isset($rows[$i])) {										
		     		echo '<div style="float:left;width:' . $width . '%" class="mod_placehere_following ' . $class . '">' . $rows[$i] . '</div>'; 			
				} else {
				 	echo '<div style="float:left;width:' . $width . '%" class="mod_placehere_following ' . $class . '">&nbsp;</div>';
				}			
				$i++;			
			} 
			echo '<br style="clear:both" />';	
			$i--;
			if($class == "even") {
				$class = "odd";
			} else {
				$class = "even";
			}
		 }
	 }
	
	 echo '</div>';
	 
 // ****************** Output **********************/ 

 } 
 


/*
 * This function by Michael Kelly http://www.conurestudios.com
 * 
 * mk_html_substr($string, $length, $addstring="")
 * @param string $string
 * @param int	 $length trim to $length characters
 * @params $addstring	characters to be displayed after trimmed string
 * 
 */
 
function mk_html_substr($string, $length, $addstring="") {
	$addstring = " " . $addstring;
	if (strlen($string) > $length) {
		if( !empty( $string ) && $length>0 ) {
			$isText = true;
			$ret = "";
			$i = 0;
			$currentChar = "";
			$lastSpacePosition = -1;
			$lastChar = "";
			$tagsArray = array();
			$currentTag = "";
			$tagLevel = 0;
			$noTagLength = strlen( strip_tags( $string ) );

			// Parser loop
			for( $j=0; $j<strlen( $string ); $j++ ) {
				$currentChar = substr( $string, $j, 1 );
				$ret .= $currentChar;
				if( $currentChar == "<") $isText = false;
				if( $isText ) {
					// Memorize last space position
					if( $currentChar == " " ) { $lastSpacePosition = $j; }
					else { $lastChar = $currentChar; }
					$i++;
				}else{
					$currentTag .= $currentChar;
				}
				// Greater than event
				if( $currentChar == ">" ) {
					$isText = true;
					// Opening tag handler
					if( ( strpos( $currentTag, "<" ) !== FALSE ) && ( strpos( $currentTag, "/>" ) === FALSE ) && ( strpos( $currentTag, "</") === FALSE ) ) {
						// Tag has attribute(s)
						if( strpos( $currentTag, " " ) !== FALSE ) {
							$currentTag = substr( $currentTag, 1, strpos( $currentTag, " " ) - 1 );
						} else {
							// Tag doesn't have attribute(s)
							$currentTag = substr( $currentTag, 1, -1 );
						}
						array_push( $tagsArray, $currentTag );
					} else if( strpos( $currentTag, "</" ) !== FALSE ) {
						array_pop( $tagsArray );
					}
					$currentTag = "";
				}
				if( $i >= $length) {
					break;
				}
			}
			// Cut HTML string at last space position
			if( $length < $noTagLength ) {
				if( $lastSpacePosition != -1 ) {
					$ret = substr( $string, 0, $lastSpacePosition );
				} else {
					$ret = substr( $string, $j );
				}
			}
			// Close broken XHTML elements
			while( sizeof( $tagsArray ) != 0 ) {
				$aTag = array_pop( $tagsArray );
				$ret .= "</" . $aTag . ">\n";
			}
		} else {
			$ret = "";
		}
		// only add string if text was cut
		if ( strlen($string) > $length ) {
			return( $ret.$addstring );
		} else {
			return ( $res );
		}
	} else {
		return ( $string );
	}
}


function mod_placehere_gallery($text,$gallerystyle="") {
 	// find all image tags and put them in array images
 	// $pattern = '/(<img)\s (src="([a-zA-Z0-9\.;:\/\?&=_|\r|\n]{1,})")/isxmU';

 	$retval = "";
	$pattern = '<img[^<>]+>';
 	preg_match_all($pattern,$text,$images); 
	
	if(!is_array($images[0]) || empty($images[0]))
				return $retval;

	$imgs	= $images[0];
	$cnt	= count($imgs);


	switch($gallerystyle) {
		case('raw'):
		for($i=0;$i<$cnt;$i++) {	
			$alt = $title = "";		
			$clean  =  modPlaceHereHTML::mod_placehere_xml_attribute_parse ($images[0][$i]); 	
			if(isset($clean['alt']))
				$alt = 	$clean['alt'];
			if(isset($clean['title']))
				$alt = 	$clean['title'];
								
			$retval .= '<img src="' . $clean["src"] . '" alt="' . $alt . '" title="' . $title . '" />';	
		};
		break;
		case('list'):	
		default:
		$retval  =  '<ul class="modplacehere_gallery">';
		for($i=0;$i<$cnt;$i++) {	
			$alt = $title = "";		
			$clean  =  modPlaceHereHTML::mod_placehere_xml_attribute_parse ($images[0][$i]); 	
			if(isset($clean['alt']))
				$alt = 	$clean['alt'];
			if(isset($clean['title']))
				$alt = 	$clean['title'];
								
			$retval .= '<li><img src="' . $clean["src"] . '" alt="' . $alt . '" title="' . $title . '" /></li>';	
		}
		$retval .= '</ul>';				
		break;		
	}
	
	return $retval;
}
	
 	/* Parses an XML tag into an array of attributes */
function mod_placehere_xml_attribute_parse ($tag) {
	
	preg_match_all('/([a-z0-9]+)=(?:\"([^\"]*)\"|\s*([^\s]+))/i', $tag, $matches, PREG_SET_ORDER);

	$attributes = array();
	foreach($matches as $att){
		$attributes[$att[1]] = $att[2];
	}
	return $attributes;
}

 
}
?>

