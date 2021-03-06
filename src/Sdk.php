<?php
declare(strict_types=1);

namespace Serato\SwsSdk;

use Serato\SwsSdk\Client;
use Serato\SwsSdk\Ecom\EcomClient;
use Serato\SwsSdk\License\LicenseClient;
use Serato\SwsSdk\Profile\ProfileClient;
use Serato\SwsSdk\Identity\IdentityClient;
use InvalidArgumentException;

/**
 * Builds SWS clients based on configuration settings.
 */
class Sdk
{
    const BASE_URI                       = 'base_uri';
    const BASE_URI_ID                    = 'id';
    const BASE_URI_LICENSE               = 'license';
    const BASE_URI_PROFILE               = 'profile';
    const BASE_URI_ECOM                  = 'ecom';

    const ENV_PRODUCTION                = 'production';
    const ENV_STAGING                   = 'staging';

    const BASE_URI_STAGING_ID           = 'https://staging-id.serato.net';
    const BASE_URI_STAGING_LICENSE      = 'https://staging-license.serato.net';
    const BASE_URI_STAGING_PROFILE      = 'https://staging-profile.serato.net';
    const BASE_URI_STAGING_ECOM         = 'https://staging-ecom.serato.net';

    const BASE_URI_PRODUCTION_ID        = 'https://id.serato.com';
    const BASE_URI_PRODUCTION_LICENSE   = 'https://license.serato.com';
    const BASE_URI_PRODUCTION_PROFILE   = 'https://profile.serato.com';
    const BASE_URI_PRODUCTION_ECOM      = 'https://ecom.serato.com';

    /**
     * Client application ID
     *
     * @var string
     */
    private $appId;

    /**
     * Client application password
     *
     * @var string
     */
    private $appPassword;

    /**
     * Client configuration data
     *
     * @var array
     */
    private $config = [
        'timeout' => 2.0,
        'handler' => null
    ];

    /**
     * The `$args` parameter accepts the following options:
     *
     * - `env`: (string) The SWS environment that the SDK interacts with. Accepted
     *   values are `production` and `staging`. One of `env` or `base_uri` must
     *   be specified.
     * - `base_uri`: (array) An array of base URIs for each SWS service. The array
     *   must contains key named `id`, `license`, `profile` and `ecom` and provide a complete base URI
     *   including protocol (http or https) for each. One of `env` or `base_uri`
     *   must be specified.
     * - `timeout`: (float) The default request timeout, in seconds.
     * - `handler`: (callable) Function that transfers HTTP requests over the
     *   wire. Passed to Guzzle clients to override the default handler
     *   (eg. to use a mock handler). See the Guzzle docs for more info.
     *
     * @link http://guzzle.readthedocs.io/en/latest/quickstart.html Guzzle documentation
     *
     * @param array     $args           Client configuration arguments
     * @param string    $appId          Client application ID
     * @param string    $appPassword    Client application password
     *
     * @return void
     *
     * @throws InvalidArgumentException If any required options are missing or
     *                                   an invalid value is provided.
     */
    public function __construct(array $args, string $appId = '', string $appPassword = '')
    {
        $this->appId = $appId;
        $this->appPassword = $appPassword;

        if (isset($args['timeout'])) {
            if (!is_float($args['timeout'])) {
                throw new InvalidArgumentException(
                    'The `timeout` config option value must be a float',
                    1000
                );
            } else {
                $this->config['timeout'] = $args['timeout'];
            }
        }

        if (isset($args['handler'])) {
            if (!is_callable($args['handler'])) {
                throw new InvalidArgumentException(
                    'The `handler` config option value must be a callable',
                    1006
                );
            } else {
                $this->config['handler'] = $args['handler'];
            }
        }

        if (isset($args['env'])) {
            if (in_array($args['env'], [self::ENV_STAGING, self::ENV_PRODUCTION])) {
                if ($args['env'] == self::ENV_STAGING) {
                    $this->setBaseUriConfig(
                        self::BASE_URI_STAGING_ID,
                        self::BASE_URI_STAGING_LICENSE,
                        self::BASE_URI_STAGING_PROFILE,
                        self::BASE_URI_STAGING_ECOM
                    );
                }
                if ($args['env'] == self::ENV_PRODUCTION) {
                    $this->setBaseUriConfig(
                        self::BASE_URI_PRODUCTION_ID,
                        self::BASE_URI_PRODUCTION_LICENSE,
                        self::BASE_URI_PRODUCTION_PROFILE,
                        self::BASE_URI_PRODUCTION_ECOM
                    );
                }
            } else {
                throw new InvalidArgumentException(
                    'The `env` config option value must be one of `' . self::ENV_STAGING .
                    '` or `' . self::ENV_PRODUCTION . '`.',
                    1001
                );
            }
        }

        if (isset($args[self::BASE_URI])) {
            if (!is_array($args[self::BASE_URI]) ||
                !isset($args[self::BASE_URI][self::BASE_URI_ID]) ||
                !isset($args[self::BASE_URI][self::BASE_URI_LICENSE]) ||
                !isset($args[self::BASE_URI][self::BASE_URI_PROFILE]) ||
                !isset($args[self::BASE_URI][self::BASE_URI_ECOM])
            ) {
                throw new InvalidArgumentException(
                    'The `base_uri` config option value must be an array containing '.
                    '`id`, `license`, `profile` and `ecom` keys.',
                    1002
                );
            } else {
                if (strpos($args[self::BASE_URI][self::BASE_URI_ID], 'http://') !== 0 &&
                    strpos($args[self::BASE_URI][self::BASE_URI_ID], 'https://') !== 0
                ) {
                    throw new InvalidArgumentException(
                        'The `base_uri` `id` config option value must including a ' .
                        'valid network protocol (ie. http or https)',
                        1003
                    );
                }
                if (strpos($args[self::BASE_URI][self::BASE_URI_LICENSE], 'http://') !== 0 &&
                    strpos($args[self::BASE_URI][self::BASE_URI_LICENSE], 'https://') !== 0
                ) {
                    throw new InvalidArgumentException(
                        'The `base_uri` `license` config option value must including a ' .
                        'valid network protocol (ie. http or https)',
                        1004
                    );
                }
                if (strpos($args[self::BASE_URI][self::BASE_URI_PROFILE], 'http://') !== 0 &&
                    strpos($args[self::BASE_URI][self::BASE_URI_PROFILE], 'https://') !== 0
                ) {
                    throw new InvalidArgumentException(
                        'The `base_uri` `profile` config option value must including a ' .
                        'valid network protocol (ie. http or https)',
                        1007
                    );
                }
                if (strpos($args[self::BASE_URI][self::BASE_URI_ECOM], 'http://') !== 0 &&
                    strpos($args[self::BASE_URI][self::BASE_URI_ECOM], 'https://') !== 0
                ) {
                    throw new InvalidArgumentException(
                        'The `base_uri` `ecom` config option value must including a ' .
                        'valid network protocol (ie. http or https)',
                        1008
                    );
                }
                $this->setBaseUriConfig(
                    $args[self::BASE_URI][self::BASE_URI_ID],
                    $args[self::BASE_URI][self::BASE_URI_LICENSE],
                    $args[self::BASE_URI][self::BASE_URI_PROFILE],
                    $args[self::BASE_URI][self::BASE_URI_ECOM]
                );
            }
        }

        if (!isset($this->config[self::BASE_URI])) {
            throw new InvalidArgumentException(
                'You must specify one of either the `env` or `base_uri` config ' .
                'option values.',
                1005
            );
        }
    }

    /**
     * Create a IdentityClient
     *
     * @return IdentityClient
     */
    public function createIdentityClient(): IdentityClient
    {
        return $this->createClient('Serato\\SwsSdk\\Identity\\IdentityClient');
    }

    /**
     * Create a LicenseClient
     *
     * @return LicenseClient
     */
    public function createLicenseClient(): LicenseClient
    {
        return $this->createClient('Serato\\SwsSdk\\License\\LicenseClient');
    }

    /**
     * Create a ProfileClient
     *
     * @return ProfileClient
     */
    public function createProfileClient(): ProfileClient
    {
        return $this->createClient('Serato\\SwsSdk\\Profile\\ProfileClient');
    }

    /**
     * Create a EcomClient
     *
     * @return EcomClient
     */
    public function createEcomClient(): EcomClient
    {
        return $this->createClient('Serato\\SwsSdk\\Ecom\\EcomClient');
    }

    private function createClient(string $className)
    {
        return new $className($this->config, $this->appId, $this->appPassword);
    }

    /**
     * Return the current configuration options
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Returns the client application ID
     *
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * Sets the client application ID
     *
     * @param string $id Application ID
     * @return self
     */
    public function setAppId(string $id): self
    {
        $this->appId = $id;
        return $this;
    }

    /**
     * Returns the client application password
     *
     * @return string
     */
    public function getAppPassword(): string
    {
        return $this->appPassword;
    }

    /**
     * Sets the client application password
     *
     * @param string $pwd Password
     * @return self
     */
    public function setAppPassword(string $pwd): self
    {
        $this->appPassword = $pwd;
        return $this;
    }

    private function setBaseUriConfig(
        string $idServiceBaseUri,
        string $licenseServiceBaseUri,
        string $profileServiceBaseUri,
        string $ecomServiceBaseUri
    ): void {
        $this->config[self::BASE_URI] = [
            self::BASE_URI_ID        => $idServiceBaseUri,
            self::BASE_URI_LICENSE   => $licenseServiceBaseUri,
            self::BASE_URI_PROFILE   => $profileServiceBaseUri,
            self::BASE_URI_ECOM      => $ecomServiceBaseUri
        ];
    }
}
