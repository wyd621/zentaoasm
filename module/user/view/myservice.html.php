<?php
/**
 * The myservice file of user module of zentaoASM.
 *
 * @copyright   Copyright 2009-2011 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @license     LGPL (http://www.gnu.org/licenses/lgpl.html)
 * @author      Congzhi Chen <congzhi@cnezsoft.com>
 * @package     User
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include '../../common/view/header.html.php';?>
<?php include '../../common/view/datepicker.html.php';?>
<div class='row'>
  <div class='span2'>
    <div class='cont-left'><?php include 'blockusermenu.html.php';?></div>
  </div>
  <div class='span10'>
    <div class='cont'>
    <table align='center' class='table-1 table-bordered'>
      <caption><div class='f-left'><?php echo $lang->user->myService;?></div></caption>
      <tr>
        <th class='w-100px'><?php echo $lang->user->serviceID;?></th>
        <th><?php echo $lang->user->product;?></th>
        <th class='w-120px'><?php echo $lang->user->serviceTime;?></th>
      </tr>
      <?php foreach($serviceProducts as $serviceProduct):?>
      <tr>
        <td class='a-center'><strong><?php echo $serviceProduct->id;?></strong></td>
        <td class='a-center'><?php echo $serviceProduct->name; ?> </td>
        <td class='a-center'><?php echo $serviceProduct->serviceTime; ?> </td>
      </tr>
      <?php endforeach;?>
    </table>
    </div>
  </div>
</div>
<?php include '../../common/view/footer.html.php';?>
