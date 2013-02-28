<?php
/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package filemanager
 * @subpackage action
 */
class ltiDeliveryProvider_actions_DeliveryLinks extends ltiProvider_actions_LinkManagement {
	
	public function __construct() {
		parent::__construct(ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton());
	}
	
}
?>