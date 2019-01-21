<?php

namespace NAttreid\PayPal;

use Exception;
use NAttreid\PayPal\Helpers\Exceptions\CredentialsNotSetException;
use NAttreid\PayPal\Helpers\Exceptions\PayPalException;
use NAttreid\PayPal\Helpers\Transaction;
use NAttreid\PayPal\Hooks\PayPalConfig;
use Nette\Http\Url;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Sale;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConfigurationException;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Exception\PayPalInvalidCredentialException;
use PayPal\Exception\PayPalMissingCredentialException;
use PayPal\Rest\ApiContext;

/**
 * Class PayPalClient
 *
 * @author Attreid <attreid@gmail.com>
 */
class PayPalClient
{

	/** @var PayPalConfig */
	private $config;

	/** @var ApiContext */
	private $apiContext;

	/** @var RedirectUrls|null */
	private $redirectUrls;

	/**
	 * PayPalClient constructor.
	 * @param PayPalConfig $config
	 * @throws CredentialsNotSetException
	 */
	public function __construct(PayPalConfig $config)
	{
		$this->config = $config;

		if (empty($this->config->clientId)) {
			throw new CredentialsNotSetException('ClientId must be set');
		} elseif (empty($this->config->secret)) {
			throw new CredentialsNotSetException('Secret token must be set');
		}

		$auth = new OAuthTokenCredential($config->clientId, $config->secret);
		$apiContext = new ApiContext($auth);
		$this->apiContext = $apiContext;
	}

	public function setReturnUrl(string $returnUrl): self
	{
		$url = new Url($returnUrl);
		$url->setQueryParameter('utm_nooverride', 1);
		$this->getRedirectUrls()->setReturnUrl($url->getAbsoluteUrl());
		return $this;
	}

	public function setCancelUrl(string $cancelUrl): self
	{
		$this->getRedirectUrls()->setCancelUrl($cancelUrl);
		return $this;
	}

	public function createTransaction(): Transaction
	{
		return new Transaction();
	}

	/**
	 * @param Transaction $transaction
	 * @return Payment
	 * @throws PayPalException
	 */
	public function createPayment(Transaction $transaction): Payment
	{
		$payer = new Payer();
		$payer->setPaymentMethod('paypal');

		$payment = new Payment();
		$payment->setIntent("sale")
			->setPayer($payer);

		if ($this->config->experienceProfileId !== null) {
			$payment->setExperienceProfileId($this->config->experienceProfileId);
		}

		$payment->setTransactions([$transaction->getTransaction()]);

		if ($this->redirectUrls !== null) {
			$payment->setRedirectUrls($this->redirectUrls);
		}

		try {
			$payment->create($this->apiContext);
		} catch (Exception $ex) {
			throw $this->parseException($ex);
		}

		return $payment;
	}

	/**
	 * @param string $paymentId
	 * @param string $payerId
	 * @return Payment
	 * @throws PayPalException
	 */
	public function paymentReturn(string $paymentId, string $payerId): Payment
	{
		try {
			$payment = Payment::get($paymentId, $this->apiContext);
			$execution = new PaymentExecution();
			$execution->setPayerId($payerId);

			$payment->execute($execution, $this->apiContext);
			return Payment::get($paymentId, $this->apiContext);
		} catch (Exception $ex) {
			throw $this->parseException($ex);
		}
	}

	/**
	 * @param string $saleId
	 * @return bool|null true => verified, false => unverified, null => no change
	 * @throws PayPalException
	 */
	public function checkPayment(string $saleId): ?bool
	{
		try {
			$sale = Sale::get($saleId, $this->apiContext);

			if ($sale->getState() === 'completed') {
				$payment = Payment::get($sale->getParentPayment(), $this->apiContext);

				switch ($payment->payer->status) {
					case 'VERIFIED':
						return true;
					case 'UNVERIFIED':
						return false;
				}
			}
		} catch (Exception $ex) {
			throw $this->parseException($ex);
		}
		return null;
	}

	private function getRedirectUrls(): RedirectUrls
	{
		if ($this->redirectUrls === null) {
			$this->redirectUrls = new RedirectUrls();
		}
		return $this->redirectUrls;
	}

	/**
	 * @param Exception $ex
	 * @return Exception|PayPalException
	 */
	private function parseException(Exception $ex): Exception
	{
		if (
			$ex instanceof PayPalConfigurationException ||
			$ex instanceof PayPalInvalidCredentialException ||
			$ex instanceof PayPalMissingCredentialException ||
			$ex instanceof PayPalConnectionException
		) {
			return new PayPalException(
				$ex->getMessage() . 'Data: ' . $ex->getData(),
				$ex->getCode(),
				$ex);
		}
		return $ex;
	}
}
