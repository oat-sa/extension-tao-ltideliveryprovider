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
 * - the test `taoQtiTest/views/cypress/fixtures/testPackages/basic-nonlinear-test.zip` (extension `oat-sa/extension-tao-ltideliveryprovider`)
 * - the test is published
 * - a LTI provider is created
 * - the LTI credentials are supplied through the cypress env file from the properties ltiKey and ltiSecret
 * - the LTI launch key is supplied through the cypress env file from the property ltiDeliveryIds.basicNonLinearTest
 */

import { launchLtiDelivery1p0, checkLtiReturnPage } from '../utils/lti.js'
import { basicNonLinearFirstLaunchSpecs, basicNonLinearSecondLaunchSpecs } from '../../../../taoQtiTest/views/cypress/tests/delivery/shared/basic-nonlinear-test.js';

describe('Basic non-linear test navigation (LTI launch)', () => {
    const deliveryKey = 'basicNonLinearTest';

    describe('Next/Previous/End navigation', () => {
        before(() => {
            launchLtiDelivery1p0(deliveryKey);
        });
        after(() => {
            checkLtiReturnPage();
        });

        basicNonLinearFirstLaunchSpecs();
    });

    describe('Skip/Skip-and-end navigation', () => {
        before(() => {
            launchLtiDelivery1p0(deliveryKey);
        });
        after(() => {
            checkLtiReturnPage();
        });

        basicNonLinearSecondLaunchSpecs();
    });
});
