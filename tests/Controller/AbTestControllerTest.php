<?php

namespace ABTesting\Tests\Controller;

use ABTesting\Controller\AbTestController;
use ABTesting\Engine;
use ABTesting\Exception\AbTestingException;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use Phalcon\Events\Manager as EventsManager;
use Exception;
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
            ->willReturnCallback(fn ($param) => match ($param) {
                'testName' => 'test',
                'winner' => 'winner',
                default => throw new Exception('Invalid call getParam("' . ((string) $param) . '")')
            })
        ;

        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn('https://www.example.org');
        $controller->request
            ->expects($this->once())
            ->method('getHTTPReferer')
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
            ->willReturnCallback(fn ($param) => match ($param) {
                'testName' => 'invalid',
                'winner' => 'winner',
                default => throw new Exception('Invalid call getParam("' . ((string) $param) . '")')
            })
        ;
        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn('https://www.example.org');
        $controller->request
            ->expects($this->once())
            ->method('getHTTPReferer')
            ->willReturn('https://www.example.org');

        /** @var Engine|MockObject $engine */
        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('invalid')
            ->willReturn(null);

        $engine
            ->expects($this->never())
            ->method('saveClick');

        $controller->countAction();
    }

    public function testCountActionNoReferer()
    {
        $controller = new AbTestController();

        $controller->response = $this->createMock(Response::class);
        $controller->response->expects($this->once())->method('redirect');

        $controller->dispatcher = $this->createMock(Dispatcher::class);
        $controller->dispatcher
            ->expects($this->never())
            ->method('getParam')
            ->willReturnCallback(fn ($param) => match ($param) {
                'testName' => 'invalid',
                'winner' => 'winner',
                default => throw new Exception('Invalid call getParam("' . ((string) $param) . '")')
            })
        ;
        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn('https://www.example.org');
        $controller->request
            ->expects($this->once())
            ->method('getHTTPReferer')
            ->willReturn('');

        /** @var Engine|MockObject $engine */
        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->never())
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
            ->willReturnCallback(fn ($param) => match ($param) {
                'testName' => 'test',
                'winner' => 'invalid',
                default => throw new Exception('Invalid call getParam("' . ((string) $param) . '")')
            })
        ;
        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn('https://www.example.org');
        $controller->request
            ->expects($this->once())
            ->method('getHTTPReferer')
            ->willReturn('https://www.example.org');

        /** @var Engine|MockObject $engine */
        $engine = $this->createMockForSingleton(Engine::class);
        $eventsManager = $this->createMock(EventsManager::class);
        $engine->expects($this->any())->method('getEventsManager')->willReturn($eventsManager);
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

    public function testCountErroredVariantAction()
    {
        $controller = new AbTestController();

        $controller->response = $this->createMock(Response::class);
        $controller->response->expects($this->once())->method('redirect');

        $controller->dispatcher = $this->createMock(Dispatcher::class);
        $controller->dispatcher
            ->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnCallback(fn ($param) => match ($param) {
                'testName' => 'test',
                'winner' => 'invalid',
                default => throw new Exception('Invalid call getParam("' . ((string) $param) . '")')
            })
        ;
        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn('https://www.example.org');
        $controller->request
            ->expects($this->once())
            ->method('getHTTPReferer')
            ->willReturn('https://www.example.org');

        /** @var Engine|MockObject $engine */
        $engine = $this->createMockForSingleton(Engine::class);
        $eventsManager = $this->createMock(EventsManager::class);
        $engine->expects($this->any())->method('getEventsManager')->willReturn($eventsManager);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('test')
            ->willThrowException(new Exception('Sample exception'));

        $engine
            ->expects($this->never())
            ->method('saveClick');

        $controller->countAction();
    }

    public function testNoRedirectAction()
    {
        $controller = new AbTestController();

        $controller->response = $this->createMock(Response::class);
        $controller->response->expects($this->once())->method('resetHeaders');
        $controller->response->expects($this->once())->method('setStatusCode')->with(404);

        $controller->request = $this->createMock(Request::class);
        $controller->request
            ->expects($this->once())
            ->method('getQuery')
            ->with('u')
            ->willReturn(null);

        $controller->countAction();
    }
}
