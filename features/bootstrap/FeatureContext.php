<?php

use App\Entity\Alias;
use App\Entity\User;
use App\Guesser\DomainGuesser;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @author louis <louis@systemli.org>
 */
class FeatureContext extends MinkContext
{
    use KernelDictionary;

    /**
     * @var array
     */
    private $placeholders = array();

    /**
     * @Given /^the database is clean$/
     */
    public function theDatabaseIsClean()
    {
        $em = $this->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @When the following Domain exists:
     */
    public function theFollowingDomainExists(TableNode $table)
    {
        foreach ($table->getColumnsHash() as $data) {
            /** @var $domain \App\Entity\Domain */
            $domain = new \App\Entity\Domain();

            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'name':
                        $domain->setName($value);
                }
            }

            $this->getManager()->persist($domain);
            $this->getManager()->flush();
        }
    }

    /**
     * @When the following User exists:
     */
    public function theFollowingUserExists(TableNode $table)
    {
        foreach ($table->getColumnsHash() as $data) {
            /** @var $user \App\Entity\User */
            $user = new User();

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'email':
                        $user->setEmail($value);

                        if (null !== $domain = $this->getDomainGuesser()->guess($value)) {
                            $user->setDomain($domain);
                        }

                        break;
                    case 'password':
                        $user->setPlainPassword($value);
                        $this->getContainer()->get('App\Helper\PasswordUpdater')->updatePassword($user);
                        break;
                    case 'roles':
                        $roles = explode(',', $value);
                        $user->setRoles($roles);
                        break;
                    case 'hash':
                        $user->setPassword($value);
                        break;
                }
            }

            $this->getManager()->persist($user);
            $this->getManager()->flush();

        }
    }

    /**
     * @When the following Voucher exists:
     */
    public function theFollowingVoucherExists(TableNode $table)
    {
        foreach ($table->getColumnsHash() as $data) {
            /** @var $voucher \App\Entity\Voucher */
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

            $this->getManager()->persist($voucher);
            $this->getManager()->flush();
        }
    }

    /**
     * @When the following Alias exists:
     */
    public function theFollowingAliasExists(TableNode $table)
    {
        foreach ($table->getColumnsHash() as $data) {
            /** @var $alias Alias */
            $alias = new Alias();

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'source':
                        $alias->setSource($value);
                        break;
                    case 'destination':
                        $alias->setDestination($value);
                        break;
                }

                if (null !== $domain = $this->getDomainGuesser()->guess($value)) {
                    $alias->setDomain($domain);
                }
            }

            $this->getManager()->persist($alias);
            $this->getManager()->flush();
        }
    }

    /**
     * @When the following ReservedName exists:
     */
    public function theFollowingReservedNameExists(TableNode $table)
    {
        foreach ($table->getColumnsHash() as $data) {
            /** @var $reservedName \App\Entity\ReservedName */
            $reservedName = new \App\Entity\ReservedName();

            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'name':
                        $reservedName->setName($value);
                }
            }

            $this->getManager()->persist($reservedName);
            $this->getManager()->flush();
        }
    }

    /**
     * @Given /^I am authenticated as "([^"]*)"$/
     */
    public function iAmAuthenticatedAs($username)
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        $client = $driver->getClient();
        $client->getCookieJar()->set(new Cookie(session_name(), true));

        $session = $this->getContainer()->get('session');

        $user = $this->getUserRepository()->findByEmail($username);
        $providerKey = "default";

        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $session->set('_security_' . $providerKey, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }

    /**
     * @When /^I set the placeholder "([^"]*)" with property "([^"]*)" for "([^"]*)"$/
     */
    public function iSetPlaceholderForUser($name, $key, $email)
    {
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
    public function iSetPlaceholderFromUrl($name, $key)
    {
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
     * @param $page
     */
    public function visit($page)
    {
        $page = $this->replacePlaceholders($page);

        parent::visit($page);
    }

    /**
     * @Given /^set the HTTP-Header "([^"]*)" to "([^"]*)"$/
     */
    public function setHttpHeaderto($name, $value)
    {
        $this->getSession()->setRequestHeader($name, $value);
    }

    /**
     * @param $key
     * @param $value
     */
    public function setPlaceholder($key, $value)
    {
        $this->placeholders[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getPlaceholder($key)
    {
        return (isset($this->placeholders[$key])) ? $this->placeholders[$key] : null;
    }

    /**
     * @return array
     */
    public function getAllPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function replacePlaceholders($string)
    {
        foreach ($this->getAllPlaceholders() as $key => $value) {
            if (strpos($string, $key) !== false) {
                $string = str_replace($key, $value, $string);
            }
        }

        return $string;
    }

    /**
     * @return \App\Repository\UserRepository
     */
    private function getUserRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('App:User');
    }

    /**
     * @return DomainGuesser
     */
    private function getDomainGuesser()
    {
        return new DomainGuesser($this->getManager());
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    private function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
