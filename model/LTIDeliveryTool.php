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

namespace oat\ltiDeliveryProvider\model;

use common_session_Session;
use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use oat\ltiDeliveryProvider\model\navigation\LtiNavigationService;
use oat\ltiDeliveryProvider\model\tasks\SendAgsScoreTask;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\session\SessionService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiAgsScoreService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiService;
use \core_kernel_classes_Property;
use \core_kernel_classes_Resource;
use oat\oatbox\user\User;
use oat\oatbox\mutex\LockTrait;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoDelivery\model\authorization\AuthorizationService;
use oat\taoDelivery\model\authorization\AuthorizationProvider;
use oat\taoLti\models\classes\TaoLti1p3Session;
use oat\taoLti\models\classes\TaoLtiSession;
use oat\taoLti\models\classes\user\Lti1p3User;

class LTIDeliveryTool extends ConfigurableService
{
    use LockTrait;

    const TOOL_INSTANCE = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LTIToolDelivery';
    
    const EXTENSION = 'ltiDeliveryProvider';
    const MODULE = 'DeliveryTool';
    const ACTION = 'launch';
    const PROPERTY_LINK_DELIVERY = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LinkDelivery';

    /**
     * @return static
     *
     * @deprecated Added fo backward compatibility. Use service locator instead.
     */
    public static function singleton(): self
    {
        return ServiceManager::getServiceManager()->get(static::class);
    }

    public function getLaunchUrl($parameters = [])
    {
        $fullAction = self::ACTION . '/' . base64_encode(json_encode($parameters));
        return _url($fullAction, self::MODULE, self::EXTENSION);
    }
    
    public function getDeliveryFromLink()
    {
        $remoteLink = LtiService::singleton()->getLtiSession()->getLtiLinkResource();
        return $remoteLink->getOnePropertyValue(new core_kernel_classes_Property(static::PROPERTY_LINK_DELIVERY));
    }
    
    public function linkDeliveryExecution(core_kernel_classes_Resource $link, $userUri, core_kernel_classes_Resource $deliveryExecution)
    {

        $link = $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID)->createDeliveryExecutionLink($userUri, $link->getUri(), $deliveryExecution->getUri());

        return !is_null($link);
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return mixed
     */
    public function getFinishUrl(DeliveryExecution $deliveryExecution)
    {
        /** @var LtiNavigationService $ltiNavigationService */
        $ltiNavigationService = $this->getServiceLocator()->get(LtiNavigationService::SERVICE_ID);

        return $ltiNavigationService->getReturnUrl($this->getLtiLaunchData(), $deliveryExecution);
    }

    /**
     * Start a new delivery execution
     *
     * @param core_kernel_classes_Resource $delivery
     * @param core_kernel_classes_Resource $link
     * @param User $user
     * @return DeliveryExecution
     * @throws \common_exception_Unauthorized
     */
    public function startDelivery(core_kernel_classes_Resource $delivery, core_kernel_classes_Resource $link, common_session_Session $session)
    {
        $user = $session->getUser();

        $lock = $this->createLock(__METHOD__ . $delivery->getUri() . $user->getIdentifier(), 30);
        $lock->acquire(true);

        $this->getAuthorizationProvider()->verifyStartAuthorization($delivery->getUri(), $user);

        /** @var LtiAssignment $assignmentService */
        $assignmentService = $this->getServiceLocator()->get(LtiAssignment::SERVICE_ID);
        if (!$assignmentService->isDeliveryExecutionAllowed($delivery->getUri(), $user)) {
            $lock->release();
            throw new \common_exception_Unauthorized(__('User is not authorized to run this delivery'));
        }
        $stateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);
        $deliveryExecution = $stateService->createDeliveryExecution($delivery->getUri(), $user, $delivery->getLabel());
        $this->linkLtiResultId($deliveryExecution);
        $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID)->createDeliveryExecutionLink($user->getIdentifier(), $link->getUri(), $deliveryExecution->getIdentifier());

        $this->sendAgsScore($session);

        $lock->release();
        return $deliveryExecution;
    }

    protected function sendAgsScore(common_session_Session $session): bool
    {
        $user = $session->getUser();

        if ($session instanceof TaoLti1p3Session
            && $user instanceof Lti1p3User
            && $user->getLaunchData()->hasVariable(LtiLaunchData::AGS_CLAIMS)
        ) {
            /** @var AgsClaim $agsClaim */
            $agsClaim = $user->getLaunchData()->getVariable(LtiLaunchData::AGS_CLAIMS);

            /** @var QueueDispatcherInterface $taskQueue */
            $taskQueue = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
            $taskQueue->createTask(new SendAgsScoreTask(), [
                'registrationId' => $session->getRegistration()->getIdentifier(),
                'agsClaim' => $agsClaim->normalize(),
                'data' => [
                    'userId' => $user->getIdentifier(),
                    'activityProgress' => ScoreInterface::ACTIVITY_PROGRESS_STATUS_STARTED
                ]
            ], 'AGS score send on test launch');
        }

        return false;
    }

    /**
     * Gives you the authorization provider for the given execution.
     *
     * @return AuthorizationProvider
     */
    protected function getAuthorizationProvider()
    {
        $authService = $this->getServiceLocator()->get(AuthorizationService::SERVICE_ID);
        return $authService->getAuthorizationProvider();
    }

    /**
     * Returns an array of DeliveryExecution
     *
     * @param core_kernel_classes_Resource $delivery
     * @param core_kernel_classes_Resource $link
     * @param string $userId
     * @return array
     */
    public function getLinkedDeliveryExecutions(core_kernel_classes_Resource $delivery, core_kernel_classes_Resource $link, $userId)
    {
        /** @var LtiDeliveryExecutionService $deliveryExecutionService */
        $deliveryExecutionService = $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID);
        $executions = $deliveryExecutionService->getLinkedDeliveryExecutions($delivery, $link, $userId);
        return $executions;
    }

    /**
     * Link `lis_result_sourcedid` to delivery execution in order to be able to retrieve delivery execution by lis_result_sourcedid
     *
     * @param DeliveryExecution $deliveryExecution
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    protected function linkLtiResultId(DeliveryExecution $deliveryExecution)
    {
        $executionIdentifier = $deliveryExecution->getIdentifier();
        // lis_outcome_service_url This value should not change from one launch to the next and in general,
        //  the TP can expect that there is a one-to-one mapping between the lis_outcome_service_url and a particular oauth_consumer_key.
        //  This value might change if there was a significant re-configuration of the TC system or if the TC moved from one domain to another.
        $launchData = $this->getLtiLaunchData();
        $resultIdentifier = $launchData->hasVariable('lis_result_sourcedid') ? $launchData->getVariable('lis_result_sourcedid') : $executionIdentifier;

        /** @var LtiResultAliasStorage $ltiResultIdStorage */
        $ltiResultIdStorage = $this->getServiceLocator()->get(LtiResultAliasStorage::SERVICE_ID);
        $ltiResultIdStorage->storeResultAlias($executionIdentifier, $resultIdentifier);
    }

    /**
     * @return LtiLaunchData
     * @throws LtiException
     */
    protected function getLtiLaunchData(): LtiLaunchData
    {
        $session = $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentSession();
        if (!$session instanceof TaoLtiSession) {
            throw new LtiException('Not an LTI session.');
        }

        return $session->getLaunchData();
    }
}
