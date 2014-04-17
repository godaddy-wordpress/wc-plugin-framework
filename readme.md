# WooCommerce Plugin Framework

This is a SkyVerge library module: a full featured WooCommerce Plugin Framework

## Known Issues

### Subscriptions Authorize-only Renewal

Subscription renewals placed with the gateway configured to "authorize" only will be marked as processing/completed by WooCommerce Subscriptions, rather than on-hold as when typically placing an authorize-only transaction.  Doesn't appear to be much we can do about it as this is built into WooCommerce Subscriptions.
