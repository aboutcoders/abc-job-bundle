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

use Abc\Bundle\JobBundle\Adapter\Bernard\ProducerAdapter;
use Abc\Bundle\JobBundle\Job\JobTypeInterface;
use Abc\Bundle\JobBundle\Job\JobTypeRegistry;
use Abc\Bundle\JobBundle\Job\ManagerInterface;
use Abc\Bundle\JobBundle\Job\Queue\Message;
use Bernard\Message\PlainMessage;
use Bernard\Producer;
use PHPUnit\Framework\TestCase;

/**
 * @author Hannes Schulz <hannes.schulz@aboutcoders.com>
 */
class ProducerAdapterTest extends TestCase
{
    /**
     * @var Producer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $producer;

    /**
     * @var JobTypeRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $manager;

    /**
     * @var ProducerAdapter
     */
    private $subject;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->producer = $this->getMockBuilder(Producer::class)->disableOriginalConstructor()->getMock();
        $this->registry = $this->getMockBuilder(JobTypeRegistry::class)->disableOriginalConstructor()->getMock();
        $this->manager  = $this->createMock(ManagerInterface::class);

        $this->subject = new ProducerAdapter($this->producer, $this->registry);
        $this->subject->setManager($this->manager);
    }

    public function testProduce()
    {
        $type    = 'JobType';
        $ticket  = 'JobTicket';
        $queue   = 'QueueName';
        $message = new Message($type, $ticket);

        $jobType = $this->createMock(JobTypeInterface::class);
        $jobType->expects($this->once())
            ->method('getQueue')
            ->willReturn($queue);

        $producerMessage = new PlainMessage('ConsumeJob', [
            'type'   => $type,
            'ticket' => $ticket
        ]);

        $this->registry->expects($this->once())
            ->method('get')
            ->with($message->getType())
            ->willReturn($jobType);

        $this->producer->expects($this->once())
            ->method('produce')
            ->with($producerMessage, $queue);

        $this->subject->produce($message);
    }

    public function testConsumeJob()
    {
        $type   = 'JobType';
        $ticket = 'JobTicket';

        $producerMessage = new PlainMessage('ConsumeJob', [
            'type'   => $type,
            'ticket' => $ticket
        ]);

        $this->manager->expects($this->once())
            ->method('onMessage')
            ->with(new Message($type, $ticket));

        $this->subject->consumeJob($producerMessage);
    }
}