<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\FOILGate;
use StreamGate\Gates\DerivativeGate;
use StreamGate\Gates\ProductRuleGate;
use StreamGate\Gates\ResultGate;

class Phase17Test extends TestCase {
    private function createProductRuleStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new ProductRuleGate());  // Before DerivativeGate
        $stream->registerGate(new DerivativeGate());
        $stream->registerGate(new FOILGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Simple products
    public function testProductRuleXTimesX() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((x)(x))", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x", $stream->getResult());
    }
    
    // Linear products
    public function testProductRuleXPlus1TimesXPlus2() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((x+1)(x+2))", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x+3", $stream->getResult());
    }
    
    public function testProductRuleXPlus1TimesXPlus3() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((x+1)(x+3))", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x+4", $stream->getResult());
    }
    
    // Products with constants
    public function testProductRule2XTimesXPlus1() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((2x)(x+1))", $stream->getId()));
        $stream->process();
        $this->assertEquals("4x+2", $stream->getResult());
    }
    
    public function testProductRule3XTimesX() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((3x)(x))", $stream->getId()));
        $stream->process();
        $this->assertEquals("6x", $stream->getResult());
    }
    
    // Higher powers
    public function testProductRuleXSquaredTimesXCubed() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((x^2)(x^3))", $stream->getId()));
        $stream->process();
        $this->assertEquals("5x^4", $stream->getResult());
    }
    
    public function testProductRuleXSquaredTimesX() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((x^2)(x))", $stream->getId()));
        $stream->process();
        $this->assertEquals("3x^2", $stream->getResult());
    }
    
    // Negative terms
    public function testProductRuleXMinus1TimesXPlus1() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((x-1)(x+1))", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x", $stream->getResult());
    }
    
    // Alternative syntax: diff((f)(g), x)
    public function testDiffSyntax() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("diff((x+1)(x+2), x)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x+3", $stream->getResult());
    }
    
    // Constant factor
    public function testProductRuleConstantTimes2X() {
        $stream = $this->createProductRuleStream();
        $stream->emit(new Event("d/dx((5)(2x))", $stream->getId()));
        $stream->process();
        $this->assertEquals("10", $stream->getResult());
    }
}
