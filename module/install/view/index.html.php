<?php
/**
 * The html template file of index method of install module of ZenTaoASM.
 *
 * @copyright   Copyright 2009-2011 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @license     LGPL (http://www.gnu.org/licenses/lgpl.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     ZenTaoASM
 * @version     $Id: index.html.php 867 2010-06-17 09:32:58Z wwccss $
 */
?>
<?php include './header.html.php';?>
<div class='row'>
  <div class='u-1'>
    <table align='center' class='table-6'>
      <caption><?php echo $lang->install->welcome;?></caption>
      <tr><td><?php echo nl2br(sprintf($lang->install->desc, $config->version));?></td></tr>
      <tr><td>
        <?php if(!isset($latestRelease)):?>
        <h3 class='a-center'><?php echo html::a($this->createLink('install', 'step1'), $lang->install->start);?></h3>
        <?php else:?>
        <?php vprintf($lang->install->newReleased, $latestRelease);?>
        <h3 class='a-center'>
          <?php 
          echo $lang->install->choice;
          echo html::a($latestRelease->url, $lang->install->seeLatestRelease, '_blank');
          echo html::a($this->createLink('install', 'step1'), $lang->install->keepInstalling);
          ?>
        </h3>
        <?php endif;?>
      </td></tr>
    </table>
  </div>
</div>
<?php include './footer.html.php';?>
