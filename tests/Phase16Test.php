<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\DerivativeGate;
use StreamGate\Gates\ResultGate;

class Phase16Test extends TestCase {
    private function createDerivativeStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new DerivativeGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Constants
    public function testDerivativeOfConstant() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(5)", $stream->getId()));
        $stream->process();
        $this->assertEquals("0", $stream->getResult());
    }
    
    // Linear
    public function testDerivativeOfX() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(x)", $stream->getId()));
        $stream->process();
        $this->assertEquals("1", $stream->getResult());
    }
    
    public function testDerivativeOf2X() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(2x)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2", $stream->getResult());
    }
    
    public function testDerivativeOf3XPlus5() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(3x+5)", $stream->getId()));
        $stream->process();
        $this->assertEquals("3", $stream->getResult());
    }
    
    // Quadratic
    public function testDerivativeOfXSquared() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(x^2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x", $stream->getResult());
    }
    
    public function testDerivativeOf3XSquared() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(3x^2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("6x", $stream->getResult());
    }
    
    public function testDerivativeOfXSquaredPlus2XPlus1() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(x^2+2x+1)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x+2", $stream->getResult());
    }
    
    // Higher powers
    public function testDerivativeOfXCubed() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(x^3)", $stream->getId()));
        $stream->process();
        $this->assertEquals("3x^2", $stream->getResult());
    }
    
    public function testDerivativeOfXToThe5() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(x^5)", $stream->getId()));
        $stream->process();
        $this->assertEquals("5x^4", $stream->getResult());
    }
    
    public function testDerivativeOfComplexPolynomial() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(2x^3-3x^2+x)", $stream->getId()));
        $stream->process();
        $this->assertEquals("6x^2-6x+1", $stream->getResult());
    }
    
    // Alternative syntax: diff(expr, var)
    public function testDiffSyntax() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("diff(x^2, x)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x", $stream->getResult());
    }
    
    // Alternative syntax: derivative(expr, var)
    public function testDerivativeSyntax() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("derivative(x^3, x)", $stream->getId()));
        $stream->process();
        $this->assertEquals("3x^2", $stream->getResult());
    }
    
    // Multiple variables (partial derivative - treat others as constants)
    public function testPartialDerivativeXY() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(x^2*y)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2xy", $stream->getResult());
    }
    
    public function testPartialDerivativeYX() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dy(x^2*y)", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2", $stream->getResult());
    }
    
    // Negative coefficients
    public function testDerivativeNegativeCoeff() {
        $stream = $this->createDerivativeStream();
        $stream->emit(new Event("d/dx(-x^2)", $stream->getId()));
        $stream->process();
        $this->assertEquals("-2x", $stream->getResult());
    }
}
