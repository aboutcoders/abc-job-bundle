<?php
/*
* This file is part of the job-bundle package.
*
* (c) Hannes Schulz <hannes.schulz@aboutcoders.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Abc\Bundle\JobBundle\Tests\Job;

use Abc\Bundle\JobBundle\Job\Status;
use PHPUnit\Framework\TestCase;

/**
 * @author Hannes Schulz <hannes.schulz@aboutcoders.com>
 */
class StatusTest extends TestCase
{
    public function testGetTerminatedStatus()
    {
        $values = [Status::CANCELLED, Status::PROCESSED, Status::ERROR];
        foreach (Status::getTerminatedStatus() as $status) {
            /**
             * @var Status $status
             */
            $this->assertContains($status->getValue(), $values);
        }
    }

    public function testGetUnterminatedStatus()
    {
        $values = [Status::REQUESTED, Status::PROCESSING, Status::CANCELLING, Status::SLEEPING];
        foreach (Status::getUnterminatedStatus() as $status) {
            /**
             * @var Status $status
             */
            $this->assertContains($status->getValue(), $values);
        }
    }

    public function testEquals()
    {
        $this->assertTrue(Status::PROCESSED()->equals(Status::PROCESSED()));
        $this->assertFalse(Status::PROCESSED()->equals(Status::CANCELLED()));
    }
}