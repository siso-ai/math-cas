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
use StreamGate\Gates\ResultGate;

class Phase7Test extends TestCase {
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
        $stream->registerGate(new PartialOperationGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Modulo Tests
    public function testSimpleModulo() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10%3", $stream->getId()));
        $stream->process();
        $this->assertEquals("1", $stream->getResult());
    }
    
    public function testModuloLarger() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("17%5", $stream->getId()));
        $stream->process();
        $this->assertEquals("2", $stream->getResult());
    }
    
    public function testModuloZeroRemainder() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("8%4", $stream->getId()));
        $stream->process();
        $this->assertEquals("0", $stream->getResult());
    }
    
    public function testModuloSmaller() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10%7", $stream->getId()));
        $stream->process();
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testModuloLarge() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("100%25", $stream->getId()));
        $stream->process();
        $this->assertEquals("0", $stream->getResult());
    }
    
    public function testModuloWithAddition() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10%3+5", $stream->getId()));
        $stream->process();
        // Should be (10%3)+5 = 1+5 = 6
        $this->assertEquals("6", $stream->getResult());
    }
    
    public function testModuloByZero() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Modulo by zero");
        
        $stream = $this->createFullStream();
        $stream->emit(new Event("10%0", $stream->getId()));
        $stream->process();
    }
    
    // Factorial Tests
    public function testFactorialFive() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("5!", $stream->getId()));
        $stream->process();
        $this->assertEquals("120", $stream->getResult());
    }
    
    public function testFactorialThree() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("3!", $stream->getId()));
        $stream->process();
        $this->assertEquals("6", $stream->getResult());
    }
    
    public function testFactorialZero() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("0!", $stream->getId()));
        $stream->process();
        $this->assertEquals("1", $stream->getResult());
    }
    
    public function testFactorialOne() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("1!", $stream->getId()));
        $stream->process();
        $this->assertEquals("1", $stream->getResult());
    }
    
    public function testFactorialSeven() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("7!", $stream->getId()));
        $stream->process();
        $this->assertEquals("5040", $stream->getResult());
    }
    
    // Combined Tests
    public function testFactorialAddition() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("3!+2!", $stream->getId()));
        $stream->process();
        // 3! = 6, 2! = 2, 6+2 = 8
        $this->assertEquals("8", $stream->getResult());
    }
    
    public function testFactorialMultiplication() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("4!*2", $stream->getId()));
        $stream->process();
        // 4! = 24, 24*2 = 48
        $this->assertEquals("48", $stream->getResult());
    }
    
    public function testModuloWithFactorial() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("10%3!", $stream->getId()));
        $stream->process();
        // 3! = 6, 10%6 = 4
        $this->assertEquals("4", $stream->getResult());
    }
    
    // Error Tests
    public function testFactorialNegative() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Factorial only defined for non-negative numbers");
        
        $stream = $this->createFullStream();
        $stream->emit(new Event("(-5)!", $stream->getId()));
        $stream->process();
    }
    
    public function testFactorialDecimal() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Factorial only defined for integers");
        
        $stream = $this->createFullStream();
        $stream->emit(new Event("3.5!", $stream->getId()));
        $stream->process();
    }
    
    public function testFactorialTooLarge() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Factorial limited to n <= 20");
        
        $stream = $this->createFullStream();
        $stream->emit(new Event("21!", $stream->getId()));
        $stream->process();
    }
}
