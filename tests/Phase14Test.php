<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\FOILGate;
use StreamGate\Gates\FactoringGate;
use StreamGate\Gates\ResultGate;

class Phase14Test extends TestCase {
    private function createFactoringStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new FOILGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new FactoringGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Simple Factoring
    public function testXSquaredPlus5XPlus6() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2+5x+6", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x+2)(x+3)", $stream->getResult());
    }
    
    public function testXSquaredPlus7XPlus12() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2+7x+12", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x+3)(x+4)", $stream->getResult());
    }
    
    // Negative Constant
    public function testXSquaredMinusXMinus6() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2-x-6", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x+2)(x-3)", $stream->getResult());
    }
    
    public function testXSquaredPlusXMinus6() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2+x-6", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x+3)(x-2)", $stream->getResult());
    }
    
    // Difference of Squares
    public function testXSquaredMinus4() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2-4", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x+2)(x-2)", $stream->getResult());
    }
    
    public function testXSquaredMinus9() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2-9", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x+3)(x-3)", $stream->getResult());
    }
    
    // Perfect Squares
    public function testXSquaredPlus2XPlus1() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2+2x+1", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x+1)(x+1)", $stream->getResult());
    }
    
    public function testXSquaredMinus4XPlus4() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2-4x+4", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x-2)(x-2)", $stream->getResult());
    }
    
    // Negative Middle Coefficient
    public function testXSquaredMinus5XPlus6() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2-5x+6", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x-2)(x-3)", $stream->getResult());
    }
    
    public function testXSquaredMinus7XPlus10() {
        $stream = $this->createFactoringStream();
        $stream->emit(new Event("x^2-7x+10", $stream->getId()));
        $stream->process();
        $this->assertEquals("(x-2)(x-5)", $stream->getResult());
    }
}
