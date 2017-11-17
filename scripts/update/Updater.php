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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 */
namespace oat\ltiDeliveryProvider\scripts\update;
use oat\ltiDeliveryProvider\model\execution\implementation\LtiDeliveryExecutionService;
use oat\ltiDeliveryProvider\model\LtiAssignment;
use oat\ltiDeliveryProvider\model\LtiLaunchDataService;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ServiceNotFoundException;
use oat\ltiDeliveryProvider\model\LtiResultAliasStorage;
use oat\ltiDeliveryProvider\model\ResultAliasService;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\taoLti\models\classes\LtiRoles;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\ltiDeliveryProvider\model\actions\GetActiveDeliveryExecution;
use oat\tao\model\actionQueue\ActionQueue;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;

class Updater extends \common_ext_ExtensionUpdater
{

    /**
     * @param string $initialVersion
     * @return string|void
     */
    public function update($initialVersion)
    {
        $this->skip('0', '1.7.1');

        if ($this->isVersion('1.7.1')) {
            try {
                $this->getServiceManager()->get(LtiAssignment::LTI_SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new LtiAssignment();
                $service->setServiceManager($this->getServiceManager());
                $this->getServiceManager()->register(LtiAssignment::LTI_SERVICE_ID, $service);
            }
            $this->setVersion('2.0.0');
        }
        $this->skip('2.0.0', '2.0.1');

        if ($this->isVersion('2.0.1')) {
            $extension = \common_ext_ExtensionsManager::singleton()->getExtensionById('ltiDeliveryProvider');

            $config = $extension->getConfig('deliveryRunner');

            $config['showControls'] = false;

            $extension->setConfig('deliveryRunner', $config);

            $this->setVersion('2.1.0');
        }

        if ($this->isVersion('2.1.0')) {
            $service = new LtiDeliveryExecutionService([]);
            $this->getServiceManager()->register(LtiDeliveryExecutionService::SERVICE_ID, $service);
            $this->setVersion('2.2.0');
        }

        $this->skip('2.2.0', '2.3.0');

        if ($this->isVersion('2.3.0')) {
            $extension = \common_ext_ExtensionsManager::singleton()->getExtensionById('ltiDeliveryProvider');

            $extension->unsetConfig('deliveryRunner');

            $this->setVersion('2.4.0');
        }

        $this->skip('2.4.0', '3.2.1');

        if ($this->isVersion('3.2.1')) {
            $service = new LtiResultAliasStorage([
                LtiResultAliasStorage::OPTION_PERSISTENCE => 'default'
            ]);
            $service->setServiceManager($this->getServiceManager());

            $migration = new \oat\ltiDeliveryProvider\scripts\dbMigrations\LtiResultAliasStorage_v1();
            $migration->apply(
                $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById('default')
            );

            $this->getServiceManager()->register(LtiResultAliasStorage::SERVICE_ID, $service);
            $this->getServiceManager()->register(ResultAliasService::SERVICE_ID, new ResultAliasService());

            $this->setVersion('3.3.0');
        }
        $this->skip('3.3.0', '3.5.1');

        if ($this->isVersion('3.5.1')) {
            AclProxy::applyRule(new AccessRule('grant', LtiRoles::CONTEXT_LEARNER, DeliveryTool::class, 'launchQueue'));
            $launchQueueConfig = new \oat\oatbox\config\ConfigurationService([
                'config' => [
                    'relaunchInterval' => 30,
                    'relaunchIntervalDeviation' => 5,
                ]
            ]);
            $this->getServiceManager()->register('ltiDeliveryProvider/LaunchQueue', $launchQueueConfig);


            $actionQueue = $this->getServiceManager()->get(ActionQueue::SERVICE_ID);
            $actions = $actionQueue->getOption(ActionQueue::OPTION_ACTIONS);
            $actions[GetActiveDeliveryExecution::class] = [
                ActionQueue::ACTION_PARAM_LIMIT => 0,
                ActionQueue::ACTION_PARAM_TTL => 3600, //one hour
            ];
            $actionQueue->setOption(ActionQueue::OPTION_ACTIONS, $actions);
            $this->getServiceManager()->register(ActionQueue::SERVICE_ID, $actionQueue);

            $ltiDeliveryExecutionService = $this->getServiceManager()->get(LtiDeliveryExecutionService::SERVICE_ID);
            $ltiDeliveryExecutionService->setOption(LtiDeliveryExecutionService::OPTION_QUEUE_PERSISTENCE, 'cache');
            $this->getServiceManager()->register(LtiDeliveryExecutionService::SERVICE_ID, $ltiDeliveryExecutionService);

            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(DeliveryExecutionState::class, [LtiDeliveryExecutionService::SERVICE_ID, 'executionStateChanged']);
            $eventManager->attach(DeliveryExecutionCreated::class, [LtiDeliveryExecutionService::SERVICE_ID, 'executionCreated']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('3.6.0');
        }

        if ($this->isVersion('3.6.0')) {

            $ltiDeliveryExecutionService = $this->getServiceManager()->get(LtiDeliveryExecutionService::SERVICE_ID);
            $ltiDeliveryExecutionService->setOption(LtiDeliveryExecutionService::OPTION_QUEUE_PERSISTENCE, 'cache');
            $this->getServiceManager()->register(LtiDeliveryExecutionService::SERVICE_ID, $ltiDeliveryExecutionService);

            $this->setVersion('3.7.0');
        }

        $this->skip('3.7.0', '3.8.1');

        if ($this->isVersion('3.8.1')) {

            $ltiLaunchDataService = new LtiLaunchDataService();
            $this->getServiceManager()->register(LtiLaunchDataService::SERVICE_ID, $ltiLaunchDataService);

            $this->setVersion('3.9.0');
        }
        $this->skip('3.9.0', '3.10.1');
    }
}
