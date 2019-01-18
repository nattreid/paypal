<?php

declare(strict_types=1);

namespace NAttreid\PayPal\Hooks;

use NAttreid\Form\Form;
use NAttreid\WebManager\Services\Hooks\HookFactory;
use Nette\ComponentModel\Component;
use Nette\Utils\ArrayHash;

/**
 * Class PayPalHook
 *
 * @author Attreid <attreid@gmail.com>
 */
class PayPalHook extends HookFactory
{

	/** @var IConfigurator */
	protected $configurator;

	public function init(): void
	{
		if (!$this->configurator->payPal) {
			$this->configurator->payPal = new PayPalConfig;
		}
	}

	/** @return Component */
	public function create(): Component
	{
		$form = $this->formFactory->create();
		$form->setAjaxRequest();

		$form->addText('clientId', 'webManager.web.hooks.payPal.clientId')
			->setDefaultValue($this->configurator->payPal->clientId);
		$form->addText('secret', 'webManager.web.hooks.payPal.clientSecret')
			->setDefaultValue($this->configurator->payPal->secret);
		$form->addText('experienceProfileId', 'webManager.web.hooks.payPal.experienceProfileId')
			->setDefaultValue($this->configurator->payPal->experienceProfileId);

		$form->addSubmit('save', 'form.save');

		$form->onSuccess[] = [$this, 'paypalFormSucceeded'];

		return $form;
	}

	public function paypalFormSucceeded(Form $form, ArrayHash $values): void
	{
		$config = $this->configurator->payPal;

		$config->clientId = $values->clientId ?: null;
		$config->secret = $values->secret ?: null;
		$config->experienceProfileId = $values->experienceProfileId ?: null;

		$this->configurator->payPal = $config;

		$this->flashNotifier->success('default.dataSaved');
	}
}