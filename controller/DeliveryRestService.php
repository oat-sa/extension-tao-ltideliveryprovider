<?php
/**
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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\ltiDeliveryProvider\controller;

use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\tao\model\TaoOntology;

/**
 * LTI Delivery REST API
 */
class DeliveryRestService extends \tao_actions_RestController 
{
    
    /**
     * return a LTI link URI from a valid delivery id
     * @author Christophe GARCIA <christopheg@taotesing.com>
     */
    public function getUrl() {
        try {
            if ($this->getRequestMethod()!= \Request::HTTP_GET) {
                    throw new \common_exception_NotImplemented('Only GET method is accepted to request this service.');
            }
            
            if(!$this->hasRequestParameter('deliveryId')) {
                $this->returnFailure(new \common_exception_MissingParameter('At least one mandatory parameter was required but found missing in your request'));
            } 
            
            $selectedDelivery = new \core_kernel_classes_Resource($this->getRequestParameter('deliveryId'));
            if(!$selectedDelivery->isInstanceOf(new \core_kernel_classes_Class(TaoOntology::CLASS_URI_DELIVERY))) {
                $this->returnFailure(new \common_exception_NotFound('Delivery not found'));
            }

            $this->returnSuccess(LTIDeliveryTool::singleton()->getLaunchUrl(array('delivery' => $selectedDelivery->getUri())));

        } catch (\Exception $ex) {
             $this->returnFailure($ex);
        }
        
    }
    
}

