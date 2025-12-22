<?php

namespace StreamGate\Tests;

use PHPUnit\Framework\TestCase;
use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\AlgebraicEvent;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\ResultGate;
use StreamGate\Algebra\Variable;
use StreamGate\Algebra\Term;
use StreamGate\Algebra\Expression;

class Phase8Test extends TestCase {
    private function createAlgebraStream(): Stream {
        $stream = new Stream();
        $stream->registerGate(new TermParseGate());
        $stream->registerGate(new ConstantTermGate());
        $stream->registerGate(new ResultGate());
        return $stream;
    }
    
    // Single Variables
    public function testSimpleX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x", $stream->getId()));
        $stream->process();
        
        // Should create AlgebraicEvent with Expression
        $this->assertEquals("x", $stream->getResult());
        
        // Verify it's an AlgebraicEvent (check last processed event)
        // For now, just verify the string output
    }
    
    public function testSimpleY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("y", $stream->getId()));
        $stream->process();
        $this->assertEquals("y", $stream->getResult());
    }
    
    public function testSimpleZ() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("z", $stream->getId()));
        $stream->process();
        $this->assertEquals("z", $stream->getResult());
    }
    
    // With Coefficients
    public function testTwoX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2x", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x", $stream->getResult());
    }
    
    public function testNegativeThreeY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("-3y", $stream->getId()));
        $stream->process();
        $this->assertEquals("-3y", $stream->getResult());
    }
    
    public function testDecimalCoefficient() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("0.5z", $stream->getId()));
        $stream->process();
        $this->assertEquals("0.5z", $stream->getResult());
    }
    
    // With Exponents
    public function testXSquared() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("x^2", $stream->getId()));
        $stream->process();
        $this->assertEquals("x^2", $stream->getResult());
    }
    
    public function testTwoXCubed() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2x^3", $stream->getId()));
        $stream->process();
        $this->assertEquals("2x^3", $stream->getResult());
    }
    
    // Multiple Variables
    public function testXY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("xy", $stream->getId()));
        $stream->process();
        // Note: Variables sorted alphabetically, so xy stays xy
        $this->assertEquals("xy", $stream->getResult());
    }
    
    public function testTwoXY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("2xy", $stream->getId()));
        $stream->process();
        $this->assertEquals("2xy", $stream->getResult());
    }
    
    public function testThreeXSquaredY() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("3x^2y", $stream->getId()));
        $stream->process();
        $this->assertEquals("3x^2y", $stream->getResult());
    }
    
    // Constants
    public function testConstantFive() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("5", $stream->getId()));
        $stream->process();
        $this->assertEquals("5", $stream->getResult());
    }
    
    public function testNegativeConstant() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("-3", $stream->getId()));
        $stream->process();
        $this->assertEquals("-3", $stream->getResult());
    }
    
    // Edge Cases
    public function testNegativeX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("-x", $stream->getId()));
        $stream->process();
        $this->assertEquals("-x", $stream->getResult());
    }
    
    public function testYX() {
        $stream = $this->createAlgebraStream();
        $stream->emit(new Event("yx", $stream->getId()));
        $stream->process();
        // Should sort to xy
        $this->assertEquals("xy", $stream->getResult());
    }
}
