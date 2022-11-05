<?php

namespace App\Tests\Behat;

use App\Entity\Alias;
use App\Entity\User;
use App\Guesser\DomainGuesser;
use App\Helper\PasswordUpdater;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
* This context class contains the definitions of the steps used by the demo
* feature file. Learn how to get started with Behat and BDD on Behat's website.
*
* @see http://behat.org/en/latest/quick_start.html
*/
class FeatureContext extends MinkContext
{
    private KernelInterface $kernel;
    private EntityManagerInterface $manager;
    private PasswordUpdater $passwordUpdater;
    private DomainGuesser $domainGuesser;
    private string $dbPlatform;
    private array $placeholders = [];
    private ?string $output;
    private array $requestParams = [];

    public function __construct(KernelInterface $kernel,
                                EntityManagerInterface $manager,
                                PasswordUpdater $passwordUpdater,
                                DomainGuesser $domainGuesser) {
        $this->kernel = $kernel;
        $this->manager = $manager;
        $this->passwordUpdater = $passwordUpdater;
        $this->domainGuesser = $domainGuesser;
        $this->dbPlatform = $this->manager->getConnection()->getDatabasePlatform()->getName();
    }

    /**
     * @Given /^the database is clean$/
     * @throws ToolsException
     */
    public function theDatabaseIsClean(): void {
        $schemaTool = new SchemaTool($this->manager);
        $metadata = $this->manager->getMetadataFactory()->getAllMetadata();

        // dropSchema leads to errors with sqlite backend since DBAL 2.10.3
        if ($this->dbPlatform === 'sqlite') {
            $schemaTool->dropDatabase();
        } else {
            $schemaTool->dropSchema($metadata);
        }
        $schemaTool->createSchema($metadata);
    }

    /**
     * @When the following Domain exists:
     */
    public function theFollowingDomainExists(TableNode $table): void {
        foreach ($table->getColumnsHash() as $data) {
            $domain = new \App\Entity\Domain();

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
    public function theFollowingUserExists(TableNode $table): void {
        foreach ($table->getColumnsHash() as $data) {
            $user = new User();

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'email':
                        $user->setEmail($value);

                        if (null !== $domain = $this->domainGuesser->guess($value)) {
                            $user->setDomain($domain);
                        }

                        break;
                    case 'password':
                        $user->setPlainPassword($value);
                        $this->passwordUpdater->updatePassword($user);
                        break;
                    case 'roles':
                        $roles = explode(',', $value);
                        $user->setRoles($roles);
                        break;
                    case 'hash':
                        $user->setPassword($value);
                        break;
                    case 'quota':
                        $user->setQuota($value);
                        break;
                    case 'recoveryStartTime':
                        $time = new \DateTime();
                        if ('NOW' !== 'value') {
                            $time = $time->modify($value);
                        }
                        $user->setRecoveryStartTime($time);
                        break;
                    case 'recoverySecretBox':
                        $user->setRecoverySecretBox($value);
                        break;
                    case 'mailCrypt':
                        $user->setMailCrypt($value);
                        break;
                    case 'mailCryptSecretBox':
                        $user->setMailCryptSecretBox($value);
                        break;
                    case 'mailCryptPublicKey':
                        $user->setMailCryptPublicKey($value);
                        break;
                    case 'totpConfirmed':
                        $user->setTotpConfirmed($value);
                        break;
                    case 'totpSecret':
                        $user->setTotpSecret($value);
                        break;
                    case 'totp_backup_codes':
                        $user->generateBackupCodes();
                        $this->setPlaceholder('totp_backup_codes', $user->getBackupCodes());
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
    public function theFollowingVoucherExists(TableNode $table): void {
        foreach ($table->getColumnsHash() as $data) {
            $voucher = new \App\Entity\Voucher();

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
     * @When the following Alias exists:
     */
    public function theFollowingAliasExists(TableNode $table): void {
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
                        $alias->setDeleted($value);
                        break;
                    case 'random':
                        $alias->setRandom($value);
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
    public function theFollowingReservedNameExists(TableNode $table): void {
        foreach ($table->getColumnsHash() as $data) {
            /** @var $reservedName \App\Entity\ReservedName */
            $reservedName = new \App\Entity\ReservedName();

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
     * @Given /^I am authenticated as "([^"]*)"$/
     * @throws UnsupportedDriverActionException
     */
    public function iAmAuthenticatedAs(string $username): void {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        $client = $driver->getClient();
        $client->getCookieJar()->set(new Cookie(session_name(), true));

        $session = $this->kernel->getContainer()->get('session');

        $user = $this->getUserRepository()->findByEmail($username);
        $providerKey = "default";

        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $session->set('_security_' . $providerKey, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }

    /**
     * @When /^I have the request params for "([^"]*)":$/
     */
    public function iHaveTheRequestParams(string $field, TableNode $table): void {
        foreach ($table->getRowsHash() as $var => $value) {
            $this->requestParams[$field][$var] = $value;
        }
    }

    /**
     * @When /^I request "(GET|PUT|POST|DELETE|PATCH) ([^"]*)"$/
     * @throws UnsupportedDriverActionException
     */
    public function iRequest(string $httpMethod, string $path): void {
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
    public function iSetPlaceholderForUser(string $name, string $key, string $email): void {
        $user = $this->getUserRepository()->findByEmail($email);

        if (!$user) {
            throw new UsernameNotFoundException();
        }

        $value = PropertyAccess::createPropertyAccessor()->getValue($user, $key);

        $this->setPlaceholder($name, $value);
    }

    /**
     * @When /^I set the placeholder "([^"]*)" from url parameter "([^"]*)"$/
     */
    public function iSetPlaceholderFromUrl(string $name, string $key): void {
        $queryParams = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY);

        if (null !== $queryParams) {
            foreach (explode("=", $queryParams) as $param => $value) {
                if ($param === $key) {
                    $this->setPlaceholder($name, $value);
                }
            }
        }
    }

    /**
     * @When /^File "([^"]*)" exists with content:$/
     */
    public function touchFile(string $path, PyStringNode $content): void
    {
        file_put_contents($path, $content);
    }

    /**
     * @param $page
     */
    public function visit($page): void {
        $page = $this->replacePlaceholders($page);

        parent::visit($page);
    }

    /**
     * @Given /^set the HTTP-Header "([^"]*)" to "([^"]*)"$/
     */
    public function setHttpHeaderto(string $name, string $value): void {
        $this->getSession()->setRequestHeader($name, $value);
    }

    /**
     * @When I run console command :command
     */
    public function iRunConsoleCommand(string $command): void {
        $this->output = shell_exec("php bin/console " . $command);
    }

    /**
     * @Then I should see :string in the console output
     */
    public function iShouldSeeInTheConsoleOutput(string $string): void {
        $output = preg_replace('/\r\n|\r|\n/', '\\n', $this->output);
        if (strpos($output, $string) === false) {
            throw new \RuntimeException(sprintf('Did not see "%s" in console output "%s"', $string, $output));
        }
    }

    /**
     * @Then I should see regex :string in the console output
     */
    public function iShouldSeeRegexInTheConsoleOutput(string $string): void {
        if (!preg_match($string, $this->output)) {
            throw new \RuntimeException(sprintf('Did not see regex "%s" in console output "%s"', $string, $this->output));
        }
    }

    /**
     * @Then I should not see :string in the console output
     */
    public function iShouldNotSeeInTheConsoleOutput(string $string): void {
        $output = preg_replace('/\r\n|\r|\n/', '\\n', $this->output);
        if (strpos($output, $string) === true) {
            throw new \RuntimeException(sprintf('Did see "%s" in console output "%s"', $string, $output));
        }
    }

    /**
     * @Then I should see empty console output
     */
    public function iShouldSeeEmptyConsoleOutput(): void {
        if ($this->output !== null) {
            throw new \RuntimeException(sprintf('Did not see empty console output: "%s"', $this->output));
        }
    }

    /**
     * @Then I enter TOTP backup code
     */
    public function iEnterTotpBackupCode(): void
    {
        $totpBackupCodes = $this->getPlaceholder('totp_backup_codes');
        if (!$totpBackupCodes) {
            throw new \RuntimeException('No TOTP backup codes cached');
        }
        $this->fillField('_auth_code', $totpBackupCodes[0]);
    }

    /**
     * @Then /^File "([^"]*)" should exist$/
     */
    public function fileExists(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('File doesn\'t exist: "%s"', $path));
        }
    }

    /**
     * @Then /^File "([^"]*)" should not exist$/
     */
    public function fileNoExists(string $path): void
    {
        if (is_file($path)) {
            throw new \RuntimeException(sprintf('File exists: "%s"', $path));
        }
    }

    /**
     * @param string $key
     * @param $value
     */
    public function setPlaceholder(string $key, $value): void
    {
        $this->placeholders[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getPlaceholder(string $key)
    {
        return $this->placeholders[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getAllPlaceholders(): array {
        return $this->placeholders;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function replacePlaceholders(string $string): string
    {
        foreach ($this->getAllPlaceholders() as $key => $value) {
            if (strpos($string, $key) !== false) {
                $string = str_replace($key, $value, $string);
            }
        }

        return $string;
    }

    /**
     * @return ObjectRepository
     */
    public function getUserRepository(): ObjectRepository
    {
        return $this->manager->getRepository('App:User');
    }
}
