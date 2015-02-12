<?php
defined('_JEXEC') or die ('Restricted access');

$oldsectionid = -1;
$oldcatid = -1;
$contentarray = array ();

foreach ($list as $article)
{
    $component = JComponentHelper::getComponent('com_content');
    $cparams = new JParameter($component->params);

    $aparams = $article->parameters;
    $cparams->merge($aparams);

    ob_start();

    if (($article->catid == $oldcatid) && ($article->sectionid == $oldsectionid))
    {
        $article->catid = 0;
        $article->sectionid = 0;
    } else
    {
        $oldcatid = $article->catid;
        $oldsectionid = $article->sectionid;
    }

    // DO NOT EDIT BEFORE THIS
?>
<?php
if ($cparams->get('show_title')):
?>
<h2 class="contentheading<?php echo $params->get('pageclass_sfx'); ?>">
    <?php
    if ($cparams->get('link_titles') && $article->readmore_link != ''):
    ?>
    <a href="<?php echo $article->readmore_link; ?>" class="contentpagetitle<?php echo $cparams->get('pageclass_sfx'); ?>">
        <?php
        echo $article->title;
        ?>
    </a>
    <?php
    else :
        echo $article->title;
    endif;
    ?>
</h2>
<?php
endif;
?>
<?php
if ((! empty($article->modified) && $cparams->get('show_modify_date')) || ($cparams->get('show_author') && ($article->author != "")) || ($cparams->get('show_create_date'))):
?>
<p class="articleinfo">
    <?php
    if (! empty($article->modified) && $cparams->get('show_modify_date')):
    ?>
    <span class="modifydate">
        <?php
        echo JText::_('Last Updated').' ('.JHTML::_('date', $article->modified, JText::_('DATE_FORMAT_LC2')).')';
        ?>
    </span>
    <?php
    endif;
    ?>
    <?php
    if (($cparams->get('show_author')) && ($article->author != "")):
    ?>
    <span class="createdby">
        <?php
        JText::printf('Written by', ($article->created_by_alias?$article->created_by_alias:$article->author));
        ?>
    </span>
    <?php
    endif;
    ?>
    <?php
    if ($cparams->get('show_create_date')):
    ?>
    <span class="createdate">
        <?php
        echo JHTML::_('date', $article->created, JText::_('DATE_FORMAT_LC2'));
        ?>
    </span>
    <?php
    endif;
    ?>
</p>
<?php
endif;
?>
<?php
if (!$cparams->get('show_intro')):
    echo $article->event->afterDisplayTitle;
endif;
?>
<p class="buttonheading">
    <?php
    if ($cparams->get('show_pdf_icon') || $cparams->get('show_print_icon') || $cparams->get('show_email_icon')):
    ?>
    <img src="./templates/beez/images/trans.gif" alt="<?php echo JText::_('attention open in a new window'); ?>" />
    <?php
    
    if ($cparams->get('show_pdf_icon')):
        echo JHTML::_('icon.pdf', $article, $cparams, $article->access);
    endif;
    
    if ($cparams->get('show_print_icon')):
        echo JHTML::_('icon.print_popup', $article, $cparams, $article->access);
    endif;
    
    if ($cparams->get('show_email_icon')):
        echo JHTML::_('icon.email', $article, $cparams, $article->access);
    endif;
    
    endif;
    
    if (($user->authorize('com_content', 'edit', 'content', 'all') || $user->authorize('com_content', 'edit', 'content', 'own'))):
        echo JHTML::_('icon.edit', $article, $cparams, $access);
    endif;
    
    ?>
</p>
<?php
if (($cparams->get('show_section') && $article->sectionid) || ($cparams->get('show_category') && $article->catid)):
?>
<p class="iteminfo">
    <?php
    if ($cparams->get('show_section') && $article->sectionid):
    ?>
    <span>
        <?php
        if ($cparams->get('link_section')):
        ?>
        <?php
        echo '<a href="'.JRoute::_(ContentHelperRoute::getSectionRoute($article->sectionid)).'">';
        ?>
        <?php
        endif;
        ?>
        <?php
        echo $article->section;
        ?>
        <?php
        if ($cparams->get('link_section')):
        ?>
        <?php
        echo '</a>';
        ?>
        <?php
        endif;
        ?>
        <?php
        if ($cparams->get('show_category')):
        ?>
        <?php
        echo ' - ';
        ?>
        <?php
        endif;
        ?>
    </span>
    <?php
    endif;
    ?>
    <?php
    if ($cparams->get('show_category') && $article->catid):
    ?>
    <span>
        <?php
        if ($cparams->get('link_category')):
        ?>
        <?php
        echo '<a href="'.JRoute::_(ContentHelperRoute::getCategoryRoute($article->catslug, $article->sectionid)).'">';
        ?>
        <?php
        endif;
        ?>
        <?php
        echo $article->category;
        ?>
        <?php
        if ($cparams->get('link_category')):
        ?>
        <?php
        echo '</a>';
        ?>
        <?php
        endif;
        ?>
    </span>
    <?php
    endif;
    ?>
</p>
<?php
endif;
?>
<?php
echo $article->event->beforeDisplayContent;
?>
<?php
if ($cparams->get('show_url') && $article->urls):
?>
<span class="small"><a href="<?php echo $article->urls; ?>" target="_blank">
        <?php
        echo $article->urls;
        ?>
    </a></span>
<?php
endif;
?>
<?php
if ( isset ($article->toc)):
    echo $article->toc;
endif;
?>
<?php
echo JFilterOutput::ampReplace($article->text);
?>
<?php
if ($cparams->get('show_readmore') && $cparams->get('show_intro') && $article->readmore_text)
{
?>
<p>
    <a href="<?php echo $article->readmore_link; ?>" class="readon<?php echo $cparams->get( 'pageclass_sfx' ); ?>">
        <?php
        echo $article->readmore_text;
        ?>
    </a>
</p>
<?php
}
else
{
?>
<a name="spacer">&nbsp;</a>
<?php
}
?>
<?php
echo $article->event->afterDisplayContent;
?>
<?php
/* DO NOT EDIT AFTER THIS */
$contentarray[] = ob_get_contents();
ob_end_clean();
}
?>
