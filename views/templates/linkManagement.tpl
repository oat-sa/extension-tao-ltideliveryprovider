<div class="main-container">
    <div id="form-title"
        class="ui-widget-header ui-corner-top ui-state-default">
		<?=get_data('formTitle')?>
	</div>
    <div id="form-container" class="ui-widget-content ui-corner-bottom">
        <table>
		
			<?if (has_data('feedback')) :?>
    			<tr>
                <th colspan="2"><?= get_data('feedback')?></th>
            </tr>
			<?php else:?>
            <tr>
                <th><?= __('Launch URL')?></th>
                <td><a href="<?= get_data('launchUrl')?>"><?= get_data('launchUrl')?></a></td>
            </tr>
			<?php endif;?>
			<tr>
                <th><?= __('Consumers')?></th>
                <td>
				<?if (count(get_data('consumers')) > 0) :?>
    				<?foreach (get_data('consumers') as $consumer) :?>
    				    <?= $consumer->getLabel()?><br />
    		        <?endforeach;?>
    		    <?php else:?>
    		      <div><?= __('No LTI consumers defined')?></div>
    		    <?php endif;?>
				</td>
            </tr>
        </table>
    </div>
</div>
<?include(TAO_TPL_PATH . 'footer.tpl');?>
