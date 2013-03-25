<?php
/*  
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2013 (original work) Open Assessment Techonologies SA (under the project TAO-PRODUCT);
 *               
 * 
 */
?>
<?php
/**
 * Allows instructors to configure the LTI remote_link
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package litDeliveryProvider
 * @subpackage actions
 */
class ltiDeliveryProvider_actions_LinkConfiguration extends tao_actions_CommonModule {
	
	/**
	 * Displays the form to select a delivery
	 */
	protected function selectDelivery() {
		
		$ltiSession = ltiProvider_models_classes_LtiService::singleton()->getLTISession();
		 
		$this->setData('dataUrl', tao_helpers_Uri::url('getOntologyData', 'Delivery', 'taoDelivery'));
		$this->setData('editInstanceUrl', tao_helpers_Uri::url('setDelivery', null, null, array('link' => $ltiSession->getLtiLinkResource()->getUri())));
		$this->setData('editClassUrl', false);
		
		$this->setData('linkTitle', $ltiSession->getResourceLinkTitle());
		
		$this->setView('selectDelivery.tpl');
	}

	/**
	 * called onSelect of a delivery
	 */
	public function setDelivery() {
		$link = new core_kernel_classes_Resource($this->getRequestParameter('link'));
		$delivery = new core_kernel_classes_Resource(tao_helpers_Uri::decode($this->getRequestParameter('uri')));
		$link->editPropertyValues(new core_kernel_classes_Property(PROPERTY_LINK_DELIVERY), $delivery);
		echo '<script language="javascript">document.location.reload()</script>';
		//echo json_encode(array('message' => __('Sequence saved successfully')));;
	}
	
	/**
	 * Either displays the currently associated delivery
	 * or calls selectDelivery in order to allow the user to select a delivery
	 * 
	 * Only accessible to LTI instructors
	 */
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