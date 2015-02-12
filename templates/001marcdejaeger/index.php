<?php
defined('_JEXEC') or die('Restricted access'); // no direct access
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php';
$document = null;
if (isset($this))
  $document = & $this;
$baseUrl = $this->baseurl;
$templateUrl = $this->baseurl . '/templates/' . $this->template;
artxComponentWrapper($document);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" >
<head>

    <jdoc:include type="head" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/system/css/system.css" type="text/css" />
    <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/system/css/general.css" type="text/css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $templateUrl; ?>/css/template.css" media="screen" />
    <!--[if IE 6]><link rel="stylesheet" href="<?php echo $templateUrl; ?>/css/template.ie6.css" type="text/css" media="screen" /><![endif]-->
    <!--[if IE 7]><link rel="stylesheet" href="<?php echo $templateUrl; ?>/css/template.ie7.css" type="text/css" media="screen" /><![endif]-->
    <script type="text/javascript" src="<?php echo $templateUrl; ?>/js/jquery.js"></script>
    <script type="text/javascript" src="<?php echo $templateUrl; ?>/js/modernizr.js"></script>
    <script type="text/javascript" src="<?php echo $templateUrl; ?>/script.js"></script>
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', 'UA-26179509-5']);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
</head>
<body>
    <header>
      <div class="inner">
          <div class="logo">
            <div class="symbol"></div>
            <div class="praktijk"></div>
            <div class="centrum"></div>
            <div class="lochristi"></div>
          </div>
      </div>
      <div class="menu-btn">
<button type="button" role="button" aria-label="Toggle Navigation" class="lines-button x">
          <span class="lines"></span>
        </button>
      </div>
    </header>

<div id="art-main"> 
  <div class="art-sheet">
    <div class="art-sheet-tl"></div>
    <div class="art-sheet-tr"></div>
    <div class="art-sheet-bl"></div>
    <div class="art-sheet-br"></div>
    <div class="art-sheet-tc"></div>
    <div class="art-sheet-bc"></div>
    <div class="art-sheet-cl"></div>
    <div class="art-sheet-cr"></div>
    <div class="art-sheet-cc"></div>
    <div class="art-sheet-body">
      <jdoc:include type="modules" name="user3" />
      <jdoc:include type="modules" name="banner1" style="artstyle" artstyle="art-nostyle" />
      <?php echo artxPositions($document, array('top1', 'top2', 'top3'), 'art-block'); ?>
      <div class="art-content-layout">
        <div class="art-content-layout-row">
          <div class="art-layout-cell art-<?php echo artxCountModules($document, 'right') ? 'content' : 'content-wide'; ?>">
            <?php
  echo artxModules($document, 'banner2', 'art-nostyle');
  if (artxCountModules($document, 'breadcrumb'))
    echo artxPost(null, artxModules($document, 'breadcrumb'));
  echo artxPositions($document, array('user1', 'user2'), 'art-article');
  echo artxModules($document, 'banner3', 'art-nostyle');
?>
            <?php if (artxHasMessages()) : ?>
            <div class="art-post">
              <div class="art-post-body">
                <div class="art-post-inner">
                  <div class="art-postcontent"> 
                    <!-- article-content -->
                    
                    <jdoc:include type="message" />
                    
                    <!-- /article-content --> 
                  </div>
                  <div class="cleared"></div>
                </div>
                <div class="cleared"></div>
              </div>
            </div>
            <?php endif; ?>
            <jdoc:include type="component" />
            <?php echo artxModules($document, 'banner4', 'art-nostyle'); ?> <?php echo artxPositions($document, array('user4', 'user5'), 'art-article'); ?> </div>
          <?php if (artxCountModules($document, 'right')) : ?>
          <div class="art-layout-cell art-sidebar1"><?php echo artxModules($document, 'right', 'art-block'); ?> </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="cleared"></div>
      <?php echo artxPositions($document, array('bottom1', 'bottom2', 'bottom3'), 'art-block'); ?>
      <jdoc:include type="modules" name="banner6" style="artstyle" artstyle="art-nostyle" />
      <div class="art-footer">
        <div class="art-footer-body">
          <div class="art-footer-text">
            <?php if (artxCountModules($document, 'copyright') == 0): ?>
            <p>Copyright &copy; 2010 ---.<br />
              All Rights Reserved.</p>
            <?php else: ?>
            <?php echo artxModules($document, 'copyright', 'art-nostyle'); ?>
            <?php endif; ?>
          </div>
          <div class="cleared"></div>
        </div>
      </div>
      <div class="cleared"></div>
    </div>
  </div>
  <div class="cleared"></div>
  <p class="art-page-footer"><?php echo artxModules($document, 'banner5', 'art-nostyle'); ?></p>
</div>
</body>
</html>