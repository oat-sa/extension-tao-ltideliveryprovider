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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 * @author Ilya Yarkavets <ilya.yarkavets@1pt.com>
 */

namespace oat\ltiDeliveryProvider\model;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoOutcomeUi\model\search\ResultCustomFieldsService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;

/**
 * Class LtiResultCustomFieldsService
 * @package oat\taoOutcomeUi\model\search
 */
class LtiResultCustomFieldsService extends ResultCustomFieldsService
{
    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return array
     * @throws \common_exception_NotFound
     */
    public function getCustomFields(DeliveryExecutionInterface $deliveryExecution)
    {
        $body = parent::getCustomFields($deliveryExecution);

        /** @var DeliveryLog $deliveryLog */
        $deliveryLog = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
        $data = $deliveryLog->get(
            $deliveryExecution->getIdentifier(),
            'LTI_DELIVERY_EXECUTION_CREATED'
        );

        if ($data) {
            $data = current($data);
            if (isset($data['data'])) {
                $body = array_merge($body, $data['data']);
            }
        }

        return $body;
    }
}