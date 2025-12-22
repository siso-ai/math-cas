<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\DerivativeGate;
use StreamGate\Gates\EquationGate;
use StreamGate\Gates\SubstitutionGate;
use StreamGate\Gates\CriticalPointsGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\ExponentGate;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;
use StreamGate\Gates\DivideGate;
use StreamGate\Gates\ResultGate;

class Phase22Test extends TestCase {
    private function createCriticalPointsStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new CriticalPointsGate());
        $stream->registerGate(new ResultGate());
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new AlgebraicAddGate());
        $stream->registerGate(new DerivativeGate());
        $stream->registerGate(new EquationGate());
        $stream->registerGate(new SubstitutionGate());
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new ExponentGate());
        $stream->registerGate(new AddGate());
        $stream->registerGate(new SubtractGate());
        $stream->registerGate(new DivideGate());
        return $stream;
    }
    
    // Quadratic - vertex
    public function testCriticalPointQuadratic() {
        $stream = $this->createCriticalPointsStream();
        $stream->emit(new Event("critical(x^2-4x+3)", $stream->getId()));
        $stream->process();
        // f(x) = x²-4x+3, f'(x) = 2x-4, critical: x=2, f(2)=-1
        $result = $stream->getResult();
        $this->assertStringContainsString("x=2", $result);
        $this->assertStringContainsString("value=-1", $result);
    }
    
    public function testCriticalPointParabola() {
        $stream = $this->createCriticalPointsStream();
        $stream->emit(new Event("critical(x^2+2x+1)", $stream->getId()));
        $stream->process();
        // f(x) = x²+2x+1, f'(x) = 2x+2, critical: x=-1, f(-1)=0
        $result = $stream->getResult();
        $this->assertStringContainsString("x=-1", $result);
        $this->assertStringContainsString("value=0", $result);
    }
    
    // Cubic
    public function testCriticalPointCubic() {
        $stream = $this->createCriticalPointsStream();
        $stream->emit(new Event("critical(x^3-3x+2)", $stream->getId()));
        $stream->process();
        // f(x) = x³-3x+2, f'(x) = 3x²-3, critical: x=1, f(1)=0
        $result = $stream->getResult();
        $this->assertStringContainsString("x=1", $result);
    }
    
    // Maximize syntax
    public function testMaximizeSyntax() {
        $stream = $this->createCriticalPointsStream();
        $stream->emit(new Event("maximize(-x^2+4x)", $stream->getId()));
        $stream->process();
        // f(x) = -x²+4x, f'(x) = -2x+4, critical: x=2, f(2)=4
        $result = $stream->getResult();
        $this->assertStringContainsString("x=2", $result);
        $this->assertStringContainsString("value=4", $result);
    }
    
    // Minimize syntax
    public function testMinimizeSyntax() {
        $stream = $this->createCriticalPointsStream();
        $stream->emit(new Event("minimize(x^2-6x+8)", $stream->getId()));
        $stream->process();
        // f(x) = x²-6x+8, f'(x) = 2x-6, critical: x=3, f(3)=-1
        $result = $stream->getResult();
        $this->assertStringContainsString("x=3", $result);
        $this->assertStringContainsString("value=-1", $result);
    }
    
    // Simple quadratic
    public function testCriticalPointSimple() {
        $stream = $this->createCriticalPointsStream();
        $stream->emit(new Event("critical(2x^2-8x+6)", $stream->getId()));
        $stream->process();
        // f(x) = 2x²-8x+6, f'(x) = 4x-8, critical: x=2, f(2)=-2
        $result = $stream->getResult();
        $this->assertStringContainsString("x=2", $result);
        $this->assertStringContainsString("value=-2", $result);
    }
    
    // Negative leading coefficient
    public function testCriticalPointNegative() {
        $stream = $this->createCriticalPointsStream();
        $stream->emit(new Event("critical(-x^2+6x-5)", $stream->getId()));
        $stream->process();
        // f(x) = -x²+6x-5, f'(x) = -2x+6, critical: x=3, f(3)=4
        $result = $stream->getResult();
        $this->assertStringContainsString("x=3", $result);
        $this->assertStringContainsString("value=4", $result);
    }
}
