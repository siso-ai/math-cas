<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\DivideGate;

class Phase1Test extends TestCase {
    public function testAddition() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        
        $stream->emit(new Event("2+3", $stream->getId()));
        $stream->process();
        
        // Should have result: 5
        $this->assertEquals(1, $stream->getEventCount());
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testSubtraction() {
        $stream = new Stream();
        $stream->registerGate(new SubtractGate());
        
        $stream->emit(new Event("5-2", $stream->getId()));
        $stream->process();
        
        // Should have result: 3
        $this->assertEquals(1, $stream->getEventCount());
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testMultiplication() {
        $stream = new Stream();
        $stream->registerGate(new MultiplyGate());
        
        $stream->emit(new Event("2*6", $stream->getId()));
        $stream->process();
        
        // Should have result: 12
        $this->assertEquals(1, $stream->getEventCount());
        $this->assertEquals("12", $stream->getResult());
    }
    
    public function testDivision() {
        $stream = new Stream();
        $stream->registerGate(new DivideGate());
        
        $stream->emit(new Event("6/2", $stream->getId()));
        $stream->process();
        
        // Should have result: 3
        $this->assertEquals(1, $stream->getEventCount());
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testDivisionByZero() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Division by zero");
        
        $stream = new Stream();
        $stream->registerGate(new DivideGate());
        
        $stream->emit(new Event("5/0", $stream->getId()));
        $stream->process();
    }
    
    public function testNegativeNumbers() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        
        $stream->emit(new Event("-5+3", $stream->getId()));
        $stream->process();
        
        $this->assertEquals("-2", $stream->getResult());
    }
    
    public function testDecimalNumbers() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        
        $stream->emit(new Event("2.5+3.5", $stream->getId()));
        $stream->process();
        
        $this->assertEquals("6", $stream->getResult());
    }
    
    public function testAllBasicOperations() {
        $testCases = [
            ["10+5", "15"],
            ["10-5", "5"],
            ["10*5", "50"],
            ["10/5", "2"],
            ["7+3", "10"],
            ["100-50", "50"],
            ["3*4", "12"],
            ["20/4", "5"],
        ];
        
        foreach ($testCases as [$input, $expected]) {
            $stream = new Stream();
            $stream->registerGate(new AddGate());
            $stream->registerGate(new SubtractGate());
            $stream->registerGate(new MultiplyGate());
            $stream->registerGate(new DivideGate());
            
            $stream->emit(new Event($input, $stream->getId()));
            $stream->process();
            
            $this->assertEquals($expected, $stream->getResult(), "Failed for expression: $input");
        }
    }
    
    public function testMultipleGatesRegistered() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        $stream->registerGate(new SubtractGate());
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new DivideGate());
        
        // Only multiplication gate should match
        $stream->emit(new Event("3*7", $stream->getId()));
        $stream->process();
        
        $this->assertEquals("21", $stream->getResult());
    }
    
    public function testGateRejection() {
        $stream = new Stream();
        $stream->registerGate(new AddGate());
        $stream->registerGate(new MultiplyGate());
        
        // Emit subtraction - should be rejected by both gates
        $event = new Event("5-2", $stream->getId());
        $stream->emit($event);
        
        // Process - event should stay in stream as it doesn't match
        $stream->process();
        
        // Should still have 1 event (unmatched)
        $this->assertEquals(1, $stream->getEventCount());
    }
}
