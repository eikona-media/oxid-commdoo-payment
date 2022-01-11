# Changelog

## v1.2.0
### Modifications
- Changed the behaviour of the plugin to intersect the finalizeOrder()-process
- Scrapped Notification functionality and based everything on the session
- Scrapped BackURL due to missing information in it.
- Enabled automatic creation of payments
- Renamed classes to match with namespace scheme

## v1.1.4
### Bugfixes
- Fixed failed deletion of order

## v1.1.3
### Modifications
- Added "backURL" to redirect onto "failURL"

## v1.1.2
### Modifications
- Changed transaction status "ERROR" to "STOP"

## v1.1.1
### Modifications
- Reinstalled deleting orders after any error
- Calling _getNextStep() directly if there is an error instead of iterating through the payments

### Bugfixes
- Renamed "iSuccess" to "payerror" in debug output

## v1.1.0
### Modifications
- Added a parameter `paymentmode` with value `reservation` that needs to be removed after testing
- Added a separate group to the internal config to distinguish between successful and failed response
- Added two new classes `FailedResponse` and `SuccessResponse` to handle different responses
- Added hash-checks to `processCommDooFailed()` 
- Added a new function `error` in `PaymentController` to ease the process of logging on the error-level
- Removed deleting of the order anywhere
- Modified transaction status `Confirmation Pending` to `NOT_FINISHED` and `Payment Confirmed` to `OK` to be more inline with OXID standards
- Modified `README` to include the configuration of new payments

### Bugfixes
- Modified `debug()`-function to only write on debug-level
- Modified `_getNextStep()` to work at `iSuccess==1`, since this is the correct status code for `OK` (rather than `0` before)
- Removed `response` parameter from `commdoo_failed`, since it is not used

