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
