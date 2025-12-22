<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\EquationGate;
use StreamGate\Gates\ResultGate;

class Phase13Test extends TestCase {
    private function createEquationStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new EquationGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Simple Equations
    public function testTwoXPlusThreeEqualsSeven() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("2x+3=7", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=2", $stream->getResult());
    }
    
    public function testXPlusFiveEqualsEight() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("x+5=8", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=3", $stream->getResult());
    }
    
    public function testThreeXEqualsNine() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("3x=9", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=3", $stream->getResult());
    }
    
    // Variable on Both Sides
    public function testXPlusFiveEqualsTwoXPlusOne() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("x+5=2x+1", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=4", $stream->getResult());
    }
    
    public function testThreeXMinusTwoEqualsXPlusSix() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("3x-2=x+6", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=4", $stream->getResult());
    }
    
    // Reversed (Constant = Expression)
    public function testSevenEqualsTwoXPlusThree() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("7=2x+3", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=2", $stream->getResult());
    }
    
    public function testFiveEqualsTwoXPlusOne() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("5=2x+1", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=2", $stream->getResult());
    }
    
    // Negative Solutions
    public function testXPlusThreeEqualsOne() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("x+3=1", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=-2", $stream->getResult());
    }
    
    public function testTwoXEqualsNegativeFour() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("2x=-4", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=-2", $stream->getResult());
    }
    
    // Fractional Solutions
    public function testTwoXEqualsThree() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("2x=3", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=1.5", $stream->getResult());
    }
    
    public function testThreeXPlusOneEqualsEight() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("3x+1=8", $stream->getId()));
        $stream->process();
        // 3x = 7, x = 7/3 = 2.333...
        $this->assertStringStartsWith("x=2.33", $stream->getResult());
    }
    
    // Zero Solution
    public function testXPlusFiveEqualsFive() {
        $stream = $this->createEquationStream();
        $stream->emit(new Event("x+5=5", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=0", $stream->getResult());
    }
}
