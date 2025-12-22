<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\IntegrationGate;
use StreamGate\Gates\DefiniteIntegralGate;
use StreamGate\Gates\SubstitutionGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\PowerGate;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;
use StreamGate\Gates\ResultGate;

class Phase21Test extends TestCase {
    private function createDefiniteIntegralStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new DefiniteIntegralGate());  // First - match definite integrals
        $stream->registerGate(new ResultGate());
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new IntegrationGate());
        $stream->registerGate(new SubstitutionGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new PowerGate());
        $stream->registerGate(new AddGate());
        $stream->registerGate(new SubtractGate());
        return $stream;
    }
    
    // Simple constants
    public function testDefiniteIntegralConstant() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[0,2] 5 dx", $stream->getId()));
        $stream->process();
        // ∫5 dx = 5x, F(2) - F(0) = 10 - 0 = 10
        $this->assertEquals("10", $stream->getResult());
    }
    
    // Linear functions
    public function testDefiniteIntegralLinear() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[0,2] x dx", $stream->getId()));
        $stream->process();
        // ∫x dx = x²/2, F(2) - F(0) = 2 - 0 = 2
        $this->assertEquals("2", $stream->getResult());
    }
    
    public function testDefiniteIntegralLinearWithCoeff() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[1,3] 2x dx", $stream->getId()));
        $stream->process();
        // ∫2x dx = x², F(3) - F(1) = 9 - 1 = 8
        $this->assertEquals("8", $stream->getResult());
    }
    
    // Quadratic functions
    public function testDefiniteIntegralQuadratic() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[0,2] x^2 dx", $stream->getId()));
        $stream->process();
        // ∫x² dx = x³/3, F(2) - F(0) = 8/3 - 0 = 2.666...
        $result = floatval($stream->getResult());
        $this->assertEqualsWithDelta(2.6667, $result, 0.001);
    }
    
    public function testDefiniteIntegralQuadraticNonZero() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[1,3] x^2 dx", $stream->getId()));
        $stream->process();
        // ∫x² dx = x³/3, F(3) - F(1) = 9 - 1/3 = 26/3 = 8.666...
        $result = floatval($stream->getResult());
        $this->assertEqualsWithDelta(8.6667, $result, 0.001);
    }
    
    // Cubic functions
    public function testDefiniteIntegralCubic() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[0,2] x^3 dx", $stream->getId()));
        $stream->process();
        // ∫x³ dx = x⁴/4, F(2) - F(0) = 4 - 0 = 4
        $this->assertEquals("4", $stream->getResult());
    }
    
    // Polynomial
    public function testDefiniteIntegralPolynomial() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[0,1] (x^2+2x) dx", $stream->getId()));
        $stream->process();
        // ∫(x²+2x) dx = x³/3+x², F(1) - F(0) = (1/3+1) - 0 = 4/3 = 1.333...
        $result = floatval($stream->getResult());
        $this->assertEqualsWithDelta(1.3333, $result, 0.001);
    }
    
    // Alternative syntax: int([a,b], expr, var)
    public function testIntSyntax() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("int([0,2], x^2, x)", $stream->getId()));
        $stream->process();
        $result = floatval($stream->getResult());
        $this->assertEqualsWithDelta(2.6667, $result, 0.001);
    }
    
    // Negative bounds
    public function testDefiniteIntegralNegativeBounds() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[-1,1] x^2 dx", $stream->getId()));
        $stream->process();
        // ∫x² dx = x³/3, F(1) - F(-1) = 1/3 - (-1/3) = 2/3 = 0.666...
        $result = floatval($stream->getResult());
        $this->assertEqualsWithDelta(0.6667, $result, 0.001);
    }
    
    // Verify area under curve
    public function testAreaUnderCurve() {
        $stream = $this->createDefiniteIntegralStream();
        $stream->emit(new Event("∫[0,3] 2x dx", $stream->getId()));
        $stream->process();
        // Area under y=2x from 0 to 3
        // ∫2x dx = x², F(3) - F(0) = 9 - 0 = 9
        $this->assertEquals("9", $stream->getResult());
    }
}
