# extension-lti-deliveryprovider
The LTI Delivery extension allows test-takers to take a delivery (delivered test) via LTI

The endpoint for this service to proctor a specific delivery is:
`https://YOUR_DOMAIN/ltiDeliveryProvider/DeliveryTool/launch?delivery=YOUR_DELIVERY_URI`

or

`https://YOUR_DOMAIN/ltiDeliveryProvider/DeliveryTool/{"delivery":"YOUR_URI"}(base64 encoded)`

This link can retrieved using the LTI button in the deliveries section in the TAO admin user interface.


Alternatively a configurable link can be used by omitting the delivery parameter
`https://YOUR_DOMAIN/ltiDeliveryProvider/DeliveryTool/launch`

In this scenario the instructor would need to call the LTI service first, and will be presented with a list of deliveries.
Once he has chosen one of these deliveries it can no longer be changed. Test-takers subsequently clicking on the same link (as identified by Resource ID) will
start the delivery chosen by the instructor.

The expected roles are:
* `Learner` for people taking a test
* `Instructor` for people configuring a link

Custom parameters:
* `max_attempts` Overrides the number of executions allowed on the delivery. Expects a positive integer value or 0 for unlimited attempts. Attempts on LTI calls are calculated per `resource_link_id` instead of per delivery.

Return Values:
* `log message` will contain the status of the delivery execution
  * **100** for an active delivery
  * **101** for a paused delivery
  * **200** for a finished delivery
  * **201** for a terminated delivery

# Configuration options:

## assignment.conf.php
No options

## LaunchQueue.conf.php

### Configuration option "relaunchInterval"

*Description:* specifies time (in seconds) for a test taker to wait before the page is reloaded when waiting in LTI queue

*Possible values:*
* Any numerical value (> 0)

### Configuration option "relaunchIntervalDeviation"

*Description:* specifies time (in seconds) to pick a random amount of seconds between 0 and relaunchIntervalDeviation, then the random result is randomly added to or subtracted from relaunchInterval for each time the queue page is being reloaded. 
The goal of this option is to prevent knocking the backend simultaneously by multiple clients. 

*Possible values:*
* Any numerical value between 0 and relaunchInterval

## LtiDeliveryExecution.conf.php

### Configuration option "queue_persistence"

*Description:* a persistence that LTI delivery execution service should work based on. Should be a persistence name that's registered in generis/persistences.conf.php

*Value example:* 
* default
* cache

## LtiLaunchData.conf.php
No options

## LtiNavigation.conf.php
### Configuration option "thankyouScreen"

*Description:* whether to 'thank you' screen should be shown once a test is passed through LTI.
 It only takes effect if the 'custom_skip_thankyou' LTI parameter is omitted. Otherwise, it's only depends on the LTI parameter.

*Possible values:* 
* true
* false

### Configuration option "delivery_return_status"

*Description:* if enabled, the 'deliveryExecutionStatus' return parameter will be included in a consumer return URL.
This parameter will always be set to a delivery execution state label.

*Possible values:* 
* true: include the parameter in consumer return URLs
* false: omit the parameter

### Configuration option "message"

*Description:* a factory for producing LTI messages

*Possible values:* 
* an instance of any class that have the 'getLtiMessage' method

*Value example:* 
* new oat\ltiDeliveryProvider\model\navigation\DefaultMessageFactory()

## LtiOutcome.conf.php
No options

## LtiResultIdStorage.conf.php
### Configuration option "persistence"
*Description:* a persistence that LTI result aliases should be stored in. Should be a persistence name that's registered in generis/persistences.conf.php

*Value example:* 
* default
* cache