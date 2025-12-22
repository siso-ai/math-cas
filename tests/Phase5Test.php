<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\ParenGate;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\DivideGate;
use StreamGate\Gates\PartialOperationGate;
use StreamGate\Gates\PrecedenceGate;
use StreamGate\Gates\ModuloGate;
use StreamGate\Gates\FactorialGate;
use StreamGate\Gates\ExponentGate;
use StreamGate\Gates\ResultGate;

class Phase5Test extends TestCase {
    private function createFullStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new FactorialGate());
        $stream->registerGate(new PrecedenceGate());
        $stream->registerGate(new ParenGate());
        $stream->registerGate(new AddGate());
        $stream->registerGate(new SubtractGate());
        $stream->registerGate(new MultiplyGate());
        $stream->registerGate(new DivideGate());
        $stream->registerGate(new ModuloGate());
        $stream->registerGate(new ExponentGate());
        $stream->registerGate(new PartialOperationGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Basic Exponent Tests
    public function testSimpleExponent() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2^3", $stream->getId()));
        $stream->process();
        $this->assertEquals("8", $stream->getResult());
    }
    
    public function testSquare() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("5^2", $stream->getId()));
        $stream->process();
        $this->assertEquals("25", $stream->getResult());
    }
    
    public function testPowerOfZero() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10^0", $stream->getId()));
        $stream->process();
        $this->assertEquals("1", $stream->getResult());
    }
    
    public function testPowerOfOne() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2^1", $stream->getId()));
        $stream->process();
        $this->assertEquals("2", $stream->getResult());
    }
    
    public function testLargePower() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("3^4", $stream->getId()));
        $stream->process();
        $this->assertEquals("81", $stream->getResult());
    }
    
    // Negative Exponents
    public function testNegativeExponent() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2^-1", $stream->getId()));
        $stream->process();
        $this->assertEquals("0.5", $stream->getResult());
    }
    
    public function testNegativeExponentTwo() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2^-2", $stream->getId()));
        $stream->process();
        $this->assertEquals("0.25", $stream->getResult());
    }
    
    public function testNegativeExponentDecimal() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10^-3", $stream->getId()));
        $stream->process();
        $this->assertEquals("0.001", $stream->getResult());
    }
    
    // Decimal Bases/Exponents
    public function testDecimalBase() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2.5^2", $stream->getId()));
        $stream->process();
        $this->assertEquals("6.25", $stream->getResult());
    }
    
    public function testFractionalExponent() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("4^0.5", $stream->getId()));
        $stream->process();
        $this->assertEquals("2", $stream->getResult());
    }
    
    // Precedence Tests
    public function testExponentBeforeAddition() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2+3^2", $stream->getId()));
        $stream->process();
        // Should be 2+(3^2) = 2+9 = 11, not (2+3)^2 = 25
        $this->assertEquals("11", $stream->getResult());
    }
    
    public function testExponentBeforeMultiplication() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2*3^2", $stream->getId()));
        $stream->process();
        // Should be 2*(3^2) = 2*9 = 18, not (2*3)^2 = 36
        $this->assertEquals("18", $stream->getResult());
    }
    
    // Right-to-Left Associativity (CRITICAL TEST)
    public function testRightAssociativity() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2^2^3", $stream->getId()));
        $stream->process();
        // Should be 2^(2^3) = 2^8 = 256
        // NOT (2^2)^3 = 4^3 = 64
        $this->assertEquals("256", $stream->getResult());
    }
    
    public function testRightAssociativityThree() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2^3^2", $stream->getId()));
        $stream->process();
        // Should be 2^(3^2) = 2^9 = 512
        // NOT (2^3)^2 = 8^2 = 64
        $this->assertEquals("512", $stream->getResult());
    }
    
    // Combined Operations
    public function testExponentWithParentheses() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(2+3)^2", $stream->getId()));
        $stream->process();
        // Parentheses override: (2+3)^2 = 5^2 = 25
        $this->assertEquals("25", $stream->getResult());
    }
    
    public function testComplexExpression() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2^3+2^2", $stream->getId()));
        $stream->process();
        // 8 + 4 = 12
        $this->assertEquals("12", $stream->getResult());
    }
    
    public function testExponentOfExpressionRight() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2^(1+2)", $stream->getId()));
        $stream->process();
        // 2^3 = 8
        $this->assertEquals("8", $stream->getResult());
    }
    
    public function testExponentOfExpressionLeft() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("(2+2)^3", $stream->getId()));
        $stream->process();
        // 4^3 = 64
        $this->assertEquals("64", $stream->getResult());
    }
}
