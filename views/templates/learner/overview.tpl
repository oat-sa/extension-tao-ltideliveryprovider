<?php
use oat\tao\helpers\Template;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title><?=__('Thank you');?></title>
	
    <script id='amd-loader' 
        type="text/javascript" 
        src="<?=Template::js('lib/require.js', 'tao')?>" 
        data-main="<?=Template::js('controller/overview')?>"
        data-config="<?=get_data('client_config_url')?>">
    </script>
	<link rel="stylesheet" type="text/css" href="<?= ROOT_URL ?>tao/views/css/reset.css" />
	<link rel="stylesheet" type="text/css" href="<?= ROOT_URL ?>tao/views/css/custom-theme/jquery-ui-1.8.22.custom.css" />
    <link rel="stylesheet" type="text/css" href="<?= BASE_WWW ?>css/thankyou.css" />	
</head>

<body>
	<div id="main" class="ui-widget-content ui-corner-all">
    	<h1><?=__('You have already taken this test.');?></h1>
        <div class="continer2">
        <?php if (get_data('allowRepeat')) :?>
        <a href="<?= _url('repeat', 'DeliveryRunner', null, array('delivery' => get_data('delivery')))?>" class="button" title="<?=__('Repeat the test')?>" >
            <?=__('Repeat');?>
    	</a>
        <?php endif; ?>
        </div>
	</div>
</body>

</html>