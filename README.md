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

```
