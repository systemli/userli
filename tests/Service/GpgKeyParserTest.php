<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\GpgKeyResult;
use App\Exception\GpgKeyParserException;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Service\GpgKeyParser;
use Crypt_GPG;
use Crypt_GPG_Exception;
use Crypt_GPG_FileException;
use Crypt_GPG_Key;
use Crypt_GPG_KeyNotFoundException;
use Crypt_GPG_NoDataException;
use Crypt_GPG_SubKey;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

class GpgKeyParserTest extends TestCase
{
    private string $email = 'alice@example.org';
    /**
     * An OpenPGP key with several UIDs to check that we
     *   - keep UIDs with correct email with realname
     *   - keep UIDs with correct email without realname
     *   - drop UIDs with wrong email
     *   - drop UIDs with wrong email but correct email in realname.
     *
     * pub   rsa3072 2020-10-21 [SC] [expires: 2022-10-21]
     *       D26D7D3E94B086E29893A4F391285F31F93A680B
     * uid           <alice@example.org> <anotheralice@example.org>>
     * uid           anotheralice@example.org
     * uid           Alice <alice@example.org>
     * uid           Alice2 <alice@example.org>
     * uid           Another Alice <anotheralice@example.org>
     * uid           alice@example.org
     * sub   rsa3072 2020-10-21 [E] [expires: 2022-10-21]
     */
    private string $validKeyAscii = '-----BEGIN PGP PUBLIC KEY BLOCK-----

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
=QPao
-----END PGP PUBLIC KEY BLOCK-----';
    private string $validKeyId = '91285F31F93A680B';
    private string $validKeyFingerprint = 'D26D 7D3E 94B0 86E2 9893  A4F3 9128 5F31 F93A 680B';
    private string $validExpireTime = '@1666343513';
    private string $brokenKeyAscii = 'brokenkeystring';
    private string $otherKeyAscii = '-----BEGIN PGP PUBLIC KEY BLOCK-----

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
hx77izIzoqOrwcQ7yTyR+Uo=
=hivm
-----END PGP PUBLIC KEY BLOCK-----';
    private string $twoKeysAscii = '-----BEGIN PGP PUBLIC KEY BLOCK-----

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
=z1eK
-----END PGP PUBLIC KEY BLOCK-----';

    // -- Integration tests (using real GPG binary) --

    public function testValidKey(): void
    {
        $parser = new GpgKeyParser();
        $result = $parser->parse($this->email, $this->validKeyAscii);

        self::assertInstanceOf(GpgKeyResult::class, $result);
        self::assertSame($this->email, $result->email);
        self::assertSame($this->validKeyId, $result->keyId);
        self::assertSame($this->validKeyFingerprint, $result->fingerprint);
        self::assertEquals(new DateTimeImmutable($this->validExpireTime), $result->expireTime);
        self::assertNotEmpty($result->keyData);
    }

    public function testBrokenKey(): void
    {
        $this->expectException(NoGpgDataException::class);

        new GpgKeyParser()->parse($this->email, $this->brokenKeyAscii);
    }

    #[DataProvider('emailWithFilterOperatorsProvider')]
    public function testParseRejectsEmailWithFilterOperators(string $email): void
    {
        $this->expectException(GpgKeyParserException::class);
        $this->expectExceptionMessage('contains characters not allowed in a GnuPG key lookup');

        new GpgKeyParser()->parse($email, $this->validKeyAscii);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emailWithFilterOperatorsProvider(): iterable
    {
        yield 'pipe (or-operator)' => ['a||b@example.org'];
        yield 'ampersand (and-operator)' => ['a&&b@example.org'];
        yield 'parenthesis (grouping)' => ['a(b)c@example.org'];
        yield 'bang (negation)' => ['a!b@example.org'];
        yield 'backslash' => ['a\\b@example.org'];
        yield 'single pipe' => ['a|b@example.org'];
    }

    public function testOtherKey(): void
    {
        $this->expectException(NoGpgKeyForUserException::class);

        new GpgKeyParser()->parse($this->email, $this->otherKeyAscii);
    }

    public function testTwoKeys(): void
    {
        $this->expectException(MultipleGpgKeysForUserException::class);

        new GpgKeyParser()->parse($this->email, $this->twoKeysAscii);
    }

    // -- Unit tests (mocked GPG) --

    public function testParseThrowsGpgKeyParserExceptionOnGpgFileException(): void
    {
        $parser = $this->createParserWithGpgException(
            new Crypt_GPG_FileException('bad homedir')
        );

        $this->expectException(GpgKeyParserException::class);
        $this->expectExceptionMessage('Failed to read GnuPG home directory:');

        $parser->parse($this->email, 'keydata');
    }

    public function testParseThrowsNoGpgDataExceptionOnImportFailure(): void
    {
        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willThrowException(new Crypt_GPG_NoDataException('no data'));

        $parser = $this->createParserWithMockedGpg($gpg);

        $this->expectException(NoGpgDataException::class);
        $this->expectExceptionMessage('Failed to import WKD key:');

        $parser->parse($this->email, 'keydata');
    }

    public function testParseThrowsGpgKeyParserExceptionOnGetKeysFailure(): void
    {
        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willReturn([]);
        $gpg->method('getKeys')->willThrowException(new Crypt_GPG_Exception('keys error'));

        $parser = $this->createParserWithMockedGpg($gpg);

        $this->expectException(GpgKeyParserException::class);
        $this->expectExceptionMessage('Failed to read keys:');

        $parser->parse($this->email, 'keydata');
    }

    public function testParseThrowsNoGpgKeyForUserExceptionWhenNoKeysFound(): void
    {
        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willReturn([]);
        $gpg->method('getKeys')->willReturn([]);

        $parser = $this->createParserWithMockedGpg($gpg);

        $this->expectException(NoGpgKeyForUserException::class);
        $this->expectExceptionMessage('No key found for');

        $parser->parse($this->email, 'keydata');
    }

    public function testParseThrowsMultipleGpgKeysForUserException(): void
    {
        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willReturn([]);
        $gpg->method('getKeys')->willReturn([new Crypt_GPG_Key(), new Crypt_GPG_Key()]);

        $parser = $this->createParserWithMockedGpg($gpg);

        $this->expectException(MultipleGpgKeysForUserException::class);
        $this->expectExceptionMessage('More than one keys found for');

        $parser->parse($this->email, 'keydata');
    }

    public function testParseThrowsGpgKeyParserExceptionOnExportFailure(): void
    {
        $key = $this->createKeyWithPrimaryKey('AABBCCDD', 1666343513);

        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willReturn([]);
        $gpg->method('getKeys')->willReturn([$key]);
        $gpg->method('exportPublicKey')->willThrowException(
            new Crypt_GPG_KeyNotFoundException('not found', 0, 'AABBCCDD')
        );

        $parser = $this->createParserWithMockedGpg($gpg);

        $this->expectException(GpgKeyParserException::class);
        $this->expectExceptionMessage('Failed to export key:');

        $parser->parse($this->email, 'keydata');
    }

    public function testParseThrowsGpgKeyParserExceptionWhenNoPrimaryKey(): void
    {
        $key = new Crypt_GPG_Key();

        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willReturn([]);
        $gpg->method('getKeys')->willReturn([$key]);
        $gpg->method('exportPublicKey')->willReturn('exported-key-data');

        $parser = $this->createParserWithMockedGpg($gpg);

        $this->expectException(GpgKeyParserException::class);
        $this->expectExceptionMessage('Failed to get GnuPG key ID.');

        $parser->parse($this->email, 'keydata');
    }

    public function testParseThrowsGpgKeyParserExceptionOnFingerprintFailure(): void
    {
        $key = $this->createKeyWithPrimaryKey('AABBCCDD', 1666343513);

        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willReturn([]);
        $gpg->method('getKeys')->willReturn([$key]);
        $gpg->method('exportPublicKey')->willReturn('exported-key-data');
        $gpg->method('getFingerprint')->willThrowException(new Crypt_GPG_Exception('fp error'));

        $parser = $this->createParserWithMockedGpg($gpg);

        $this->expectException(GpgKeyParserException::class);
        $this->expectExceptionMessage('Failed to get GnuPG key fingerprint:');

        $parser->parse($this->email, 'keydata');
    }

    public function testParseSuccessWithMockedGpg(): void
    {
        $key = $this->createKeyWithPrimaryKey('AABBCCDD11223344', 1666343513);

        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willReturn([]);
        $gpg->method('getKeys')->willReturn([$key]);
        $gpg->method('exportPublicKey')->willReturn('exported-key-data');
        $gpg->method('getFingerprint')->willReturn('AAAA BBBB CCCC DDDD');

        $parser = $this->createParserWithMockedGpg($gpg);
        $result = $parser->parse($this->email, 'keydata');

        self::assertInstanceOf(GpgKeyResult::class, $result);
        self::assertSame($this->email, $result->email);
        self::assertSame('AABBCCDD11223344', $result->keyId);
        self::assertSame('AAAA BBBB CCCC DDDD', $result->fingerprint);
        self::assertEquals(new DateTimeImmutable('@1666343513'), $result->expireTime);
        self::assertSame(base64_encode('exported-key-data'), $result->keyData);
    }

    public function testParseSuccessWithNoExpiry(): void
    {
        $key = $this->createKeyWithPrimaryKey('AABBCCDD11223344', 0);

        $gpg = $this->createStub(Crypt_GPG::class);
        $gpg->method('importKey')->willReturn([]);
        $gpg->method('getKeys')->willReturn([$key]);
        $gpg->method('exportPublicKey')->willReturn('exported-key-data');
        $gpg->method('getFingerprint')->willReturn('AAAA BBBB CCCC DDDD');

        $parser = $this->createParserWithMockedGpg($gpg);
        $result = $parser->parse($this->email, 'keydata');

        self::assertNull($result->expireTime);
    }

    private function createKeyWithPrimaryKey(string $keyId, int $expirationDate): Crypt_GPG_Key
    {
        $subKey = new Crypt_GPG_SubKey();
        $subKey->setId($keyId);
        $subKey->setExpirationDate($expirationDate);

        $key = new Crypt_GPG_Key();
        $key->addSubKey($subKey);

        return $key;
    }

    /**
     * Creates a GpgKeyParser that throws an exception when createGpg() is called.
     */
    private function createParserWithGpgException(Throwable $exception): GpgKeyParser
    {
        return new class($exception) extends GpgKeyParser {
            public function __construct(private readonly Throwable $exception)
            {
            }

            protected function createGpg(string $homedir): Crypt_GPG
            {
                throw $this->exception;
            }
        };
    }

    /**
     * Creates a GpgKeyParser that returns the given mock Crypt_GPG instance.
     */
    private function createParserWithMockedGpg(Crypt_GPG $gpg): GpgKeyParser
    {
        return new class($gpg) extends GpgKeyParser {
            public function __construct(private readonly Crypt_GPG $gpg)
            {
            }

            protected function createGpg(string $homedir): Crypt_GPG
            {
                return $this->gpg;
            }
        };
    }
}
