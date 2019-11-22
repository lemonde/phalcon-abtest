<?php

namespace ABTesting\Tests\Controller;

use ABTesting\Controller\AbTestController;
use ABTesting\Engine;
use ABTesting\Exception\AbTestingException;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use Phalcon\Http\Request;
use ABTesting\Tests\TestCase;
use Phalcon\Http\Response;
use Phalcon\Mvc\Dispatcher;
use PHPUnit\Framework\MockObject\MockObject;

class AbTestControllerTest extends TestCase
{
    public function testCountAction()
    {
        $controller = new AbTestController();

        $controller->response = $this->createMock(Response::class);
        $controller->response->expects($this->once())->method('redirect');

        $controller->dispatcher = $this->createMock(Dispatcher::class);
        $controller->dispatcher
            ->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['testName'], ['winner'])
            ->willReturnOnConsecutiveCalls('test', 'winner');

        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn('https://www.example.org');

        /** @var Engine|MockObject $engine */
        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('test')
            ->willReturn(new Test('test', [
                new Variant('winner', 'winner'),
                new Variant('variant', 'variant'),
            ]));

        $engine
            ->expects($this->once())
            ->method('saveClick')
            ->with('test', 'winner');

        $controller->countAction();
    }

    public function testCountInvalidTestAction()
    {
        $controller = new AbTestController();

        $controller->response = $this->createMock(Response::class);
        $controller->response->expects($this->once())->method('redirect');

        $controller->dispatcher = $this->createMock(Dispatcher::class);
        $controller->dispatcher
            ->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['testName'], ['winner'])
            ->willReturnOnConsecutiveCalls('invalid', 'winner');
        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn('https://www.example.org');

        /** @var Engine|MockObject $engine */
        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('invalid')
            ->willThrowException(new AbTestingException());

        $engine
            ->expects($this->never())
            ->method('saveClick');

        $controller->countAction();
    }

    public function testCountInvalidVariantAction()
    {
        $controller = new AbTestController();

        $controller->response = $this->createMock(Response::class);
        $controller->response->expects($this->once())->method('redirect');

        $controller->dispatcher = $this->createMock(Dispatcher::class);
        $controller->dispatcher
            ->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['testName'], ['winner'])
            ->willReturnOnConsecutiveCalls('test', 'invalid');
        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn('https://www.example.org');

        /** @var Engine|MockObject $engine */
        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('test')
            ->willReturn(new Test('test', [
                new Variant('winner', 'winner'),
                new Variant('variant', 'variant'),
            ]));

        $engine
            ->expects($this->never())
            ->method('saveClick');

        $controller->countAction();
    }

    /**
     * @expectedException \Phalcon\Mvc\Dispatcher\Exception
     * @expectedExceptionMessage Missing target URL in query
     */
    public function testNoRedirectAction()
    {
        $controller = new AbTestController();
        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn(null);

        $controller->countAction();
    }
}
