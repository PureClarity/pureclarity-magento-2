<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Bmz;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Bmz\Install;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Backend\App\Action;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\Zones\Installer;
use Psr\Log\LoggerInterface;

/**
 * Class InstallTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Bmz\Install
 */
class InstallTest extends TestCase
{
    /** @var Index $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var JsonFactory $resultJsonFactory */
    private $resultJsonFactory;

    /** @var Json $resultJson */
    private $resultJson;

    /** @var Installer $zoneInstaller */
    private $zoneInstaller;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var MockObject|Http $request */
    private $request;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->method('getRequest')
            ->willReturn($this->request);

        $this->zoneInstaller = $this->getMockBuilder(Installer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactory->method('create')
            ->willReturn($this->resultJson);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Install(
            $this->context,
            $this->resultJsonFactory,
            $this->zoneInstaller,
            $this->logger
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Install::class, $this->object);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testAction()
    {
        self::assertInstanceOf(Action::class, $this->object);
    }

    /**
     * Tests the execute function in single store mode, making sure menu is set correctly
     */
    public function testExecute()
    {
        $this->request->expects(self::at(0))
            ->method('getParam')
            ->with('storeid')
            ->willReturn('1');

        $this->request->expects(self::at(1))
            ->method('getParam')
            ->with('themeid')
            ->willReturn('2');

        $this->zoneInstaller->expects(self::once())
            ->method('install')
            ->with(
                [
                    'homepage',
                    'product_page',
                    'basket_page',
                    'order_confirmation_page'
                ],
                1,
                2
            )
            ->willReturn(['installed' => 'HP-01']);

        $this->resultJson->expects(self::once())
            ->method('setData')
            ->with(['installed' => 'HP-01', 'success' => true]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests the execute function in single store mode, making sure menu is set correctly
     */
    public function testExecuteException()
    {
        $this->request->expects(self::at(0))
            ->method('getParam')
            ->with('storeid')
            ->willReturn('1');

        $this->request->expects(self::at(1))
            ->method('getParam')
            ->with('themeid')
            ->willReturn('1');

        $this->zoneInstaller->expects(self::once())
            ->method('install')
            ->willThrowException(new \Exception('An error'));

        $this->resultJson->expects(self::once())
            ->method('setData')
            ->with(['success' => false]);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity Zone install error: An error');

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }
}
