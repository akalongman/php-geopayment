# GeoPayment

[![Build Status](https://travis-ci.org/akalongman/php-geopayment.svg?branch=master)](https://travis-ci.org/akalongman/php-geopayment)
[![Latest Stable Version](https://img.shields.io/packagist/v/Longman/geopayment.svg)](https://packagist.org/packages/longman/geopayment)
[![Total Downloads](https://img.shields.io/packagist/dt/Longman/geopayment.svg)](https://packagist.org/packages/longman/geopayment)
[![Downloads Month](https://img.shields.io/packagist/dm/Longman/geopayment.svg)](https://packagist.org/packages/longman/geopayment)
[![License](https://img.shields.io/packagist/l/Longman/geopayment.svg)](LICENSE.md)

Georgian bank/terminal payment integration library

## Table of Contents
- [Installation](#installation)
    - [Composer](#composer)
- [Usage](#usage)
    - [Card Payments](#card-payments) (Visa/MasterCard/AmEX)
        - [BOG](#bog) (Bank of Georgia)
            - [Step 1: Redirecting on payment page](#bog-step-1-redirecting-on-payment-page)
            - [Step 2: Bank checks payment availability](#bog-step-2-bank-checks-payment-availability)
            - [Step 3: Bank registers payment](#bog-step-3-bank-registers-payment)
        - [Cartu](#cartu) (Cartu Bank)
            - [Step 1: Redirecting on payment page](#cartu-step-1-redirecting-on-payment-page)
            - [Step 2: Bank registers payment](#cartu-step-2-bank-registers-payment)
    - [Terminal Payments](#terminal-payments)
        - [TBC Pay](#tbc-pay)
            - [Step 1: Check payment](#tbc-pay-step-1-check-payment)
            - [Step 2: Register Payment](#tbc-pay-step-2-register-payment)
            - [Recommended Response codes](#tbc-pay-recommended-response-codes)
        - [Liberty Pay](#liberty-pay)
            - [Step 1: Check payment](#liberty-pay-step-1-check-payment)
            - [Step 2: Register Payment](#liberty-liberty-step-2-register-payment)
            - [Recommended Response codes](#liberty-pay-recommended-response-codes)

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
        "longman/geopayment": "*"
    }
}
```
And run composer update

**Or** run a command in your command line:

```
composer require longman/geopayment
```

***

[(Back to top)](#table-of-contents)


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
$payment = new Payment('{provider}', '{type}', $options);

// do more job depending on bank documentation

```

**Important:** If your .config file is under server document_root, you must deny access to that file via http.

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

In server section:
```
location ~ /\. {
    deny all;
    access_log off;
    log_not_found off;
}
```

### Card Payments

#### Bog

BOG config example you can find here [.bog.example](examples/.bog.example)

##### Bog Step 1: Redirecting on payment page

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.bog',
];

// Create payment instance
$payment = new Payment('bog', Payment::TYPE_CARD, $options);

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

##### Bog Step 2: Bank checks payment availability

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.bog',
];

$payment = new Payment('bog', Payment::TYPE_CARD, $options);

// Set mode 'check'
$payment->setMode('check');

// Check IP (if needed)
$payment->checkIpAllowed();

// Check HTTP authorization
$payment->checkHttpAuth();

// Check signature validation (depends on documentation)
$payment->checkSignature();

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

$payment->sendSuccessResponse($params);

```


##### Bog Step 3: Bank registers payment

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.bog',
];

$payment = new Payment('bog', Payment::TYPE_CARD, $options);

// Set mode 'reg'
$payment->setMode('reg');

// Check IP (if needed)
$payment->checkIpAllowed();

// Check HTTP authorization
$payment->checkHttpAuth();

// Check signature validation (depends on documentation)
$payment->checkSignature();

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
    $payment->sendErrorResponse('result_code is empty!');
}

// Register payment with result code (1 - success, 2 - failed)
. . .

// Send response
$payment->sendSuccessResponse();

```

***

[(Back to top)](#table-of-contents)



#### Cartu

Cartu config example you can find here [.cartu.example](examples/.cartu.example)

##### Cartu Step 1: Redirecting on payment page

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.cartu',
];

// Create payment instance
$payment = new Payment('cartu', Payment::TYPE_CARD, $options);

// Set mode 'redirect'
$payment->setMode('redirect');

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

##### Cartu Step 2: Bank registers payment

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.cartu',
];

$payment = new Payment('cartu', Payment::TYPE_CARD, $options);

$payment->setMode('response');

// Check IP (if needed)
$payment->checkIpAllowed();

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

        $payment->sendErrorResponse('Transaction failed');
        break;

    case 'U': // unfinished

        $payment->sendErrorResponse('Unfinished request');

        break;

    default:
        // throw error
        $payment->sendErrorResponse('Status unspecified');

        break;
}


```

***

[(Back to top)](#table-of-contents)



### Terminal Payments


#### TBC Pay

TBC Pay config example you can find here [.tbcpay.example](examples/.tbcpay.example)

##### TBC Pay Step 1: Check payment

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.tbcpay',
];

// Create payment instance
$payment = new Payment('tbcpay', Payment::TYPE_PAY, $options);

// Set mode 'check'
$payment->setMode('check');

// Check IP (if needed)
$payment->checkIpAllowed();

// Check HTTP authorization
$payment->checkHttpAuth();

// Get account identifier from request
$account = $payment->getParam('account');
if (empty($account)) {
    // Pass response code and message
    $payment->sendErrorResponse(4, 'Invalid Account Number Format');
}

// Check account id in db and show error if needed
. . .

// Generate some extra data for response. You can add more parameters if needed
$extra = [];
$extra['customer'] = 'John Doe';
$extra['debt'] = '500.00';

$payment->sendSuccessResponse($extra);

```

##### TBC Pay Step 2: Register Payment

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Longman\GeoPayment\Payment;

$options = [
    'config_path' => '/path/to/config/.tbcpay',
];

// Create payment instance
$payment = new Payment('tbcpay', Payment::TYPE_PAY, $options);

// Set mode 'reg'
$payment->setMode('reg');

// Check IP (if needed)
$payment->checkIpAllowed();

// Check HTTP authorization
$payment->checkHttpAuth();

// Get account identifier from request
$account = $payment->getParam('account');
if (empty($account)) {
    $payment->sendErrorResponse(4, 'Invalid Account Number Format');
}

// Check account id in db and show error if needed
. . .

// Get transaction id
$txn_id = $payment->getParam('txn_id');
if (empty($txn_id)) {
    $payment->sendErrorResponse(300, 'txn_id is not defined');
}

// Check transaction id in db and show error if needed
. . .

// Get payd amount
$sum = $payment->getParam('sum');
if (empty($sum)) {
    $payment->sendErrorResponse(300, 'sum is not defined');
}

$payment->sendSuccessResponse();


```

##### TBC Pay: Recommended Response codes

| Code | Message |
|:----:|:-------:|
| 0    | Success |
| 1    | Server timeout |
| 4    | Invalid account format |
| 5    | Account not found |
| 7    | Payment is restricted |
| 215  | Duplicate transaction |
| 275  | Invalid amount |
| 300  | Internal server error |


***

[(Back to top)](#table-of-contents)


#### Liberty Pay

##### Liberty Pay Step 1: Check payment

TBD

##### Liberty Pay Step 2: Register Payment

TBD

##### Liberty Pay: Recommended Response codes

| Code | Message |
|:----:|:-------:|
| 0    | Success |
| 1    | Server timeout |
| 4    | Invalid account format |
| 5    | Account not found |
| 7    | Payment is restricted |
| 215  | Duplicate transaction |
| 275  | Invalid amount |
| 300  | Internal server error |


***

[(Back to top)](#table-of-contents)




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