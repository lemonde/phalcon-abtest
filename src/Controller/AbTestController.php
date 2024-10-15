<?php

namespace ABTesting\Controller;

use ABTesting\Engine;
use ABTesting\Exception\AbTestingException;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\View\Simple as SimpleView;

class AbTestController extends Controller
{
    public function countAction()
    {
        $url = $this->request->getQuery('u');

        if (empty($url)) {
            $this->response->resetHeaders();
            $this->response->setStatusCode(404);
            return;
        }

        if (empty($this->request->getHTTPReferer())) {
            $this->response->redirect($url, true, 302);
            return;
        }

        $testName = $this->dispatcher->getParam('testName');
        $testWinner = $this->dispatcher->getParam('winner');
        $engine = Engine::getInstance();

        try {
            $test = $engine->getTest($testName);

            if (empty($test)) {
                $this->response->redirect($url, true, 302);
                return;
            }

            $winner = $test->getVariant($testWinner);

            if ($winner) {
                $engine->saveClick($testName, $testWinner);
            }

            $this->response->redirect($url, true, 302);
        } catch (\Throwable $t) {
            if ($engine->getEventsManager()) {
                $e = new AbTestingException("Unable to count click for $testName:$testWinner.", 404, $t);
                $engine->getEventsManager()->fire('abtest:beforeException', Engine::getInstance(), $e);
            }

            $this->response->redirect($url, true, 302);
        }
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function reportAction()
    {
        $this->view->disable();

        $view = new SimpleView();
        $view->setDI($this->getDI());
        $view->setViewsDir(dirname(__DIR__) . '/Views/');
        $view->registerEngines(['.volt' => Volt::class]);

        $type = $this->request->hasQuery('type') ? strtolower((string) $this->request->getQuery('type')) : 'total';
        $deviceFilter = $this->request->hasQuery('device') ? strtolower((string) $this->request->getQuery('device')) : '*';
        $testName = $this->request->hasQuery('test') ? strtolower((string) $this->request->getQuery('test')) : null;
        $range = max(min($this->request->hasQuery('range') ? intval($this->request->getQuery('range')) : 1, 1000), 1);
        $names = [];

        $data = [];

        $engine = Engine::getInstance();

        foreach ($engine->getTests() as $test) {
            $names[] = $test->getIdentifier();

            if ($testName && $testName !== strtolower($test->getIdentifier())) {
                continue;
            }

            foreach ($test->getVariants() as $variant) {
                $testName = $test->getIdentifier();
                $restrictions = [];

                switch ($type) {
                    case '10m':
                        foreach (range(0, $range - 1) as $diff) {
                            $diff = $diff * 10;
                            $date = date_create_immutable()->modify("-$diff minutes");
                            $restrictions[] = $date;
                        }
                        break;
                    case 'hour':
                        foreach (range(0, $range - 1) as $diff) {
                            $date = date_create_immutable()->modify("-$diff hours");
                            $restrictions[] = $date;
                        }

                        break;
                    case 'day':
                        foreach (range(0, $range - 1) as $diff) {
                            $date = date_create();
                            $date->modify("-$diff days");
                            $restrictions[] = $date;
                        }

                        break;
                    case 'month':
                        foreach (range(0, $range - 1) as $diff) {
                            $date = date_create_immutable()->modify("-$diff months");
                            $restrictions[] = $date;
                        }
                        break;
                    default:
                        $restrictions[] = '';
                }

                foreach ($restrictions as $restriction) {
                    switch ($type) {
                        case '10m':
                            $header = substr($restriction->format('d/m/Y H\hi'), 0, -1) . '0';
                            $groupBy = $restriction->getTimestamp();
                            $restriction = substr($restriction->format('YmdHi'), 0, -1);
                            break;
                        case 'hour':
                            $header = $restriction->format('d/m/Y H\h');
                            $groupBy = $restriction->getTimestamp();
                            $restriction = $restriction->format('YmdH');
                            break;
                        case 'day':
                            $header = $restriction->format('d/m/Y');
                            $groupBy = $restriction->getTimestamp();
                            $restriction = $restriction->format('Ymd');
                            break;
                        case 'month':
                            $header = $restriction->format('m/Y');
                            $groupBy = $restriction->getTimestamp();
                            $restriction = $restriction->format('Ym');
                            break;
                        default:
                            $header = 'total';
                            $groupBy = 'total';
                    }

                    $restriction =  $restriction . '*' . $variant->getIdentifier() . '*' . ($deviceFilter === '*' ? '' : ($deviceFilter . '*'));
                    $key = array_push($data, [
                            'header' => $header,
                            'testName' => $testName,
                            'date' => $groupBy,
                            'device' => $deviceFilter,
                            'template' => $variant->getIdentifier(),
                            'impression' => 0,
                            'click' => 0,
                            'ctr' => 0,
                        ]) - 1;

                    foreach ($engine->getCounter()->getCount($testName, $restriction) as $identifier => $count) {
                        [, , $device, $countType] = explode(':', $identifier);

                        if ($countType === 'print') {
                            $data[$key]['impression'] += $count;
                        } else {
                            $data[$key]['click'] += $count;
                        }

                        if ($data[$key]['impression'] === 0) {
                            $data[$key]['ctr'] = (float) 0;
                        } else {
                            $data[$key]['ctr'] = round(($data[$key]['click'] / $data[$key]['impression'] * 100), 2);
                        }
                    }
                }
            }
        }

        return $view->render('report.volt', [
            'data' => $data,
            'pathname' => $this->dispatcher->getActionName(),
            'tests' => $names,
            'current_test' => $testName,
            'current_device' => $deviceFilter,
            'current_type' => $type,
            'current_range' => $range,
        ]);
    }
}
