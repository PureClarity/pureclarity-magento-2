<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Model\ResourceModel\State as StateResource;
use Pureclarity\Core\Model\State;
use Pureclarity\Core\Model\StateRepository;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\ResourceModel\StateFactory;
use Pureclarity\Core\Model\ResourceModel\State\CollectionFactory;
use Pureclarity\Core\Model\ResourceModel\State\Collection;

/**
 * Class StateRespositoryTest
 *
 * Tests the methods in \Pureclarity\Core\Model\StateRepository
 */
class StateRepositoryTest extends TestCase
{
    /** @var StateRepository $object */
    private $object;

    /** @var MockObject|CollectionFactory $collectionFactoryMock */
    private $collectionFactoryMock;

    /** @var MockObject|Collection $collectionMock */
    private $collectionMock;

    /** @var MockObject|StateFactory $stateFactoryMock */
    private $stateFactoryMock;

    /** @var MockObject|StateResource $stateResourceMock */
    private $stateResourceMock;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);

        $this->collectionMock = $this->createPartialMock(
            Collection::class,
            [
                'addFieldToFilter',
                'getSize',
                'setCurPage',
                'setPageSize',
                'load',
                'addOrder',
                'getFirstItem',
                'getItems'
            ]
        );

        $this->collectionFactoryMock->expects($this->any())->method('create')
            ->will($this->returnValue($this->collectionMock));

        $this->stateFactoryMock = $this->createPartialMock(
            StateFactory::class,
            ['create']
        );

        $this->stateResourceMock = $this->createMock(StateResource::class);

        $this->stateFactoryMock->expects($this->any())->method('create')
            ->will($this->returnValue($this->stateResourceMock));

        $this->object = new StateRepository(
            $this->collectionFactoryMock,
            $this->stateFactoryMock
        );
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->createMock(State::class);

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

        return $state;
    }

    public function testStateRepositoryInstance()
    {
        $this->assertInstanceOf(StateRepository::class, $this->object);
    }

    public function testStateRepositoryInterface()
    {
        $this->assertInstanceOf(StateRepositoryInterface::class, $this->object);
    }

    public function testGetListByName()
    {
        $this->collectionMock->expects(self::once())->method('addFieldToFilter')->with('name');
        $this->collectionMock->expects(self::once())->method('getItems')->willReturn(
            [$this->getStateMock(), $this->getStateMock()]
        );
        $result = $this->object->getListByName('name');
        self::assertCount(2, $result);
        self::assertInstanceOf(StateInterface::class, $result[0]);
    }

    public function testGetByNameAndStore()
    {
        $this->collectionMock->expects($this->once())->method('getFirstItem')->willReturn($this->getStateMock());
        $result = $this->object->getByNameAndStore('name', 1);
        $this->assertInstanceOf(StateInterface::class, $result);
    }

    public function testGetByNameAndStoreExpectingResult()
    {
        $this->collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->getStateMock('1', 'name', 'value!', '1'));

        $result = $this->object->getByNameAndStore('name', 1);
        $this->assertInstanceOf(StateInterface::class, $result);
        $this->assertEquals('1', $result->getId());
        $this->assertEquals('name', $result->getName());
        $this->assertEquals('value!', $result->getValue());
        $this->assertEquals('1', $result->getStoreId());
    }

    public function testCollectionGetsCorrectFilters()
    {
        $this->collectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter');

        $this->collectionMock->expects($this->at(0))
        ->method('addFieldToFilter')
            ->with('name', 'name_to_look_for');

        $this->collectionMock->expects($this->at(1))
            ->method('addFieldToFilter')
            ->with('store_id', 1);

        $this->object->getByNameAndStore('name_to_look_for', 1);
    }

    public function testSave()
    {
        $this->stateResourceMock->expects($this->exactly(1))
            ->method('save');

        $this->object->save($this->getStateMock());
    }

    public function testBadSave()
    {
        $this->stateResourceMock->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception('Something bad happened'));

        $this->expectException(CouldNotSaveException::class);

        $this->object->save($this->getStateMock());
    }

    public function testDelete()
    {
        $this->stateResourceMock->expects($this->exactly(1))
            ->method('delete');

        $this->object->delete($this->getStateMock());
    }

    public function testBadDelete()
    {
        $this->stateResourceMock->expects($this->any())
            ->method('delete')
            ->willThrowException(new \Exception('Something bad happened'));

        $this->expectException(CouldNotDeleteException::class);

        $this->object->delete($this->getStateMock());
    }
}
