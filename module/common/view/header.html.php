<?php
if($extView = $this->getExtViewFile(__FILE__)){include $extView; return helper::cd();}
include 'header.lite.html.php';
//include 'chosen.html.php';
//include 'validation.html.php';
?>
<?php
/* Load hook files for current page. */
$extPath      = $this->app->getModuleRoot() . '/common/ext/view/';
$extHookRule  = $extPath . 'header.*.hook.php';
$extHookFiles = glob($extHookRule);
if($extHookFiles) foreach($extHookFiles as $extHookFile) include $extHookFile;
?>
<?php if(empty($_GET['onlybody']) or $_GET['onlybody'] != 'yes'):?>
<?php $this->app->loadConfig('sso');?>
<?php if(!empty($this->config->sso->redirect)) js::set('ssoRedirect', $this->config->sso->redirect);?>
<header id='header'>
  <div id='mainHeader'>
    <div class='container'>
      <hrgroup id='heading'>
        <?php if(empty($this->config->sso->redirect)):?>
        <h1 id='companyname' title='<?php printf($lang->welcome, $app->company->name);?>'><?php printf($lang->welcome, $app->company->name);?></h1>
        <?php endif;?>
      </hrgroup>
      <nav id='navbar'><?php commonModel::printMainmenu($this->moduleName);?></nav>
      <div id='toolbar'>
        <?php common::printAboutBar();?>
        <div id="userMenu">
          <?php common::printSearchBox();?>
          <ul id="userNav" class="nav nav-default">
            <?php list($adminName, $adminModule, $adminMethod) = explode('|', $lang->adminMenu);?>
            <li><?php echo html::a($this->createLink($adminModule, $adminMethod), $adminName);?></li>
            <li><?php common::printUserBar();?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div id='subHeader'>
    <div class='container'>
      <div id="pageNav" class='btn-toolbar'><?php if(isset($lang->modulePageNav)) echo $lang->modulePageNav;?></div>
      <nav id='subNavbar'><?php common::printModuleMenu($this->moduleName);?></nav>
      <div id="pageActions"><div class='btn-toolbar'><?php if(isset($lang->modulePageActions)) echo $lang->modulePageActions;?></div></div>
    </div>
  </div>
  <?php
  if(!empty($this->config->sso->redirect))
  {
      css::import($defaultTheme . 'bindranzhi.css');
      js::import($jsRoot . 'bindranzhi.js');
  }
  ?>
</header>

<main id='main' <?php if(!empty($this->config->sso->redirect)) echo "class='ranzhiFixedTfootAction'";?> >
<?php endif;?>
  <div class='container'>
