<?php
declare(strict_types=1);

namespace NAttreid\PayPal\DI;

use NAttreid\Cms\Configurator\Configurator;
use NAttreid\Cms\DI\ExtensionTranslatorTrait;
use NAttreid\PayPal\Hooks\PayPalConfig;
use NAttreid\PayPal\Hooks\PayPalHook;
use NAttreid\WebManager\Services\Hooks\HookService;
use Nette\DI\Statement;

if (trait_exists('NAttreid\Cms\DI\ExtensionTranslatorTrait')) {
	class PayPalExtension extends AbstractPayPalExtension
	{
		use ExtensionTranslatorTrait;

		protected function prepareConfig(array $payPal)
		{
			$builder = $this->getContainerBuilder();
			$hook = $builder->getByType(HookService::class);
			if ($hook) {
				$builder->addDefinition($this->prefix('hook'))
					->setType(PayPalHook::class);

				$this->setTranslation(__DIR__ . '/../lang/', [
					'webManager'
				]);

				return new Statement('?->payPal \?: new ' . PayPalConfig::class, ['@' . Configurator::class]);
			} else {
				return parent::prepareConfig($payPal);
			}
		}
	}
} else {
	class PayPalExtension extends AbstractPayPalExtension
	{
	}
}