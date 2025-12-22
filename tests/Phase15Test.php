<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\EquationGate;
use StreamGate\Gates\QuadraticGate;
use StreamGate\Gates\ResultGate;

class Phase15Test extends TestCase {
    private function createQuadraticStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new QuadraticGate());
        $stream->registerGate(new EquationGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Two Solutions
    public function testXSquaredMinus5XPlus6Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2-5x+6=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=2,3", $stream->getResult());
    }
    
    public function testXSquaredMinus3XPlus2Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2-3x+2=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=1,2", $stream->getResult());
    }
    
    public function testXSquaredPlus5XPlus6Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2+5x+6=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=-3,-2", $stream->getResult());
    }
    
    // One Solution (Double Root)
    public function testXSquaredPlus2XPlus1Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2+2x+1=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=-1", $stream->getResult());
    }
    
    public function testXSquaredMinus4XPlus4Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2-4x+4=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=2", $stream->getResult());
    }
    
    // Difference of Squares
    public function testXSquaredMinus4Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2-4=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=-2,2", $stream->getResult());
    }
    
    public function testXSquaredMinus9Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2-9=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=-3,3", $stream->getResult());
    }
    
    // No Real Solutions
    public function testXSquaredPlus1Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2+1=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("no real solutions", $stream->getResult());
    }
    
    public function testXSquaredPlus4Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("x^2+4=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("no real solutions", $stream->getResult());
    }
    
    // Negative Leading Coefficient
    public function testNegativeXSquaredPlus4Equals0() {
        $stream = $this->createQuadraticStream();
        $stream->emit(new Event("-x^2+4=0", $stream->getId()));
        $stream->process();
        $this->assertEquals("x=-2,2", $stream->getResult());
    }
}
