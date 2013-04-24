<?php

namespace Oro\Bundle\GridBundle\Tests\Unit\Filter\ORM;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\GridBundle\Filter\ORM\StringFilter;

class StringFilterTest extends FilterTestCase
{
    protected function createTestFilter()
    {
        return new StringFilter($this->getTranslatorMock());
    }

    public function getOperatorDataProvider()
    {
        return array(
            array(TextFilterType::TYPE_CONTAINS, 'LIKE'),
            array(TextFilterType::TYPE_EQUAL, '='),
            array(TextFilterType::TYPE_NOT_CONTAINS, 'NOT LIKE'),
            array(false, 'LIKE')
        );
    }

    public function filterDataProvider()
    {
        return array(
            'not_array_value' => array(
                'data' => '',
                'expectProxyQueryCalls' => array()
            ),
            'no_data' => array(
                'data' => array(),
                'expectProxyQueryCalls' => array()
            ),
            'no_value' => array(
                'data' => array('value' => ''),
                'expectProxyQueryCalls' => array()
            ),
            'equals' => array(
                'data' => array('value' => 'test', 'type' => TextFilterType::TYPE_EQUAL),
                'expectProxyQueryCalls' => array(
                    array('getUniqueParameterId', array(), 'p1'),
                    array('andWhere',
                        array(
                            $this->getExpressionFactory()->eq(
                                self::TEST_ALIAS . '.' . self::TEST_FIELD,
                                ':' . self::TEST_NAME . '_p1'
                            )
                        ), null),
                    array('setParameter', array(self::TEST_NAME . '_p1', 'test'), null)
                )
            ),
            'like' => array(
                'data' => array('value' => 'test', 'type' => TextFilterType::TYPE_CONTAINS),
                'expectProxyQueryCalls' => array(
                    array('getUniqueParameterId', array(), 'p1'),
                    array('andWhere',
                        array(
                            $this->getExpressionFactory()->like(
                                self::TEST_ALIAS . '.' . self::TEST_FIELD,
                                ':' . self::TEST_NAME . '_p1'
                            )
                        ), null),
                    array('setParameter', array(self::TEST_NAME . '_p1', '%test%'), null)
                )
            ),
            'equals_having' => array(
                'data' => array('value' => 'test', 'type' => TextFilterType::TYPE_EQUAL),
                'expectProxyQueryCalls' => array(
                    array('getUniqueParameterId', array(), 'p1'),
                    array('andHaving',
                        array(
                            $this->getExpressionFactory()->eq(
                                'CONCAT(field_alias)',
                                ':' . self::TEST_NAME . '_p1'
                            )
                        ), null),
                    array('setParameter', array(self::TEST_NAME . '_p1', 'test'), null)
                ),
                array(
                    'field_mapping' => array(
                        'fieldExpression' => 'CONCAT(field_alias)'
                    )
                )
            ),
        );
    }

    public function testGetDefaultOptions()
    {
        $this->assertEquals(
            array(
                'format' => '%%%s%%',
                'form_type' => TextFilterType::NAME
            ),
            $this->model->getDefaultOptions()
        );
    }
}
