<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\DistributionGate;
use StreamGate\Gates\FOILGate;
use StreamGate\Gates\ResultGate;

class Phase12Test extends TestCase {
    private function createAlgebraStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new FOILGate());
        $stream->registerGate(new DistributionGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Basic FOIL
    public function testXPlusOneTimesXPlusTwo() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x+1)(x+2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2+3x+2", $stream->getResult());
    }
    
    public function testXPlusThreeTimesXMinusTwo() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x+3)(x-2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2+x-6", $stream->getResult());
    }
    
    public function testXMinusOneTimesXPlusOne() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x-1)(x+1)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2-1", $stream->getResult());
    }
    
    // With Coefficients
    public function testTwoXPlusOneTimesThreeXPlusFour() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(2x+1)(3x+4)", $stream->getId()));
        $stream->process();
        $this->assertEquals("6x^2+11x+4", $stream->getResult());
    }
    
    public function testTwoXMinusOneTimesXPlusThree() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(2x-1)(x+3)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x^2+5x-3", $stream->getResult());
    }
    
    // Difference of Squares
    public function testXPlusYTimesXMinusY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x+y)(x-y)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2-y^2", $stream->getResult());
    }
    
    public function testAPlusBTimesAMinusB() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(a+b)(a-b)", $stream->getId()));
        $stream->process();
        $this->assertEquals("a^2-b^2", $stream->getResult());
    }
    
    // Perfect Squares
    public function testXPlusOneSquared() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x+1)(x+1)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2+2x+1", $stream->getResult());
    }
    
    public function testXMinusTwoSquared() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x-2)(x-2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2-4x+4", $stream->getResult());
    }
    
    // Multiple Variables
    public function testXPlusYTimesAPlusB() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x+y)(a+b)", $stream->getId()));
        $stream->process();
        $this->assertEquals("ax+bx+ay+by", $stream->getResult()); // Order: variables sorted
    }
    
    // Negative Coefficients
    public function testNegativeXPlusOneTimesXPlusTwo() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(-x+1)(x+2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("-x^2-x+2", $stream->getResult());
    }
    
    // Three Terms
    public function testXPlusYPlusZTimesAPlusB() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x+y+z)(a+b)", $stream->getId()));
        $stream->process();
        $this->assertEquals("ax+bx+ay+by+az+bz", $stream->getResult()); // Order: variables sorted
    }
    
    // Constant Terms
    public function testTwoPlusXTimesThreePlusY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(2+x)(3+y)", $stream->getId()));
        $stream->process();
        $this->assertEquals("6+2y+3x+xy", $stream->getResult()); // Order: constants first, then by variables
    }
    
    // Complex
    public function testTwoXPlusThreeYTimesXMinusY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(2x+3y)(x-y)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x^2+xy-3y^2", $stream->getResult());
    }
    
    public function testXSquaredPlusXTimesXMinusOne() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("(x^2+x)(x-1)", $stream->getId()));
        $stream->process();
        // (x^2+x)(x-1) = x^3 - x^2 + x^2 - x = x^3 - x (correct!)
        $this->assertEquals("x^3-x", $stream->getResult());
    }
}
