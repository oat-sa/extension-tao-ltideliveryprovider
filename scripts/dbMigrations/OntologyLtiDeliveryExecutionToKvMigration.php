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
 */

namespace oat\ltiDeliveryProvider\scripts\dbMigrations;

use oat\generis\model\OntologyAwareTrait;
use oat\ltiDeliveryProvider\model\execution\implementation\KvLtiDeliveryExecutionService;
use oat\ltiDeliveryProvider\model\execution\implementation\OntologyLTIDeliveryExecutionLink;
use oat\oatbox\extension\script\ScriptAction;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\ltiDeliveryProvider\model\execution\implementation\LtiDeliveryExecutionService as OntologyDeliveryExecutionService;
use oat\oatbox\log\LoggerAggregator;
use oat\oatbox\log\VerboseLoggerFactory;

/**
 * Class MigrateLinksPersistence
 *
 * Script to migrate Ontology Lti links from ontology to key value persistence
 * - Must have `--kv-persistence` option as KV persistence destination of migration
 * - Overrides the LtiDeliveryExecutionService config with KeyValue implementation if `--no-migrate-service` is not set
 * - Delete Ontology data after migration if `--no-delete` is not set
 *
 * @package oat\ltiDeliveryProvider\scripts\dbMigrations
 */
class OntologyLtiDeliveryExecutionToKvMigration extends ScriptAction
{
    use OntologyAwareTrait;

    /**
     * Run the migration
     */
    protected function run()
    {
        try {
            $this->setVerbosity();

            /** @var LtiDeliveryExecutionService  $ltiDeliveryExecution */
            $ltiDeliveryExecution = $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID);

            if (!$ltiDeliveryExecution instanceof OntologyDeliveryExecutionService) {
                return new \common_report_Report(\common_report_Report::TYPE_ERROR, ' LtiDeliveryExecution migration must be done on a Ontology Service e.q. LtiDeliveryExecutionService.');
            }

            $kvDeliveryExecutionService = new KvLtiDeliveryExecutionService(array(
                KvLtiDeliveryExecutionService::OPTION_PERSISTENCE => $this->getKeyValuePersistenceName()
            ));
            if ($this->getOption('no-migrate-service') !== true) {
                $this->registerService(LtiDeliveryExecutionService::SERVICE_ID, $kvDeliveryExecutionService);
                $this->logNotice('LtiDeliveryExecution service was set to KeyValue implementation.');
            }

            $class = $this->getClass(OntologyLTIDeliveryExecutionLink::CLASS_LTI_DELIVERYEXECUTION_LINK);
            $iterator = new \core_kernel_classes_ResourceIterator($class);
            $i = 0;
            foreach ($iterator as $instance) {

                $properties = $instance->getPropertiesValues(array(
                    OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_USER,
                    OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_LINK,
                    OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID,
                ));

                $user = $this->getPropertyValue($properties, OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_USER);
                $link = $this->getPropertyValue($properties, OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_LINK);
                $deliveryExecution = $this->getPropertyValue($properties, OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID);

                if ($kvDeliveryExecutionService->createDeliveryExecutionLink($user, $link, $deliveryExecution)) {
                    if ($this->getOption('no-delete') !== true) {
                        $instance->delete();
                        $this->logInfo('LtiDeliveryExecution "' . $instance->getUri() .'" deleted from ontology storage.');
                    }
                    $this->logNotice('LtiDeliveryExecution "' . $instance->getUri() .'" successfully migrated.');
                    $i++;
                } else {
                    $this->logError('LtiDeliveryExecution "' . $instance->getUri() .'" cannot be migrated.');
                }
            }
            $this->logNotice('LtiDeliveryExecution migrated: ' . $i);
        } catch (\Exception $e) {
            return \common_report_Report::createFailure('LtiDeliveryExecution migration has failed with error message : ' . $e->getMessage());

        }

        return \common_report_Report::createSuccess('LtiDeliveryExecution successfully has been migrated from Ontology to KV value. Count of LtiDeliveryExecution migrated: ' . $i);
    }


    /**
     * Extract a property value from $properties array
     *
     * @param array $properties
     * @param $propertyName
     * @return string
     */
    protected function getPropertyValue(array $properties, $propertyName)
    {
        if (!isset($properties[$propertyName])) {
            return null;
        }
        $value = reset($properties[$propertyName]);
        return $value instanceof \core_kernel_classes_Resource ? $value->getUri() : (string) $value;
    }

    /**
     * Get the persistence name from option
     *
     * @return string
     * @throws \common_Exception
     */
    protected function getKeyValuePersistenceName()
    {
        $this->getKeyValuePersistence();
        return $this->getOption('kv-persistence');
    }

    /**
     * Create the persistence from option and validate as KeyValue persistence
     *
     * @return \common_persistence_KeyValuePersistence
     * @throws \common_Exception
     */
    protected function getKeyValuePersistence()
    {
        $persistenceName = $this->getOption('kv-persistence');
        /** @var \common_persistence_Manager $persistenceManager */
        $persistenceManager = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID);
        $persistence = $persistenceManager->getPersistenceById($persistenceName);
        if (!$persistence instanceof \common_persistence_KeyValuePersistence) {
            throw new \common_Exception('Given persistence is not a key value');
        }
        return $persistence;
    }

    /**
     * If verbose option is set, set the appropriate logger
     */
    protected function setVerbosity()
    {
        if ($this->getOption('verbose') === true) {
            $verboseLogger = VerboseLoggerFactory::getInstance(['-nc', '-vv']);
            $this->setLogger(new LoggerAggregator(array(
                $this->getLogger(),
                $verboseLogger
            )));
        }
    }

    /**
     * Provides option of script
     *
     * @return array
     */
    protected function provideOptions()
    {
        return [
            'kv-persistence' => array(
                'prefix' => 'kv',
                'longPrefix' => 'kv-persistence',
                'required' => true,
                'description' => 'The KeyValue persistence where you want to migrate to.',
            ),
            'no-migrate-service' => array(
                'prefix' => 'nms',
                'longPrefix' => 'no-migrate-service',
                'flag' => true,
                'description' => 'Don\'t migrate the ontology LtiDeliveryExecutionService to KvLtiDeliveryExecutionService.',
            ),
            'no-delete' => array(
                'prefix' => 'nd',
                'longPrefix' => 'no-delete',
                'flag' => true,
                'description' => 'Don\'t delete ontology LtiDeliveryExecution after migration.',
            ),
            'verbose' => array(
                'prefix' => 'v',
                'longPrefix' => 'verbose',
                'flag' => true,
                'description' => 'Output the log as command output.',
            ),
        ];
    }

    /**
     * Provides description of the script
     *
     * @return string
     */
    protected function provideDescription()
    {
        return 'Migration script to migrate Lti Links from Ontology to KeyValue persistence.';
    }

    /**
     * Provides help of this script
     *
     * @return array
     */
    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints the help.'
        ];
    }
}