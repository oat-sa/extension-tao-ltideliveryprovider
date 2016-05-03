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

use oat\ltiDeliveryProvider\model\LtiRestApiService;

class RestService extends \tao_actions_CommonRestModule
{
    const LTI_USER_ID = 'lti_user_id';
    const LTI_CONSUMER_KEY = 'lti_consumer_key';

    /**
     * taoResultServer_actions_QtiRestResults constructor.
     * Pass model service to handle http call business
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = LtiRestApiService::singleton();
    }

    /**
     * End point to get common user uri by lti user id
     */
    public function getUserId()
    {
        try {
            if (strtolower($this->getRequestMethod())!=='get') {
                throw new \common_exception_NoImplementation();
            }

            $parameters = $this->getParameters();
            if (!isset($parameters[self::LTI_USER_ID])) {
                throw new \common_exception_MissingParameter(self::LTI_USER_ID, __FUNCTION__);
            }
            if (!isset($parameters[self::LTI_CONSUMER_KEY])) {
                throw new \common_exception_MissingParameter(self::LTI_CONSUMER_KEY, __FUNCTION__);
            }

            $id = $parameters[self::LTI_USER_ID];
            $key = $parameters[self::LTI_CONSUMER_KEY];

            $data = $this->service->getUserId($id, $key);
            if (!$data) {
                \common_Logger::i('Id ' . $id . ' is not found.');
                throw new \common_exception_NoContent('No id found for the given id.');
            }

            $this->returnSuccess($data);
        } catch (\Exception $e) {
            \common_Logger::w($e->getMessage());
            $this->returnFailure($e);
        }
    }

    /**
     * Optionnaly a specific rest controller may declare
     * aliases for parameters used for the rest communication
     */
    protected function getParametersAliases()
    {
        return array(
            'user_id' => self::LTI_USER_ID,
            'oauth_consumer_key' => self::LTI_CONSUMER_KEY
        );
    }

    /**
     * Return array of required parameters sorted by http method
     * @return array
     */
    protected function getParametersRequirements()
    {
        return array();
    }
}