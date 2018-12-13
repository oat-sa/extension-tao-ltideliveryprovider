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
 */

namespace oat\ltiDeliveryProvider\controller;

use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\ltiDeliveryProvider\model\LtiLaunchDataService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoLti\controller\ToolModule;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiService;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;
use \tao_helpers_Uri;
use \common_session_SessionManager;
use \common_Logger;
use \core_kernel_classes_Resource;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\ltiDeliveryProvider\model\LtiAssignment;
use oat\tao\model\actionQueue\ActionFullException;

/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package ltiDeliveryProvider
 */
class DeliveryTool extends ToolModule
{
    /**
     * Setting this parameter to 'true' will prevent resuming a testsession in progress
     * and will start a new testsession whenever the lti tool is launched 
     * 
     * @var string
     */
    const PARAM_FORCE_RESTART = 'custom_force_restart';
    
    /**
     * Setting this parameter to 'true' will prevent the thank you screen to be shown after
     * the test and skip directly to the return url
     *
     * @var string
     */
    const PARAM_SKIP_THANKYOU = 'custom_skip_thankyou';

    /**
     * Setting this parameter to a string will show this string as the title of the thankyou
     * page. (no effect if PARAM_SKIP_THANKYOU is set to 'true')
     *
     * @var string
     */
    const PARAM_THANKYOU_MESSAGE = 'custom_message';

    /**
     * (non-PHPdoc)
     * @see ToolModule::run()
     *
     * @throws LtiException
     * @throws \InterruptedActionException
     * @throws \ResolverException
     * @throws \common_exception_Error
     * @throws \common_exception_IsAjaxAction
     * @throws \common_exception_NotFound
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    public function run()
    {
        $compiledDelivery = $this->getDelivery();
        if (is_null($compiledDelivery) || !$compiledDelivery->exists()) {
            if ($this->hasAccess(LinkConfiguration::class, 'configureDelivery')) {
                // user authorised to select the Delivery
                $this->redirect(tao_helpers_Uri::url('configureDelivery', 'LinkConfiguration', null));
            } else {
                // user NOT authorised to select the Delivery
                throw new LtiException(
                    __('This tool has not yet been configured, please contact your instructor'),
                    LtiErrorMessage::ERROR_INVALID_PARAMETER);
            }
        } else {
            $user = common_session_SessionManager::getSession()->getUser();
            $isLearner = !is_null($user) && in_array(LtiRoles::CONTEXT_LEARNER, $user->getRoles());
            if ($isLearner) {
                if ($this->hasAccess(DeliveryRunner::class, 'runDeliveryExecution')) {
                    try {
                        $activeExecution = $this->getActiveDeliveryExecution($compiledDelivery);
                        if ($activeExecution && $activeExecution->getState()->getUri() != DeliveryExecution::STATE_PAUSED) {
                            $deliveryExecutionStateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);
                            $deliveryExecutionStateService->pause($activeExecution);
                        }
                        $this->redirect($this->getLearnerUrl($compiledDelivery, $activeExecution));
                    } catch (QtiTestExtractionFailedException $e) {
                        common_Logger::i($e->getMessage());
                        throw new LtiException($e->getMessage());
                    } catch (ActionFullException $e) {
                        $this->redirect(_url('launchQueue', 'DeliveryTool', null, [
                            'position' => $e->getPosition(),
                            'delivery' => $compiledDelivery->getUri()
                        ]));
                    }
                } else {
                    common_Logger::e('Lti learner has no access to delivery runner');
                    $this->returnError(__('Access to this functionality is restricted'), false);
                }   
            } elseif ($this->hasAccess(LinkConfiguration::class, 'configureDelivery')) {
                $this->redirect(_url('showDelivery', 'LinkConfiguration', null, array('uri' => $compiledDelivery->getUri())));
            } else {
                $this->returnError(__('Access to this functionality is restricted to students'), false);
            }
        }
    }

    /**
     * @throws LtiException
     * @throws \common_exception_Error
     */
    public function launchQueue()
    {
        $delivery = $this->getDelivery();
        if (!$delivery->exists()) {
            throw new LtiException(
            __('Delivery does not exist. Please contact your instructor.'),
            LtiErrorMessage::ERROR_INVALID_PARAMETER);
        }
        $runUrl = _url('run', 'DeliveryTool', null, ['delivery' => $delivery->getUri()]);
        $config = $this->getServiceLocator()->get('ltiDeliveryProvider/LaunchQueue')->getConfig();
        $config['runUrl'] = $runUrl;
        $this->defaultData();
        $this->setData('delivery', $delivery);
        $this->setData('position', intval($this->getRequestParameter('position')));
        $this->setData('client_params', $config);
        $this->setView('learner/launchQueue.tpl');
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @param DeliveryExecution $activeExecution
     * @return string
     * @throws LtiException
     * @throws \common_exception_Error
     */
    protected function getLearnerUrl(\core_kernel_classes_Resource $delivery, DeliveryExecution $activeExecution = null)
    {
        $user = \common_session_SessionManager::getSession()->getUser();
        if ($activeExecution === null) {
            $activeExecution = $this->getActiveDeliveryExecution($delivery);
        }

        if ($activeExecution !== null) {
            return _url('runDeliveryExecution', 'DeliveryRunner', null, array('deliveryExecution' => $activeExecution->getIdentifier()));
        }

        /** @var LtiAssignment $assignmentService */
        $assignmentService = $this->getServiceLocator()->get(LtiAssignment::SERVICE_ID);
        if ($assignmentService->isDeliveryExecutionAllowed($delivery->getUri(), $user)) {
            return _url('ltiOverview', 'DeliveryRunner', null, array('delivery' => $delivery->getUri()));
        } else {
            throw new LtiException(
                __('User is not authorized to run this delivery'),
                LtiErrorMessage::ERROR_LAUNCH_FORBIDDEN
            );
        }
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return mixed|null|DeliveryExecution
     */
    protected function getActiveDeliveryExecution(\core_kernel_classes_Resource $delivery)
    {
        $deliveryExecutionService = $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID);
        return $deliveryExecutionService->getActiveDeliveryExecution($delivery);
    }
    
    /**
     * (non-PHPdoc)
     * @see ToolModule::getTool()
     */
    protected function getTool()
    {
        return LTIDeliveryTool::singleton();
    }

    /**
     * Returns the delivery associated with the current link
     * either from url or from the remote_link if configured
     * returns null if none found
     *
     * @return core_kernel_classes_Resource
     * @throws LtiException
     * @throws \common_exception_Error
     */
    private function getDelivery()
    {
        $returnValue = null;
        //passed as aprameter
        if ($this->hasRequestParameter('delivery')) {
            $returnValue = new core_kernel_classes_Resource($this->getRequestParameter('delivery'));
        } else {
            
            $launchData = LtiService::singleton()->getLtiSession()->getLaunchData();

            /** @var LtiLaunchDataService $launchDataService */
            $launchDataService = $this->getServiceLocator()->get(LtiLaunchDataService::SERVICE_ID);
            $returnValue = $launchDataService->findDeliveryFromLaunchData($launchData);
        }
        return $returnValue;
    }
    
}
