# SilverStripe Shop Webshipper integration

Adds integration to Webshipper API.

## Installation

composer require "silvershop/webshipper"

After installing the module, rebuild the database and create your first Webshipper shipping method in the `Shipping` model admin

### Shippable
The module adds an extension to product which add a checkbox `IsShippable`. This checkbox decides if the orderItem should be synced to Webshipper. This means that youc an have products in the shop that can be bought but not synced to Webshipper. The standard setting of `IsShippable` is `True`, but can be set to false be extending the specific product class with `DisableIsShippable`.

### Checkout
In your `CheckoutComponentConfig` file you can setup a condition to only show shipping methods if one of the products has `Shippable = true` by encapsling the component like this:

```PHP
if($order->hasShippable()){
    $this->addComponent(\Silvershop\Webshipper\WebshipperShippingCheckoutComponent::create());
}
```

The cart block on the checkoutpage should also be encapsuled in a div with class `ordersummary`, to make sure the order total gets updated when the shipping method changes.

### Cronjobs
To sync orders from Silvershop to Webshipper make sure you setup a cronjob to run `SyncToWebshipper` task and also the `DeleteFromWebshipper` task which deletes orders on Webshipper that has been cancelled by either the user or admin.


