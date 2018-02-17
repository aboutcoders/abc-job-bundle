<?php
/*
* This file is part of the job-bundle package.
*
* (c) Hannes Schulz <hannes.schulz@aboutcoders.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Abc\Bundle\JobBundle\Tests\Doctrine;

use Abc\Bundle\JobBundle\Doctrine\Job;
use Abc\Bundle\JobBundle\Serializer\Job\SerializationHelper;
use PHPUnit\Framework\TestCase;

/**
 * @author Hannes Schulz <hannes.schulz@aboutcoders.com>
 */
class JobTest extends TestCase
{
    /**
     * @var SerializationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializationHelper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->serializationHelper = $this->getMockBuilder(SerializationHelper::class)->disableOriginalConstructor()->getMock();
        Job::setSerializationHelper($this->serializationHelper);
    }

    public function testSetParameters()
    {
        $parameters = ['foobar'];

        $this->serializationHelper->expects($this->once())
            ->method('serializeParameters')
            ->with('JobType', $parameters)
            ->willReturn('serializedParameters');

        $job = new Job();
        $job->setType('JobType');
        $job->setParameters($parameters);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetParametersThrowsInvalidArgumentException()
    {
        $this->serializationHelper->expects($this->once())
            ->method('serializeParameters')
            ->willThrowException(new \Exception('asd'));

        $job = new Job();
        $job->setType('JobType');
        $job->setParameters(['foobar']);
    }

    public function testGetParameters()
    {
        $job = new Job();
        $job->setType('JobType');
        $this->setProperty($job, 'serializedParameters', 'SerializedParameters');

        $this->serializationHelper->expects($this->once())
            ->method('deserializeParameters')
            ->with('JobType', 'SerializedParameters')
            ->willReturn(['foobar']);


        $this->assertEquals(['foobar'], $job->getParameters());
    }

    public function testGetParametersWithNull()
    {
        $job = new Job();
        $job->setType('JobType');

        $this->serializationHelper->expects($this->never())
            ->method('deserializeParameters');

        $this->assertEquals(null, $job->getParameters());
    }

    public function testGetParametersWithSerializerException()
    {
        $job = new Job();
        $job->setType('JobType');
        $this->setProperty($job, 'serializedParameters', 'SerializedParameters');

        $this->serializationHelper->expects($this->any())
            ->method('deserializeParameters')
            ->willThrowException(new \Exception('Some deserialization error'));

        try {
            $job->getParameters();
        } catch (\Exception $e) {
            $this->assertNull($job->getParameters());
        }
    }

    public function testSetResponse()
    {
        $response = ['foobar'];

        $this->serializationHelper->expects($this->once())
            ->method('serializeReturnValue')
            ->with('JobType', $response)
            ->willReturn('SerializedResponse');

        $job = new Job();
        $job->setType('JobType');
        $job->setResponse($response);

        $this->assertAttributeEquals('SerializedResponse', 'serializedResponse', $job);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetResponseThrowsInvalidArgumentException()
    {
        $this->serializationHelper->expects($this->once())
            ->method('serializeReturnValue')
            ->willThrowException(new \Exception());

        $job = new Job();
        $job->setType('JobType');
        $job->setResponse(['foobar']);
    }

    public function testAGetResponse()
    {
        $job = new Job();
        $job->setType('JobType');
        $this->setProperty($job, 'serializedResponse', 'SerializedResponse');

        $this->serializationHelper->expects($this->once())
            ->method('deserializeReturnValue')
            ->with('JobType', 'SerializedResponse')
            ->willReturn(['foobar']);

        $this->assertEquals(['foobar'], $job->getResponse());
    }

    public function testGetResponseWithNull()
    {
        $job = new Job();
        $job->setType('JobType');

        $this->serializationHelper->expects($this->never())
            ->method('deserializeReturnValue');

        $this->assertEquals(null, $job->getResponse());
    }

    public function testGetResponseWithSerializerException()
    {
        $job = new Job();
        $job->setType('JobType');
        $this->setProperty($job, 'serializedResponse', 'SerializedResponse');

        $this->serializationHelper->expects($this->any())
            ->method('deserializeReturnValue')
            ->willThrowException(new \Exception('Some deserialization error'));

        try {
            $job->getResponse();
        } catch (\Exception $e) {
            $this->assertNull($job->getResponse());
        }
    }

    /**
     * @param mixed  $object
     * @param string $name
     * @param mixed  $value
     */
    private function setProperty($object, $name, $value)
    {
        $reflection         = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($name);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }
}