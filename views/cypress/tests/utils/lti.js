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

import { getFullUrl } from '../../../../tao/views/cypress/utils/helpers.js';
import urls from './urls.js';

/**
 * List the default values for some LTI parameters
 * @type {Object}
 */
const ltiDefault = {
    ltiRole: 'Learner',
    ltiLocale: 'en-US',
    ltiBaseLaunchUrl: urls.launch,
    ltiReturnUrl: urls.thankYou,
};

/**
 * Gets the value for a LTI parameter. It will come either from:
 * - the supplied options
 * - the Cypress env config
 * - the ltiDefault
 * @param {String} name - The name of the parameter for which get the value
 * @param {Object} [options] - A list of options in which the value may be defined
 * @returns {*}
 */
export function getLtiParameter(name, options = null) {
    if (options && options[name]) {
        return options[name];
    }
    return Cypress.env(name) || ltiDefault[name];
}

/**
 * Gets the LTI BaseLaunch URL
 * @param {Object} [options] - A list of options in which the value may be defined
 * @returns {String}
 */
export function getLtiBaseLaunchUrl(options = null) {
    return getFullUrl(getLtiParameter('ltiBaseLaunchUrl', options), '/');
}

/**
 * Gets the LTI Return URL
 * @param {Object} [options] - A list of options in which the value may be defined
 * @returns {String}
 */
export function getLtiReturnUrl(options = null) {
    return getFullUrl(getLtiParameter('ltiReturnUrl', options));
}

/**
 * Launches a delivery by LTI 1.1 request, using the method appropriate to the environment
 * @param {String} deliveryKey - the key in the env.deliveryIds object for accessing the deliveryId
 * @param {Object} [options]
 * @param {String} [options.ltiRole] - default is 'Learner'
 * @param {String} [options.ltiLocale] - default comes from global cypress config
 * @param {String} [options.ltiBaseLaunchUrl] - default comes from global cypress config, or from the `urls` collection
 * @param {String} [options.ltiReturnUrl] - default comes from global cypress config, or from the `urls` collection
 */
export function launchLtiDelivery1p0(deliveryKey, options) {
    cy.ltiLaunch({
        ltiVersion: '1p0',
        ltiKey: Cypress.env('ltiKey'),
        ltiSecret: Cypress.env('ltiSecret'),
        ltiResourceId: Cypress.env('ltiDeliveryIds')[deliveryKey],
        ltiBaseLaunchUrl: getLtiBaseLaunchUrl(options),
        ltiReturnUrl: getLtiReturnUrl(options),
        ltiLocale: getLtiParameter('ltiLocale', options),
        ltiRole: getLtiParameter('ltiRole', options)
    });
}

/**
 * Checks the return page at the end of the LTI session
 */
export function checkLtiReturnPage() {
    cy.location().should(location => {
        expect(`${location.origin}${location.pathname}`).to.equal(getLtiReturnUrl());
    });
}
