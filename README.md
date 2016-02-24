# GeoPayment

[![Build Status](https://travis-ci.org/akalongman/php-geopayment.svg?branch=master)](https://travis-ci.org/akalongman/php-geopayment)
[![Latest Stable Version](https://img.shields.io/packagist/v/Longman/geopayment.svg)](https://packagist.org/packages/longman/geopayment)
[![Total Downloads](https://img.shields.io/packagist/dt/Longman/geopayment.svg)](https://packagist.org/packages/longman/geopayment)
[![Downloads Month](https://img.shields.io/packagist/dm/Longman/geopayment.svg)](https://packagist.org/packages/longman/geopayment)
[![License](https://img.shields.io/packagist/l/Longman/geopayment.svg)](https://github.com/akalongman/php-geopayment/LICENSE.md)

Georgian banks payment integration library

## Table of Contents
- [Installation](#installation)
    - [Composer](#composer)
- [Usage](#usage)
    - [BOG](#bog) Bank of Georgia
        - [Step 1: Redirecting on payment page](#bog-step-1-redirecting-on-payment-page)
        - [Step 2: Bank checks payment availability](#bog-step-2-bank-checks-payment-availability)
        - [Step 3: Bank registers payment](#bog-step-3-bank-registers-payment)
    - [Cartu](#cartu) Cartu Bank
        - [Step 1: Redirecting on payment page](#cartu-step-1-redirecting-on-payment-page)
        - [Step 2: Bank registers payment](#cartu-step-2-bank-registers-payment)
- [TODO](#todo)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)


## Installation

### Composer

Install this package through [Composer](https://getcomposer.org/).

Edit your project's `composer.json` file to require `longman/geopayment`

Create *composer.json* file:
```js
{
    "name": "yourproject/yourproject",
    "type": "project",
    "require": {
        "longman/geopayment": "~1.0.0"
    }
}
```
And run composer update

**Or** run a command in your command line:

```
composer require longman/geopayment
```

## Usage

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

// You can specify all options here
$options = [
    'option1' => 'value1',
    'option2' => 'value2',
    'option3' => 'value3',
    . . .
];

// or create .configfile file and specify file path in options
$options = [
    'config_path' => '/path/to/folder/.configfile',
];

// Create payment instance
$payment = new Payment('{provider}', $options);

// do more job depending on bank documentation

```

**Important:** If your .configfile file is under server document_root, you must deny access to that file via http.

Apache

Add in your .htaccess file:
```
<Files ~ "^\.(.*)$">
    Order allow,deny
    Deny from all
    Satisfy all
</Files>
```

Nginx

TBD

### Bog

BOG config example you can find here [.bog.example](https://github.com/akalongman/php-geopayment/.bog.example)

#### Bog Step 1: Redirecting on payment page

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.bog',
];

// Create payment instance
$payment = new Payment('bog', $options);

// Set mode 'redirect'
$bog->setMode('redirect');

// Set success url
$payment->setSuccessUrl('your_success_url');

// Set fail url
$payment->setFailUrl('your_fail_url');

// Set order id or any payment identificator in your db
$payment->addParam('order_id', 'your_order_id');

// You can add more params if needed
$payment->addParam('param1', 'value1');
$payment->addParam('param2', 'value2');

// And simple redirect
$payment->redirect();

// Or get payment initialization url if needed and after redirect
$url = $payment->getPaymentUrl();
. . .
$payment->redirect($url);
```

#### Bog Step 2: Bank checks payment availability

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.bog',
];

$payment = new Payment('bog', $options);

// Set mode 'check'
$bog->setMode('check');

// Check HTTP authorization
$bog->checkHttpAuth();

// Check signature validation (depends on documentation)
$bog->checkSignature();

// Here you must check order_id or any other parameters which before redirecting set via $payment->addParam
$order_id = $payment->getParam('o.order_id');
if (!$order_id) {
    $payment->sendErrorResponse('order_id is empty!');
}

// check if order exists
$order = get_order_from_yout_db($order_id);
if (empty($order)) {
    $payment->sendErrorResponse('order with id "'.$order_id.'" not found!');
}

// check if order already completed
if ($order->isCompleted()) {
    $payment->sendErrorResponse('Purchase for order "'.$order_id.'" already completed!');
}
. . .

// Build parameters for response
$params = [];
$params['amount'] = 'Order price (In minor units)';
$params['short_desc'] = 'Payment short description';
$params['long_desc'] = 'Payment long description';

$bog->sendSuccessResponse($params);

```


#### Bog Step 3: Bank registers payment

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.bog',
];

$payment = new Payment('bog', $options);

// Set mode 'reg'
$bog->setMode('reg');

// Check HTTP authorization
$bog->checkHttpAuth();

// Check signature validation (depends on documentation)
$bog->checkSignature();

// Here you must check order_id or any other parameters which before redirecting set via $payment->addParam
$order_id = $payment->getParam('o.order_id');
if (!$order_id) {
    $payment->sendErrorResponse('order_id is empty!');
}

// check if order exists
$order = get_order_from_yout_db($order_id);
if (empty($order)) {
    $payment->sendErrorResponse('order with id "'.$order_id.'" not found!');
}

// check if order already completed
if ($order->isCompleted()) {
    $payment->sendErrorResponse('Purchase for order "'.$order_id.'" already completed!');
}

// Get and check payment result code
$result_code = $payment->getParam('result_code');
if (empty($result_code)) {
    $bog->sendErrorResponse('result_code is empty!');
}

// Register payment with result code (1 - success, 2 - failed)
. . .

// Send response
$bog->sendSuccessResponse();

```


### Cartu

Cartu config example you can find here [.cartu.example](https://github.com/akalongman/php-geopayment/.cartu.example)

#### Cartu Step 1: Redirecting on payment page

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.cartu',
];

// Create payment instance
$payment = new Payment('cartu', $options);

// Set mode 'redirect'
$bog->setMode('redirect');

// generate order id
$order_id = '1111111';

// prepare parameters for redirect
$purchase_desc = $order_id.'!<product name>!<product quantity>!<etc>';
$purchase_amt = '20.25';

$payment->addParam('PurchaseDesc', $purchase_desc);
$payment->addParam('PurchaseAmt', $purchase_amt);

// And simple redirect
$payment->redirect();

// Or get payment initialization url if needed and after redirect
$url = $payment->getPaymentUrl();
. . .
$payment->redirect($url);
```

#### Cartu Step 2: Bank registers payment

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.cartu',
];

$payment = new Payment('cartu', $options);

$payment->setMode('response');

// get bank parameters
$TransactionId = $payment->getTransactionId();
$PaymentId = $payment->getPaymentId();
$PaymentDate = $payment->getPaymentDate();
$Amount = $payment->getAmount();
$CardType = $payment->getCardType();
$Reason = $payment->getReason();
$Status = $payment->getStatus();

switch($Status) {
    case 'C': // check
        // check order availability by TransactionId

        $payment->sendSuccessResponse($params);
        break;

    case 'Y': // success
        // update order status by TransactionId

        $payment->sendSuccessResponse($params);
        break;

    case 'N': // failed
        // set order status to failed

        $bog->sendErrorResponse('Transaction failed');
        break;

    case 'U': // unfinished

        $bog->sendErrorResponse('Unfinished request');

        break;

    default:
        // throw error
        $bog->sendErrorResponse('Status unspecified');

        break;
}


```

## TODO

Add more providers and write more tests

## Troubleshooting

If you like living on the edge, please report any bugs you find on the
[PHP GeoPayment issues](https://github.com/akalongman/php-geopayment/issues) page.

## Contributing

Pull requests are welcome.
See [CONTRIBUTING.md](CONTRIBUTING.md) for information.

## License

Please see the [LICENSE](LICENSE.md) included in this repository for a full copy of the MIT license,
which this project is licensed under.

## Credits

- [Avtandil Kikabidze aka LONGMAN](https://github.com/akalongman)

Full credit list in [CREDITS](CREDITS)