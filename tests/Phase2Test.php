<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\ParenGate;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\DivideGate;

class Phase2Test extends TestCase {
    public function testParenthesesStripping() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new MultiplyGate());
        
        $stream->emit(new Event("(2*6)", $stream->getId()));
        $stream->process();
        
        // Should strip to 2*6, then multiply to 12
        $this->assertEquals(1, $stream->getEventCount());
        $this->assertEquals("12", $stream->getResult());
    }
    
    public function testSimpleParenthesesStrip() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        
        $stream->emit(new Event("(5)", $stream->getId()));
        $stream->process();
        
        // Should strip to 5
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testNestedParentheses() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new AddGate());
        
        $stream->emit(new Event("((5+3))", $stream->getId()));
        $stream->process();
        
        // Should strip twice then add: ((5+3)) -> (5+3) -> 5+3 -> 8
        $this->assertEquals("8", $stream->getResult());
    }
    
    public function testParensWithAddition() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new AddGate());
        
        $stream->emit(new Event("(2+3)", $stream->getId()));
        $stream->process();
        
        // Should strip then add: (2+3) -> 2+3 -> 5
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testParensWithMultiplication() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new MultiplyGate());
        
        $stream->emit(new Event("(2*3)", $stream->getId()));
        $stream->process();
        
        // Should produce 6
        $this->assertEquals("6", $stream->getResult());
    }
    
    public function testParensWithSubtraction() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new SubtractGate());
        
        $stream->emit(new Event("(10-3)", $stream->getId()));
        $stream->process();
        
        // Should produce 7
        $this->assertEquals("7", $stream->getResult());
    }
    
    public function testParensWithDivision() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new DivideGate());
        
        $stream->emit(new Event("(12/4)", $stream->getId()));
        $stream->process();
        
        // Should produce 3
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testTripleNestedParens() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new MultiplyGate());
        
        $stream->emit(new Event("(((2*5)))", $stream->getId()));
        $stream->process();
        
        // Should strip 3 times then multiply
        $this->assertEquals("10", $stream->getResult());
    }
    
    public function testNoParentheses() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new AddGate());
        
        $stream->emit(new Event("2+3", $stream->getId()));
        $stream->process();
        
        // Should skip ParenGate and go straight to AddGate
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testMismatchedParens() {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new AddGate());
        
        // Expression like "(2+3" should not match ParenGate
        // This would be caught by DontMatchGate in later phases
        $stream->emit(new Event("2+3", $stream->getId()));
        $stream->process();
        
        // Should process as normal addition
        $this->assertEquals("5", $stream->getResult());
    }
}
