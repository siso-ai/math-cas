<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\LoggingLevel;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\DivideGate;
use StreamGate\Gates\DontMatchGate;
use StreamGate\Gates\ResultGate;

class Phase23Test extends TestCase {
    // Test 1: Logging OFF (default) - no overhead
    public function testLoggingOff() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        $stream->registerGate(new ResultGate());
        
        // Default should be OFF
        $this->assertEquals(LoggingLevel::OFF, $stream->getLoggingLevel());
        
        $stream->emit(new Event("2+3", $stream->getId()));
        $stream->process();
        
        $this->assertEquals("5", $stream->getResult());
        $this->assertEquals([], $stream->getHistory());
    }
    
    // Test 2: Logging MINIMAL - just gate names
    public function testLoggingMinimal() {
        $stream = new Stream();
        $stream->setLoggingLevel(LoggingLevel::MINIMAL);
        $stream->registerGate(new AddGate());
        $stream->registerGate(new ResultGate());
        
        $stream->emit(new Event("2+3", $stream->getId()));
        $stream->process();
        
        $this->assertEquals("5", $stream->getResult());
        $path = $stream->getTransformationPath();
        $this->assertContains('StreamGate\\Gates\\AddGate', $path);
    }
    
    // Test 3: Logging DETAILED - before/after states
    public function testLoggingDetailed() {
        $stream = new Stream();
        $stream->setLoggingLevel(LoggingLevel::DETAILED);
        $stream->registerGate(new AddGate());
        $stream->registerGate(new ResultGate());
        
        $stream->emit(new Event("2+3", $stream->getId()));
        $stream->process();
        
        $this->assertEquals("5", $stream->getResult());
        
        $history = $stream->getHistory();
        $this->assertNotEmpty($history);
        $this->assertEquals('StreamGate\\Gates\\AddGate', $history[0]['gate']);
        $this->assertEquals('2+3', $history[0]['before']);
        $this->assertEquals('consumed', $history[0]['after']);
    }
    
    // Test 4: DontMatchGate - invalid input
    public function testDontMatchGateInvalidInput() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        $stream->registerGate(new SubtractGate());
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new DivideGate());
        $stream->registerGate(new DontMatchGate());
        $stream->registerGate(new ResultGate());
        
        $stream->emit(new Event("2++3", $stream->getId()));
        $stream->process();
        
        $result = $stream->getResult();
        $this->assertStringStartsWith("Error:", $result);
        $this->assertStringContainsString("2++3", $result);
    }
    
    // Test 5: DontMatchGate - unrecognized operator
    public function testDontMatchGateUnrecognizedOperator() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        $stream->registerGate(new DontMatchGate());
        $stream->registerGate(new ResultGate());
        
        $stream->emit(new Event("2@3", $stream->getId()));
        $stream->process();
        
        $result = $stream->getResult();
        $this->assertStringStartsWith("Error:", $result);
        $this->assertStringContainsString("Unrecognized expression", $result);
    }
    
    // Test 6: Valid input should not trigger DontMatchGate
    public function testDontMatchGateDoesNotTriggerOnValid() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        $stream->registerGate(new DontMatchGate());
        $stream->registerGate(new ResultGate());
        
        $stream->emit(new Event("2+3", $stream->getId()));
        $stream->process();
        
        $result = $stream->getResult();
        $this->assertEquals("5", $result);
        $this->assertStringNotContainsString("Error", $result);
    }
    
    // Test 7: Multiple operations with logging
    public function testMultipleOperationsWithLogging() {
        $stream = new Stream();
        $stream->setLoggingLevel(LoggingLevel::DETAILED);
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new AddGate());
        $stream->registerGate(new ResultGate());
        
        $stream->emit(new Event("2*3", $stream->getId()));
        $stream->process();
        
        $this->assertEquals("6", $stream->getResult());
        
        $history = $stream->getHistory();
        $this->assertNotEmpty($history);
        $this->assertEquals('2*3', $history[0]['before']);
    }
    
    // Test 8: DEBUG level includes rejection info
    public function testDebugLevelIncludes Rejection() {
        $stream = new Stream();
        $stream->setLoggingLevel(LoggingLevel::DEBUG);
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new DivideGate());
        $stream->registerGate(new AddGate());
        $stream->registerGate(new DontMatchGate());
        $stream->registerGate(new ResultGate());
        
        $stream->emit(new Event("invalid", $stream->getId()));
        $stream->process();
        
        $result = $stream->getResult();
        $this->assertStringContainsString("Error", $result);
        $this->assertStringContainsString("Rejected by", $result);
    }
}
