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
 * Copyright (c) 2013-2023 (original work) Open Assessment Technologies SA.
 */

namespace oat\ltiDeliveryProvider\controller;

use oat\ltiDeliveryProvider\model\navigation\Command\GenerateReturnUrlCommand;
use oat\tao\helpers\UrlHelper;
use oat\tao\model\theme\ThemeServiceInterface;
use oat\taoDelivery\controller\DeliveryServer;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLti\controller\traits\LtiModuleTrait;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiService;
use oat\taoLti\models\classes\theme\LtiHeadless;
use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\ltiDeliveryProvider\model\navigation\LtiNavigationService;
use oat\taoQtiTest\model\Service\ConcurringSessionService;

/**
 * Called by the DeliveryTool to override DeliveryServer settings
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package ltiDeliveryProvider
 */
class DeliveryRunner extends DeliveryServer
{
    use LtiModuleTrait;

    /**
     * Defines if the top and bottom action menu should be displayed or not
     *
     * @return boolean
     */
    protected function showControls()
    {
        $themeService = $this->getServiceManager()->get(ThemeServiceInterface::SERVICE_ID);
        if ($themeService instanceof ThemeServiceInterface || $themeService instanceof LtiHeadless) {
            return !$themeService->isHeadless();
        }
        return false;
    }

    protected function getReturnUrl()
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();

        return _url(
            'ltiReturn',
            'DeliveryRunner',
            'ltiDeliveryProvider',
            ['deliveryExecution' => $deliveryExecution->getIdentifier()]
        );
    }

    public function ltiReturn()
    {
        $isFeedback = false;
        $queryString = [];
        $concurringService = $this->getConcurringSessionService();
        $navigation = $this->getServiceLocator()->get(LtiNavigationService::SERVICE_ID);
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $launchData = LtiService::singleton()->getLtiSession()->getLaunchData();

        if ($concurringService->isConcurringSession($deliveryExecution)) {
            $isFeedback = true;
            $queryString = [
                'reason' => 'concurrent-test'
            ];
            $concurringService->clearConcurringSession($deliveryExecution);
        }

        $redirectUrl = $navigation->generateReturnUrl(
            new GenerateReturnUrlCommand(
                $launchData,
                $deliveryExecution,
                $isFeedback,
                $queryString
            )
        );
        \common_Logger::i(
            sprintf(
                'Redirected from the deliveryExecution %s to %s',
                $deliveryExecution->getIdentifier(),
                $redirectUrl
            )
        );

        $this->redirect($redirectUrl);
    }

    /**
     * Shown uppon returning to a finished delivery execution
     */
    public function ltiOverview()
    {
        $this->setData('delivery', $this->getRequestParameter('delivery'));
        $this->setData('allowRepeat', true);
        $this->setView('learner/overview.tpl');
    }

    /**
     * @throws LtiException
     * @throws \InterruptedActionException
     * @throws \ResolverException
     * @throws \common_exception_Error
     * @throws \common_exception_IsAjaxAction
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    public function repeat()
    {
        $delivery = new \core_kernel_classes_Resource($this->getRequestParameter('delivery'));

        $remoteLink = LtiService::singleton()->getLtiSession()->getLtiLinkResource();
        $user = \common_session_SessionManager::getSession()->getUser();

        try {
            $newExecution = $this->getServiceLocator()->get(LTIDeliveryTool::class)->startDelivery(
                $delivery,
                $remoteLink,
                $user
            );
            $this->getConcurringSessionService()->pauseActiveDeliveryExecutionsForUser($newExecution);
            $runDeliveryExecutionUrl = $this->getServiceLocator()->get(UrlHelper::class)->buildUrl(
                'runDeliveryExecution',
                null,
                null,
                ['deliveryExecution' => $newExecution->getIdentifier()]
            );
            $this->redirect($runDeliveryExecutionUrl);
        } catch (\common_exception_Unauthorized $e) {
            $ltiException = new LtiException(
                $e->getMessage(),
                LtiErrorMessage::ERROR_LAUNCH_FORBIDDEN
            );
            $this->returnLtiError($ltiException);
        }
    }

    /**
     * @throws LtiException
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    public function thankYou()
    {
        $launchData = LtiService::singleton()->getLtiSession()->getLaunchData();

        if ($launchData->hasVariable(LtiLaunchData::TOOL_CONSUMER_INSTANCE_NAME)) {
            $this->setData('consumerLabel', $launchData->getVariable(LtiLaunchData::TOOL_CONSUMER_INSTANCE_NAME));
        } elseif ($launchData->hasVariable(LtiLaunchData::TOOL_CONSUMER_INSTANCE_DESCRIPTION)) {
            $this->setData(
                'consumerLabel',
                $launchData->getVariable(LtiLaunchData::TOOL_CONSUMER_INSTANCE_DESCRIPTION)
            );
        }

        if ($launchData->hasReturnUrl()) {
            $this->setData('returnUrl', $launchData->getReturnUrl());
        }

        if ($launchData->hasVariable(DeliveryTool::PARAM_THANKYOU_MESSAGE)) {
            $this->setData('message', $launchData->getVariable(DeliveryTool::PARAM_THANKYOU_MESSAGE));
        }

        $this->setData('allowRepeat', false);
        $this->setView('learner/thankYou.tpl');
    }

    public function feedback(): void
    {
        $this->setData('reason', $this->getRequestParameter('reason'));
        $this->setView('learner/feedback.tpl');
    }

    /**
     * Redirect user to return URL
     */
    public function finishDeliveryExecution()
    {
        $deliveryExecution = null;
        if ($this->hasRequestParameter('deliveryExecution')) {
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution(
                $this->getRequestParameter('deliveryExecution')
            );
            if ($deliveryExecution->getState() !== DeliveryExecution::STATE_FINISHIED) {
                $stateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);
                $stateService->finish($deliveryExecution);
            }
        }
        $redirectUrl = $deliveryExecution
            ? $this->getServiceLocator()->get(LTIDeliveryTool::class)->getFinishUrl($deliveryExecution)
            : $this->getReturnUrl();
        $this->redirect($redirectUrl);
    }

    private function getConcurringSessionService(): ConcurringSessionService
    {
        return $this->getPsrContainer()->get(ConcurringSessionService::class);
    }
}
