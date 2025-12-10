<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\ReservedName;
use App\Entity\Setting;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\Voucher;
use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\UserNotificationType;
use App\Enum\WebhookEvent;
use App\Guesser\DomainGuesser;
use App\Helper\PasswordUpdater;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use OTPHP\TOTP;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

use const PHP_URL_QUERY;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
class FeatureContext extends MinkContext
{
    private readonly string $dbPlatform;
    private array $placeholders = [];
    private array $requestParams = [];
    private SessionFactoryInterface $sessionFactory;
    private CacheItemPoolInterface $cache;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $manager,
        private readonly PasswordUpdater $passwordUpdater,
        private readonly DomainGuesser $domainGuesser,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->sessionFactory = $this->getContainer()->get('session.factory');
        $this->cache = $this->getContainer()->get('cache.app');
        $this->dbPlatform = $this->manager->getConnection()->getDatabasePlatform()->getName();
    }

    public function getContainer(): ContainerInterface
    {
        $container = $this->kernel->getContainer();

        return $container->has('test.service_container') ? $container->get('test.service_container') : $container;
    }

    /**
     * @Given /^the database is clean$/
     *
     * @throws ToolsException
     */
    public function theDatabaseIsClean(): void
    {
        $schemaTool = new SchemaTool($this->manager);
        $metadata = $this->manager->getMetadataFactory()->getAllMetadata();

        // dropSchema leads to errors with sqlite backend since DBAL 2.10.3
        if ('sqlite' === $this->dbPlatform) {
            $schemaTool->dropDatabase();
        } else {
            $schemaTool->dropSchema($metadata);
        }
        $schemaTool->createSchema($metadata);

        // Clear the cache to ensure settings are not cached between tests
        $this->cache->clear();
    }

    /**
     * @When the following Domain exists:
     */
    public function theFollowingDomainExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $domain = new Domain();

            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'name':
                        $domain->setName($value);
                }
            }

            $this->manager->persist($domain);
            $this->manager->flush();
        }
    }

    /**
     * @When the following User exists:
     */
    public function theFollowingUserExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $email = $data['email'] ?? '';
            $user = new User($email);

            if (null !== $domain = $this->domainGuesser->guess($email)) {
                $user->setDomain($domain);
            }

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'email':
                        // Already handled above
                        break;
                    case 'password':
                        $this->passwordUpdater->updatePassword($user, $value);
                        break;
                    case 'roles':
                        $roles = explode(',', (string) $value);
                        $user->setRoles($roles);
                        break;
                    case 'hash':
                        $user->setPassword($value);
                        break;
                    case 'quota':
                        $user->setQuota($value);
                        break;
                    case 'recoveryStartTime':
                        $time = new DateTime();
                        if ('NOW' !== 'value') {
                            $time = $time->modify($value);
                        }
                        $user->setRecoveryStartTime($time);
                        break;
                    case 'recoverySecretBox':
                        $user->setRecoverySecretBox($value);
                        break;
                    case 'mailCrypt':
                        $user->setMailCryptEnabled((bool) $value);
                        break;
                    case 'mailCryptSecretBox':
                        $user->setMailCryptSecretBox($value);
                        break;
                    case 'mailCryptPublicKey':
                        $user->setMailCryptPublicKey($value);
                        break;
                    case 'totpConfirmed':
                        $user->setTotpConfirmed((bool) $value);
                        break;
                    case 'totpSecret':
                        $user->setTotpSecret($value);
                        break;
                    case 'totp_backup_codes':
                        $user->generateBackupCodes();
                        $this->setPlaceholder('totp_backup_codes', $user->getTotpBackupCodes());
                        break;
                }
            }

            $this->manager->persist($user);
            $this->manager->flush();
        }
    }

    /**
     * @When the following Voucher exists:
     */
    public function theFollowingVoucherExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $voucher = new Voucher();

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'code':
                        $voucher->setCode($value);
                        break;
                    case 'user':
                        $user = $this->getUserRepository()->findByEmail($value);

                        if (null !== $user) {
                            $voucher->setUser($user);
                        }

                        break;
                }
            }

            $this->manager->persist($voucher);
            $this->manager->flush();
        }
    }

    /**
     * @When the following Setting exists:
     */
    public function theFollowingSettingExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $name = $data['name'] ?? '';
            $value = $data['value'] ?? '';

            if (empty($name)) {
                continue;
            }

            $setting = new Setting($name, $value);

            $this->manager->persist($setting);
            $this->manager->flush();
        }
    }

    /**
     * @When the following Alias exists:
     */
    public function theFollowingAliasExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $alias = new Alias();

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'user_id':
                        $user = $this->getUserRepository()->find($value);
                        $alias->setUser($user);
                        break;
                    case 'source':
                        $alias->setSource($value);
                        break;
                    case 'destination':
                        $alias->setDestination($value);
                        break;
                    case 'deleted':
                        $alias->setDeleted((bool) $value);
                        break;
                    case 'random':
                        $alias->setRandom((bool) $value);
                        break;
                }

                if (null !== $domain = $this->domainGuesser->guess($value)) {
                    $alias->setDomain($domain);
                }
            }

            $this->manager->persist($alias);
            $this->manager->flush();
        }
    }

    /**
     * @When the following ReservedName exists:
     */
    public function theFollowingReservedNameExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $reservedName = new ReservedName();

            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'name':
                        $reservedName->setName($value);
                }
            }

            $this->manager->persist($reservedName);
            $this->manager->flush();
        }
    }

    /**
     * @When the following WebhookEndpoint exists:
     */
    public function theFollowingWebhookEndpointExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $url = $data['url'] ?? null;
            $secret = $data['secret'] ?? null;

            $events = isset($data['events']) ? explode(',', $data['events']) : null;
            $enabled = !isset($data['enabled']) || $data['enabled'];
            $endpoint = new WebhookEndpoint($url, $secret);
            $endpoint->setEvents($events);
            $endpoint->setEnabled((bool) $enabled);

            $this->manager->persist($endpoint);
            $this->manager->flush();
        }
    }

    /**
     * @When the following WebhookDelivery exists:
     */
    public function theFollowingWebhookDeliveryExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $endpointId = $data['endpoint_id'] ?? null;
            $type = WebhookEvent::from($data['type']);
            $requestBody = isset($data['request_body']) ? json_decode($data['request_body'], true) : null;
            $requestHeaders = isset($data['request_headers']) ? json_decode($data['request_headers'], true) : null;
            $responseCode = $data['response_code'] ?? null;
            $responseBody = $data['response_body'] ?? null;

            $endpoint = $this->manager->getRepository(WebhookEndpoint::class)->find($endpointId);
            if (null === $endpoint) {
                throw new RuntimeException(sprintf('WebhookEndpoint with id %s not found', $endpointId));
            }

            $delivery = new WebhookDelivery($endpoint, $type, $requestBody, $requestHeaders);

            $delivery->setResponseCode((int) $responseCode);
            $delivery->setResponseBody($responseBody);
            $delivery->setSuccess($responseCode >= 200 && $responseCode < 300);
            $delivery->setError($delivery->isSuccess() ? null : 'Unknown error');

            $this->manager->persist($delivery);
            $this->manager->flush();
        }
    }

    /**
     * @When the following UserNotification exists:
     */
    public function theFollowingUserNotificationExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $user = $this->getUserRepository()->findByEmail($data['email']);
            $type = UserNotificationType::from($data['type']);
            $notification = new UserNotification($user, $type);

            $this->manager->persist($notification);
            $this->manager->flush();
        }
    }

    /**
     * @Then /^the user "([^"]*)" should not have a "([^"]*)" notification$/
     */
    public function theUserShouldNotHaveAUserNotification(string $email, string $type): void
    {
        $user = $this->getUserRepository()->findByEmail($email);
        $repo = $this->manager->getRepository(UserNotification::class);

        $notifications = $repo->findBy(['user' => $user, 'type' => $type]);

        if (count($notifications) > 0) {
            throw new RuntimeException(sprintf('User "%s" has %d notification(s) of type "%s"', $email, count($notifications), $type));
        }
    }

    /**
     * @Given /^I am authenticated as "([^"]*)"$/
     *
     * @throws UnsupportedDriverActionException
     */
    public function iAmAuthenticatedAs(string $username): void
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        $user = $this->getUserRepository()->findByEmail($username);
        $token = new TestBrowserToken($user->getRoles(), $user, 'main');

        $this->tokenStorage->setToken($token);

        $session = $this->sessionFactory->createSession();
        $session->set('_security_main', serialize($token));
        $session->save();

        $client = $driver->getClient();
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId(), null, null));
    }

    /**
     * @When /^I have the request params for "([^"]*)":$/
     */
    public function iHaveTheRequestParams(string $field, TableNode $table): void
    {
        foreach ($table->getRowsHash() as $var => $value) {
            $this->requestParams[$field][$var] = $value;
        }
    }

    /**
     * @When /^I request "(GET|PUT|POST|DELETE|PATCH) ([^"]*)"$/
     *
     * @throws UnsupportedDriverActionException
     */
    public function iRequest(string $httpMethod, string $path): void
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        $client = $driver->getClient();

        $method = strtoupper($httpMethod);
        $formParams = $this->requestParams;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        // Magic is here : allow to simulate any HTTP verb
        $client->request(
            $method,
            $this->locatePath($path),
            $formParams,
            [],
            $headers);
    }

    /**
     * @When /^I set the placeholder "([^"]*)" with property "([^"]*)" for "([^"]*)"$/
     */
    public function iSetPlaceholderForUser(string $name, string $key, string $email): void
    {
        $user = $this->getUserRepository()->findByEmail($email);

        if (!$user) {
            throw new UserNotFoundException();
        }

        $value = PropertyAccess::createPropertyAccessor()->getValue($user, $key);

        $this->setPlaceholder($name, $value);
    }

    /**
     * @When /^I set the placeholder "([^"]*)" from url parameter "([^"]*)"$/
     */
    public function iSetPlaceholderFromUrl(string $name, string $key): void
    {
        $queryParams = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY);

        if (null !== $queryParams) {
            foreach (explode('=', $queryParams) as $param => $value) {
                if ($param === $key) {
                    $this->setPlaceholder($name, $value);
                }
            }
        }
    }

    /**
     * @When /^I generate a TOTP code from "([^"]*)" and fill to field "([^"]*)"/
     */
    public function iGenerateTotpCodeFromSecret(string $placeholder, string $field): void
    {
        $secret = $this->getPlaceholder($placeholder);
        $totp = TOTP::create($secret);
        $code = $totp->now();

        if (null === $code) {
            throw new RuntimeException('Failed to generate TOTP code');
        }

        $this->fillField($field, $code);
    }

    /**
     * @When /I set the placeholder "([^"]*)" from html element "([^"]*)"$/
     *
     * @throws Exception
     */
    public function iSetPlaceholderFromElement(string $name, string $selector): void
    {
        $element = $this->getSession()->getPage()->find('css', $selector);

        if (null === $element) {
            throw new Exception(sprintf('Element "%s" not found', $selector));
        }

        $value = $element->getText();
        if (null === $value) {
            throw new Exception(sprintf('Element "%s" not found', $selector));
        }

        $this->setPlaceholder($name, $value);
    }

    /**
     * @When /^File "([^"]*)" exists with content:$/
     */
    public function touchFile(string $path, PyStringNode $content): void
    {
        file_put_contents($path, $content);
    }

    public function visit($page): void
    {
        $page = $this->replacePlaceholders($page);

        parent::visit($page);
    }

    /**
     * @Given /^set the HTTP-Header "([^"]*)" to "([^"]*)"$/
     */
    public function setHttpHeaderto(string $name, string $value): void
    {
        $this->getSession()->setRequestHeader($name, $value);
    }

    /**
     * @Then I enter TOTP backup code
     */
    public function iEnterTotpBackupCode(): void
    {
        $totpBackupCodes = $this->getPlaceholder('totp_backup_codes');
        if (!$totpBackupCodes) {
            throw new RuntimeException('No TOTP backup codes cached');
        }
        $this->fillField('_auth_code', $totpBackupCodes[0]);
    }

    /**
     * @Then /^File "([^"]*)" should exist$/
     */
    public function fileExists(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('File doesn\'t exist: "%s"', $path));
        }
    }

    /**
     * @Then /^File "([^"]*)" should not exist$/
     */
    public function fileNoExists(string $path): void
    {
        if (is_file($path)) {
            throw new RuntimeException(sprintf('File exists: "%s"', $path));
        }
    }

    public function setPlaceholder(string $key, $value): void
    {
        $this->placeholders[$key] = $value;
    }

    public function getPlaceholder(string $key)
    {
        return $this->placeholders[$key] ?? null;
    }

    public function getAllPlaceholders(): array
    {
        return $this->placeholders;
    }

    public function replacePlaceholders(string $string): string
    {
        foreach ($this->getAllPlaceholders() as $key => $value) {
            if (str_contains($string, $key)) {
                $string = str_replace($key, $value, $string);
            }
        }

        return $string;
    }

    public function getUserRepository(): ObjectRepository
    {
        return $this->manager->getRepository(User::class);
    }
}
