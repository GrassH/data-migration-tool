<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace unit\testsuite\Migration\Step\Customer;

use Migration\Step\Customer\Helper;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Entity name
     */
    const ENTITY = 'customer';

    /**
     * Document name
     */
    const DOCUMENT = 'customer_entity';

    /**
     * Attribute name
     */
    const ATTRIBUTE = 'password_hash';
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var \Migration\Resource\Source|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $source;

    /**
     * @var \Migration\Resource\Destination|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $destination;

    /**
     * @var \Migration\Reader\Groups|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $readerAttributes;

    /**
     * @var \Migration\Reader\Groups|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $readerGroups;

    /**
     * @var \Migration\Resource\Adapter\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $adapter;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $select;

    /**
     * @var array
     */
    protected $sourceDocuments = [
        self::DOCUMENT => 'entity_id'
    ];

    /**
     * @var array
     */
    protected $attribute = [
        [
            'attribute_id' => '12',
            'backend_type' => 'varchar',
            'attribute_code' => self::ATTRIBUTE,
            'entity_type_id' => '1'
        ]
    ];

    public function setUp()
    {
        $this->adapter = $this->getMockBuilder('Migration\Resource\Adapter\Mysql')
            ->setMethods(['fetchAll', 'getSelect'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->select = $this->getMockBuilder('Magento\Framework\DB\Select')
            ->setMethods(['from', 'join', 'where', 'getAdapter'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->select->expects($this->any())->method('from')->willReturnSelf();
        $this->select->expects($this->any())->method('join')->willReturnSelf();
        $this->select->expects($this->any())->method('where')->willReturnSelf();
        $this->select->expects($this->any())->method('getAdapter')->willReturn($this->adapter);
        $this->adapter->expects($this->any())->method('getSelect')->willReturn($this->select);

        $this->source = $this->getMockBuilder('Migration\Resource\Source')
            ->disableOriginalConstructor()
            ->getMock();
        $this->destination = $this->getMockBuilder('Migration\Resource\Destination')
            ->disableOriginalConstructor()
            ->getMock();

        $this->readerAttributes = $this->getMockBuilder('Migration\Reader\Groups')
            ->disableOriginalConstructor()
            ->getMock();

        $this->readerGroups = $this->getMockBuilder('Migration\Reader\Groups')
            ->setMethods(['getGroup'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->readerGroups->expects($this->at(0))
            ->method('getGroup')
            ->with('source_documents')
            ->willReturn($this->sourceDocuments);

        $groupsFactory = $this->getMockBuilder('Migration\Reader\GroupsFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $groupsFactory->expects($this->at(0))
            ->method('create')
            ->with('customer_attribute_groups_file')
            ->willReturn($this->readerAttributes);
        $groupsFactory->expects($this->at(1))
            ->method('create')
            ->with('customer_document_groups_file')
            ->willReturn($this->readerGroups);

        $this->helper = new Helper(
            $this->source,
            $this->destination,
            $groupsFactory
        );
    }

    /**
     * @param array $attributeData
     * @param array $expected
     *
     * @dataProvider dataProviderUpdateAttributeData
     */
    public function testUpdateAttributeData($attributeData, $expected)
    {
        $this->getAttributeType();

        $this->readerAttributes->expects($this->any())
            ->method('getGroup')
            ->with(self::DOCUMENT)
            ->willReturn($this->attribute);

        $this->adapter->expects($this->at(2))->method('fetchAll')->with($this->select)->willReturn($attributeData);

        $structure = $this->getMockBuilder('Migration\Resource\Structure')
            ->disableOriginalConstructor()
            ->getMock();
        $record = $this->getMockBuilder('Migration\Resource\Record')
            ->disableOriginalConstructor()
            ->getMock();
        $configReader = $this->getMockBuilder('Migration\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $record->expects($this->any())->method('getValue')->with('entity_id')->willReturn('1');
        $record->expects($this->any())->method('getData')->willReturn([]);
        $record->expects($this->any())->method('setData')->with($expected);
        $destinationRecords = new \Migration\Resource\Record\Collection($structure, [$record]);

        $this->helper->updateAttributeData(self::ENTITY, self::DOCUMENT, $destinationRecords, $configReader);
    }

    /**
     * Init EAV attributes
     */
    protected function getAttributeType()
    {
        $entities = [self::ENTITY => ''];
        $documentGroups = [self::ATTRIBUTE => ''];

        $this->readerGroups->expects($this->at(0))
            ->method('getGroup')
            ->with('eav_entities')
            ->willReturn($entities);
        $this->readerGroups->expects($this->at(1))
            ->method('getGroup')
            ->with(self::ENTITY)
            ->willReturn($this->sourceDocuments);

        $this->source->expects($this->any())->method('getAdapter')->willReturn($this->adapter);
        $this->adapter->expects($this->at(1))->method('fetchAll')->with($this->select)->willReturn($this->attribute);

        $this->readerAttributes->expects($this->any())->method('getGroup')->willReturn($documentGroups);

        $this->helper->getAttributeType(self::DOCUMENT);
    }

    /**
     * @return array
     */
    public function dataProviderUpdateAttributeData()
    {
        return [
            [
                [
                    [
                        'entity_id' => '1',
                        'attribute_id' => '12',
                        'value' => '34356a3d028accfb3c2996827b706bf5:UmPvGtih25eQCjC5f6NMwqkds500x2Jd'
                    ]
                ],
                [
                    self::ATTRIBUTE => '34356a3d028accfb3c2996827b706bf5:UmPvGtih25eQCjC5f6NMwqkds500x2Jd:0'
                ]
            ],
            [
                [
                    [
                        'entity_id' => '1',
                        'attribute_id' => '12',
                        'value' => '123123q:UmPvGtih25eQCjC5f6NMwqkds500x2Jd'
                    ]
                ],
                [
                    self::ATTRIBUTE => '123123q:UmPvGtih25eQCjC5f6NMwqkds500x2Jd'
                ]
            ]
        ];
    }
}
