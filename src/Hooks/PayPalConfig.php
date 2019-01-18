<?php

declare(strict_types=1);

namespace NAttreid\PayPal\Hooks;

use Nette\SmartObject;

/**
 * Class PayPalConfig
 *
 * @property string|null $clientId
 * @property string|null $secret
 * @property string|null $experienceProfileId
 *
 * @author Attreid <attreid@gmail.com>
 */
class PayPalConfig
{
	use SmartObject;

	/** @var string|null */
	private $clientId;

	/** @var string|null */
	private $secret;

	/** @var string|null */
	private $experienceProfileId;

	protected function getClientId(): ?string
	{
		return $this->clientId;
	}

	protected function setClientId(?string $clientId): void
	{
		$this->clientId = $clientId;
	}

	protected function getSecret(): ?int
	{
		return $this->secret;
	}

	protected function setSecret(?int $secret): void
	{
		$this->secret = $secret;
	}

	protected function setExperienceProfileId(?string $experienceProfileId): void
	{
		$this->experienceProfileId = $experienceProfileId;
	}

	protected function getExperienceProfileId(): ?string
	{
		return $this->experienceProfileId;
	}
}