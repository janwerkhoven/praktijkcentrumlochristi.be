<?php

/**
 * @subpackage  system - HOT Login
 * @version     1.5
 * @author      Alessandro Argentiero
 * @license     GNU/GPL v.2
 * @see         /plugins/system/hotlogin/LICENSE.php
 */

 defined( '_JEXEC' ) or die( 'Restricted access' );
 $user = & JFactory::getUser();
 $status=$user->get('guest');
 $quicklogout=$this->params->get( 'quicklogout', 'n' );
 $fixed=$this->params->get( 'fixed');
 $position="absolute";
 if ($fixed=="y") { $position="fixed";}
?>
<script>
    var hoffset= <?php echo $this->params->get( 'v_offset', '0' ); ?>;
    
function sendform() {
    var i;
    var mydiv=document.getElementById('HLrender');
    var elms =  mydiv.getElementsByTagName("*");
    for(var i = 0, maxI = elms.length; i < maxI; ++i) { 
        var elm = elms[i];
        if (elm.tagName=="FORM" ) {
           elm.submit();
           break;
        }
    }
} 
</script>
<?php if ( !empty( $LoginModule ) ): ?>
    <div id="HLwrapper" style="position: <?php echo $position ?>; margin-top: <?php echo $this->params->get( 'outdraw', '0' ); ?>px;">
       <div id="HLsiteemu" style="width: <?php echo $this->params->get( 'site_width', '900px' ); ?>;" >
        <div id="HLcontainer"  style="margin-right: <?php echo $this->params->get( 'tab_offset', '20' ); ?>px;">
            <div ID="HLhidden">
                 <div ID="HLmodule">
                      <div ID="HLrender">              
                        <?php echo JModuleHelper::renderModule( $LoginModule ); ?>
                      </div>
                 </div>
                 <div ID="HLsep">
                 </div>
            </div>
            <div id="HLhandle">
            <?php if ( ($status==0) and ($quicklogout=='y') ) : ?>
                <A href="#" id="HLtrigger" style="display:none;">.</a>
                <A href="#" onClick="sendform();" style="<?php echo $this->params->get( 'handle_css', '' ); ?>"> <?php echo JText::_( 'BUTTON_LOGOUT'); ?></a> 
            <?php else : ?>
                <A href="#" id="HLtrigger" style="<?php echo $this->params->get( 'handle_css', '' ); ?>"> <?php echo $LoginModule->title; ?></a> 
            <?php endif; ?>                 
            </div>
        </div>        
       </div> 
	</div>	
<?php endif; ?>    