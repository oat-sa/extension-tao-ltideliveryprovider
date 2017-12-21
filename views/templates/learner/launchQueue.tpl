<?php
use oat\tao\helpers\Template;
use oat\tao\helpers\Layout;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?=__('Test Launch Queue');?></title>
        <?= \tao_helpers_Scriptloader::render() ?>
        <link rel="stylesheet" type="text/css" href="<?= Template::css('reset.css','tao') ?>" />
        <link rel="stylesheet" type="text/css" href="<?= Template::css('custom-theme/jquery-ui-1.8.22.custom.css','tao') ?>" />
        <link rel="stylesheet" type="text/css" href="<?= Template::css('errors.css','tao') ?>" />
        <link rel="stylesheet" type="text/css" href="<?= Template::css('userError.css','tao') ?>" />
        <?= Layout::getAmdLoader(Template::js('loader/app.min.js', 'tao'), 'controller/app', get_data('client_params')) ?>
    </head>
    <body>
        <div id="main" class="ui-widget-content ui-corner-all">
            <div>
                <h1><?=__('Test Launch Queue');?></h1>
                <p id="warning_msg">
                    <?= __('An unusually high number of people are testing right now, your test will launch in a few moments to ensure that it runs smoothly. Thanks for your patience!') ?>
                </p>
            </div>
        </div>
    </body>
</html>
