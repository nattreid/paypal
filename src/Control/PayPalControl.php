<?php

namespace NAttreid\PayPal\Control;

use NAttreid\PayPal\Helpers\Exceptions\PayPalException;
use NAttreid\PayPal\Helpers\Transaction;
use NAttreid\PayPal\PayPalClient;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\InvalidLinkException;

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

	/** @var Transaction */
	private $transaction;

	public function __construct(PayPalClient $client)
	{
		parent::__construct();
		$this->client = $client;
		$this->transaction = $client->createTransaction();
	}

	public function setCurrency(string $currency): void
	{
		$this->transaction->setCurrency($currency);
	}

	protected function setShipping(float $shipping): void
	{
		$this->transaction->setShipping($shipping);
	}

	protected function setTax(float $tax): void
	{
		$this->transaction->setTax($tax);
	}

	public function addItem(string $name, int $quantity, float $price): void
	{
		$this->transaction->addItem($name, $quantity, $price);
	}

	/**
	 * @throws PayPalException
	 * @throws AbortException
	 * @throws InvalidLinkException
	 */
	public function handleCheckout(): void
	{
		try {
			$this->client
				->setCancelUrl($this->link('//cancel!'))
				->setReturnUrl($this->link('//return!'));

			$payment = $this->client->createPayment($this->transaction);

			$this->onCheckout($payment);
			$this->presenter->redirectUrl($payment->getApprovalLink());
		} catch (PayPalException $ex) {
			$this->callError($ex);
		}
	}

	/**
	 * @throws PayPalException
	 */
	public function handleReturn(): void
	{
		$paymentId = $this->presenter->getParameter('paymentId');
		$payerId = $this->presenter->getParameter('PayerID');

		try {
			$payment = $this->client->paymentReturn($paymentId, $payerId);

			$transactions = $payment->getTransactions();
			$relatedResources = $transactions[0]->getRelatedResources();
			$sale = $relatedResources[0]->getSale();

			$this->onSuccess($payment, $sale);
		} catch (PayPalException $ex) {
			$this->callError($ex);
		}
	}

	public function handleCancel()
	{
		$this->onCancel();
	}

	/**
	 * @param PayPalException $ex
	 * @throws PayPalException
	 */
	private function callError(PayPalException $ex)
	{
		if (!$this->onError) {
			throw $ex;
		}
		$this->onError($ex);
	}

	public function render($text = "Pay", $attrs = [])
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
