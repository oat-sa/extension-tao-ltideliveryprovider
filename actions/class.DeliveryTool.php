<?php
/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package filemanager
 * @subpackage action
 */
class ltiDeliveryProvider_actions_DeliveryTool extends ltiProvider_actions_ToolModule {
	
	const TOOL_INSTANCE = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LTITool';
	
	public function __construct() {
		parent::__construct();		
	}
	
	private function getDelivery() {
		$returnValue = null;
		if ($this->hasRequestParameter('delivery')) {
			$returnValue = new core_kernel_classes_Resource($this->getRequestParameter('delivery'));
		}
		return $returnValue;
	}
	
	public function launch() {
		
		$delivery = $this->getDelivery();
		
		if (is_null($delivery)) {
			if (tao_helpers_funcACL_funcACL::hasAccess('ltiDeliveryProvider', 'LinkConfiguration', 'selectDelivery')) {
				// user authorised to select the Delivery
				$this->redirect(tao_helpers_Uri::url('selectDelivery', 'LinkConfiguration', null, $param));
			} else {
				// user NOT authorised to select the Delivery
				$this->setView('missing.tpl');
			}
		} else {
			$processDefinition = $delivery->getUniquePropertyValue(new core_kernel_classes_Property(TAO_DELIVERY_PROCESS));
			
			$userService = taoDelivery_models_classes_UserService::singleton();
			$subject = $userService->getCurrentUser();
			
			$newProcessExecution = taoDelivery_models_classes_DeliveryService::singleton()->initDeliveryExecution($processDefinition, $subject);
	
			$param = array('processUri' => $newProcessExecution->getUri());
			$this->redirect(tao_helpers_Uri::url('index', 'ProcessBrowser', 'taoDelivery', $param));
		}
	}
	
}
?>