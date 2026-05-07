<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Helper\Service;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Helper\Service\Url;

/**
 * Class UrlTest
 *
 * Tests the methods in \Pureclarity\Core\Helper\Service\Url
 */
class UrlTest extends TestCase
{
    /**
     * Default PureClarity ClientScript URL
     *
     * @var string
     */
    private $scriptUrl = '//pcs.pureclarity.net';

    /**
     * Default PureClarity API URL (eu-west-1)
     *
     * @var string
     */
    private $apiUrl = "https://api-eu-w-1.pureclarity.net";

    /**
     * Default PureClarity SFTP URL (eu-west-1)
     *
     * @var string
     */
    private $sftpUrl = "https://sftp-eu-w-1.pureclarity.net";

    /** @var Url $object */
    private $object;

    protected function setUp(): void
    {
        $this->object = new Url();
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testStoreDataInstance()
    {
        $this->assertInstanceOf(Url::class, $this->object);
    }

    /**
     * Tests that the admin url is returned correctly
     */
    public function testGetAdminUrl()
    {
        $this->assertEquals('https://admin.pureclarity.com', $this->object->getAdminUrl());
    }

    /**
     * Tests that the github url is returned correctly
     */
    public function testGetGithubUrl()
    {
        $this->assertEquals(
            'https://api.github.com/repos/pureclarity/pureclarity-magento-2/releases/latest',
            $this->object->getGithubUrl()
        );
    }

    /**
     * Tests that the Delta endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetDeltaEndpoint()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_HOST=' . $localUrl);
        $this->assertEquals($localUrl . '/api/productdelta', $this->object->getDeltaEndpoint(1));
    }

    /**
     * Tests that the Delta endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetDeltaEndpointReal()
    {
        putenv('PURECLARITY_HOST=');
        $this->assertEquals($this->apiUrl . '/api/productdelta', $this->object->getDeltaEndpoint(1));
    }

    /**
     * Tests that the signup request endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetSignupRequestEndpointUrl()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_HOST=' . $localUrl);
        $this->assertEquals(
            $localUrl . '/api/plugin/signuprequest',
            $this->object->getSignupRequestEndpointUrl(1)
        );
    }

    /**
     * Tests that the signup request endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetSignupRequestEndpointUrlReal()
    {
        putenv('PURECLARITY_HOST=');
        $this->assertEquals(
            $this->apiUrl . '/api/plugin/signuprequest',
            $this->object->getSignupRequestEndpointUrl(1)
        );
    }

    /**
     * Tests that the signup status endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetSignupStatusEndpointUrl()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_HOST=' . $localUrl);
        $this->assertEquals(
            $localUrl . '/api/plugin/signupstatus',
            $this->object->getSignupStatusEndpointUrl(1)
        );
    }

    /**
     * Tests that the signup status endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetSignupStatusEndpointUrlReal()
    {
        putenv('PURECLARITY_HOST=');
        $this->assertEquals(
            $this->apiUrl . '/api/plugin/signupstatus',
            $this->object->getSignupStatusEndpointUrl(1)
        );
    }

    /**
     * Tests that the sftp endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetFeedSftpUrl()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_FEED_HOST=' . $localUrl);
        putenv('PURECLARITY_FEED_PORT=1234');
        $this->assertEquals(
            $localUrl . ':1234/',
            $this->object->getFeedSftpUrl(1)
        );
    }

    /**
     * Tests that the sftp endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetFeedSftpUrlReal()
    {
        putenv('PURECLARITY_FEED_HOST=');
        putenv('PURECLARITY_FEED_PORT=');
        $this->assertEquals(
            $this->sftpUrl . '/',
            $this->object->getFeedSftpUrl(1)
        );
    }

    /**
     * Tests that the client script base url is returned correctly
     */
    public function testGetClientScriptBaseUrl()
    {
        $this->assertEquals($this->scriptUrl, $this->object->getClientScriptBaseUrl());
    }

    /**
     * Tests that the client script url is returned correctly - with env variable set to override the real value
     */
    public function testGetClientScriptUrl()
    {
        $localUrl = 'http://127.0.0.1/';
        putenv('PURECLARITY_SCRIPT_URL=' . $localUrl);
        $this->assertEquals(
            $localUrl . 'ACCESSKEY1234/cs.js',
            $this->object->getClientScriptUrl('ACCESSKEY1234')
        );
    }

    /**
     * Tests that the client script url is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetClientScriptUrlReal()
    {
        putenv('PURECLARITY_SCRIPT_URL=');
        $this->assertEquals(
            $this->scriptUrl . '/ACCESSKEY1234/cs.js',
            $this->object->getClientScriptUrl('ACCESSKEY1234')
        );
    }

    /**
     * Tests that the serverside endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetServerSideEndpoint()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_HOST=' . $localUrl);
        $this->assertEquals(
            $localUrl . '/api/serverside',
            $this->object->getServerSideEndpoint(1)
        );
    }

    /**
     * Tests that the serverside endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetServerSideEndpointReal()
    {
        putenv('PURECLARITY_HOST=');
        $this->assertEquals(
            $this->apiUrl . '/api/serverside',
            $this->object->getServerSideEndpoint(1)
        );
    }
}
