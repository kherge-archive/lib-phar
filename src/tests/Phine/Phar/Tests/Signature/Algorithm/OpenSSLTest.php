<?php

namespace Phine\Phar\Tests\Signature\Algorithm;

use Phine\Phar\File\Reader;
use Phine\Phar\Signature\Algorithm\OpenSSL;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link OpenSSL} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class OpenSSLTest extends TestCase
{
    /**
     * The instance of the algorithm being tested.
     *
     * @var OpenSSL
     */
    private $algorithm;

    /**
     * The file that is being used for testing.
     *
     * @var string
     */
    private $file;

    /**
     * The actual hash for the file.
     *
     * @var string
     */
    private $hash;

    /**
     * The test private key.
     *
     * @var string
     */
    private $key = <<<KEY
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAqVSh4hSHZCB3KarL6GfI+qSUYP0xA4vBL2SBcLvqTZD+dNG0
DC6gKkWPfBUtUrARE0C8XmANgsm1BuMp3UBDurqCPfM6Vx92mKAXwbjL4UZZz5ga
QSD/hMvI9tMtZRslq4dmXya/Am8W4KKxEmdpoyZnCU8yPqMw+neAXwFy3hUjgVzW
a/ULpFjjF/9XGwOKlnMm5Eb7xC2CBNb1BoSbVWZYHCILwx2ni4r5/o4z5Dd4VoFW
Oe2tTR+G2tcWnF1kf9PQnWOJCtBtEfzTPJEgifhz5eA7tQil8gyRn/n9mjXwZZpT
VGlUXvzke+71BQYSTlXPbfFVoGPsm8jWw2NdtQIDAQABAoIBACZ/6l2pnr32frl/
zqRdAo5wYJcrwhrlCevBXYfZBfUUYDKF4nq5mCt8TNsgwoEQLJW0zI9lpfTEcU8r
U5+tRqj8UnQM6wkXi4K4taYTYvGpqe5gDiocO1SBdAQgsCvSkA5Ptwgv2lKOmQRC
oWGGlzdj2h38/nbM6mwsQwj23pvdEXa3hcxdYjJdiBw5nXXf9X4HPWox2Yv4nFLF
EZb5MIlGfXdjpyyI+ZC9zbi4S3wFQUVqnsxNhdAKLFOY6Qd57B5Eb1zg/5ca+MP/
7AmtFVX7C7vwCgO/O0It0V5eLPutThhN4l4L5F/abAVzh5w0zfAdVklRKN9OgEhD
sx0L7GkCgYEAtXi8frG8sl3pOFHgE9LW+KoVoegdNDVgSNwX9N4+eJx64hxAhs36
8658B7G7XTlJRBQ1uQUMqME9tg4WY3XZrU8exgBiqrgMmaeycv5NsQ4GeYkA/6G0
zYqYETagQ6753lzLd+nfHizzoHcrW06tDRmH0fXybSvcrzGPPEIMzW0CgYEA7t9u
sg4VNI1bZiSC59b4S/t64s2+9xYFaSvzu9oSpV7xCldK31grSWf1nEPLh3Kw2R5c
OUqSfjZVM3u9Mnei+lc2M3eQxYNaq+WHztO9KYzr413/NmATk+7zapFc3T1qieHy
SvtyhF7R/F2pshq1SyG0N42GDLXpBku8CNVKDGkCgYEAntLytje8nKdQjLgr022+
M5g4oqFRnfXxNRNb7DXumwTpD6gGWXVBY7CcYOeOwkJ2+xxtAGeI8tFdv06JDbeB
MguDNWv07tFyRbDdn4MuX/2UcR4VP6Y5ZYMdIEOc9F/Z1GGGuoZ3fiN9fj2ONrWf
A04K7xnAeQfgd3x6jhSIu+kCgYAOD9UiYjXZXCDvSGL0ZvFC99DqHRaK0R8Ma+vz
OQkz9vzVkQH1V+lkbtxEkLEgjh3fCix/quYoy+YEG9qtZ+GOPygPq5A4MF1cFS0D
fe96zLLuNZBakRlV8hEeSuRemcj1yPlMdLTvGe3en1zoAS5+98L2Kjyj7umC116A
Xp33UQKBgH7TSvPBPwHDwMnuALO2+JYTkCFX6WklQukHcOLOh/f02QerrYBRkZ1q
f73OT4u5Qn5+i6BYVHCiAMafVR9+ThoXJ+Rq6Yk7ZfKKaMvvDcmK6wDRceP3wJ93
93QaO9Y5yEJEdoLhtkKFH4udI6i4XaKLRhVP/MyWejHSlabQkP27
-----END RSA PRIVATE KEY-----
KEY;


    /**
     * The read for the test file.
     *
     * @var Reader
     */
    private $reader;

    /**
     * The temporary file manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * Make sure that we get back the expected flag.
     */
    public function testGetFlag()
    {
        $this->assertSame(
            0x10,
            $this->algorithm->getFlag(),
            'Make sure we get the right flag.'
        );
    }

    /**
     * Make sure that we can read the signature from the file.
     */
    public function testReadSignature()
    {
        $this->assertSame(
            array(
                'hash' => $this->hash,
                'hash_type' => 'OpenSSL'
            ),
            $this->algorithm->readSignature($this->reader),
            'Make sure we can read the signature.'
        );
    }

    /**
     * Make sure that we can verify the signature.
     */
    public function testVerifySignature()
    {
        $this->assertTrue(
            $this->algorithm->verifySignature($this->reader),
            'Make sure the signature is verified.'
        );
    }

    /**
     * Make sure that an exception is thrown if the public key is not present.
     */
    public function testVerifySignatureNoPublicKey()
    {
        unlink($this->file . '.pubkey');

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'The path "' . $this-> file . '.pubkey" is not a file or does not exist.'
        );

        $this->algorithm->verifySignature($this->reader);
    }

    /**
     * Sets up the test.
     */
    protected function setUp()
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('The "openssl" extension is not available.');
        }

        $this->algorithm = new OpenSSL();
        $this->temp = new Temp();
        $this->file = $this->temp->createDir() . '/test';

        touch($this->file);

        $this->reader = new Reader($this->file);

        $resource = openssl_pkey_get_private($this->key);

        openssl_sign('This is the test content.', $this->hash, $resource);

        $public = openssl_pkey_get_details($resource);
        $public = $public['key'];

        openssl_free_key($resource);

        file_put_contents(
            $this->file,
            sprintf(
                '%s%s%s%s%s',
                'This is the test content.',
                $this->hash,
                pack('V', strlen($this->hash)),
                pack('V', 0x10),
                'GBMB'
            )
        );

        file_put_contents(
            $this->file . '.pubkey',
            $public
        );

        $this->hash = strtoupper(bin2hex($this->hash));

        clearstatcache();
    }

    /**
     * Clean up the test.
     */
    protected function tearDown()
    {
        // working around weird bug on PHP 5.3.3
        if ($this->temp) {
            $this->temp->purgePaths();
        }
    }
}
