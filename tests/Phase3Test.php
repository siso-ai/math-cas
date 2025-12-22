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
use StreamGate\Gates\PartialOperationGate;
use StreamGate\Gates\ResultGate;

class Phase3Test extends TestCase {
    private function createFullStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new AddGate());
        $stream->registerGate(new SubtractGate());
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new DivideGate());
        $stream->registerGate(new PartialOperationGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    public function testPartialAdditionLeft() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(2*3)+5", $stream->getId()));
        $stream->process();
        
        // Should resolve to: 6+5 = 11
        $this->assertEquals("11", $stream->getResult());
    }
    
    public function testPartialAdditionRight() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("5+(2*3)", $stream->getId()));
        $stream->process();
        
        // Should resolve to: 5+6 = 11
        $this->assertEquals("11", $stream->getResult());
    }
    
    public function testPartialMultiplicationLeft() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(2+3)*4", $stream->getId()));
        $stream->process();
        
        // Should resolve to: 5*4 = 20
        $this->assertEquals("20", $stream->getResult());
    }
    
    public function testPartialMultiplicationRight() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("4*(2+3)", $stream->getId()));
        $stream->process();
        
        // Should resolve to: 4*5 = 20
        $this->assertEquals("20", $stream->getResult());
    }
    
    public function testPartialSubtractionLeft() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(10-3)-2", $stream->getId()));
        $stream->process();
        
        // Should resolve to: 7-2 = 5
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testPartialSubtractionRight() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10-(3+2)", $stream->getId()));
        $stream->process();
        
        // Should resolve to: 10-5 = 5
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testPartialDivisionLeft() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(20/4)/2", $stream->getId()));
        $stream->process();
        
        // Should resolve to: 5/2 = 2.5
        $this->assertEquals("2.5", $stream->getResult());
    }
    
    public function testPartialDivisionRight() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("20/(2*2)", $stream->getId()));
        $stream->process();
        
        // Should resolve to: 20/4 = 5
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testNestedPartialOperations() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("((2+3)*2)+1", $stream->getId()));
        $stream->process();
        
        // Should resolve: (2+3) -> 5, 5*2 -> 10, 10+1 -> 11
        $this->assertEquals("11", $stream->getResult());
    }
    
    public function testComplexNestedExpression() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(2*6)*3", $stream->getId()));
        $stream->process();
        
        // Should resolve: (2*6) -> 12, 12*3 -> 36
        $this->assertEquals("36", $stream->getResult());
    }
    
    public function testDeepNesting() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(((2+3)*2)+1)*2", $stream->getId()));
        $stream->process();
        
        // Should resolve: 2+3->5, 5*2->10, 10+1->11, 11*2->22
        $this->assertEquals("22", $stream->getResult());
    }
    
    public function testPartialWithDecimal() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(10/4)+2.5", $stream->getId()));
        $stream->process();
        
        // Should resolve: 10/4->2.5, 2.5+2.5->5
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testMultiplePartials() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(2+3)+(4*5)", $stream->getId()));
        $stream->process();
        
        // Should resolve: 2+3->5, 4*5->20, 5+20->25
        $this->assertEquals("25", $stream->getResult());
    }
    
    public function testPartialSubtractionOrder() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2-(3+1)", $stream->getId()));
        $stream->process();
        
        // Should resolve: 3+1->4, 2-4->-2
        $this->assertEquals("-2", $stream->getResult());
    }
    
    public function testPartialDivisionOrder() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("12/(2*2)", $stream->getId()));
        $stream->process();
        
        // Should resolve: 2*2->4, 12/4->3
        $this->assertEquals("3", $stream->getResult());
    }
}
