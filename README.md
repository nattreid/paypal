# PayPal pro Nette Framework
Nastavení v **config.neon**
```neon
extensions:
    payPal: NAttreid\PayPal\DI\PayPalExtension

payPal:
	clientId: xxxXXXxXXXXxxxx
	secret: xxxXXXxXXXXxxxx
	experienceProfileId: xxxXXXxXXXXxxxx
	sdkConfig:
		mode: sandbox
		log.Enabled: true
		log.FileName: '%logDir%/PayPal.log'
		log.LogLevel: DEBUG
		validation.level: log
		cache.enabled: true
		cache.FileName: '%tempDir%/paypal/auth.cache'
		http.CURLOPT_CONNECTTIMEOUT: 30
```

sdkConfig je nastavení [paypal/PayPal-PHP-SDK](https://github.com/paypal/PayPal-PHP-SDK), [sdk-config-sample](https://github.com/paypal/PayPal-PHP-SDK/blob/master/sample/sdk_config.ini)


### Použití
```php
/** @var \Nattreid\PayPal\Control\IPayPalControlFactory @inject */
public $payPalControlFactory;

/** @var \Nattreid\PayPal\PayPalClient @inject */
public $payPalClient;

protected function createComponentButton(): \Nattreid\PayPal\Control\PayPalControl
{
    $control = $this->payPalControlFactory->create();
    $control->setCurrency('CZK');

    foreach ($this->order->items as $item) {
        $control->addItem(
            $item->name,
            $item->count,
            $item->price
        );
    }

    $discount = $this->order->discount;
    if ($discount) {
        $control->addItem(
            'Discount',
            1,
            -$discount
        );
    }

    $control->onSuccess[] = function (Payment $paid, Sale $sale, bool $pending) {
        $this->order->paypalTransactionId = $sale->getId();

        if ($pending) {
            $this->order->setState(Pending::class);
        } else {
            $this->order->setState(Payed::class);
        }
        $this->redirect($this->link('success'));
    };

    $control->onError[] = function (PayPalException $exception) {
        Debugger::log($exception->getMessage(), 'paypal');
        $this->redirect($this->link('error'));
    };

    $control->onCancel[] = function () {
        $this->order->setState(Cancel::class);
        $this->redirect($this->link('cancel'));
    };
    
    return $control;
}

public function paypalCheckPayments(): void
{
    foreach ($this->orders as $order) {
        if ($order->paypalTransactionId !== null) {
            try {
                $status = $this->payPalClient->checkPayment($order->paypalTransactionId);

                if ($status === true) {
                    $order->setState(Payed::class);
                } elseif ($status === false) {
                    $order->setState(Cancel::class);
                }
            } catch (PayPalException $ex) {

            }
        }
    }
}
```
