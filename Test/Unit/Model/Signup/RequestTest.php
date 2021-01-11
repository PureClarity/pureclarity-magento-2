<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Signup;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Pureclarity\Core\Helper\Serializer;
use Pureclarity\Core\Helper\UrlValidator;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Service\Url;
use Pureclarity\Core\Helper\StoreData;
use Pureclarity\Core\Model\Config\Source\Region;
use Pureclarity\Core\Model\Signup\Request;
use Pureclarity\Core\Model\State;

/**
 * Class RequestTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Signup\Request
 */
class RequestTest extends TestCase
{
    /** @var Request $object */
    private $object;

    /** @var mixed[] $requestParams */
    private $requestParams;

    /** @var string $stateSaveName */
    private $stateSaveName;

    /** @var string $stateSaveValue */
    private $stateSaveValue;

    /** @var integer $stateSaveStoreId */
    private $stateSaveStoreId;

    /** @var MockObject|Curl $curlMock */
    private $curlMock;

    /** @var MockObject|Url $urlMock */
    private $urlMock;

    /** @var MockObject|Region $regionMock */
    private $regionMock;

    /** @var MockObject|StoreManagerInterface $storeManagerMock */
    private $storeManagerMock;

    /** @var MockObject|Serializer $serializerMock */
    private $serializerMock;

    /** @var MockObject|StoreData $storeDataMock */
    private $storeDataMock;

    /** @var MockObject|StateRepositoryInterface $stateRepositoryMock */
    private $stateRepositoryMock;

    /** @var MockObject|UrlValidator $urlValidator */
    private $urlValidator;

    protected function setUp()
    {
        $this->curlMock = $this->getMockBuilder(Curl::class)
             ->disableOriginalConstructor()
             ->getMock();

        $this->urlMock = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionMock->method('getValidRegions')->willReturn(
            [
                1 => 'Europe',
                4 => 'USA'
            ]
        );

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeDataMock = $this->getMockBuilder(StoreData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepositoryMock = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlValidator = $this->getMockBuilder(UrlValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock->expects($this->any())
            ->method('serialize')->will(
                $this->returnCallback(
                    function ($param) {
                        return json_encode($param);
                    }
                )
            );

        $this->serializerMock->expects($this->any())
            ->method('unserialize')->will(
                $this->returnCallback(
                    function ($param) {
                        return json_decode($param, true);
                    }
                )
            );

        $this->object = new Request(
            $this->curlMock,
            $this->urlMock,
            $this->regionMock,
            $this->storeManagerMock,
            $this->serializerMock,
            $this->storeDataMock,
            $this->stateRepositoryMock,
            $this->urlValidator
        );
    }

    /**
     * Returns default params for use with calls
     * @return array
     */
    private function getDefaultParams()
    {
        return [
            'firstname' => 'First name',
            'lastname' => 'Last name',
            'email' => 'example@example.com',
            'company' => 'Company',
            'password' => 'PureClarity123!',
            'store_name' => 'Store Name',
            'region' => 1,
            'url' => 'http://www.google.com/',
            'store_id' => 1,
            'phone' => '123456789'
        ];
    }

    /**
     * Generates a State mock
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $state->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $state->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $state->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        $state->expects($this->any())
            ->method('getValue')
            ->willReturn($value);

        $state->expects($this->once())
            ->method('setName')
            ->will($this->returnCallback(function ($value) {
                $this->stateSaveName = $value;
            }));

        $state->expects($this->once())
            ->method('setValue')
            ->will($this->returnCallback(function ($value) {
                $this->stateSaveValue = $value;
            }));

        $state->expects($this->once())
            ->method('setStoreId')
            ->will($this->returnCallback(function ($value) {
                $this->stateSaveStoreId = $value;
            }));

        return $state;
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        $this->assertInstanceOf(Request::class, $this->object);
    }

    /**
     * Tests sendRequest handles missing data
     */
    public function testEmptyParams()
    {
        $result = $this->object->sendRequest([]);

        $this->assertEquals(false, $result['success']);
        $this->assertEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals(
            'Missing First name,Missing Last name,Missing Email Address,'
            . 'Missing Company,Missing Password,Missing Store Name,Missing Region,Missing URL',
            $result['error']
        );
    }

    /**
     * Tests sendRequest handles an invalid email
     */
    public function testInvalidEmail()
    {
        $this->urlValidator->method('isValid')->with('http://www.google.com/', ['http', 'https'])->willReturn(true);

        $params = $this->getDefaultParams();
        $params['email'] = 'Email Address';

        $result = $this->object->sendRequest($params);

        $this->assertEquals(false, $result['success']);
        $this->assertEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals('Invalid Email Address', $result['error']);
    }

    /**
     * Tests sendRequest handles an invalid url
     */
    public function testInvalidUrl()
    {
        $this->urlValidator->method('isValid')->with('ABCDE', ['http', 'https'])->willReturn(false);

        $params = $this->getDefaultParams();
        $params['url'] = 'ABCDE';

        $result = $this->object->sendRequest($params);

        $this->assertEquals(false, $result['success']);
        $this->assertEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals('Invalid URL', $result['error']);
    }

    /**
     * Tests sendRequest handles an invalid region
     */
    public function testInvalidRegion()
    {
        $this->urlValidator->method('isValid')->with('http://www.google.com/', ['http', 'https'])->willReturn(true);

        $params = $this->getDefaultParams();
        $params['region'] = 7;

        $result = $this->object->sendRequest($params);

        $this->assertEquals(false, $result['success']);
        $this->assertEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals('Invalid Region selected', $result['error']);
    }

    /**
     * Tests sendRequest handles an invalid password
     */
    public function testInvalidPassword()
    {
        $this->urlValidator->method('isValid')->with('http://www.google.com/', ['http', 'https'])->willReturn(true);

        $params = $this->getDefaultParams();
        $params['password'] = 'password';

        $result = $this->object->sendRequest($params);

        $this->assertEquals(false, $result['success']);
        $this->assertEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals(
            'Password not strong enough, must contain 1 lowercase letter, ' .
            '1 uppercase letter, 1 number and be 8 characters or longer',
            $result['error']
        );
    }

    /**
     * Tests sendRequest handles an invalid store
     */
    public function testInvalidStore()
    {
        $this->urlValidator->method('isValid')->with('http://www.google.com/', ['http', 'https'])->willReturn(true);

        $params = $this->getDefaultParams();

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willThrowException(new NoSuchEntityException());

        $result = $this->object->sendRequest($params);

        $this->assertEquals(false, $result['success']);
        $this->assertEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals('Invalid Store selected', $result['error']);
    }

    /**
     * Tests sendRequest handles a valid request
     */
    public function testValidRequest()
    {
        $this->urlValidator->method('isValid')->with('http://www.google.com/', ['http', 'https'])->willReturn(true);

        $params = $this->getDefaultParams();

        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->curlMock
            ->expects($this->once())
            ->method('post')
            ->will($this->returnCallback(function ($url, $request) {
                $requestParams = json_decode($request, true);
                $this->requestParams = $requestParams;
            }));

        $this->stateRepositoryMock
            ->expects($this->once())
            ->method('save');

        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        $result = $this->object->sendRequest($params);

        $this->assertEquals(true, $result['success']);
        $this->assertNotEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals('', $result['error']);

        // Validate the request sent the right params
        $this->assertArrayHasKey('Id', $this->requestParams);
        $this->assertArrayHasKey('Platform', $this->requestParams);
        $this->assertArrayHasKey('Email', $this->requestParams);
        $this->assertArrayHasKey('FirstName', $this->requestParams);
        $this->assertArrayHasKey('LastName', $this->requestParams);
        $this->assertArrayHasKey('Company', $this->requestParams);
        $this->assertArrayHasKey('Region', $this->requestParams);
        $this->assertArrayHasKey('Currency', $this->requestParams);
        $this->assertArrayHasKey('TimeZone', $this->requestParams);
        $this->assertArrayHasKey('Url', $this->requestParams);
        $this->assertArrayHasKey('Password', $this->requestParams);
        $this->assertArrayHasKey('StoreName', $this->requestParams);
        $this->assertArrayHasKey('Phone', $this->requestParams);

        // Validate state save was called correctly with correct values
        $signUpValues = json_decode($this->stateSaveValue, true);

        $this->assertEquals('signup_request', $this->stateSaveName);
        $this->assertEquals(1, $this->stateSaveStoreId);

        $this->assertArrayHasKey('id', $signUpValues);
        $this->assertArrayHasKey('store_id', $signUpValues);
        $this->assertArrayHasKey('region', $signUpValues);

        $this->assertEquals($this->requestParams['Id'], $signUpValues['id']);
        $this->assertEquals(1, $signUpValues['store_id']);
        $this->assertEquals(1, $signUpValues['region']);
    }

    /**
     * Tests sendRequest handles a 400 response
     */
    public function test400Response()
    {
        $this->urlValidator->method('isValid')->with('http://www.google.com/', ['http', 'https'])->willReturn(true);

        $params = $this->getDefaultParams();
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(400);
        $data = [
            'errors' => [
                'An error'
            ]
        ];
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($data));

        $result = $this->object->sendRequest($params);

        $this->assertEquals(false, $result['success']);
        $this->assertNotEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals('Signup error: An error', $result['error']);
    }

    /**
     * Tests sendRequest handles an unexpected response
     */
    public function testUnexpectedResponse()
    {
        $this->urlValidator->method('isValid')->with('http://www.google.com/', ['http', 'https'])->willReturn(true);

        $params = $this->getDefaultParams();
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(504);
        $data = [
            'errors' => [
                'An error'
            ]
        ];
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($data));

        $result = $this->object->sendRequest($params);

        $this->assertEquals(false, $result['success']);
        $this->assertNotEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals(
            'PureClarity server error occurred. If this persists, '
            . 'please contact PureClarity support. Error code 504',
            $result['error']
        );
    }

    /**
     * Tests sendRequest handles a timeout
     */
    public function testTimeoutResponse()
    {
        $this->urlValidator->method('isValid')->with('http://www.google.com/', ['http', 'https'])->willReturn(true);

        $params = $this->getDefaultParams();
        $this->curlMock->expects($this->any())
            ->method('post')
            ->willThrowException(new \Exception('Request timed out'));

        $result = $this->object->sendRequest($params);

        $this->assertEquals(false, $result['success']);
        $this->assertNotEquals('', $result['request_id']);
        $this->assertEquals('', $result['response']);
        $this->assertEquals('Connection to PureClarity server timed out, please try again', $result['error']);
    }
}
