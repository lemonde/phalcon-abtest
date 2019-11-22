<?php

namespace ABTesting\Plugin;

use ABTesting\Engine;
use ABTesting\Exception\AbTestingException;
use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class AnnotationListener extends Injectable
{
    /**
     * @var bool
     */
    private $isTesting = false;
    /**
     * @var string[]
     */
    private $tests = [];
    /**
     * @param Event $event
     * @param Dispatcher $dispatcher
     * @throws AbTestingException
     */
    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
        $this->checkIsTesting($dispatcher->getControllerClass(), $dispatcher->getActiveMethod());

        if ($this->isTesting) {
            $this->calculate();
        } else {
            Engine::getInstance()->deactivate();
        }
    }
    /**
     * @param string $controllerClass
     * @param string $controllerMethod
     * @throws AbTestingException
     */
    private function checkIsTesting(string $controllerClass, string $controllerMethod)
    {
        $actionAnnotations = $this->annotations->getMethod($controllerClass, $controllerMethod);

        if ($actionAnnotations->has('AbTesting')) {
            $testingAnnotations = $actionAnnotations->getAll('AbTesting');

            foreach ($testingAnnotations as $testingAnnotation) {
                $testName = $testingAnnotation->getNamedArgument('name') ?? $testingAnnotation->getArgument(0);

                if (empty($testName)) {
                    throw new AbTestingException('Missing test name in annotation', AbTestingException::INCOMPLETE_ANNOTATION);
                }

                $this->tests[] = $testName;
                $this->isTesting = true;
            }
        }
    }

    private function calculate()
    {
        $engine = Engine::getInstance();

        foreach ($this->tests as $testName) {
            $test = $engine->getTest($testName);

            if (!empty($test)) {
                $test->battle();
            }
        }
    }
}
