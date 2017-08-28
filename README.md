# Faspay Payment Gateway Package for Laravel 5

Use Faspay as your payment gateway for your project? Then this package is for you.
This is a laravel package to communicate with [Faspay Payment Gateway API](https://mediaindonusa.com)  (currently only support DEBIT API)

## Installation

To get started with Faspay, run this command or add the package to your `composer.json`

    composer require krisnasw/faspay

## Configuration

After installing the Faspay package, register the `Krisnasw\Faspay\FaspayServiceProvider` in your `config/app.php` file.
Also, add the `Faspay` and `Payment` facade to the `aliases` array in your `app` configuration file:
```php
'Faspay' => Krisnasw\Faspay\Facades\Faspay::class,
'Payment' => Krisnasw\Faspay\Facades\Payment::class,
```

Finally publish the config file:

    php artisan vendor:publish --provider="Krisnasw\Faspay\FaspayServiceProvider"

and change `merchant_id`, `merchant_name`, `user_id`, and `password` in the `config/faspay.php` with yours.

## How To Use

After all sets, use this Faspay package as follows:

```php
// Customer class example. You can apply to any model you want.

use Krisnasw\Faspay\CustomerInterface;

class Customer implements CustomerInterface
{
  public function getFaspayCustomerNumber()
  {
    return 'customer-number';
  }

  public function getFaspayCustomerName()
  {
    return 'customer-name';
  }

  public function getFaspayCustomerEmail()
  {
    return 'customer-email';
  }
  
  public function getFaspayCustomerPhone()
  {
    return 'customer-phone';
  }

  public function getFaspayPreferredCurrency()
  {
    return 'customer-currency';
  }
}
```

```php
// Item class example. You can apply to any model you want.

use Krisnasw\Faspay\Payable;

class Item implements Payable
{
  public function getPayableName()
  {
    return 'Product Name';
  }

  public function getPayablePrice()
  {
    return 300000;
  }
}
```

```php
// An example how to use the API.

Route::get('/', function () {

  $customer = new Customer();
  $payable = new Item();

  $payment = Payment::performedBy($customer)
    ->via('web')
    ->payWith('tcash')
    ->addTax(10)
    ->addMiscFee(1000);

  $payment->addItem($payable, 2);

  $response = Faspay::registerPayment($payment);

  return Faspay::redirectToPay($payment);
});

Route::get('/callback-notif', function(\Illuminate\Http\Request $request) {
  return Faspay::notified($request, function(\Krisnasw\Faspay\Notification $notification) {
    return $notification;
  });
});
```

To generate a custom billing number/code, you can create a class that implements BillingProfileInterface, for example:

```php
class TopupBillingProfile implements BillingProfileInterface
{
  public function description()
  {
    return 'Topup Saldo'; 
  }

  public function generate(Payment $payment)
  {
    return str_random(15);
  }
}
```

and then pass it as a second argument of `registerPayment()` method.

## Bugs & Improvements

This package is far from perfect.
It doesn't support BCA KlikPay yet.
It doesn't support Faspay Credit API also.
Feel free to report me any bug you found or send me pull requests.