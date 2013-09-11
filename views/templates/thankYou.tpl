<?include(TAO_TPL_PATH.'layout_header.tpl')?>
<h1><?=__('Thank you');?></h1>

<?php if (has_data('returnUrl')) :?>
<a href="<?=get_data('returnUrl')?>"><?=__('Return');?></a>
<?php endif; ?>
<?include(TAO_TPL_PATH.'layout_footer.tpl')?>