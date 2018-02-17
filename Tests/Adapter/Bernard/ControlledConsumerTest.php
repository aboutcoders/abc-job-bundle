<?php
/*
* This file is part of the job-bundle package.
*
* (c) Hannes Schulz <hannes.schulz@aboutcoders.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Abc\Bundle\JobBundle\Tests\Adapter\Bernard;

use Abc\Bundle\JobBundle\Adapter\Bernard\ControlledConsumer;
use Abc\ProcessControl\ControllerInterface;
use Bernard\Queue;
use Bernard\Router;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Hannes Schulz <hannes.schulz@aboutcoders.com>
 */
class ControlledConsumerTest extends TestCase
{
    /**
     * @var Router|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var ControllerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $controller;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->router     = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->getMock();
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->controller = $this->createMock(ControllerInterface::class);
    }

    public function testConsumeChecksController()
    {
        $queue = $this->createMock(Queue::class);

        $this->controller->expects($this->once())
            ->method('doStop')
            ->willReturn(true);

        $subject = $this->buildSubject(['invoke']);
        $subject->consume($queue);

        $subject->expects($this->never())
            ->method('invoke');
    }

    public function testInvokesParentTick()
    {
        $queue = $this->createMock(Queue::class);

        $subject = $this->buildSubject(['configure']);

        $subject->expects($this->atLeastOnce())
            ->method('configure');

        $subject->consume($queue, [
            'max-runtime' => 1,
            'stop-when-empty' => true
        ]);
    }

    /**
     * @param array $methods
     * @return ControlledConsumer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function buildSubject(array $methods) {
        return $this->getMockBuilder(ControlledConsumer::class)
            ->setConstructorArgs([$this->router, $this->dispatcher, $this->controller])
            ->setMethods($methods)
            ->getMock();
    }
}