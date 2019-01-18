<?php

namespace Nattreid\PayPal\Control;

use NAttreid\PayPal\Helpers\Exceptions\PayPalException;
use Nette\Application\UI\Control;

/**
 * Class PayPalControl
 *
 * @author Attreid <attreid@gmail.com>
 */
class PayPalControl extends Control
{
	public $onCheckout = [];
	public $onSuccess = [];
	public $onCancel = [];
	public $onError = [];

	/** @var PayPalClient */
	private $client;

	public function __construct(PayPalClient $client)
	{
		parent::__construct();
		$this->client = $client;
	}

	public function setCurrency(string $currency): void
	{
		$this->client->setCurrency($currency);
	}

	protected function setShipping(float $shipping): void
	{
		$this->client->setShipping($shipping);
	}

	protected function setTax(float $tax): void
	{
		$this->client->setTax($tax);
	}

	public function addItem(string $name, int $quantity, float $price): void
	{
		$this->client->addItem($name, $quantity, $price);
	}

	public function handleCheckout(): void
	{
		try {
			$this->client
				->setCancelUrl($this->link('//cancel!'))
				->setReturnUrl($this->link('//return!'));

			$payment = $this->client->createPayment();

			$this->onCheckout($payment);
			$this->presenter->redirectUrl($payment->getApprovalLink());
		} catch (PayPalException $ex) {
			$this->callError($ex);
		}
	}

	public function handleReturn(): void
	{
		$paymentId = $this->presenter->getParameter('paymentId');
		$payerId = $this->presenter->getParameter('PayerID');

		try {
			$payment = $this->client->paymentReturn($paymentId, $payerId);
			$this->onSuccess($payment);
		} catch (PayPalException $ex) {
			$this->callError($ex);
		}
	}

	public function handleCancel()
	{
		$this->onCancel();
	}

	private function callError(PayPalException $ex)
	{
		if (!$this->onError) {
			throw $ex;
		}
		$this->onError($ex);
	}

	public function render($attrs = array(), $text = "Pay")
	{
		$template = $this->template;
		$template->setFile(__DIR__ . '/templates/default.latte');
		$template->text = $text;
		$template->attrs = $attrs;
		$template->render();
	}
}

interface IPayPalControlFactory
{
	public function create(): PayPalControl;
}
