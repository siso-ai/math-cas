<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\IntegrationGate;
use StreamGate\Gates\ResultGate;

class Phase20Test extends TestCase {
    private function createIntegrationStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new IntegrationGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Constants
    public function testIntegralOfConstant() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫5 dx", $stream->getId()));
        $stream->process();
        $this->assertEquals("5x + C", $stream->getResult());
    }
    
    // Linear
    public function testIntegralOfX() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫x dx", $stream->getId()));
        $stream->process();
        // x^(1+1)/(1+1) = x^2/2
        $this->assertEquals("0.5x^2 + C", $stream->getResult());
    }
    
    public function testIntegralOf2X() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫2x dx", $stream->getId()));
        $stream->process();
        // 2*x^2/2 = x^2
        $this->assertEquals("x^2 + C", $stream->getResult());
    }
    
    public function testIntegralOf3X() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫3x dx", $stream->getId()));
        $stream->process();
        // 3*x^2/2 = 1.5x^2
        $this->assertEquals("1.5x^2 + C", $stream->getResult());
    }
    
    // Quadratic
    public function testIntegralOfXSquared() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫x^2 dx", $stream->getId()));
        $stream->process();
        // x^3/3 = 0.333...x^3
        $result = $stream->getResult();
        $this->assertStringStartsWith("0.333", $result);
        $this->assertStringContainsString("x^3", $result);
    }
    
    public function testIntegralOf3XSquared() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫3x^2 dx", $stream->getId()));
        $stream->process();
        // 3*x^3/3 = x^3
        $this->assertEquals("x^3 + C", $stream->getResult());
    }
    
    // Higher powers
    public function testIntegralOfXCubed() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫x^3 dx", $stream->getId()));
        $stream->process();
        // x^4/4 = 0.25x^4
        $this->assertEquals("0.25x^4 + C", $stream->getResult());
    }
    
    public function testIntegralOfXToThe5() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫x^5 dx", $stream->getId()));
        $stream->process();
        // x^6/6
        $result = $stream->getResult();
        $this->assertStringStartsWith("0.166", $result);
        $this->assertStringContainsString("x^6", $result);
    }
    
    // Polynomials
    public function testIntegralOfPolynomial() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫(x^2+2x) dx", $stream->getId()));
        $stream->process();
        // x^3/3 + x^2
        $result = $stream->getResult();
        $this->assertStringContainsString("x^3", $result);
        $this->assertStringContainsString("x^2", $result);
        $this->assertStringContainsString("+ C", $result);
    }
    
    public function testIntegralOfComplexPolynomial() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫(2x^3+3x^2+x) dx", $stream->getId()));
        $stream->process();
        // 2x^4/4 + 3x^3/3 + x^2/2 = 0.5x^4 + x^3 + 0.5x^2
        $result = $stream->getResult();
        $this->assertStringContainsString("x^4", $result);
        $this->assertStringContainsString("x^3", $result);
        $this->assertStringContainsString("x^2", $result);
    }
    
    // Alternative syntax: integrate(expr, var)
    public function testIntegrateSyntax() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("integrate(x^2, x)", $stream->getId()));
        $stream->process();
        $result = $stream->getResult();
        $this->assertStringContainsString("x^3", $result);
        $this->assertStringContainsString("+ C", $result);
    }
    
    // Alternative syntax: int(expr, var)
    public function testIntSyntax() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("int(x^3, x)", $stream->getId()));
        $stream->process();
        $this->assertEquals("0.25x^4 + C", $stream->getResult());
    }
    
    // Multiple variables (partial integration - treat others as constants)
    public function testPartialIntegrationXY() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫2xy dx", $stream->getId()));
        $stream->process();
        // Treat y as constant: 2y*x^2/2 = yx^2
        $this->assertEquals("x^2y + C", $stream->getResult());
    }
    
    // Negative coefficients
    public function testIntegralNegativeCoeff() {
        $stream = $this->createIntegrationStream();
        $stream->emit(new Event("∫-x^2 dx", $stream->getId()));
        $stream->process();
        $result = $stream->getResult();
        $this->assertStringStartsWith("-0.333", $result);
        $this->assertStringContainsString("x^3", $result);
    }
}
