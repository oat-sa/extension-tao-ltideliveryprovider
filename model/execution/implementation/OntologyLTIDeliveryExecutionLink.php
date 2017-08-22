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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *               
 * 
 */

namespace oat\ltiDeliveryProvider\model\execution\implementation;

use oat\ltiDeliveryProvider\model\execution\LTIDeliveryExecutionLink;

/**
 * Class OntologyLTIDeliveryExecutionLink
 * Ontology implementation of LTIDeliveryExecutionLink
 * @package oat\ltiDeliveryProvider\model\linkDeliveryExecution
 */
class OntologyLTIDeliveryExecutionLink extends \core_kernel_classes_Resource implements LTIDeliveryExecutionLink {

    const CLASS_LTI_DELIVERYEXECUTION_LINK = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LinkDeliveryExecution';
    const PROPERTY_LTI_DEL_EXEC_LINK_USER = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LinkDeliveryExecutionUser';
    const PROPERTY_LTI_DEL_EXEC_LINK_LINK = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LinkDeliveryExecutionLink';
    const PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LinkDeliveryExecutionExecution';

}
