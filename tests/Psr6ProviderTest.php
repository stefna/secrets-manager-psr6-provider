<?php declare(strict_types=1);

namespace Stefna\SecretsManager\Provider\Psr6\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Stefna\SecretsManager\Provider\ProviderInterface;
use Stefna\SecretsManager\Provider\Psr6\Psr6Provider;
use Stefna\SecretsManager\Values\Secret;

final class Psr6ProviderTest extends TestCase
{
	public function testGetSecretMissingInCache(): void
	{
		$testKey = 'test-key';
		$testValue = 'value';
		$secret = new Secret($testKey, $testValue);
		$mockItem = $this->createMock(CacheItemInterface::class);
		$mockItem
			->expects($this->once())
			->method('isHit')
			->willReturn(false);
		$mockItem
			->expects($this->never())
			->method('get');
		$mockItem
			->expects($this->once())
			->method('set')
			->with(json_encode([$testValue, []]));

		$mockCachePool = $this->createMock(CacheItemPoolInterface::class);
		$mockCachePool
			->expects($this->once())
			->method('getItem')
			->with(sha1($testKey))
			->willReturn($mockItem);
		$mockCachePool
			->expects($this->once())
			->method('save')
			->with($mockItem);

		$mockProvider = $this->createMock(ProviderInterface::class);
		$mockProvider
			->expects($this->once())
			->method('getSecret')
			->with($testKey)
			->willReturn($secret);

		$provider = new Psr6Provider($mockProvider, $mockCachePool);
		$returnedSecret = $provider->getSecret($testKey);
		$this->assertSame($secret, $returnedSecret);
	}

	public function testGetSecretInCache(): void
	{
		$testKey = 'test-key';
		$testValue = 'value';
		$mockItem = $this->createMock(CacheItemInterface::class);
		$mockItem
			->expects($this->once())
			->method('isHit')
			->willReturn(true);
		$mockItem
			->expects($this->once())
			->method('get')
			->willReturn(json_encode([$testValue, []]));

		$mockCachePool = $this->createMock(CacheItemPoolInterface::class);
		$mockCachePool
			->expects($this->once())
			->method('getItem')
			->with(sha1($testKey))
			->willReturn($mockItem);

		$mockProvider = $this->createMock(ProviderInterface::class);
		$mockProvider
			->expects($this->never())
			->method('getSecret');

		$provider = new Psr6Provider($mockProvider, $mockCachePool);
		$secret = $provider->getSecret($testKey);

		$this->assertSame($testKey, $secret->getKey());
		$this->assertSame($testValue, $secret->getValue());
	}

	public function testPutSecret(): void
	{
		$testKey = 'test-key';
		$testValue = 'value';
		$secret = new Secret($testKey, $testValue);
		$mockItem = $this->createMock(CacheItemInterface::class);
		$mockItem
			->expects($this->once())
			->method('set')
			->with(json_encode([$testValue, []]));

		$mockCachePool = $this->createMock(CacheItemPoolInterface::class);
		$mockCachePool
			->expects($this->once())
			->method('getItem')
			->with(sha1($testKey))
			->willReturn($mockItem);
		$mockCachePool
			->expects($this->once())
			->method('hasItem')
			->with(sha1($testKey))
			->willReturn(false);
		$mockCachePool
			->expects($this->once())
			->method('save')
			->with($mockItem);

		$mockProvider = $this->createMock(ProviderInterface::class);
		$mockProvider
			->expects($this->once())
			->method('putSecret')
			->with($secret)
			->willReturn($secret);

		$provider = new Psr6Provider($mockProvider, $mockCachePool);
		$provider->putSecret($secret);
	}

	public function testPutSecretCleansCache(): void
	{
		$testKey = 'test-key';
		$testValue = 'value';
		$secret = new Secret($testKey, $testValue);
		$mockItem = $this->createMock(CacheItemInterface::class);
		$mockItem
			->expects($this->once())
			->method('set')
			->with(json_encode([$testValue, []]));

		$mockCachePool = $this->createMock(CacheItemPoolInterface::class);
		$mockCachePool
			->expects($this->once())
			->method('getItem')
			->with(sha1($testKey))
			->willReturn($mockItem);
		$mockCachePool
			->expects($this->once())
			->method('hasItem')
			->with(sha1($testKey))
			->willReturn(true);
		$mockCachePool
			->expects($this->once())
			->method('deleteItem')
			->with(sha1($testKey))
			->willReturn(true);
		$mockCachePool
			->expects($this->once())
			->method('save')
			->with($mockItem);

		$mockProvider = $this->createMock(ProviderInterface::class);
		$mockProvider
			->expects($this->once())
			->method('putSecret')
			->with($secret)
			->willReturn($secret);

		$provider = new Psr6Provider($mockProvider, $mockCachePool);
		$provider->putSecret($secret);
	}

	public function testDeleteSecret(): void
	{
		$testKey = 'test-key';
		$secretToDelete = new Secret($testKey, '');

		$mockCachePool = $this->createMock(CacheItemPoolInterface::class);
		$mockCachePool
			->expects($this->once())
			->method('hasItem')
			->with(sha1($testKey))
			->willReturn(true);
		$mockCachePool
			->expects($this->once())
			->method('deleteItem')
			->with(sha1($testKey))
			->willReturn(true);

		$mockProvider = $this->createMock(ProviderInterface::class);
		$mockProvider
			->expects($this->once())
			->method('deleteSecret')
			->with($secretToDelete);

		$provider = new Psr6Provider($mockProvider, $mockCachePool);
		$provider->deleteSecret($secretToDelete);
	}
}
