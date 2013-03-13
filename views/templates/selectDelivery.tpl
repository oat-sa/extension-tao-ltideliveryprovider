<?include(TAO_TPL_PATH.'layout_header.tpl')?>
<br />
<div class="ui-widget-header ui-state-default ui-corner-top">
	<b><?=__('Please select a delivery for %s', get_data('linkTitle'))?></b>
</div>
<div class="ui-widget-content ui-corner-bottom">
	<div id="tree-chooser" ></div>
	<div id="tree-action" class="tree-actions"></div>
</div>
<div id="form-container"></div>

<script type="text/javascript">
	$(function(){
		require(['require', 'jquery', 'generis.tree.browser'], function(req, $, GenerisTreeBrowserClass) {
			new GenerisTreeBrowserClass('#tree-chooser', "<?=get_data('dataUrl')?>", {
				formContainer: "#form-container",
				actionId: "chooser",
				hideInstances: 'false',
				editClassAction: "<?=get_data('editClassUrl')?>",
				editInstanceAction: "<?=get_data('editInstanceUrl')?>",
				createInstanceAction: false,
				moveInstanceAction: false,
				subClassAction: false,
				deleteAction: false,
				duplicateAction: false,
			});
		});
	});
</script>
<?include(TAO_TPL_PATH.'layout_footer.tpl')?>