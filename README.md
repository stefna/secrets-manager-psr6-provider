# Stefna Secrets Manager - Psr 6 Provider

Psr 6 provider for [Stefna Secrets Manager](https://bitbucket.org/stefnadev/secrets-manager)

## Table of Contents

1. [Installation](#installation)
2. [Usage](#usage)

### Installation

```bash
$ composer require stefna/secrets-manager-core stefna/secrets-manager-psr6-provider
```

### Usage

```php
use Stefna\SecretsManager\Manager;
use Stefna\SecretsManager\Provider\AwsSecretsManager\AwsSecretsManagerProvider;
use Stefna\SecretsManager\Provider\Psr6\Psr6Provider;

$manager = new Manager(
	new Psr6Provider(
		new AwsSecretsManagerProvider(),
		new Psr6Implementaion(),
		[
			'ttl' => 600,
		]
	)
);
```

**Cache options**

- `ttl` default value is `0` so it never expires
- `prefix` optional prefix for the cache key
- `tags` optional tags for cache. Only used if cache pool implementation returns `TaggableCacheItemInterface`
