<?php

declare(strict_types=1);

namespace NAttreid\PayPal\DI;

use NAttreid\PayPal\Hooks\PayPalConfig;
use Nattreid\PayPal\IPayPalFactory;
use Nattreid\PayPal\PayPal;
use Nette\DI\CompilerExtension;

/**
 * Class AbstractComgateExtension
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class AbstractPayPalExtension extends CompilerExtension
{

	private $defaults = [
		'clientId' => null,
		'secret' => null,
		'experienceProfileId' => null,
		'sdkConfig' => [
			'mode' => 'live',
			'log.Enabled' => true,
			'log.FileName' => '%logDir%/PayPal.log',
			'log.LogLevel' => 'INFO',
			'validation.level' => 'log',
			'cache.enabled' => 'true',
			'cache.FileName' => '%tempDir%/paypal/auth.cache',
			'http.CURLOPT_CONNECTTIMEOUT' => 30
		]
	];

	public function loadConfiguration(): void
	{
		$config = $this->validateConfig($this->defaults, $this->getConfig());
		$builder = $this->getContainerBuilder();

		$payPal = $this->prepareConfig($config);

		$builder->addDefinition($this->prefix('paypal'))
			->setFactory(PayPal::class)
			->setImplement(IPayPalFactory::class)
			->setArguments([$payPal]);
	}

	protected function prepareConfig(array $config)
	{
		$builder = $this->getContainerBuilder();
		return $builder->addDefinition($this->prefix('config'))
			->setFactory(PayPalConfig::class)
			->addSetup('$clientId', [$config['clientId']])
			->addSetup('$secret', [$config['secret']])
			->addSetup('$experienceProfileId', [$config['experienceProfileId']]);
	}
}