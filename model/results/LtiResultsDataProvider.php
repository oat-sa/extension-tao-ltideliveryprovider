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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\model\results;

use oat\ltiDeliveryProvider\model\execution\implementation\OntologyLTIDeliveryExecutionLink;
use oat\tao\model\search\SearchService;
use oat\tao\model\search\strategy\GenerisSearch;
use oat\taoDelivery\model\execution\OntologyDeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLti\models\classes\ResourceLink\OntologyLink;
use oat\taoResultServer\models\classes\search\ResultsDataProvider;

/**
 * Class LtiREsultsdataProvider
 * @package oat\ltiDeliveryProvider\model\results
 */
class LtiResultsDataProvider extends ResultsDataProvider
{

    /**
     * @param      $queryString
     * @param null $rootClass
     * @param int  $start
     * @param int  $count
     * @return mixed
     * @throws \core_kernel_persistence_Exception
     */
    public function query($queryString, $rootClass = null, $start = 0, $count = 10, $options = [])
    {
        $search = SearchService::getSearchImplementation();
        $results = parent::query($queryString, $rootClass, $start, $count, $options);
        if ($search instanceof GenerisSearch && $queryString) {
            $class = new \core_kernel_classes_Class(OntologyLink::CLASS_LTI_INCOMINGLINK);
            $resultsLinks = $class->searchInstances(array(
                OntologyLink::PROPERTY_LINK_ID => $queryString
            ), array(
                'recursive' => true,
                'like'      => true,
            ));

            /** @var \core_kernel_classes_Resource $resource */
            foreach ($resultsLinks as $resource) {
                $uri = $resource->getUri();
                $class = new \core_kernel_classes_Class(OntologyLTIDeliveryExecutionLink::CLASS_LTI_DELIVERYEXECUTION_LINK);
                $executionLinks = $class->searchInstances(array(
                    OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_LINK => $uri
                ));
                if ($executionLinks) {
                    /** @var \core_kernel_classes_Resource $executionLink */
                    foreach ($executionLinks as $executionLink) {
                        $executionUri = $executionLink->getOnePropertyValue(new \core_kernel_classes_Property(OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID));
                        if ($executionUri) {
                            $execution = ServiceProxy::singleton()->getDeliveryExecution($executionUri);
                            try {
                                if (isset($options[self::PARAM_RESULT_CLASS]) && $options[self::PARAM_RESULT_CLASS] == OntologyDeliveryExecution::CLASS_URI) {
                                    $results->append($executionUri);
                                } else {
                                    $results->append($execution->getDelivery()->getUri());
                                }

                            } catch (\Exception $e) {}
                        }
                    }
                }
            }
        }

        return $results;
    }
}
