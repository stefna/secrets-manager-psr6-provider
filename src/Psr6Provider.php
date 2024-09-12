<?php declare(strict_types=1);

namespace Stefna\SecretsManager\Provider\Psr6;

use Cache\TagInterop\TaggableCacheItemInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Stefna\SecretsManager\Provider\ProviderInterface;
use Stefna\SecretsManager\Values\Secret;

final class Psr6Provider implements ProviderInterface
{
	public function __construct(
		private ProviderInterface $provider,
		private CacheItemPoolInterface $cachePool,
		/** @var array{prefix?: string, ttl?: int, tags?: string[]} */
		private array $cacheOptions = [],
	) {}

	public function getSecret(string $key, ?array $options = []): Secret
	{
		$cacheKey = $this->getCacheKey($key);
		$item = $this->cachePool->getItem($cacheKey);
		if ($item !== null && $item->isHit()) {
			[$value, $metadata] = json_decode($item->get(), true);

			return new Secret($key, $value, $metadata);
		}

		$secret = $this->provider->getSecret($key, $options);
		$this->persistToCache($secret, $item);

		return $secret;
	}

	public function putSecret(Secret $secret, ?array $options = []): Secret
	{
		$cacheKey = $this->getCacheKey($secret->getKey());
		$this->provider->putSecret($secret, $options);
		if ($this->cachePool->hasItem($cacheKey)) {
			$this->cachePool->deleteItem($cacheKey);
		}

		$item = $this->cachePool->getItem($cacheKey);
		$this->persistToCache($secret, $item);

		return $secret;
	}

	public function deleteSecret(Secret $secret, ?array $options = []): void
	{
		$cacheKey = $this->getCacheKey($secret->getKey());
		$this->provider->deleteSecret($secret);
		if ($this->cachePool->hasItem($cacheKey)) {
			$this->cachePool->deleteItem($cacheKey);
		}
	}

	private function getCacheKey(string $key): string
	{
		$key = sha1($key);
		if (isset($this->cacheOptions['prefix'])) {
			$key = $this->cacheOptions['prefix'] . '_' . $key;
		}
		return $key;
	}

	private function persistToCache(Secret $secret, CacheItemInterface $item): void
	{
		$item->set(json_encode([$secret->getValue(), $secret->getMetadata()]));
		if (isset($this->cacheOptions['ttl'])) {
			$item->expiresAfter($this->cacheOptions['ttl']);
		}
		if (isset($this->cacheOptions['tags']) && $item instanceof TaggableCacheItemInterface) {
			$item->setTags($this->cacheOptions['tags']);
		}
		$this->cachePool->save($item);
	}
}
