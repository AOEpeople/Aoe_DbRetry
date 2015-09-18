[![AOE](aoe-logo.png)](http://www.aoe.com)

# Aoe_DbRetry Magento Module [![Build Status](https://travis-ci.org/AOEpeople/Aoe_DbRetry.svg?branch=master)](https://travis-ci.org/AOEpeople/Aoe_DbRetry)

## License
[OSL v3.0](http://opensource.org/licenses/OSL-3.0)

## Contributors
* [Lee Saferite](https://github.com/LeeSaferite) (AOE)

## Compatability
* Module Dependencies
    * Mage_Core (implicit)

## Description
This module is very simple and focus on one task.
It replaces the DB adapter with an extended version that will retry queries if the connection is lost, the query cannot obtain a needed lock, or a deadlock occours.
These three situations are detected via exception messages.
The underlying (parent) code actually wraps at least one of these exceptions up inside another exception, so we check for that and unwrap the exception if needed.

## Configuration
* </app/etc/local.xml>/config/global/resources/{connection name}/connection/retries
    * {connection_name} is referring to the named connection, like 'default_setup' which is the default connection
    * Integer number between 0 and 5 that indicates how many times to retry the query
    * Default value is 5 retries
* </app/etc/local.xml>/config/global/resources/{connection name}/connection/retry_power
    * {connection_name} is referring to the named connection, like 'default_setup' which is the default connection
    * Integer number between 1 and 5 that indicate the power of the exponential backoff feature
    * Default value is 2
    * Used in: {sleep seconds} = pow({try}, {retry_power})

## NOTES
* This module is currently being written for PHP 5.4+ and Magento CE 1.8+ support only.
* When PHP 5.4 hits EOL, the minimum requirements will be updated to reflect this.
