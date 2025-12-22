<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Event;
use StreamGate\Stream;
use StreamGate\Gate;

class Phase0Test extends TestCase {
    public function testEventCreation() {
        $event = new Event("test_data", "stream_1");
        $this->assertEquals("test_data", $event->data);
        $this->assertEquals("stream_1", $event->streamId);
        $this->assertEquals([], $event->rejectedBy);
    }
    
    public function testEventRejection() {
        $event = new Event("test", "stream_1");
        $event->gatesInRoom = 3;
        
        $event->reject("Gate1");
        $this->assertEquals(["Gate1"], $event->rejectedBy);
        $this->assertFalse($event->isRejectedByAll());
        
        $event->reject("Gate2");
        $event->reject("Gate3");
        $this->assertTrue($event->isRejectedByAll());
    }
    
    public function testEventRejectionNoDuplicates() {
        $event = new Event("test", "stream_1");
        $event->gatesInRoom = 2;
        
        $event->reject("Gate1");
        $event->reject("Gate1"); // Try to reject twice
        $this->assertEquals(["Gate1"], $event->rejectedBy);
        $this->assertCount(1, $event->rejectedBy);
    }
    
    public function testStreamCreation() {
        $stream = new Stream();
        $this->assertNotNull($stream->getId());
        $this->assertEquals(0, $stream->getEventCount());
    }
    
    public function testStreamWithCustomId() {
        $stream = new Stream("custom_stream_id");
        $this->assertEquals("custom_stream_id", $stream->getId());
    }
    
    public function testStreamEmit() {
        $stream = new Stream();
        $event = new Event("data", $stream->getId());
        $stream->emit($event);
        
        $this->assertEquals(1, $stream->getEventCount());
        $this->assertTrue($stream->hasEvents());
    }
    
    public function testStreamMultipleEmits() {
        $stream = new Stream();
        $stream->emit(new Event("data1", $stream->getId()));
        $stream->emit(new Event("data2", $stream->getId()));
        $stream->emit(new Event("data3", $stream->getId()));
        
        $this->assertEquals(3, $stream->getEventCount());
    }
}
