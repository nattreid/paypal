<?php

namespace Nattreid\PayPal;

use Exception;
use NAttreid\PayPal\Helpers\Exceptions\PayPalException;
use NAttreid\PayPal\Hooks\PayPalConfig;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\Url;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConfigurationException;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Exception\PayPalInvalidCredentialException;
use PayPal\Exception\PayPalMissingCredentialException;
use PayPal\Rest\ApiContext;

/**
 * Class PayPal
 *
 * @author Attreid <attreid@gmail.com>
 */
class PayPal extends Control
{
	public $onCheckout = [];
	public $onSuccess = [];
	public $onCancel = [];
	public $onError = [];

	/** @var PayPalConfig */
	private $config;

	/** @var ApiContext */
	private $apiContext;

	/** @var string */
	private $currency;

	/** @var Item[] */
	private $items = [];

	/** @var float */
	private $total = 0;

	/** @var float|null */
	private $shipping;

	/** @var float|null */
	private $tax;

	public function __construct(PayPalConfig $config)
	{
		parent::__construct();

		$this->config = $config;

		$auth = new OAuthTokenCredential($config->clientId, $config->secret);
		$apiContext = new ApiContext($auth);
		$this->apiContext = $apiContext;
	}

	public function setCurrency(string $currency): void
	{
		$this->currency = $currency;
	}

	protected function setShipping(float $shipping): void
	{
		$this->shipping = $shipping;
	}

	protected function setTax(float $tax): void
	{
		$this->tax = $tax;
	}

	public function addItem(string $name, int $quantity, float $price): void
	{
		$item = new Item;
		$item
			->setName($name)
			->setCurrency($this->currency)
			->setQuantity($quantity)
			->setPrice($price);
		$this->total += ($price * $quantity);
		$this->items[] = $item;
	}

	public function handleCheckout(): void
	{
		$payment = $this->createPayment();

		$redirectUrls = new RedirectUrls();

		$redirectUrls->setCancelUrl($this->link('//cancel!'));

		$url = new Url($this->link('//return!'));
		$url->setQueryParameter('utm_nooverride', 1);
		$redirectUrls->setReturnUrl($url->getAbsoluteUrl());

		$payment->setRedirectUrls($redirectUrls);

		try {
			$payment->create($this->apiContext);
		} catch (Exception $ex) {
			$this->parseException($ex);
		}

		$this->onCheckout($payment);

		$approvalUrl = $payment->getApprovalLink();
		$this->presenter->redirectUrl($approvalUrl);
	}

	public function handleReturn(): void
	{
		$paymentId = $this->presenter->getParameter('paymentId');
		$payerId = $this->presenter->getParameter('PayerID');

		try {
			$payment = Payment::get($paymentId, $this->apiContext);
			$execution = new PaymentExecution();
			$execution->setPayerId($payerId);

			$payment->execute($execution, $this->apiContext);
			$paidPayment = Payment::get($paymentId, $this->apiContext);

			$this->onSuccess($paidPayment);
		} catch (Exception $ex) {
			$this->parseException($ex);
		}
	}

	public function handleCancel()
	{
		$this->onCancel();
	}

	private function createPayment(): Payment
	{
		$payer = new Payer();
		$payer->setPaymentMethod('paypal');

		$payment = new Payment();
		$payment->setIntent("sale")
			->setPayer($payer);

		if ($this->config->experienceProfileId !== null) {
			$payment->setExperienceProfileId($this->config->experienceProfileId);
		}

		$payment->setTransactions([$this->createTransaction()]);

		return $payment;
	}

	private function createTransaction(): Transaction
	{
		$itemLists = new ItemList();
		$itemLists->setItems($this->items);

		$details = new Details();
		$details->setSubtotal($this->total);
		if ($this->shipping !== null) {
			$details->setShipping($this->shipping);
		}
		if ($this->tax !== null) {
			$details->setTax($this->tax);
		}

		$amount = new Amount();
		$amount
			->setCurrency($this->currency)
			->setTotal($this->total + ($this->shipping ?? 0) + ($this->tax ?? 0))
			->setDetails($details);

		$transaction = new Transaction();
		$transaction
			->setAmount($amount)
			->setItemList($itemLists);

		return $transaction;
	}

	/**
	 * @param Exception $ex
	 * @throws Exception
	 */
	private function parseException(Exception $ex)
	{
		if (
			$ex instanceof PayPalConfigurationException ||
			$ex instanceof PayPalInvalidCredentialException ||
			$ex instanceof PayPalMissingCredentialException ||
			$ex instanceof PayPalConnectionException
		) {
			$exception = new PayPalException(
				$ex->getMessage() . 'Data: ' . $ex->getData(),
				$ex->getCode(),
				$ex);

			if (!$this->onError) {
				throw $exception;
			}

			$this->onError($exception);
		}
		throw $ex;
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

interface IPayPalFactory
{
	public function create(): PayPal;
}
