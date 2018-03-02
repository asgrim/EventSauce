<?php

namespace EventSauce\EventSourcing\Integration\Upcasting;

use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\UpcastingMessageSerializer;
use EventSauce\EventSourcing\Time\TestClock;
use EventSauce\EventSourcing\Upcasting\DelegatingUpcaster;
use PHPUnit\Framework\TestCase;
use function iterator_to_array;

class UpcastingEventsTest extends TestCase
{
    /**
     * @test
     */
    public function upcasting_works()
    {
        $clock = new TestClock();
        $pointInTime = $clock->pointInTime();
        $defaultDecorator = new DefaultHeadersDecorator();
        $eventType = (new DotSeparatedSnakeCaseInflector())->classNameToType(UpcastedEventStub::class);
        $payload = [
            'headers' => [
                Header::EVENT_TYPE        => $eventType,
                Header::TIME_OF_RECORDING => $pointInTime->toString(),
            ],
            'payload' => [],
        ];

        $upcaster = new DelegatingUpcaster(new UpcasterStub());
        $serializer = new UpcastingMessageSerializer(new ConstructingMessageSerializer(), $upcaster);

        $message = iterator_to_array($serializer->unserializePayload($payload))[0];
        $expected = $defaultDecorator->decorate(new Message(new UpcastedEventStub(
            $pointInTime,
            'upcasted'
        )))->withHeader('version', 1);

        $this->assertEquals($expected, $message);

    }
    /**
     * @test
     */
    public function upcasting_is_ignored_when_not_configured()
    {
        $clock = new TestClock();
        $pointInTime = $clock->pointInTime();
        $defaultDecorator = new DefaultHeadersDecorator();
        $eventType = (new DotSeparatedSnakeCaseInflector())->classNameToType(UpcastedEventStub::class);
        $payload = [
            'headers' => [
                Header::EVENT_TYPE        => $eventType,
                Header::TIME_OF_RECORDING => $pointInTime->toString(),
            ],
            'payload' => [],
        ];

        $upcaster = new DelegatingUpcaster();
        $serializer = new UpcastingMessageSerializer(new ConstructingMessageSerializer(), $upcaster);

        $message = iterator_to_array($serializer->unserializePayload($payload))[0];
        $expected = $defaultDecorator->decorate(new Message(new UpcastedEventStub(
            $pointInTime,
            'undefined'
        )));

        $this->assertEquals($expected, $message);

    }

    /**
     * @test
     */
    public function serializing_still_works()
    {
        $clock = new TestClock();
        $pointInTime = $clock->pointInTime();
        $upcaster = new DelegatingUpcaster(new UpcasterStub());
        $serializer = new UpcastingMessageSerializer(new ConstructingMessageSerializer(), $upcaster);

        $message = new Message(new UpcastedEventStub(
            $pointInTime,
            'a value'
        ));

        $serializeMessage = $serializer->serializeMessage($message);
        $expectedPayload = [
            'headers'        => [],
            'payload'         => [
                'property'            => 'a value',
            ],
        ];

        $this->assertEquals($expectedPayload, $serializeMessage);
    }
}