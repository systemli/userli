<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\RawMinkContext;
use RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use const JSON_ERROR_NONE;
use const JSON_PRETTY_PRINT;

/**
 * Context for API testing with JSON requests and Bearer token authentication.
 */
class ApiContext extends RawMinkContext implements Context
{
    private ?string $authToken = null;

    /**
     * @Given I have a valid API token :token
     */
    public function iHaveAValidApiToken(string $token): void
    {
        $this->authToken = $token;
    }

    /**
     * @Given I have an invalid API token
     */
    public function iHaveAnInvalidApiToken(): void
    {
        $this->authToken = 'invalid-token-12345';
    }

    /**
     * @Given I have no API token
     */
    public function iHaveNoApiToken(): void
    {
        $this->authToken = null;
    }

    /**
     * @When I send a :method request to :path
     *
     * @throws UnsupportedDriverActionException
     */
    public function iSendARequestTo(string $method, string $path): void
    {
        $this->sendRequest($method, $path);
    }

    /**
     * @When I send a :method request to :path with form data:
     *
     * @throws UnsupportedDriverActionException
     */
    public function iSendARequestToWithFormData(string $method, string $path, TableNode $table): void
    {
        $data = $table->getRowsHash();
        $this->sendRequest($method, $path, $data);
    }

    /**
     * @When I send a :method request to :path with JSON:
     *
     * @throws UnsupportedDriverActionException
     */
    public function iSendARequestToWithJson(string $method, string $path, PyStringNode $json): void
    {
        $this->sendRequest($method, $path, [], $json->getRaw());
    }

    /**
     * @When I send a :method request to :path with raw body:
     *
     * @throws UnsupportedDriverActionException
     */
    public function iSendARequestToWithRawBody(string $method, string $path, PyStringNode $body): void
    {
        $this->sendRequest($method, $path, [], $body->getRaw(), false);
    }

    /**
     * @Then the response status code should equal :code
     */
    public function theResponseStatusCodeShouldEqual(int $code): void
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        $client = $driver->getClient();
        $actual = $client->getResponse()->getStatusCode();

        if ($actual !== $code) {
            throw new RuntimeException(sprintf('Expected status code %d but got %d. Response: %s', $code, $actual, substr($client->getResponse()->getContent(), 0, 500)));
        }
    }

    /**
     * @Then the response should be JSON
     */
    public function theResponseShouldBeJson(): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(sprintf('Response is not valid JSON: %s. Content: %s', json_last_error_msg(), substr($content, 0, 200)));
        }
    }

    /**
     * @Then the JSON response should equal:
     */
    public function theJsonResponseShouldEqual(PyStringNode $expected): void
    {
        $actual = $this->getSession()->getPage()->getContent();
        $expectedData = json_decode($expected->getRaw(), true);
        $actualData = json_decode($actual, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(sprintf('Response is not valid JSON: %s', json_last_error_msg()));
        }

        if ($expectedData !== $actualData) {
            throw new RuntimeException(sprintf("JSON mismatch.\nExpected: %s\nActual: %s", json_encode($expectedData, JSON_PRETTY_PRINT), json_encode($actualData, JSON_PRETTY_PRINT)));
        }
    }

    /**
     * @Then the JSON response should be:
     */
    public function theJsonResponseShouldBe(PyStringNode $expected): void
    {
        $this->theJsonResponseShouldEqual($expected);
    }

    /**
     * @Then the JSON path :path should equal :expected
     */
    public function theJsonPathShouldEqual(string $path, string $expected): void
    {
        $value = $this->getJsonPathValue($path);

        // Handle type conversion for comparison
        if (is_numeric($expected)) {
            if (str_contains($expected, '.')) {
                $expected = (float) $expected;
            } else {
                $expected = (int) $expected;
            }
        } elseif ('true' === $expected) {
            $expected = true;
        } elseif ('false' === $expected) {
            $expected = false;
        } elseif ('null' === $expected) {
            $expected = null;
        }

        if ($value !== $expected) {
            throw new RuntimeException(sprintf('JSON path "%s" expected "%s" but got "%s"', $path, var_export($expected, true), var_export($value, true)));
        }
    }

    /**
     * @Then the JSON path :path should not be empty
     */
    public function theJsonPathShouldNotBeEmpty(string $path): void
    {
        $value = $this->getJsonPathValue($path);

        if (empty($value)) {
            throw new RuntimeException(sprintf('JSON path "%s" is empty or null', $path));
        }
    }

    /**
     * @Then the JSON path :path should be empty
     */
    public function theJsonPathShouldBeEmpty(string $path): void
    {
        $value = $this->getJsonPathValue($path);

        if (!empty($value)) {
            throw new RuntimeException(sprintf('JSON path "%s" expected to be empty but got "%s"', $path, var_export($value, true)));
        }
    }

    /**
     * @Then the JSON path :path should exist
     */
    public function theJsonPathShouldExist(string $path): void
    {
        $this->getJsonPathValue($path);
    }

    /**
     * @Then the JSON response should contain :count items
     */
    public function theJsonResponseShouldContainItems(int $count): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new RuntimeException('JSON response is not an array');
        }

        $actualCount = count($data);
        if ($actualCount !== $count) {
            throw new RuntimeException(sprintf('Expected %d items but got %d', $count, $actualCount));
        }
    }

    /**
     * @throws UnsupportedDriverActionException
     */
    private function sendRequest(string $method, string $path, array $parameters = [], ?string $content = null, bool $setContentType = true): void
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException('This step is only supported by the BrowserKitDriver', $driver);
        }

        $client = $driver->getClient();

        $server = [
            'HTTP_ACCEPT' => 'application/json',
        ];

        if (null !== $content && $setContentType) {
            $server['CONTENT_TYPE'] = 'application/json';
        }

        if (null !== $this->authToken) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$this->authToken;
        }

        $client->request(
            strtoupper($method),
            $path,
            $parameters,
            [],
            $server,
            $content
        );
    }

    private function getJsonPathValue(string $path): mixed
    {
        $content = $this->getSession()->getPage()->getContent();
        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(sprintf('Response is not valid JSON: %s', json_last_error_msg()));
        }

        // Convert dot notation to PropertyAccess format: "body.user" -> "[body][user]"
        $accessPath = '['.str_replace('.', '][', $path).']';

        $accessor = PropertyAccess::createPropertyAccessor();

        if (!$accessor->isReadable($data, $accessPath)) {
            throw new RuntimeException(sprintf('JSON path "%s" does not exist in response', $path));
        }

        return $accessor->getValue($data, $accessPath);
    }
}
