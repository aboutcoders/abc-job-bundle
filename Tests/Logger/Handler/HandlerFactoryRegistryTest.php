<?php
/*
* This file is part of the job-bundle package.
*
* (c) Hannes Schulz <hannes.schulz@aboutcoders.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Abc\Bundle\JobBundle\Tests\Logger\Handler;

use Abc\Bundle\JobBundle\Logger\Handler\HandlerFactoryInterface;
use Abc\Bundle\JobBundle\Logger\Handler\HandlerFactoryRegistry;
use Abc\Bundle\JobBundle\Model\Job;
use Monolog\Handler\HandlerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Hannes Schulz <hannes.schulz@aboutcoders.com>
 */
class HandlerFactoryRegistryTest extends TestCase
{
    /**
     * @var HandlerFactoryRegistry
     */
    private $subject;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->subject = new HandlerFactoryRegistry();
    }

    /**
     * @param int     $level
     * @param boolean $bubble
     * @dataProvider provideLevels
     */
    public function testCreateHandlers($level, $bubble)
    {
        $job      = new Job();
        $factory1 = $this->createMock(HandlerFactoryInterface::class);
        $handler1 = $this->createMock(HandlerInterface::class);
        $factory2 = $this->createMock(HandlerFactoryInterface::class);
        $handler2 = $this->createMock(HandlerInterface::class);

        $factory1->expects($this->once())
            ->method('createHandler')
            ->with($job, $level, $bubble)
            ->willReturn($handler1);

        $factory2->expects($this->once())
            ->method('createHandler')
            ->with($job, $level, $bubble)
            ->willReturn($handler2);

        $this->subject->register($factory1);
        $this->subject->register($factory2);

        $handlers = $this->subject->createHandlers($job, $level, $bubble);

        $this->assertContains($handler1, $handlers);
        $this->assertContains($handler2, $handlers);
    }

    public static function provideLevels()
    {
        return [
            [100, true],
            [100, false]
        ];
    }
}