<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Block\Adminhtml\Dashboard\Signup;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Regions;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Store;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class SignupTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class SignupTest extends TestCase
{
    /** @var Signup $object */
    private $object;

    /** @var Context $context */
    private $context;

    /** @var Stores $storesViewModel */
    private $storesViewModel;

    /** @var Regions $regionsViewModel */
    private $regionsViewModel;

    /** @var Store $storeViewModel */
    private $storeViewModel;

    /** @var FormKey $formKey */
    private $formKey;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storesViewModel = $this->getMockBuilder(Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionsViewModel = $this->getMockBuilder(Regions::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeViewModel = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formKey = $this->getMockBuilder(FormKey::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Signup(
            $this->context,
            $this->storesViewModel,
            $this->regionsViewModel,
            $this->storeViewModel,
            $this->formKey
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Signup::class, $this->object);
    }

    public function testTemplate()
    {
        $this->assertInstanceOf(Template::class, $this->object);
    }

    public function testGetPureclarityStoresViewModel()
    {
        $this->assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }

    public function testGetPureclarityRegionsViewModel()
    {
        $this->assertInstanceOf(Regions::class, $this->object->getPureclarityRegionsViewModel());
    }

    public function testGetPureclarityStoreViewModel()
    {
        $this->assertInstanceOf(Store::class, $this->object->getPureclarityStoreViewModel());
    }

    public function testGetformKey()
    {
        $formKey = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->formKey->expects($this->once())
            ->method('getFormKey')
            ->willReturn($formKey);

        $this->assertEquals($formKey, $this->object->getFormKey());
    }
}
