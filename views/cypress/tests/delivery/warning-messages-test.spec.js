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
 * Copyright (c) 2021 Open Assessment Technologies SA ;
 */

/**
 * Note: this test requires the following:
 * - the extension `oat-sa/extension-tao-ltideliveryprovider` is installed
 * - the test `taoQtiTest/views/cypress/fixtures/testPackages/warning-messages-test.zip` (extension `oat-sa/extension-tao-testqti`)
 * - the test is published
 * - a LTI provider is created
 * - the LTI credentials are supplied through the cypress env file from the properties ltiKey and ltiSecret
 * - the LTI launch key is supplied through the cypress env file from the property ltiDeliveryIds.warningMessagesTest
 */

import { launchLtiDelivery1p0, checkLtiReturnPage } from '../utils/lti.js';
import {
    warningMessagesFirstLaunchSpecs,
    warningMessagesSecondLaunchSpecs
} from '../../../../../taoQtiTest/views/cypress/tests/delivery/shared/warning-messages-test.js';

describe('Test warning messages', () => {
    const deliveryKey = 'warningMessagesTest';

    describe('Test warning messages (part 1)', () => {
        before(() => {
            launchLtiDelivery1p0(deliveryKey);
        });
        after(() => {
            checkLtiReturnPage();
        });

        warningMessagesFirstLaunchSpecs();
    });

    describe('Test warning messages (part 2)', () => {
        before(() => {
            launchLtiDelivery1p0(deliveryKey);
        });
        after(() => {
            checkLtiReturnPage();
        });

        warningMessagesSecondLaunchSpecs();
    });
});
