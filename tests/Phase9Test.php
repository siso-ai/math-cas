<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\ResultGate;

class Phase9Test extends TestCase {
    private function createAlgebraStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Simple Addition
    public function testXPlusX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x+x", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x", $stream->getResult());
    }
    
    public function testTwoXPlusThreeX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2x+3x", $stream->getId()));
        $stream->process();
        $this->assertEquals("5x", $stream->getResult());
    }
    
    public function testYPlusYPlusY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("y+y+y", $stream->getId()));
        $stream->process();
        $this->assertEquals("3y", $stream->getResult());
    }
    
    // Subtraction
    public function testFiveXMinusTwoX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("5x-2x", $stream->getId()));
        $stream->process();
        $this->assertEquals("3x", $stream->getResult());
    }
    
    public function testXMinusX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x-x", $stream->getId()));
        $stream->process();
        $this->assertEquals("0", $stream->getResult());
    }
    
    // Multiple Variables (like terms)
    public function testXYPlusXY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("xy+xy", $stream->getId()));
        $stream->process();
        $this->assertEquals("2xy", $stream->getResult());
    }
    
    public function testTwoXYPlusThreeXY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2xy+3xy", $stream->getId()));
        $stream->process();
        $this->assertEquals("5xy", $stream->getResult());
    }
    
    // Unlike Terms (stay separate)
    public function testXPlusY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x+y", $stream->getId()));
        $stream->process();
        $this->assertEquals("x+y", $stream->getResult());
    }
    
    public function testXSquaredPlusX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x^2+x", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2+x", $stream->getResult());
    }
    
    public function testTwoXPlusThree() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2x+3", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x+3", $stream->getResult());
    }
    
    // Mixed (combine what we can)
    public function testTwoXPlusThreePlusFourXPlusFive() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2x+3+4x+5", $stream->getId()));
        $stream->process();
        $this->assertEquals("6x+8", $stream->getResult());
    }
    
    public function testXPlusTwoYPlusThreeXMinusY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x+2y+3x-y", $stream->getId()));
        $stream->process();
        $this->assertEquals("4x+y", $stream->getResult());
    }
    
    // Coefficients
    public function testDecimalCoefficients() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("0.5x+1.5x", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x", $stream->getResult());
    }
    
    public function testNegativeXPlusTwoX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("-x+2x", $stream->getId()));
        $stream->process();
        $this->assertEquals("x", $stream->getResult());
    }
    
    public function testComplexCombination() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("3x+2y+5x-y+4", $stream->getId()));
        $stream->process();
        $this->assertEquals("8x+y+4", $stream->getResult());
    }
}
