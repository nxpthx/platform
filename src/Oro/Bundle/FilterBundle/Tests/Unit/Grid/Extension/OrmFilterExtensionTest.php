<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

class OrmFilterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var OrmFilterExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->extension  = new OrmFilterExtension($this->translator);
    }

    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $this->extension->setParameters(new ParameterBag($input));
        $this->assertEquals($expected, $this->extension->getParameters()->all());
    }

    /**
     * @return array
     */
    public function setParametersDataProvider()
    {
        return [
            'empty'    => [
                'input'    => [],
                'expected' => [],
            ],
            'regular'  => [
                'input'    => [
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'firstName' => ['value' => 'John'],
                    ],
                ],
                'expected' => [
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'firstName' => ['value' => 'John'],
                    ],
                ]
            ],
            'minified' => [
                'input'    => [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        OrmFilterExtension::MINIFIED_FILTER_PARAM => [
                            'firstName' => ['value' => 'John'],
                        ],
                    ]
                ],
                'expected' => [
                    ParameterBag::MINIFIED_PARAMETERS     => [
                        OrmFilterExtension::MINIFIED_FILTER_PARAM => [
                            'firstName' => ['value' => 'John'],
                        ],
                    ],
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'firstName' => ['value' => 'John'],
                    ],
                ]
            ],
        ];
    }

    /**
     * @param array $gridConfig
     * @param array $parameters
     * @param mixed $expected
     *
     * @dataProvider valuesDataProvider
     */
    public function testGetValuesToApply(array $gridConfig, array $parameters, $expected)
    {
        $gridConfig = DatagridConfiguration::create($gridConfig);

        $dataSource = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();

        $filter = $this->getMock('Oro\Bundle\FilterBundle\Filter\FilterInterface');
        $filter
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('filter'));

        $form = $this
            ->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form
            ->expects($this->any())
            ->method('isSubmitted')
            ->will($this->returnValue(false));

        if (is_array($expected)) {
            $form
                ->expects($this->once())
                ->method('submit')
                ->with($this->equalTo($expected));
        } else {
            $form
                ->expects($this->never())
                ->method('submit');
        }

        $filter
            ->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        $qb = $this
            ->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource
            ->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $this->extension->addFilter('string', $filter);
        $this->extension->setParameters(new ParameterBag($parameters));
        $this->extension->visitDatasource($gridConfig, $dataSource);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function valuesDataProvider()
    {
        return [
            'default_filter_no_parameters_modified_grid'  => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [
                    PagerInterface::PAGER_ROOT_PARAM => [
                        PagerInterface::DISABLED_PARAM => true
                    ]
                ],
                false
            ],
            'default_filter_no_parameters_new_grid'       => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [],
                ['value' => 'filter-value']
            ],
            'parametrized_without_default_filters'        => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ]
                    ]
                ],
                [
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'filter' => [
                            'value' => 'filter-value'
                        ]
                    ]
                ],
                ['value' => 'filter-value']
            ],
            'override_default_filters'                    => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'filter' => [
                            'value' => 'override-value'
                        ]
                    ]
                ],
                ['value' => 'override-value']
            ],
            'empty_parametrized_overrides_default_filter' => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'filter' => []
                    ]
                ],
                []
            ]
        ];
    }
}
