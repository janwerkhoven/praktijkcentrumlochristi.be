<?php
/**
 * @version		mod_placehere
 * @package		Joomla
 * @copyright	Copyright (C) 2007 Eike Pierstorff eike@diebesteallerzeiten.de
 * @license		GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die ('Restricted access');

require_once (JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');

class modPlaceHereHelper
{
    var $access = false;
    var $catid = false;
    var $sectionid = false;
    var $slug;
    var $created;
    var $modified;
    var $title;
    var $text;
    var $readmore_text;
    var $readmore_link;

    function modPlaceHereHelper()
    {
        // yummy yummy yummy this constructor is a dummy
    }

    function getList( & $params)
    {
        global $mainframe;
        JPluginHelper::importPlugin('content');
        $limitstart = JRequest::getVar('limitstart', 0, '', 'int');
        $dispatcher = & JDispatcher::getInstance();
        $db = & JFactory::getDBO();
        $user = & JFactory::getUser();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $userId = (int)$user->get('id');
        $show_front = $params->get('show_front', 1);
        $showrelated = $params->get('showrelated', 1);
        $aid = $user->get('aid', 0);
        // Id of currently displayed article
        $curid = JRequest::getVar('id', 0, '', 'int');
        $hide_current = $params->get('hide_current', 0);
        // trim text to n characters ?
        $trim = $params->get('trim', false);
        $addstring = $params->get('addstring', "");
        //Item, cat, section
        $type = trim($params->get('type'));
        //  ID or comma separated lists of ids
        $showbyid = trim($params->get('showbyid'));
		/* 
			Expands a range of ids, 1-4,5-7 will become 1,2,3,4,5,6,7
		*/
		preg_match_all('/([0-9]*+[\-]+[0-9]*)/i', $showbyid, $matches, PREG_SET_ORDER);
		for($i=0;$i<count($matches);$i++) {
			$temp = explode("-",$matches[$i][0]);
			$ttemp = array();
			for($z=$temp[0];$z<=$temp[1];$z++) {
				$ttemp[] = $z;	
			}
			$showbyid = str_replace($matches[$i][0],implode(",",$ttemp),$showbyid);
		}
	
        // if number of articles to display isn't set we use some really large number
        $count = (int)$params->get('count', 27364237647283476);
        // offset display starts with this article
        $offset = trim($params->get('offset'), 0);
        // map id to type
        $cimid = $catid = $secid = false;
        switch($type)
        {
            case '3': // display items from a Section
                $secid = true; // s_ection ID
                if ($params->get("show_section") == 1)
                {
                    $params->set('section', 1);
                }
            break;
            case '2': // display items from a Category
                $catid = true; // c_ategory ID
                if ($params->get("show_category") == 1)
                {
                    $params->set('category', 1);
                }
            break;
            case '1': // dispay articles
            default:
                $cimid = true; // c_ontent i_tem ID
                break;
    }

    $contentConfig = & JComponentHelper::getParams('com_content');
    $access = !$contentConfig->get('shownoauth');

    $nullDate = $db->getNullDate();

    $date = & JFactory::getDate();
    $now = $date->toMySQL();

    // 15/08/08 moved this to the top since we need to know the ids for the ordering
    $Condition = " AND 0 "; //do nothing if the following do not match
    if ($cimid)
    { // find ids for artices, either related articles by metakey or by given Ids
        //find related article. 30.12.2008 by Keywan Ghadami
        if ($showbyid == '' && (true == $showrelated))
        { //no id given: find related article
            //can only do this on the article page
            if ($option == 'com_content' && $view == 'article' && $curid)
            {
                $query = 'SELECT metakey'.
                ' FROM #__content'.
                ' WHERE id = '.(int)$curid;
                $db->setQuery($query);
                if ($metakey = trim($db->loadResult()))
                {
                    // explode the meta keys on a comma
                    $keys = explode(',', $metakey);
                    $tags = array ();

                    //assemble any non-blank word(s)
                    foreach ($keys as $key)
                    {
                        $key = trim($key);
                        if ($key)
                        {
                            $tags[] = $db->getEscaped($key);
                        }
                    }

                    $counted = count($tags);
                    if ($counted != 0)
                    {
                        $query = 'SELECT id '.
                        ' FROM #__content a'.
                        ' WHERE id != '.$curid.' AND ( a.metakey = "'.implode('" OR a.metakey = "', $tags).'" )';
                        $db->setQuery($query);
                        $ids = $db->loadResultArray();
                        $counted = count($ids);
                        if ($counted != 0)
                        {
                            JArrayHelper::toInteger($ids);
                            $Condition = ' AND (a.id='.implode(' OR a.id=', $ids).')';
                        }
                    }
                }
            }
        } else
        {
            $ids = explode(',', $showbyid);
            JArrayHelper::toInteger($ids);
            $Condition = ' AND (a.id='.implode(' OR a.id=', $ids).')';
        }
    }

    if ($catid)
    { // find ids for articles from the given Category Ids
        $ids = explode(',', $showbyid);
        JArrayHelper::toInteger($ids);
        $Condition = ' AND (cc.id='.implode(' OR cc.id=', $ids).')';
    }
	

    if ($secid)
    { // find ids for articles from the given Section Ids
        $ids = explode(',', $showbyid);
        JArrayHelper::toInteger($ids);
        $Condition = ' AND (s.id='.implode(' OR s.id=', $ids).')';
    }

	

	// Tags. This requires the Joomlatags-Extension from joomlatags.org
	$tags = $params->get('tags', false);
	if($tags) { // is the tag parameter set
	    // First we check if the exenstion is installed
		$db->setQuery("SELECT link from #__components  WHERE link = 'option=com_tag'");
		if($db->loadResult()) {	
			// Now we find the content ids belonging to the tags
			$taglist = explode(",",$tags);
			$tagsCondition = " WHERE (a.name='".implode("' OR a.name='", $taglist)."')";
			$query = "SELECT DISTINCT(b.cid) 
						FROM #__tag_term as a
						LEFT JOIN #__tag_term_content AS b on a.id = b.tid" . $tagsCondition;
			$db->setQuery($query);
			if($rows = $db->loadResultArray()) {
				$Condition .= ' AND (a.id='.implode(' OR a.id=', $rows).')';
			}
		}
	}
	
    $where = 'a.state = 1'
    .' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'
    .' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )';

    if ($hide_current)
    { // hides the article that is displayed in mainbody
        $where .= " AND a.id != '".$curid."' ";
    }

    // User Filter
    switch($params->get('user_id'))
    {
        case 'by_me':
            $where .= ' AND (created_by = '.(int)$userId.' OR modified_by = '.(int)$userId.')';
            break;
        case 'not_me':
            $where .= ' AND (created_by <> '.(int)$userId.' AND modified_by <> '.(int)$userId.')';
            break;
    }
	
    // Ordering
    switch($params->get('ordering'))
    {
        case 'exact':
            $ordering .= ' FIELD(a.id , '.implode(' ,', $ids).')';
            break;
		case 'hits_desc':
            $ordering .= 'a.hits DESC';
     	break;
		case 'p_up_desc':		
            $ordering .= 'publish_up DESC';
		break;
		case 'p_up_asc':		
            $ordering .= 'publish_up ASC';
		break;		
        case 'o_dsc':
            $ordering = 'a.ordering DESC';
            break;
        case 'o_asc':
            $ordering = 'a.ordering ASC';
            break;
        case 'm_dsc':
            $ordering = 'a.modified DESC, a.created DESC';
            break;
        case 's_asc':
            $ordering = 'a.sectionid ASC';
            break;
        case 's_dsc':
            $ordering = 'a.sectionid DESC';
            break;
        case 'cy_asc':
            $ordering = 'a.catid ASC, a.sectionid ASC';
            break;
        case 'cy_dsc':
            $ordering = 'a.catid DESC, a.sectionid DESC';
            break;
        case 'random':
            $ordering = 'RAND()';
            break;
        case 'c_dsc':
        default:
            $ordering = 'a.created DESC';
            break;
    }

    switch($params->get('flip_frontpage'))
    {
        case (1):
            $flip = " NOT ";
            break;
        default:
            $flip = "  ";
            break;
    }

    // Ordering
    switch($params->get('sec_ordering'))
    {
        case 'o_dsc':
            $ordering .= ',a.ordering DESC';
            break;
        case 'o_asc':
            $ordering .= ',a.ordering ASC';
            break;
        case 'm_dsc':
            $ordering .= ',a.modified DESC, a.created DESC';
            break;
        case 's_asc':
            $ordering .= ',a.sectionid ASC';
            break;
        case 's_dsc':
            $ordering .= ',a.sectionid DESC';
            break;
        case 'cy_asc':
            $ordering .= ',a.catid ASC, a.sectionid ASC';
            break;
        case 'cy_dsc':
            $ordering .= ',a.catid DESC, a.sectionid DESC';
            break;
        case 'random':
            $ordering .= ',RAND()';
            break;
        case 'c_dsc':
            $ordering .= ',a.created DESC';
            break;
        case 'none':
        default:
            break;
    }

    // Content Items only
    // John Woelfel - +18 lines - Change query to allow for  static content items in Joomla 1.5
    // 04/04/08 Mike Bronner fixed sql to display author names
    if ($cimid)
    {
        $query = 'SELECT a.*, '.
        ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'.
        ' \'\' as catslug,'.
        ' 0 as catid, \'\' as category, '.
        ' 0 as sectionid, \'\' as section'.
        ' , u.name AS author, g.name AS groups '.
        ' FROM #__content AS a'.
        ($show_front == '0'?' LEFT JOIN #__content_frontpage AS f ON f.content_id = a.id':'').
        ' LEFT JOIN #__users AS u ON u.id = a.created_by '.
		        ' LEFT JOIN #__groups AS g ON a.access = g.id'.
        ' WHERE '.$where.
        ($access?' AND a.access <= '.(int)$aid.' ':'').
        $Condition.
        ($show_front == '0'?' AND f.content_id IS  '.$flip.' NULL ':'').
        ' ORDER BY '.$ordering;
    }
    else
    {
        // 08/03/08 Sql Error corrected by Maurice
        // http://diebesteallerzeiten.de/blog/module-for-15-alpha/#comment-2634
        $query = 'SELECT a.*, '.
        ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'.
        ' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'.
        ' , cc.id as catid, cc.title as category '.
        ' , s.id as sectionid, s.title as section'.
        ' , u.name AS author, g.name AS groups '.
        ' FROM #__content AS a'.
        ($show_front == '0'?' LEFT JOIN #__content_frontpage AS f ON f.content_id = a.id':'').
        ' INNER JOIN #__categories AS cc ON cc.id = a.catid'.
        ' INNER JOIN #__sections AS s ON s.id = a.sectionid'.
        ' LEFT JOIN #__users AS u ON u.id = a.created_by '.
        ' LEFT JOIN #__groups AS g ON a.access = g.id'.
        ' WHERE '.$where.' AND s.id > 0'.
        ($access?' AND a.access <= '.(int)$aid.' AND cc.access <= '.(int)$aid.' AND s.access <= '.(int)$aid:'').
        $Condition.
        ($show_front == '0'?' AND f.content_id IS  '.$flip.' NULL ':'').
        ' AND s.published = 1'.
        ' AND cc.published = 1'.
        ' ORDER BY '.$ordering;
    }

	// die($query);
	
    $db->setQuery($query, $offset, $count);
    $rows = $db->loadObjectList();

    // there is no result
    if (count($rows) == 0 && $params->get('show_notfoundtext'))
    {
        // a dummy object with lots of empty properties so the template won't throw notices
        $rows[0] = new modPlaceHereHelper();
        $rows[0]->event = new stdClass ();
        $rows[0]->event->afterDisplayTitle = NULL;
        $rows[0]->event->beforeDisplayContent = NULL;
        $rows[0]->event->afterDisplayContent = NULL;

        // since there is no actual article we set the icon to false
        $params->set('show_pdf_icon', false);
        $params->set('show_print_icon', false);
        $params->set('show_email_icon', false);

        $rows[0]->title = $params->get('notfoundtitle', '');
        $rows[0]->text = $params->get('notfoundtext', '');
        return $rows;
    }

    for ($i = 0; $i < count($rows); $i++)
    {
        $rows[$i]->readmore_link = "";
        $rows[$i]->readmore_text = "";

        $rows[$i]->url = ContentHelperRoute::getArticleRoute($rows[$i]->slug, $rows[$i]->catslug, $rows[$i]->sectionid);
        if ($params->get('link_to_cat', 0) == 1)
        {
            $rows[$i]->url = ContentHelperRoute::getCategoryRoute($rows[$i]->catid, $rows[$i]->sectionid);
        }
        $rows[$i]->parameters = new JParameter($rows[$i]->attribs);

        // "Gallery Mode" displays images from main content
        $gal = false;
        if ($params->get('gallery'))
        {
            $gal = modPlaceHereHTML::mod_placehere_gallery($rows[$i]->fulltext, $params->get('gallery_outputmode'));
        }

        if ($params->get('show_intro') && $params->get('use_metadesc', 'no') == 'no')
        {
            $rows[$i]->text = $rows[$i]->introtext;
            $rows[$i]->readmore_link = $rows[$i]->url;
            if ($rows[$i]->fulltext)
            {
                $rows[$i]->readmore_text = $rows[$i]->parameters->get('readmore')?$rows[$i]->parameters->get('readmore'):
                    $params->get('readmoretext');
                }
            } else if ($params->get('use_metadesc', 'no') == 'yes')
            {
                // we set introtext as default, if there actually is a metadesc we override this
                $rows[$i]->text = $rows[$i]->introtext;
                if (! empty($rows[$i]->metadesc))
                $rows[$i]->text = $rows[$i]->metadesc;
            } else
            {
                $rows[$i]->text = $rows[$i]->introtext.$rows[$i]->fulltext;
            }

            if ($params->get('striptags', false) == true)
            {
                $rows[$i]->text = strip_tags($rows[$i]->text);
            }

            if ($trim)
            {
                $rows[$i]->readmore_link = $rows[$i]->url;
                $rows[$i]->readmore_text = $rows[$i]->parameters->get('readmore')?$rows[$i]->parameters->get('readmore'):
                    $params->get('readmoretext');
                    $rows[$i]->text = modPlaceHereHTML::mk_html_substr($rows[$i]->text, $trim, $addstring);
                }

                if ($gal)
                {
                    switch($params->get('gallery_position'))
                    {
                        case (3):
                            $rows[$i]->text = $gal;
                            break;
                        case (2):
                            $rows[$i]->text = $gal.$rows[$i]->text;
                            break;
                        case (1):
                        default:
                            $rows[$i]->text = $rows[$i]->text.$gal;
                            break;
                    }
                }

                $plugins = $params->get('plugins', 0);
                switch($plugins)
                {
                    case (1):
                        $rows[$i]->event = new stdClass ();
                        $rows[$i]->event->afterDisplayTitle = NULL;
                        $rows[$i]->event->beforeDisplayContent = NULL;
                        $rows[$i]->event->afterDisplayContent = NULL;
                        break;
                    case (0):
                    default:
                        $rows[$i]->event = new stdClass ();
                        $results = $dispatcher->trigger('onPrepareContent', array ( & $rows[$i], & $rows[$i]->parameters, $limitstart));
                        $results = $dispatcher->trigger('onAfterDisplayTitle', array ($rows[$i], & $rows[$i]->parameters, $limitstart));
                        $rows[$i]->event->afterDisplayTitle = trim(implode("\n", $results));
                        $results = $dispatcher->trigger('onBeforeDisplayContent', array ( & $rows[$i], & $rows[$i]->parameters, $limitstart));
                        $rows[$i]->event->beforeDisplayContent = trim(implode("\n", $results));
                        $results = $dispatcher->trigger('onAfterDisplayContent', array ( & $rows[$i], & $rows[$i]->parameters, $limitstart));
                        $rows[$i]->event->afterDisplayContent = trim(implode("\n", $results));
                        break;
                }
            }
			
		
            return $rows;
        }
    }
