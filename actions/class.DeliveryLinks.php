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
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package filemanager
 * @subpackage action
 */
class ltiDeliveryProvider_actions_DeliveryLinks extends taoLti_actions_LinkManagement {
	
	public function __construct() {
		parent::__construct(ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton());
	}
    /**
     * Displays the LTI link for the consumer with respect to the currently selected delviery
     * at tdelviery level, checks if the delviery is related to a resultserver cofnigured with the correct outcome service impelmentation
     * @author patrick <patrick@taotesting.com>
     */
	public function index(){
        $feedBackMessage = '';
        //checks the constraint for the results handling, depends on taoResultServer, taoLtiBasicOutcome
        $selectedDelivery = new core_kernel_classes_Resource(tao_helpers_Uri::decode($this->getRequestParameter('uri')));
        try {
            $resultServer = $selectedDelivery->getUniquePropertyValue(new core_kernel_classes_Property(TAO_DELIVERY_RESULTSERVER_PROP));
        } catch (Exception $e) {
            $feedBackMessage = __("The delivery is not associated to a Result server storage policy");
        }
        try {
        $resultServerModel = $resultServer->getUniquePropertyValue(new core_kernel_classes_Property(TAO_RESULTSERVER_MODEL_PROP));
        } catch (Exception $e) {
            $feedBackMessage = __("The delivery is associated to an unconfigured result server storage policy ");
        }

        if ($resultServerModel->getUri() == "http://www.tao.lu/Ontologies/taoLtiBasicOutcome.rdf#LtiResultServerModel") {
            //$feedBackMessage = __("This is delivery is correctly configured for returning results using LTI Basic outcome Service");
        } else {
            $feedBackMessage = __("Notice : In order to use this delivery from an external LTI consumer tool, you will need to configure it with a LTI Basic outcome result server");
        }
        
        $compiledDelivery = taoDelivery_models_classes_CompilationService::singleton()->getCompiledContent($selectedDelivery);
        if (is_null($compiledDelivery)) {
            $feedBackMessage = __('Delivery has not been compiled yet');
        } else {
            $this->setData('launchUrl', $this->service->getLaunchUrl(array('delivery' => $compiledDelivery->getUri())));
        }

        if (!empty($feedBackMessage)) {
            $this->setData('feedback', $feedBackMessage);
        }
        $class = new core_kernel_classes_Class(CLASS_LTI_CONSUMER);
        $this->setData('consumers', $class->getInstances());
        $this->setView('linkManagement.tpl', 'ltiDeliveryProvider');

    }
}