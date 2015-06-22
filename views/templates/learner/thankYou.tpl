<?php
use oat\tao\helpers\Template;
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title><?=__('Thank you');?></title>
	
    <link rel="stylesheet" type="text/css" href="<?= Template::css('reset.css','tao') ?>" />
	<link rel="stylesheet" type="text/css" href="<?= Template::css('custom-theme/jquery-ui-1.8.22.custom.css','tao') ?>" />
    <link rel="stylesheet" type="text/css" href="<?= Template::css('thankyou.css') ?>" />	
</head>

<body>
	<div id="main" class="ui-widget-content ui-corner-all">
			<h1><?=__('You have finished the test!');?></h1>
<?php if (has_data('returnUrl')) :?>
			<div class='message'>
    		<?=has_data('consumerLabel')
    		  ? __('Click on the back button to return to %s.', get_data('consumerLabel'))
    		  : __('Click on the back button to return.');?>
		  </div>
<?php endif; ?>
        		  <div class="continer2">
<?php if (has_data('returnUrl')) :?>
        		  <a href="<?=get_data('returnUrl')?>" class="button" <?php if (has_data('consumerLabel')):?>title="<?=__('Return to %s.',get_data('consumerLabel'))?>"<?php endif;?>>
        			<?=__('Back');?>
        			</a>
<?php endif; ?>
<?php if (get_data('allowRepeat')) :?>
        		  <a href="<?=get_data('returnUrl')?>" class="button" title="<?=__('Repeat the test')?>">
        			<?=__('Repeat');?>
        			</a>
<?php endif; ?>
    		</div>
		</div>
</body>

</html>