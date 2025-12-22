<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\DistributionGate;
use StreamGate\Gates\SubstitutionGate;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\ExponentGate;
use StreamGate\Gates\ResultGate;

class Phase11Test extends TestCase {
    private function createFullStream(): Stream {
        $stream = new Stream();
        // Algebraic gates
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new DistributionGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new SubstitutionGate());
        // Arithmetic gates
        $stream->registerGate(new AddGate());
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new ExponentGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Single Variable
    public function testSubstituteX() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 5);
        $stream->emit(new Event("x", $stream->getId()));
        $stream->process();
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testSubstituteTwoX() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 5);
        $stream->emit(new Event("2x", $stream->getId()));
        $stream->process();
        $this->assertEquals("10", $stream->getResult());
    }
    
    public function testSubstituteTwoXPlusThree() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 5);
        $stream->emit(new Event("2x+3", $stream->getId()));
        $stream->process();
        $this->assertEquals("13", $stream->getResult());
    }
    
    // Multiple Variables
    public function testSubstituteXPlusY() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 2);
        $stream->setVariable('y', 3);
        $stream->emit(new Event("x+y", $stream->getId()));
        $stream->process();
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testSubstituteTwoXPlusY() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 2);
        $stream->setVariable('y', 3);
        $stream->emit(new Event("2x+y", $stream->getId()));
        $stream->process();
        $this->assertEquals("7", $stream->getResult());
    }
    
    public function testSubstituteXY() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 2);
        $stream->setVariable('y', 3);
        $stream->emit(new Event("xy", $stream->getId()));
        $stream->process();
        $this->assertEquals("6", $stream->getResult());
    }
    
    // With Exponents
    public function testSubstituteXSquared() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 3);
        $stream->emit(new Event("x^2", $stream->getId()));
        $stream->process();
        $this->assertEquals("9", $stream->getResult());
    }
    
    public function testSubstituteXSquaredPlusTwoXPlusOne() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 4);
        $stream->emit(new Event("x^2+2x+1", $stream->getId()));
        $stream->process();
        $this->assertEquals("25", $stream->getResult());
    }
    
    // Partial Substitution
    public function testPartialSubstituteTwoXPlusY() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 2);
        $stream->emit(new Event("2x+y", $stream->getId()));
        $stream->process();
        $this->assertEquals("4+y", $stream->getResult());
    }
    
    public function testPartialSubstituteXPlusThreeY() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 5);
        $stream->emit(new Event("x+3y", $stream->getId()));
        $stream->process();
        $this->assertEquals("5+3y", $stream->getResult());
    }
    
    // Zero Value
    public function testSubstituteXZero() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 0);
        $stream->emit(new Event("5x+7", $stream->getId()));
        $stream->process();
        $this->assertEquals("7", $stream->getResult());
    }
    
    // Complex
    public function testSubstituteComplex() {
        $stream = $this->createFullStream();
        $stream->setVariable('x', 2);
        $stream->setVariable('y', 3);
        $stream->emit(new Event("2x^2+xy+3", $stream->getId()));
        $stream->process();
        $this->assertEquals("17", $stream->getResult());
    }
}
