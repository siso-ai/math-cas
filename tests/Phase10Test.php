<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\DistributionGate;
use StreamGate\Gates\ResultGate;

class Phase10Test extends TestCase {
    private function createAlgebraStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new DistributionGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Simple Distribution
    public function testTwoTimesXPlusThree() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2(x+3)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x+6", $stream->getResult());
    }
    
    public function testThreeTimesXMinusTwo() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("3(x-2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("3x-6", $stream->getResult());
    }
    
    public function testFiveTimesYPlusOne() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("5(y+1)", $stream->getId()));
        $stream->process();
        $this->assertEquals("5y+5", $stream->getResult());
    }
    
    // Variable Multiplier
    public function testXTimesYPlusTwo() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x(y+2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("xy+2x", $stream->getResult());
    }
    
    public function testYTimesXPlusOne() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("y(x+1)", $stream->getId()));
        $stream->process();
        $this->assertEquals("xy+y", $stream->getResult());
    }
    
    // Multiple Terms Inside
    public function testTwoTimesXPlusYPlusZ() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2(x+y+z)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x+2y+2z", $stream->getResult());
    }
    
    public function testThreeTimesTwoXMinusYPlusFour() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("3(2x-y+4)", $stream->getId()));
        $stream->process();
        $this->assertEquals("6x-3y+12", $stream->getResult());
    }
    
    // Negative Multiplier
    public function testNegativeTwoTimesXPlusThree() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("-2(x+3)", $stream->getId()));
        $stream->process();
        $this->assertEquals("-2x-6", $stream->getResult());
    }
    
    public function testTwoTimesNegativeXPlusThree() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2(-x+3)", $stream->getId()));
        $stream->process();
        $this->assertEquals("-2x+6", $stream->getResult());
    }
    
    // Fractional
    public function testHalfTimesTwoXPlusFour() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("0.5(2x+4)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x+2", $stream->getResult());
    }
    
    // With Exponents
    public function testTwoTimesXSquaredPlusX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2(x^2+x)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x^2+2x", $stream->getResult());
    }
    
    // Combined Distribution
    public function testTwoTimesThreeXPlusFour() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2(3x+4)", $stream->getId()));
        $stream->process();
        $this->assertEquals("6x+8", $stream->getResult());
    }
    
    public function testXTimesXPlusOne() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x(x+1)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2+x", $stream->getResult());
    }
    
    // Multiple Variables
    public function testXYTimesAPlusB() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("xy(a+b)", $stream->getId()));
        $stream->process();
        $this->assertEquals("axy+bxy", $stream->getResult());
    }
    
    public function testComplexDistribution() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2x(3y+z)", $stream->getId()));
        $stream->process();
        $this->assertEquals("6xy+2xz", $stream->getResult());
    }
}
