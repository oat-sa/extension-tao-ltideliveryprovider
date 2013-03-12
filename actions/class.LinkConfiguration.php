<?php
/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package filemanager
 * @subpackage action
 */
class ltiDeliveryProvider_actions_LinkConfiguration extends tao_actions_CommonModule {
	
	protected function selectDelivery() {
		
		$ltiSession = ltiProvider_models_classes_LtiService::singleton()->getLTISession();
		 
		$this->setData('treeName', __('Select'));
		$this->setData('dataUrl', tao_helpers_Uri::url('getOntologyData', 'Delivery', 'taoDelivery'));
		
		$this->setData('editInstanceUrl', tao_helpers_Uri::url('setDelivery', null, null, array('link' => $ltiSession->getLtiLinkResource()->getUri())));
		$this->setData('editClassUrl', false);
		
		$this->setData('instanceName', 'tralala');
		
		
		$this->setView('selectDelivery.tpl');
	}

	public function setDelivery() {
		$link = new core_kernel_classes_Resource($this->getRequestParameter('link'));
		$delivery = new core_kernel_classes_Resource(tao_helpers_Uri::decode($this->getRequestParameter('uri')));
		$link->editPropertyValues(new core_kernel_classes_Property(PROPERTY_LINK_DELIVERY), $delivery);
		$this->redirect(_url('configureDelivery'));
	}
	
	public function configureDelivery() {
		$delivery = ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton()->getDeliveryFromLink();
		if (is_null($delivery)) {
			$this->selectDelivery();
		} else {
			$this->setData('delivery', $delivery);			
			$this->setView('viewDelivery.tpl');
		}
	}
	
}
?>