<?php
use oat\tao\helpers\Template;
?><!DOCTYPE html>
<html>
<head>
    <title><?=__('Test error');?></title>
    <link rel="stylesheet" href="<?= Template::css('reset.css','tao') ?>"/>
    <link rel="stylesheet" href="<?= Template::css('custom-theme/jquery-ui-1.9.2.custom.css','tao') ?>"/>
    <link rel="stylesheet" href="<?= Template::css('feedback.css') ?>"/>
    <link rel="shortcut icon" href="<?= Template::img('favicon.ico', 'tao') ?>"/>
</head>
<body>
<div id="main" class="ui-widget-content ui-corner-all">
<?php if (isset($reason) && $reason == 'concurrent-test'): ?>
    <h1>Test paused</h1>
    <div class="message">
        <?= __('The test was suspended because another one was opened in a different window or tab'); ?>.
    </div>
<?php else: ?>
    <h1>Test error</h1>
    <div class="message">
        <?= __('There was an error during the test'); ?>.
    </div>
<?php endif; ?>

    <footer class="logo"></footer>
</div>
</body>
</html>
