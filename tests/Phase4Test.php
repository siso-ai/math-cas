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
use StreamGate\Gates\PrecedenceGate;
use StreamGate\Gates\ResultGate;

class Phase4Test extends TestCase {
    private function createFullStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new PrecedenceGate());
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new AddGate());
        $stream->registerGate(new SubtractGate());
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new DivideGate());
        $stream->registerGate(new PartialOperationGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    public function testMultiplicationBeforeAddition() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2+3*4", $stream->getId()));
        $stream->process();
        
        // Should be 2+(3*4) = 2+12 = 14, not (2+3)*4 = 5*4 = 20
        $this->assertEquals("14", $stream->getResult());
    }
    
    public function testDivisionBeforeSubtraction() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10-6/2", $stream->getId()));
        $stream->process();
        
        // Should be 10-(6/2) = 10-3 = 7, not (10-6)/2 = 4/2 = 2
        $this->assertEquals("7", $stream->getResult());
    }
    
    public function testMultiplicationBeforeSubtraction() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10-2*3", $stream->getId()));
        $stream->process();
        
        // Should be 10-(2*3) = 10-6 = 4, not (10-2)*3 = 8*3 = 24
        $this->assertEquals("4", $stream->getResult());
    }
    
    public function testDivisionBeforeAddition() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10+20/4", $stream->getId()));
        $stream->process();
        
        // Should be 10+(20/4) = 10+5 = 15, not (10+20)/4 = 30/4 = 7.5
        $this->assertEquals("15", $stream->getResult());
    }
    
    public function testMultipleHighPrecedence() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2*3*4", $stream->getId()));
        $stream->process();
        
        // Should be (2*3)*4 = 6*4 = 24
        $this->assertEquals("24", $stream->getResult());
    }
    
    public function testMultipleLowPrecedence() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10+5+3", $stream->getId()));
        $stream->process();
        
        // Should be (10+5)+3 = 15+3 = 18
        $this->assertEquals("18", $stream->getResult());
    }
    
    public function testMixedOperations() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2+3*4-6/2", $stream->getId()));
        $stream->process();
        
        // Should be 2+(3*4)-(6/2) = 2+12-3 = 11
        $this->assertEquals("11", $stream->getResult());
    }
    
    public function testLeftToRightSamePrecedence() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10-5-2", $stream->getId()));
        $stream->process();
        
        // Should be (10-5)-2 = 5-2 = 3, not 10-(5-2) = 10-3 = 7
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testDivisionLeftToRight() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("20/4/2", $stream->getId()));
        $stream->process();
        
        // Should be (20/4)/2 = 5/2 = 2.5, not 20/(4/2) = 20/2 = 10
        $this->assertEquals("2.5", $stream->getResult());
    }
    
    public function testComplexExpression() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("5+3*2-8/4", $stream->getId()));
        $stream->process();
        
        // Should be 5+(3*2)-(8/4) = 5+6-2 = 9
        $this->assertEquals("9", $stream->getResult());
    }
    
    public function testWithNegativeNumbers() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("-5+3*2", $stream->getId()));
        $stream->process();
        
        // Should be -5+(3*2) = -5+6 = 1
        $this->assertEquals("1", $stream->getResult());
    }
    
    public function testExpressionWithParentheses() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(2+3)*4", $stream->getId()));
        $stream->process();
        
        // Parentheses override precedence: should be 5*4 = 20
        $this->assertEquals("20", $stream->getResult());
    }
    
    public function testMixedParensAndPrecedence() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(2+3)*4+5", $stream->getId()));
        $stream->process();
        
        // Should be ((2+3)*4)+5 = (5*4)+5 = 20+5 = 25
        $this->assertEquals("25", $stream->getResult());
    }
    
    public function testPrecedenceWithParensRight() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2*3+(4+5)", $stream->getId()));
        $stream->process();
        
        // Should be (2*3)+(4+5) = 6+9 = 15
        $this->assertEquals("15", $stream->getResult());
    }
}
