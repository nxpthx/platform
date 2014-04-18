<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
 */
class WidgetControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var DashboardWidget
     */
    protected $widget;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->client = static::createClient([], ToolsAPI::generateWsseHeader());
        $this->em     = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->configProvider = $this->client->getContainer()->get('oro_dashboard.config_provider');

        $this->widget = $this->createWidget();
        $this->em->persist($this->widget);
        $this->em->flush($this->widget);
    }

    public function testPut()
    {
        $data = [
            'isExpanded'     => 1,
            'layoutPosition' => [2, 20]
        ];

        $this->client->request(
            'PUT',
            $this->client->generate(
                'oro_api_put_dashboard_widget',
                [
                    'dashboardId' => $this->widget->getDashboard()->getId(),
                    'widgetId'    => $this->widget->getId(),
                ]
            ),
            $data,
            [],
            ToolsAPI::generateWsseHeader()
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->em->refresh($this->widget);

        $this->assertEquals($data['isExpanded'], $this->widget->isExpanded());
        $this->assertEquals($data['layoutPosition'], $this->widget->getLayoutPosition());
    }

    public function testAddWidget()
    {
        $widgets = $this->configProvider->getWidgetConfigs();

        $widgetNames = array_keys($widgets);

        $widgetName = $widgetNames[0];
        $id = $this->widget->getDashboard()->getId();
        $this->client->request(
            'POST',
            $this->client->generate(
                'oro_api_post_dashboard_widget_add_widget'
            ),
            array('dashboardId' => $id, 'widgetName' => $widgetName),
            array(),
            ToolsAPI::generateWsseHeader()
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $content = json_decode($result->getContent(), true);
        $this->assertEquals($this->configProvider->getWidgetConfig($widgetName), $content['config']);
        $this->assertEquals($widgetName, $content['widget']['name']);
        $this->assertEquals($id, $content['widget']['dashboard']['id']);

    }

    /**
     * @depends testPut
     */
    public function testDelete()
    {
        $this->client->request(
            'DELETE',
            $this->client->generate(
                'oro_api_delete_dashboard_widget',
                [
                    'dashboardId' => $this->widget->getDashboard()->getId(),
                    'widgetId'    => $this->widget->getId(),
                ]
            ),
            [],
            [],
            ToolsAPI::generateWsseHeader()
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->client->request(
            'DELETE',
            $this->client->generate(
                'oro_api_delete_dashboard_widget',
                [
                    'dashboardId' => $this->widget->getDashboard()->getId(),
                    'widgetId'    => $this->widget->getId(),
                ]
            ),
            [],
            [],
            ToolsAPI::generateWsseHeader()
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 404);
    }

    /**
     * @param array $widgets
     * @param array $expectedPositions
     *
     * @dataProvider widgetsProvider
     */
    public function testPositions($widgets, $expectedPositions)
    {
        foreach ($widgets as $widget) {
            $this->em->persist($widget);
        }
        $this->em->flush();

        $dashboard = null;
        $data      = ['layoutPositions' => []];
        foreach ($widgets as $widget) {
            /* @var DashboardWidget $widget */
            $data['layoutPositions'][$widget->getId()] = array_map(
                function ($item) {
                    return $item * 10;
                },
                $widget->getLayoutPosition()
            );

            $dashboard = $widget->getDashboard();
        }

        $this->client->request(
            'PUT',
            $this->client->generate(
                'oro_api_put_dashboard_widget_positions',
                [
                    'dashboardId' => $dashboard->getId(),
                ]
            ),
            $data,
            [],
            ToolsAPI::generateWsseHeader()
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);


        foreach ($widgets as $key => $widget) {
            $this->em->refresh($widget);
            $this->assertEquals($expectedPositions[$key], $widget->getLayoutPosition());
        }
    }

    /**
     * @return array
     */
    public function widgetsProvider()
    {
        return [
            'multiple' => [
                'widgets'           => [
                    $this->createWidget('widget1', false),
                    $this->createWidget('widget2', true, [2, 2]),
                ],
                'expectedPositions' => [
                    [10, 10],
                    [20, 20]
                ]
            ],
            'single'   => [
                'widgets'           => [
                    $this->createWidget('widget1', false)
                ],
                'expectedPositions' => [
                    [10, 10]
                ]
            ]
        ];
    }

    /**
     * @param string $name
     * @param bool   $isExpanded
     * @param array  $layoutPositions
     * @return DashboardWidget
     */
    protected function createWidget($name = 'widget', $isExpanded = true, array $layoutPositions = [1, 1])
    {
        $dashboard = new Dashboard();
        $dashboard->setName('dashboard');

        $widget = new DashboardWidget();
        $widget
            ->setName($name)
            ->setExpanded($isExpanded)
            ->setLayoutPosition($layoutPositions)
            ->setDashboard($dashboard);

        $dashboard->addWidget($widget);

        return $widget;
    }
}