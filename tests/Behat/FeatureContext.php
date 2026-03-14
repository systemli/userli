<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Entity\ReservedName;
use App\Entity\Setting;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\Voucher;
use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\ApiScope;
use App\Enum\UserNotificationType;
use App\Enum\WebhookEvent;
use App\Helper\PasswordUpdater;
use App\Helper\TotpBackupCodeGenerator;
use App\Service\ApiTokenManager;
use App\Service\DomainGuesser;
use App\Service\OpenPgpKeyManager;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\SQLitePlatform;
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
        private readonly TotpBackupCodeGenerator $totpBackupCodeGenerator,
    ) {
        $this->sessionFactory = $this->getContainer()->get('session.factory');
        $this->cache = $this->getContainer()->get('cache.app');
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
        if ($this->manager->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            $schemaTool->dropDatabase();
        } else {
            $schemaTool->dropSchema($metadata);
        }
        $schemaTool->createSchema($metadata);

        // Clear identity map to prevent collisions when entities are re-created
        $this->manager->clear();

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
                        $time = ('NOW' === $value) ? new DateTimeImmutable() : new DateTimeImmutable($value);
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
                    case 'passwordChangeRequired':
                        $user->setPasswordChangeRequired((bool) $value);
                        break;
                    case 'totp_backup_codes':
                        $codes = $this->totpBackupCodeGenerator->generate();
                        $user->setTotpBackupCodes($codes);
                        $this->setPlaceholder('totp_backup_codes', $codes);
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
            $code = $data['code'] ?? 'default';
            $voucher = new Voucher($code);

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'user':
                        $user = $this->getUserRepository()->findByEmail($value);

                        if (null !== $user) {
                            $voucher->setUser($user);
                            $voucher->setDomain($user->getDomain());
                        }

                        break;
                }
            }

            // Set default domain if no domain was set via user
            if (null === $voucher->getDomain()) {
                $defaultDomain = $this->manager->getRepository(Domain::class)->getDefaultDomain();
                $voucher->setDomain($defaultDomain);
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

            $existing = $this->manager->getRepository(Setting::class)->findOneBy(['name' => $name]);

            if (null !== $existing) {
                $existing->setValue($value);
            } else {
                $setting = new Setting($name, $value);
                $this->manager->persist($setting);
            }

            $this->manager->flush();
        }

        // Clear settings cache so updated values take effect
        $this->cache->clear();
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
                        if (null !== $domain = $this->domainGuesser->guess($value)) {
                            $alias->setDomain($domain);
                        }
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

            if (isset($data['domains'])) {
                foreach (explode(',', $data['domains']) as $domainName) {
                    $domain = $this->manager->getRepository(Domain::class)->findOneBy(['name' => trim($domainName)]);
                    if (null === $domain) {
                        throw new RuntimeException(sprintf('Domain "%s" not found', trim($domainName)));
                    }
                    $endpoint->addDomain($domain);
                }
            }

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
     */
    public function iAmAuthenticatedAs(string $username): void
    {
        $driver = $this->getSession()->getDriver();

        // For real browser drivers (e.g. Panther), authenticate via the login form
        if (!$driver instanceof BrowserKitDriver) {
            $user = $this->getUserRepository()->findByEmail($username);
            if (null === $user) {
                throw new RuntimeException(sprintf('User "%s" does not exist', $username));
            }

            // Find the plain password from our test fixtures (default: "asdasd")
            $this->visit('/login');
            $this->fillField('_username', $username);
            $this->fillField('_password', 'asdasd');
            $this->pressButton('Sign in');

            return;
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
        $path = $this->replacePlaceholders($path);
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
     * @When /^I set the placeholder "([^"]*)" with property "([^"]*)" for alias "([^"]*)"$/
     */
    public function iSetPlaceholderForAlias(string $name, string $key, string $source): void
    {
        $alias = $this->manager->getRepository(Alias::class)->findOneBy(['source' => $source]);

        if (!$alias) {
            throw new RuntimeException(sprintf('Alias with source "%s" not found', $source));
        }

        $value = PropertyAccess::createPropertyAccessor()->getValue($alias, $key);

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
     * @When I set hidden field :fieldId to :value
     *
     * @throws Exception
     */
    public function iSetHiddenFieldTo(string $fieldId, string $value): void
    {
        $element = $this->getSession()->getPage()->find('css', sprintf('input[id="%s"]', $fieldId));

        if (null === $element) {
            throw new Exception(sprintf('Hidden field "%s" not found', $fieldId));
        }

        $element->setValue($value);
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

    /**
     * @Then /^the user "([^"]*)" should exist$/
     */
    public function theUserShouldExist(string $email): void
    {
        // Clear the entity manager to ensure we fetch fresh data
        $this->manager->clear();

        $user = $this->getUserRepository()->findByEmail($email);

        if (null === $user) {
            throw new RuntimeException(sprintf('User "%s" does not exist', $email));
        }
    }

    /**
     * @Then /^the user "([^"]*)" should have passwordChangeRequired$/
     */
    public function theUserShouldHavePasswordChangeRequired(string $email): void
    {
        $this->manager->clear();

        $user = $this->getUserRepository()->findByEmail($email);

        if (null === $user) {
            throw new RuntimeException(sprintf('User "%s" does not exist', $email));
        }

        if (!$user->isPasswordChangeRequired()) {
            throw new RuntimeException(sprintf('User "%s" does not have passwordChangeRequired set', $email));
        }
    }

    /**
     * @Then /^the user "([^"]*)" should have a mailCryptSecretBox$/
     */
    public function theUserShouldHaveAMailCryptSecretBox(string $email): void
    {
        $this->manager->clear();

        $user = $this->getUserRepository()->findByEmail($email);

        if (null === $user) {
            throw new RuntimeException(sprintf('User "%s" does not exist', $email));
        }

        if (!$user->hasMailCryptSecretBox()) {
            throw new RuntimeException(sprintf('User "%s" does not have a mailCryptSecretBox', $email));
        }
    }

    /**
     * @Then /^the user "([^"]*)" should not have totpConfirmed$/
     */
    public function theUserShouldNotHaveTotpConfirmed(string $email): void
    {
        $this->manager->clear();

        $user = $this->getUserRepository()->findByEmail($email);

        if (null === $user) {
            throw new RuntimeException(sprintf('User "%s" does not exist', $email));
        }

        if ($user->getTotpConfirmed()) {
            throw new RuntimeException(sprintf('User "%s" still has totpConfirmed set', $email));
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
                $string = str_replace($key, (string) $value, $string);
            }
        }

        return $string;
    }

    /**
     * @When the following ApiToken exists:
     */
    public function theFollowingApiTokenExists(TableNode $table): void
    {
        $apiTokenManager = $this->getContainer()->get(ApiTokenManager::class);

        foreach ($table->getColumnsHash() as $data) {
            $token = $data['token'] ?? '';
            $name = $data['name'] ?? 'Test Token';
            $scopeStrings = isset($data['scopes']) ? explode(',', $data['scopes']) : [];

            // Convert scope strings to ApiScope enum values
            $scopes = array_map(static fn (string $scope) => ApiScope::from(trim($scope))->value, $scopeStrings);

            $apiTokenManager->create($token, $name, $scopes);
        }
    }

    /**
     * @When the following OpenPgpKey exists:
     */
    public function theFollowingOpenPgpKeyExists(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $data) {
            $openPgpKey = new OpenPgpKey();

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'email':
                        $openPgpKey->setEmail($value);
                        break;
                    case 'keyId':
                        $openPgpKey->setKeyId($value);
                        break;
                    case 'keyFingerprint':
                        $openPgpKey->setKeyFingerprint($value);
                        break;
                    case 'keyData':
                        $openPgpKey->setKeyData($value);
                        break;
                    case 'uploader':
                        $user = $this->getUserRepository()->findByEmail($value);

                        if (null !== $user) {
                            $openPgpKey->setUploader($user);
                        }

                        break;
                }
            }

            if (null !== $openPgpKey->getEmail()) {
                [$localPart] = explode('@', $openPgpKey->getEmail());
                $openPgpKey->setWkdHash(OpenPgpKeyManager::wkdHash($localPart));

                $domain = $this->domainGuesser->guess($openPgpKey->getEmail());
                if (null === $domain) {
                    throw new RuntimeException(sprintf('No matching domain found for email "%s"', $openPgpKey->getEmail()));
                }
                $openPgpKey->setDomain($domain);
            }

            $this->manager->persist($openPgpKey);
            $this->manager->flush();
        }
    }

    /**
     * @Then the response header :header should equal :expected
     *
     * @throws UnsupportedDriverActionException
     */
    public function theResponseHeaderShouldEqual(string $header, string $expected): void
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        $actual = $driver->getClient()->getResponse()->headers->get($header);

        if ($actual !== $expected) {
            throw new RuntimeException(sprintf('Expected header "%s" to equal "%s" but got "%s"', $header, $expected, $actual ?? 'null'));
        }
    }

    /**
     * @When I set the host header to :host
     */
    public function iSetTheHostHeaderTo(string $host): void
    {
        $this->getSession()->setRequestHeader('Host', $host);
    }

    /**
     * @Then the response body should contain :text
     *
     * @throws UnsupportedDriverActionException
     */
    public function theResponseBodyShouldContain(string $text): void
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        $body = $driver->getClient()->getResponse()->getContent();

        if (!str_contains($body, $text)) {
            throw new RuntimeException(sprintf("Expected response body to contain \"%s\" but got:\n%s", $text, $body));
        }
    }

    public function getUserRepository(): ObjectRepository
    {
        return $this->manager->getRepository(User::class);
    }

    /**
     * @Then /^I should see the password strength feedback "([^"]*)"$/
     */
    public function iShouldSeeThePasswordStrengthFeedback(string $expectedText): void
    {
        $this->assertDriverSupportsJavascript();

        $feedback = $this->getSession()->getPage()->find('css', '[data-password-strength-target="feedback"]');
        if (null === $feedback) {
            throw new RuntimeException('Password strength feedback element not found');
        }

        $this->getSession()->wait(5000, sprintf(
            'document.querySelector(\'[data-password-strength-target="feedback"]\').textContent.includes(%s)',
            json_encode($expectedText)
        ));

        $actualText = $feedback->getText();
        if (!str_contains($actualText, $expectedText)) {
            throw new RuntimeException(sprintf('Expected password strength feedback to contain "%s", but got "%s"', $expectedText, $actualText));
        }
    }

    /**
     * @Then /^the password strength feedback should not be visible$/
     */
    public function thePasswordStrengthFeedbackShouldNotBeVisible(): void
    {
        $this->assertDriverSupportsJavascript();

        $feedback = $this->getSession()->getPage()->find('css', '[data-password-strength-target="feedback"]');
        if (null === $feedback) {
            throw new RuntimeException('Password strength feedback element not found');
        }

        if ($feedback->isVisible()) {
            throw new RuntimeException(sprintf('Expected password strength feedback to be hidden, but it is visible with text: "%s"', $feedback->getText()));
        }
    }

    /**
     * @Then /^I should see (\d+) active password strength segments$/
     */
    public function iShouldSeeActivePasswordStrengthSegments(int $expectedCount): void
    {
        $this->assertDriverSupportsJavascript();

        // Wait for the strength meter to update
        $this->getSession()->wait(2000, sprintf(
            'document.querySelectorAll(\'[data-password-strength-target="segment"]:not(.bg-gray-200)\').length === %d',
            $expectedCount
        ));

        $segments = $this->getSession()->getPage()->findAll('css', '[data-password-strength-target="segment"]');
        $activeCount = 0;

        foreach ($segments as $segment) {
            $class = $segment->getAttribute('class') ?? '';
            if (!str_contains($class, 'bg-gray-200')) {
                ++$activeCount;
            }
        }

        if ($activeCount !== $expectedCount) {
            throw new RuntimeException(sprintf('Expected %d active password strength segments, but found %d', $expectedCount, $activeCount));
        }
    }

    /**
     * @Then /^I wait for the element "([^"]*)" to appear$/
     */
    public function iWaitForTheElementToAppear(string $cssSelector): void
    {
        $this->assertDriverSupportsJavascript();

        $result = $this->getSession()->wait(5000, sprintf(
            'document.querySelector(%s) !== null',
            json_encode($cssSelector)
        ));

        if (!$result) {
            throw new RuntimeException(sprintf('Element "%s" did not appear within 5 seconds', $cssSelector));
        }
    }

    /**
     * @When /^I type "([^"]*)" into the field "([^"]*)"$/
     */
    public function iTypeIntoTheField(string $value, string $field): void
    {
        $this->assertDriverSupportsJavascript();

        $element = $this->getSession()->getPage()->findField($field);

        if (null === $element) {
            throw new RuntimeException(sprintf('Field "%s" not found', $field));
        }

        // Focus the field first (triggers Stimulus connect/focus events)
        $element->focus();

        // Type character by character to trigger input events
        foreach (str_split($value) as $char) {
            $currentValue = $element->getValue().$char;
            $element->setValue($currentValue);
        }
    }

    /**
     * @Then /^I wait (\d+) milliseconds$/
     */
    public function iWaitMilliseconds(int $milliseconds): void
    {
        $this->getSession()->wait($milliseconds);
    }

    /**
     * @Then /^I wait for text "([^"]*)" to appear$/
     */
    public function iWaitForTextToAppear(string $text): void
    {
        $this->assertDriverSupportsJavascript();

        $result = $this->getSession()->wait(
            10000,
            sprintf('document.body && document.body.textContent.includes(%s)', json_encode($text))
        );

        if (!$result) {
            throw new RuntimeException(sprintf('Text "%s" did not appear within 10 seconds', $text));
        }
    }

    /**
     * @When /^I press the "([^"]*)" button$/
     */
    public function iPressTheButton(string $ariaLabel): void
    {
        $element = $this->getSession()->getPage()->find('css', sprintf('button[aria-label="%s"]', addcslashes($ariaLabel, '"')));

        if (null === $element) {
            throw new RuntimeException(sprintf('Button with aria-label "%s" not found', $ariaLabel));
        }

        $element->click();
    }

    /**
     * @Then /^I wait for the modal to appear$/
     */
    public function iWaitForTheModalToAppear(): void
    {
        $this->assertDriverSupportsJavascript();

        $result = $this->getSession()->wait(5000,
            '(function() {'
            .'  var overlays = document.querySelectorAll(\'[data-modal-target="overlay"], [data-delete-modal-target="overlay"]\');'
            .'  return Array.from(overlays).some(function(el) { return !el.classList.contains("hidden") && el.classList.contains("opacity-100"); });'
            .'})()'
        );

        if (!$result) {
            throw new RuntimeException('Modal did not appear within 5 seconds');
        }
    }

    /**
     * @Then /^I wait for the modal to close$/
     */
    public function iWaitForTheModalToClose(): void
    {
        $this->assertDriverSupportsJavascript();

        $result = $this->getSession()->wait(5000,
            '(function() {'
            .'  var overlays = document.querySelectorAll(\'[data-modal-target="overlay"], [data-delete-modal-target="overlay"]\');'
            .'  return overlays.length === 0 || Array.from(overlays).every(function(el) { return el.classList.contains("hidden"); });'
            .'})()'
        );

        if (!$result) {
            throw new RuntimeException('Modal did not close within 5 seconds');
        }
    }

    /**
     * @Then /^I should see "([^"]*)" in the modal$/
     */
    public function iShouldSeeInTheModal(string $text): void
    {
        $this->assertDriverSupportsJavascript();

        $dialog = $this->findVisibleModalDialog();

        if (!str_contains($dialog->getText(), $text)) {
            throw new RuntimeException(sprintf('Text "%s" not found in modal dialog', $text));
        }
    }

    /**
     * @When /^I click "([^"]*)" in the modal$/
     */
    public function iClickInTheModal(string $buttonText): void
    {
        $this->assertDriverSupportsJavascript();

        $dialog = $this->findVisibleModalDialog();
        $button = $dialog->findButton($buttonText);

        if (null === $button) {
            throw new RuntimeException(sprintf('Button "%s" not found in modal dialog', $buttonText));
        }

        $button->click();
    }

    /**
     * @When /^I fill in "([^"]*)" with "([^"]*)" in the modal$/
     */
    public function iFillInWithInTheModal(string $field, string $value): void
    {
        $this->assertDriverSupportsJavascript();

        $dialog = $this->findVisibleModalDialog();
        $fieldElement = $dialog->findField($field);

        if (null === $fieldElement) {
            throw new RuntimeException(sprintf('Field "%s" not found in modal dialog', $field));
        }

        $fieldElement->setValue($value);
    }

    /**
     * @When /^I press the (\d+(?:st|nd|rd|th)) "([^"]*)" button$/
     */
    public function iPressTheNthButton(string $nth, string $button): void
    {
        $index = match ($nth) {
            '1st' => 0,
            '2nd' => 1,
            '3rd' => 2,
            default => ((int) $nth) - 1,
        };

        $buttons = $this->getSession()->getPage()->findAll('named', ['button', $button]);

        if (!isset($buttons[$index])) {
            throw new RuntimeException(sprintf('The %s button "%s" was not found on the page (found %d)', $nth, $button, count($buttons)));
        }

        $buttons[$index]->press();
    }

    /**
     * Finds the currently visible modal dialog element.
     *
     * Supports both `data-modal-target="dialog"` and
     * `data-delete-modal-target="dialog"` selectors.
     */
    private function findVisibleModalDialog(): NodeElement
    {
        $page = $this->getSession()->getPage();
        $selectors = ['[data-modal-target="dialog"]', '[data-delete-modal-target="dialog"]'];

        foreach ($selectors as $selector) {
            $dialogs = $page->findAll('css', $selector);
            foreach ($dialogs as $dialog) {
                if ($dialog->isVisible()) {
                    return $dialog;
                }
            }
        }

        throw new RuntimeException('No visible modal dialog found');
    }

    private function assertDriverSupportsJavascript(): void
    {
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step requires a JavaScript-capable browser driver', $driver);
        }
    }
}
