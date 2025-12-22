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
use StreamGate\Gates\SquareRootGate;
use StreamGate\Gates\NthRootGate;
use StreamGate\Gates\FloorGate;
use StreamGate\Gates\CeilGate;
use StreamGate\Gates\AbsoluteValueGate;
use StreamGate\Gates\ResultGate;

class Phase6Test extends TestCase {
    private function createFullStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new FactorialGate());
        $stream->registerGate(new SquareRootGate());
        $stream->registerGate(new NthRootGate());
        $stream->registerGate(new FloorGate());
        $stream->registerGate(new CeilGate());
        $stream->registerGate(new AbsoluteValueGate());
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
    
    // Square Root Tests
    public function testSimpleSquareRoot() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("√4", $stream->getId()));
        $stream->process();
        $this->assertEquals("2", $stream->getResult());
    }
    
    public function testSquareRootNine() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("√9", $stream->getId()));
        $stream->process();
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testSquareRootSixteen() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("√16", $stream->getId()));
        $stream->process();
        $this->assertEquals("4", $stream->getResult());
    }
    
    public function testSquareRootNonPerfect() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("√2", $stream->getId()));
        $stream->process();
        $result = floatval($stream->getResult());
        $this->assertEqualsWithDelta(1.414, $result, 0.01);
    }
    
    public function testSquareRootOfExpression() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("√(4+5)", $stream->getId()));
        $stream->process();
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testSquareRootZero() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("√0", $stream->getId()));
        $stream->process();
        $this->assertEquals("0", $stream->getResult());
    }
    
    // Nth Root Tests
    public function testCubeRoot() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("3√27", $stream->getId()));
        $stream->process();
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testCubeRootEight() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("3√8", $stream->getId()));
        $stream->process();
        $this->assertEquals("2", $stream->getResult());
    }
    
    public function testFourthRoot() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("4√16", $stream->getId()));
        $stream->process();
        $this->assertEquals("2", $stream->getResult());
    }
    
    public function testNthRootOfExpression() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("3√(1+7)", $stream->getId()));
        $stream->process();
        $this->assertEquals("2", $stream->getResult());
    }
    
    // Floor Tests
    public function testFloorPositive() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌊3.7⌋", $stream->getId()));
        $stream->process();
        $this->assertEquals("3", $stream->getResult());
    }
    
    public function testFloorNegative() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌊-3.7⌋", $stream->getId()));
        $stream->process();
        $this->assertEquals("-4", $stream->getResult());
    }
    
    public function testFloorInteger() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌊5⌋", $stream->getId()));
        $stream->process();
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testFloorOfExpression() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌊10/3⌋", $stream->getId()));
        $stream->process();
        $this->assertEquals("3", $stream->getResult());
    }
    
    // Ceiling Tests
    public function testCeilPositive() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌈3.2⌉", $stream->getId()));
        $stream->process();
        $this->assertEquals("4", $stream->getResult());
    }
    
    public function testCeilNegative() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌈-3.2⌉", $stream->getId()));
        $stream->process();
        $this->assertEquals("-3", $stream->getResult());
    }
    
    public function testCeilInteger() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌈5⌉", $stream->getId()));
        $stream->process();
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testCeilOfExpression() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌈10/3⌉", $stream->getId()));
        $stream->process();
        $this->assertEquals("4", $stream->getResult());
    }
    
    // Absolute Value Tests
    public function testAbsPositive() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("|5|", $stream->getId()));
        $stream->process();
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testAbsNegative() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("|-5|", $stream->getId()));
        $stream->process();
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testAbsZero() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("|0|", $stream->getId()));
        $stream->process();
        $this->assertEquals("0", $stream->getResult());
    }
    
    public function testAbsOfExpression() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("|3-7|", $stream->getId()));
        $stream->process();
        $this->assertEquals("4", $stream->getResult());
    }
    
    // Combined Operations
    public function testFloorPlusCeil() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌊5.9⌋+⌈5.1⌉", $stream->getId()));
        $stream->process();
        $this->assertEquals("11", $stream->getResult());
    }
    
    public function testAbsOfFloor() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("|⌊-3.7⌋|", $stream->getId()));
        $stream->process();
        $this->assertEquals("4", $stream->getResult());
    }
    
    public function testSquareRootWithOperations() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("√9+1", $stream->getId()));
        $stream->process();
        $this->assertEquals("4", $stream->getResult());
    }
    
    public function testMultiplyBySquareRoot() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("2*√9", $stream->getId()));
        $stream->process();
        $this->assertEquals("6", $stream->getResult());
    }
    
    public function testFloorMultiplied() {
        $stream = $this->createFullStream();
        $stream->emit(new Event("⌊3.7⌋*2", $stream->getId()));
        $stream->process();
        $this->assertEquals("6", $stream->getResult());
    }
}
