<?php

namespace ABTesting\Tests\Plugin;

use ABTesting\Engine;
use ABTesting\Plugin\AnnotationListener;
use ABTesting\Test\Test;
use ABTesting\Tests\TestCase;
use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Annotation;
use Phalcon\Annotations\Collection;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class AnnotationListenerTest extends TestCase
{
    public function testBeforeDispatchNotTested()
    {
        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('deactivate');

        $event = $this->createMock(Event::class);

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects($this->once())
            ->method('getControllerClass')
            ->willReturn('ControllerClass');
        $dispatcher
            ->expects($this->once())
            ->method('getActiveMethod')
            ->willReturn('activeMethod');

        $annotationMethod = $this->createMock(Collection::class);
        $annotationMethod
            ->expects($this->once())
            ->method('has')
            ->with('AbTesting')
            ->willReturn(false);

        $annotations = $this->createMock(Adapter::class);
        $annotations
            ->expects($this->once())
            ->method('getMethod')
            ->with('ControllerClass', 'activeMethod')
            ->willReturn($annotationMethod);

        $listener = new AnnotationListener();
        $listener->annotations = $annotations;
        $listener->beforeDispatch($event, $dispatcher);
    }

    /**
     * @expectedException \ABTesting\Exception\AbTestingException
     */
    public function testBeforeDispatchIncompleteAnnotation()
    {
        $event = $this->createMock(Event::class);

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects($this->once())
            ->method('getControllerClass')
            ->willReturn('ControllerClass');
        $dispatcher
            ->expects($this->once())
            ->method('getActiveMethod')
            ->willReturn('activeMethod');

        $annotation = $this->createMock(Annotation::class);
        $annotation
            ->expects($this->once())
            ->method('getNamedArgument')
            ->with('name')
            ->willReturn(null);
        $annotation
            ->expects($this->once())
            ->method('getArgument')
            ->with(0)
            ->willReturn(null);

        $annotationMethod = $this->createMock(Collection::class);
        $annotationMethod
            ->expects($this->once())
            ->method('has')
            ->with('AbTesting')
            ->willReturn(true);
        $annotationMethod
            ->expects($this->once())
            ->method('getAll')
            ->with('AbTesting')
            ->willReturn([$annotation]);

        $annotations = $this->createMock(Adapter::class);
        $annotations
            ->expects($this->once())
            ->method('getMethod')
            ->with('ControllerClass', 'activeMethod')
            ->willReturn($annotationMethod);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->never())
            ->method('getTest')
            ->with('testName')
            ->willReturn($this->createMock(Test::class));

        $listener = new AnnotationListener();
        $listener->annotations = $annotations;
        $listener->beforeDispatch($event, $dispatcher);
    }

    public function testBeforeDispatch()
    {
        $event = $this->createMock(Event::class);

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects($this->once())
            ->method('getControllerClass')
            ->willReturn('ControllerClass');
        $dispatcher
            ->expects($this->once())
            ->method('getActiveMethod')
            ->willReturn('activeMethod');

        $annotation = $this->createMock(Annotation::class);
        $annotation
            ->expects($this->once())
            ->method('getNamedArgument')
            ->with('name')
            ->willReturn('testName');

        $annotationMethod = $this->createMock(Collection::class);
        $annotationMethod
            ->expects($this->once())
            ->method('has')
            ->with('AbTesting')
            ->willReturn(true);
        $annotationMethod
            ->expects($this->once())
            ->method('getAll')
            ->with('AbTesting')
            ->willReturn([$annotation]);

        $annotations = $this->createMock(Adapter::class);
        $annotations
            ->expects($this->once())
            ->method('getMethod')
            ->with('ControllerClass', 'activeMethod')
            ->willReturn($annotationMethod);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn($this->createMock(Test::class));

        $listener = new AnnotationListener();
        $listener->annotations = $annotations;
        $listener->beforeDispatch($event, $dispatcher);
    }
}
