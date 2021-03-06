<?php
/**
 * The manage view of faq module of zentaoASM
 *
 * @copyright   Copyright 2009-2011 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @license     LGPL (http://www.gnu.org/licenses/lgpl.html)
 * @author      Congzhi Chen<congzhi@cnezsoft.com>
 * @package     faq
 * @version     $Id: buildform.html.php 1914 2011-06-24 10:11:25Z yidong@cnezsoft.com $
 * @link        http://www.zentao.net
 */
?>
<?php include '../../common/view/header.admin.html.php';?>
<style type='text/css'>
hr {margin:0px;}
</style>
<table class='table-1 bd-none' align='center'>
<tr valign='top'>
<td class='w-200px' style='padding:0'>
<table class='table-1'>
  <caption><?php echo $lang->faq->productList;?></caption>
  <tr><td><?php echo html::select('product', $productList, $selectedProductID, 'class=select-1 onchange=switchProduct(this.value)');?></td></tr>
  <?php if(!empty($categories)):?>
  <?php foreach($categories as $id => $category):?>
  <tr>
    <td>
    <?php echo html::a($this->inLink('manage', "productID=$selectedProductID&categoryID=$category->id"), $category->name);?>
    <div class='a-right'><?php echo html::a($this->inLink('create', "productID=$selectedProductID&categoryID=$category->id"), $lang->faq->create);?></div><hr />  
    </td>
  </tr>
  <?php endforeach;?>
  <?php endif;?>
</table>
</td>
<td style='padding:0'>
<table class='table-1'>
  <caption><?php echo ($selectedProductID == '0'? $lang->product->all : $productList[$selectedProductID]) . $lang->arrow . $lang->faq->faqList;?></caption>
  <?php $i = 1; foreach($faqs as $id => $faq):?>
  <tr><td>
  <?php echo $i . '. Q: ' . $faq->request . '?' . "<br />" . 'A:' . $faq->answer; $i++;?><br />
  <div class='a-right'>
  <?php 
  echo html::a($this->inLink('delete', "FAQID=$faq->id"), $lang->faq->delete, "hiddenwin");
  echo html::a($this->inLink('edit', "FAQID=$faq->id"), $lang->faq->edit);
  ?>
  </div><hr />
  </td></tr>
  <?php endforeach;?>
</table>
</td></tr></table>
<?php include '../../common/view/footer.admin.html.php';?>
