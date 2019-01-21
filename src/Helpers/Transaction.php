<?php

declare(strict_types=1);

namespace NAttreid\PayPal\Helpers;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;

/**
 * Class PayPalTransaction
 *
 * @author Attreid <attreid@gmail.com>
 */
class Transaction
{

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

	public function setCurrency(string $currency): self
	{
		$this->currency = $currency;
		return $this;
	}

	public function setShipping(float $shipping): self
	{
		$this->shipping = $shipping;
		return $this;
	}

	public function setTax(float $tax): self
	{
		$this->tax = $tax;
		return $this;
	}

	public function addItem(string $name, int $quantity, float $price): self
	{
		$item = new Item;
		$item
			->setName($name)
			->setCurrency($this->currency)
			->setQuantity($quantity)
			->setPrice($price);
		$this->total += ($price * $quantity);
		$this->items[] = $item;
		return $this;
	}

	public function getTransaction(): \PayPal\Api\Transaction
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

		$transaction = new \PayPal\Api\Transaction();
		$transaction
			->setAmount($amount)
			->setItemList($itemLists);

		return $transaction;
	}
}