<?php
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Doctrine\ORM\Tools\SchemaTool;

class VoucherUnlinkCommandTest extends WebTestCase
{
    private $display;

    public function setUp()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        if (!isset($metadatas)) {
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
        }
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }
        $this->postFixtureSetup();

        $fixtures = array(
        //     'Acme\MyBundle\DataFixtures\ORM\LoadUserData',
        );
        $this->loadFixtures($fixtures);
    }

    public function testWithoutUsers()
    {
        $this->display = $this->runCommand('usrmgmt:voucher:unlink');
        $this->assertContains('0 vouchers', $this->display);
    }

    public function testSuspicious()
    {
        // load some data
        $this->loadFixtures(array(
            'AppBundle\DataFixtures\ORM\LoadDomainData',
            'AppBundle\DataFixtures\ORM\LoadUserData',
            'AppBundle\DataFixtures\ORM\LoadVoucherData',
        ));

        // re-run test
        $this->display = $this->runCommand('usrmgmt:voucher:unlink');
        $this->assertContains('suspicious@systemli.org', $this->display);
    }
}
