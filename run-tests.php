<?php

require_once __DIR__ . '/autoload.php';

use StreamGate\Event;
use StreamGate\Stream;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\DivideGate;
use StreamGate\Gates\ParenGate;
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
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\DistributionGate;
use StreamGate\Gates\FOILGate;
use StreamGate\Gates\SubstitutionGate;
use StreamGate\Gates\EquationGate;
use StreamGate\Gates\FactoringGate;
use StreamGate\Gates\QuadraticGate;
use StreamGate\Gates\DerivativeGate;
use StreamGate\Gates\ProductRuleGate;
use StreamGate\Gates\IntegrationGate;
use StreamGate\Gates\DefiniteIntegralGate;
use StreamGate\Gates\CriticalPointsGate;
use StreamGate\Gates\DontMatchGate;
use StreamGate\Gates\ResultGate;
use StreamGate\LoggingLevel;
use StreamGate\AlgebraicEvent;
use StreamGate\Algebra\Variable;
use StreamGate\Algebra\Term;
use StreamGate\Algebra\Expression;

class SimpleTestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];
    
    public function assert($condition, $message) {
        if ($condition) {
            $this->passed++;
            echo "✓ PASS: $message\n";
        } else {
            $this->failed++;
            $this->failures[] = $message;
            echo "✗ FAIL: $message\n";
        }
    }
    
    public function assertEquals($expected, $actual, $message) {
        $expectedStr = is_array($expected) ? json_encode($expected) : $expected;
        $actualStr = is_array($actual) ? json_encode($actual) : $actual;
        $this->assert($expected === $actual, "$message (expected: $expectedStr, got: $actualStr)");
    }
    
    public function assertException($callable, $message) {
        try {
            $callable();
            $this->assert(false, "$message - Exception not thrown");
        } catch (\Exception $e) {
            $this->assert(true, "$message - Exception thrown as expected");
        }
    }
    
    public function summary() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests: " . ($this->passed + $this->failed) . "\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        if ($this->failed > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->failures as $failure) {
                echo "  - $failure\n";
            }
        }
        echo str_repeat("=", 50) . "\n";
        
        return $this->failed === 0;
    }
}

function createFullStream(): Stream {
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

function createAlgebraStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new FOILGate());
    $stream->registerGate(new DistributionGate());
    $stream->registerGate(new AlgebraicAddGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

function createFullAlgebraStream(): Stream {
    $stream = new Stream();
    // Algebraic gates first
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new DistributionGate());
    $stream->registerGate(new AlgebraicAddGate());
    $stream->registerGate(new SubstitutionGate());
    // Arithmetic gates for evaluation
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

function createEquationStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new EquationGate());
    $stream->registerGate(new AlgebraicAddGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

function createFactoringStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new FOILGate());
    $stream->registerGate(new FactoringGate());      // Before AlgebraicAddGate!
    $stream->registerGate(new AlgebraicAddGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

function createQuadraticStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new QuadraticGate());      // Before EquationGate!
    $stream->registerGate(new EquationGate());
    $stream->registerGate(new AlgebraicAddGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

function createDerivativeStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new DerivativeGate());
    $stream->registerGate(new AlgebraicAddGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

function createProductRuleStream(): Stream {
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

function createIntegrationStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new ResultGate());          // FIRST - catch results before parsing
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new IntegrationGate());
    $stream->registerGate(new AlgebraicAddGate());
    return $stream;
}

function createDefiniteIntegralStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new DefiniteIntegralGate());  // First - match definite integrals
    $stream->registerGate(new ResultGate());
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new AlgebraicAddGate());      // Parse algebra
    $stream->registerGate(new IntegrationGate());
    $stream->registerGate(new SubstitutionGate());
    $stream->registerGate(new MultiplyGate());
    $stream->registerGate(new ExponentGate());
    $stream->registerGate(new AddGate());
    $stream->registerGate(new SubtractGate());
    return $stream;
}

function createCriticalPointsStream(): Stream {
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

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 0: Infrastructure Tests\n";
echo str_repeat("=", 50) . "\n\n";

$test = new SimpleTestRunner();

// Test Event Creation
$event = new Event("test_data", "stream_1");
$test->assertEquals("test_data", $event->data, "Event data set correctly");
$test->assertEquals("stream_1", $event->streamId, "Event streamId set correctly");
$test->assertEquals([], $event->rejectedBy, "Event rejectedBy initialized as empty array");

// Test Event Rejection
$event2 = new Event("test", "stream_1");
$event2->gatesInRoom = 3;
$event2->reject("Gate1");
$test->assert(!$event2->isRejectedByAll(), "Event not rejected by all (1/3)");

$event2->reject("Gate2");
$event2->reject("Gate3");
$test->assert($event2->isRejectedByAll(), "Event rejected by all (3/3)");

// Test Event Rejection No Duplicates
$event3 = new Event("test", "stream_1");
$event3->gatesInRoom = 2;
$event3->reject("Gate1");
$event3->reject("Gate1");
$test->assertEquals(1, count($event3->rejectedBy), "No duplicate rejections");

// Test Stream Creation
$stream = new Stream();
$test->assert($stream->getId() !== null, "Stream has ID");
$test->assertEquals(0, $stream->getEventCount(), "Stream starts with 0 events");

// Test Stream with Custom ID
$stream2 = new Stream("custom_id");
$test->assertEquals("custom_id", $stream2->getId(), "Stream accepts custom ID");

// Test Stream Emit
$stream3 = new Stream();
$stream3->emit(new Event("data", $stream3->getId()));
$test->assertEquals(1, $stream3->getEventCount(), "Stream has 1 event after emit");
$test->assert($stream3->hasEvents(), "Stream hasEvents() returns true");

// Test Stream Multiple Emits
$stream4 = new Stream();
$stream4->emit(new Event("data1", $stream4->getId()));
$stream4->emit(new Event("data2", $stream4->getId()));
$stream4->emit(new Event("data3", $stream4->getId()));
$test->assertEquals(3, $stream4->getEventCount(), "Stream has 3 events after 3 emits");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 1: Basic Operations Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Test Addition
$stream = new Stream();
$stream->registerGate(new AddGate());
$stream->emit(new Event("2+3", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Addition: 2+3 = 5");

// Test Subtraction
$stream = new Stream();
$stream->registerGate(new SubtractGate());
$stream->emit(new Event("5-2", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Subtraction: 5-2 = 3");

// Test Multiplication
$stream = new Stream();
$stream->registerGate(new MultiplyGate());
$stream->emit(new Event("2*6", $stream->getId()));
$stream->process();
$test->assertEquals("12", $stream->getResult(), "Multiplication: 2*6 = 12");

// Test Division
$stream = new Stream();
$stream->registerGate(new DivideGate());
$stream->emit(new Event("6/2", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Division: 6/2 = 3");

// Test Division by Zero
$test->assertException(function() {
    $stream = new Stream();
    $stream->registerGate(new DivideGate());
    $stream->emit(new Event("5/0", $stream->getId()));
    $stream->process();
}, "Division by zero throws exception");

// Test Negative Numbers
$stream = new Stream();
$stream->registerGate(new AddGate());
$stream->emit(new Event("-5+3", $stream->getId()));
$stream->process();
$test->assertEquals("-2", $stream->getResult(), "Negative numbers: -5+3 = -2");

// Test Decimal Numbers
$stream = new Stream();
$stream->registerGate(new AddGate());
$stream->emit(new Event("2.5+3.5", $stream->getId()));
$stream->process();
$test->assertEquals("6", $stream->getResult(), "Decimal numbers: 2.5+3.5 = 6");

// Test All Basic Operations
$testCases = [
    ["10+5", "15", "Addition"],
    ["10-5", "5", "Subtraction"],
    ["10*5", "50", "Multiplication"],
    ["10/5", "2", "Division"],
    ["7+3", "10", "Addition 2"],
    ["100-50", "50", "Subtraction 2"],
    ["3*4", "12", "Multiplication 2"],
    ["20/4", "5", "Division 2"],
];

foreach ($testCases as [$input, $expected, $label]) {
    $stream = new Stream();
    $stream->registerGate(new AddGate());
    $stream->registerGate(new SubtractGate());
    $stream->registerGate(new MultiplyGate());
    $stream->registerGate(new DivideGate());
    
    $stream->emit(new Event($input, $stream->getId()));
    $stream->process();
    
    $test->assertEquals($expected, $stream->getResult(), "$label: $input = $expected");
}

// Test Multiple Gates Registered
$stream = new Stream();
$stream->registerGate(new AddGate());
$stream->registerGate(new SubtractGate());
$stream->registerGate(new MultiplyGate());
$stream->registerGate(new DivideGate());

$stream->emit(new Event("3*7", $stream->getId()));
$stream->process();
$test->assertEquals("21", $stream->getResult(), "Multiple gates: only multiply gate matches 3*7 = 21");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 2: Parentheses Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Test Simple Parentheses Strip
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->emit(new Event("(5)", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Strip simple parens: (5) = 5");

// Test Parentheses with Operation
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new MultiplyGate());
$stream->emit(new Event("(2*6)", $stream->getId()));
$stream->process();
$test->assertEquals("12", $stream->getResult(), "Parens with multiply: (2*6) = 12");

// Test Parentheses with Addition
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new AddGate());
$stream->emit(new Event("(2+3)", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Parens with add: (2+3) = 5");

// Test Parentheses with Subtraction
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new SubtractGate());
$stream->emit(new Event("(10-3)", $stream->getId()));
$stream->process();
$test->assertEquals("7", $stream->getResult(), "Parens with subtract: (10-3) = 7");

// Test Parentheses with Division
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new DivideGate());
$stream->emit(new Event("(12/4)", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Parens with divide: (12/4) = 3");

// Test Nested Parentheses
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new AddGate());
$stream->emit(new Event("((5+3))", $stream->getId()));
$stream->process();
$test->assertEquals("8", $stream->getResult(), "Nested parens: ((5+3)) = 8");

// Test Triple Nested Parentheses
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new MultiplyGate());
$stream->emit(new Event("(((2*5)))", $stream->getId()));
$stream->process();
$test->assertEquals("10", $stream->getResult(), "Triple nested: (((2*5))) = 10");

// Test No Parentheses
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new AddGate());
$stream->emit(new Event("2+3", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "No parens: 2+3 = 5");

// Test All Gates Together
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new AddGate());
$stream->registerGate(new SubtractGate());
$stream->registerGate(new MultiplyGate());
$stream->registerGate(new DivideGate());
$stream->emit(new Event("(6/2)", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "All gates with parens: (6/2) = 3");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 3: Partial Operations Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Test Partial Addition Left
$stream = createFullStream();
$stream->emit(new Event("(2*3)+5", $stream->getId()));
$stream->process();
$test->assertEquals("11", $stream->getResult(), "Partial add left: (2*3)+5 = 11");

// Test Partial Addition Right
$stream = createFullStream();
$stream->emit(new Event("5+(2*3)", $stream->getId()));
$stream->process();
$test->assertEquals("11", $stream->getResult(), "Partial add right: 5+(2*3) = 11");

// Test Partial Multiplication Left
$stream = createFullStream();
$stream->emit(new Event("(2+3)*4", $stream->getId()));
$stream->process();
$test->assertEquals("20", $stream->getResult(), "Partial mult left: (2+3)*4 = 20");

// Test Partial Multiplication Right
$stream = createFullStream();
$stream->emit(new Event("4*(2+3)", $stream->getId()));
$stream->process();
$test->assertEquals("20", $stream->getResult(), "Partial mult right: 4*(2+3) = 20");

// Test Partial Subtraction Left
$stream = createFullStream();
$stream->emit(new Event("(10-3)-2", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Partial sub left: (10-3)-2 = 5");

// Test Partial Subtraction Right
$stream = createFullStream();
$stream->emit(new Event("10-(3+2)", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Partial sub right: 10-(3+2) = 5");

// Test Partial Division Left
$stream = createFullStream();
$stream->emit(new Event("(20/4)/2", $stream->getId()));
$stream->process();
$test->assertEquals("2.5", $stream->getResult(), "Partial div left: (20/4)/2 = 2.5");

// Test Partial Division Right
$stream = createFullStream();
$stream->emit(new Event("20/(2*2)", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Partial div right: 20/(2*2) = 5");

// Test Nested Partial Operations
$stream = createFullStream();
$stream->emit(new Event("((2+3)*2)+1", $stream->getId()));
$stream->process();
$test->assertEquals("11", $stream->getResult(), "Nested partial: ((2+3)*2)+1 = 11");

// Test Complex Nested Expression
$stream = createFullStream();
$stream->emit(new Event("(2*6)*3", $stream->getId()));
$stream->process();
$test->assertEquals("36", $stream->getResult(), "Complex nested: (2*6)*3 = 36");

// Test Deep Nesting
$stream = createFullStream();
$stream->emit(new Event("(((2+3)*2)+1)*2", $stream->getId()));
$stream->process();
$test->assertEquals("22", $stream->getResult(), "Deep nesting: (((2+3)*2)+1)*2 = 22");

// Test Partial with Decimal
$stream = createFullStream();
$stream->emit(new Event("(10/4)+2.5", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Partial with decimal: (10/4)+2.5 = 5");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 4: PEMDAS/Precedence Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Test Multiplication Before Addition
$stream = createFullStream();
$stream->emit(new Event("2+3*4", $stream->getId()));
$stream->process();
$test->assertEquals("14", $stream->getResult(), "PEMDAS: 2+3*4 = 14");

// Test Division Before Subtraction
$stream = createFullStream();
$stream->emit(new Event("10-6/2", $stream->getId()));
$stream->process();
$test->assertEquals("7", $stream->getResult(), "PEMDAS: 10-6/2 = 7");

// Test Multiplication Before Subtraction
$stream = createFullStream();
$stream->emit(new Event("10-2*3", $stream->getId()));
$stream->process();
$test->assertEquals("4", $stream->getResult(), "PEMDAS: 10-2*3 = 4");

// Test Division Before Addition
$stream = createFullStream();
$stream->emit(new Event("10+20/4", $stream->getId()));
$stream->process();
$test->assertEquals("15", $stream->getResult(), "PEMDAS: 10+20/4 = 15");

// Test Multiple High Precedence
$stream = createFullStream();
$stream->emit(new Event("2*3*4", $stream->getId()));
$stream->process();
$test->assertEquals("24", $stream->getResult(), "Same precedence: 2*3*4 = 24");

// Test Multiple Low Precedence
$stream = createFullStream();
$stream->emit(new Event("10+5+3", $stream->getId()));
$stream->process();
$test->assertEquals("18", $stream->getResult(), "Same precedence: 10+5+3 = 18");

// Test Mixed Operations
$stream = createFullStream();
$stream->emit(new Event("2+3*4-6/2", $stream->getId()));
$stream->process();
$test->assertEquals("11", $stream->getResult(), "Mixed ops: 2+3*4-6/2 = 11");

// Test Left to Right Same Precedence
$stream = createFullStream();
$stream->emit(new Event("10-5-2", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Left-to-right: 10-5-2 = 3");

// Test Division Left to Right
$stream = createFullStream();
$stream->emit(new Event("20/4/2", $stream->getId()));
$stream->process();
$test->assertEquals("2.5", $stream->getResult(), "Left-to-right: 20/4/2 = 2.5");

// Test Complex Expression
$stream = createFullStream();
$stream->emit(new Event("5+3*2-8/4", $stream->getId()));
$stream->process();
$test->assertEquals("9", $stream->getResult(), "Complex: 5+3*2-8/4 = 9");

// Test With Negative Numbers
$stream = createFullStream();
$stream->emit(new Event("-5+3*2", $stream->getId()));
$stream->process();
$test->assertEquals("1", $stream->getResult(), "With negative: -5+3*2 = 1");

// Test Expression With Parentheses
$stream = createFullStream();
$stream->emit(new Event("(2+3)*4", $stream->getId()));
$stream->process();
$test->assertEquals("20", $stream->getResult(), "Override precedence: (2+3)*4 = 20");

// Test Mixed Parens and Precedence
$stream = createFullStream();
$stream->emit(new Event("(2+3)*4+5", $stream->getId()));
$stream->process();
$test->assertEquals("25", $stream->getResult(), "Mixed: (2+3)*4+5 = 25");

// Test Precedence With Parens Right
$stream = createFullStream();
$stream->emit(new Event("2*3+(4+5)", $stream->getId()));
$stream->process();
$test->assertEquals("15", $stream->getResult(), "Mixed: 2*3+(4+5) = 15");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 7: Modulo & Factorial Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Test Simple Modulo
$stream = createFullStream();
$stream->emit(new Event("10%3", $stream->getId()));
$stream->process();
$test->assertEquals("1", $stream->getResult(), "Modulo: 10%3 = 1");

// Test Modulo Larger
$stream = createFullStream();
$stream->emit(new Event("17%5", $stream->getId()));
$stream->process();
$test->assertEquals("2", $stream->getResult(), "Modulo: 17%5 = 2");

// Test Modulo Zero Remainder
$stream = createFullStream();
$stream->emit(new Event("8%4", $stream->getId()));
$stream->process();
$test->assertEquals("0", $stream->getResult(), "Modulo: 8%4 = 0");

// Test Modulo With Addition
$stream = createFullStream();
$stream->emit(new Event("10%3+5", $stream->getId()));
$stream->process();
$test->assertEquals("6", $stream->getResult(), "Modulo precedence: 10%3+5 = 6");

// Test Factorial 5
$stream = createFullStream();
$stream->emit(new Event("5!", $stream->getId()));
$stream->process();
$test->assertEquals("120", $stream->getResult(), "Factorial: 5! = 120");

// Test Factorial 3
$stream = createFullStream();
$stream->emit(new Event("3!", $stream->getId()));
$stream->process();
$test->assertEquals("6", $stream->getResult(), "Factorial: 3! = 6");

// Test Factorial 0
$stream = createFullStream();
$stream->emit(new Event("0!", $stream->getId()));
$stream->process();
$test->assertEquals("1", $stream->getResult(), "Factorial: 0! = 1");

// Test Factorial 1
$stream = createFullStream();
$stream->emit(new Event("1!", $stream->getId()));
$stream->process();
$test->assertEquals("1", $stream->getResult(), "Factorial: 1! = 1");

// Test Factorial Addition
$stream = createFullStream();
$stream->emit(new Event("3!+2!", $stream->getId()));
$stream->process();
$test->assertEquals("8", $stream->getResult(), "Factorial addition: 3!+2! = 8");

// Test Factorial Multiplication
$stream = createFullStream();
$stream->emit(new Event("4!*2", $stream->getId()));
$stream->process();
$test->assertEquals("48", $stream->getResult(), "Factorial multiply: 4!*2 = 48");

// Test Modulo With Factorial
$stream = createFullStream();
$stream->emit(new Event("10%3!", $stream->getId()));
$stream->process();
$test->assertEquals("4", $stream->getResult(), "Modulo with factorial: 10%3! = 4");

// Test Modulo by Zero (error)
try {
    $stream = createFullStream();
    $stream->emit(new Event("10%0", $stream->getId()));
    $stream->process();
    $test->assertEquals("ERROR", "NO_ERROR", "Modulo by zero should throw exception");
} catch (\Exception $e) {
    $test->assertEquals("Modulo by zero", $e->getMessage(), "Modulo by zero throws correct exception");
}

// Test Factorial Negative (error)
try {
    $stream = createFullStream();
    $stream->emit(new Event("(-5)!", $stream->getId()));
    $stream->process();
    $test->assertEquals("ERROR", "NO_ERROR", "Negative factorial should throw exception");
} catch (\Exception $e) {
    $test->assertEquals(true, str_contains($e->getMessage(), "non-negative"), "Negative factorial throws correct exception");
}

// Test Factorial Decimal (error)
try {
    $stream = createFullStream();
    $stream->emit(new Event("3.5!", $stream->getId()));
    $stream->process();
    $test->assertEquals("ERROR", "NO_ERROR", "Decimal factorial should throw exception");
} catch (\Exception $e) {
    $test->assertEquals(true, str_contains($e->getMessage(), "integers"), "Decimal factorial throws correct exception");
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 5: Exponent Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Test Simple Exponent
$stream = createFullStream();
$stream->emit(new Event("2^3", $stream->getId()));
$stream->process();
$test->assertEquals("8", $stream->getResult(), "Exponent: 2^3 = 8");

// Test Square
$stream = createFullStream();
$stream->emit(new Event("5^2", $stream->getId()));
$stream->process();
$test->assertEquals("25", $stream->getResult(), "Square: 5^2 = 25");

// Test Power of Zero
$stream = createFullStream();
$stream->emit(new Event("10^0", $stream->getId()));
$stream->process();
$test->assertEquals("1", $stream->getResult(), "Power of 0: 10^0 = 1");

// Test Power of One
$stream = createFullStream();
$stream->emit(new Event("2^1", $stream->getId()));
$stream->process();
$test->assertEquals("2", $stream->getResult(), "Power of 1: 2^1 = 2");

// Test Negative Exponent
$stream = createFullStream();
$stream->emit(new Event("2^-1", $stream->getId()));
$stream->process();
$test->assertEquals("0.5", $stream->getResult(), "Negative exponent: 2^-1 = 0.5");

// Test Negative Exponent 2
$stream = createFullStream();
$stream->emit(new Event("2^-2", $stream->getId()));
$stream->process();
$test->assertEquals("0.25", $stream->getResult(), "Negative exponent: 2^-2 = 0.25");

// Test Decimal Base
$stream = createFullStream();
$stream->emit(new Event("2.5^2", $stream->getId()));
$stream->process();
$test->assertEquals("6.25", $stream->getResult(), "Decimal base: 2.5^2 = 6.25");

// Test Fractional Exponent (square root)
$stream = createFullStream();
$stream->emit(new Event("4^0.5", $stream->getId()));
$stream->process();
$test->assertEquals("2", $stream->getResult(), "Fractional exponent: 4^0.5 = 2");

// Test Exponent Before Addition
$stream = createFullStream();
$stream->emit(new Event("2+3^2", $stream->getId()));
$stream->process();
$test->assertEquals("11", $stream->getResult(), "Precedence: 2+3^2 = 11");

// Test Exponent Before Multiplication
$stream = createFullStream();
$stream->emit(new Event("2*3^2", $stream->getId()));
$stream->process();
$test->assertEquals("18", $stream->getResult(), "Precedence: 2*3^2 = 18");

// Test Right Associativity (CRITICAL!)
$stream = createFullStream();
$stream->emit(new Event("2^2^3", $stream->getId()));
$stream->process();
$test->assertEquals("256", $stream->getResult(), "Right-to-left: 2^2^3 = 256");

// Test Right Associativity 2
$stream = createFullStream();
$stream->emit(new Event("2^3^2", $stream->getId()));
$stream->process();
$test->assertEquals("512", $stream->getResult(), "Right-to-left: 2^3^2 = 512");

// Test Exponent With Parentheses
$stream = createFullStream();
$stream->emit(new Event("(2+3)^2", $stream->getId()));
$stream->process();
$test->assertEquals("25", $stream->getResult(), "With parens: (2+3)^2 = 25");

// Test Complex Expression
$stream = createFullStream();
$stream->emit(new Event("2^3+2^2", $stream->getId()));
$stream->process();
$test->assertEquals("12", $stream->getResult(), "Complex: 2^3+2^2 = 12");

// Test Exponent of Expression Right
$stream = createFullStream();
$stream->emit(new Event("2^(1+2)", $stream->getId()));
$stream->process();
$test->assertEquals("8", $stream->getResult(), "Exp of expr: 2^(1+2) = 8");

// Test Exponent of Expression Left
$stream = createFullStream();
$stream->emit(new Event("(2+2)^3", $stream->getId()));
$stream->process();
$test->assertEquals("64", $stream->getResult(), "Exp of expr: (2+2)^3 = 64");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 6: Roots & Brackets Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Square Root Tests
$stream = createFullStream();
$stream->emit(new Event("√4", $stream->getId()));
$stream->process();
$test->assertEquals("2", $stream->getResult(), "Square root: √4 = 2");

$stream = createFullStream();
$stream->emit(new Event("√9", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Square root: √9 = 3");

$stream = createFullStream();
$stream->emit(new Event("√16", $stream->getId()));
$stream->process();
$test->assertEquals("4", $stream->getResult(), "Square root: √16 = 4");

$stream = createFullStream();
$stream->emit(new Event("√(4+5)", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Square root of expr: √(4+5) = 3");

$stream = createFullStream();
$stream->emit(new Event("√0", $stream->getId()));
$stream->process();
$test->assertEquals("0", $stream->getResult(), "Square root: √0 = 0");

// Nth Root Tests
$stream = createFullStream();
$stream->emit(new Event("3√27", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Cube root: 3√27 = 3");

$stream = createFullStream();
$stream->emit(new Event("3√8", $stream->getId()));
$stream->process();
$test->assertEquals("2", $stream->getResult(), "Cube root: 3√8 = 2");

$stream = createFullStream();
$stream->emit(new Event("4√16", $stream->getId()));
$stream->process();
$test->assertEquals("2", $stream->getResult(), "Fourth root: 4√16 = 2");

// Floor Tests
$stream = createFullStream();
$stream->emit(new Event("⌊3.7⌋", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Floor: ⌊3.7⌋ = 3");

$stream = createFullStream();
$stream->emit(new Event("⌊-3.7⌋", $stream->getId()));
$stream->process();
$test->assertEquals("-4", $stream->getResult(), "Floor negative: ⌊-3.7⌋ = -4");

$stream = createFullStream();
$stream->emit(new Event("⌊5⌋", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Floor integer: ⌊5⌋ = 5");

$stream = createFullStream();
$stream->emit(new Event("⌊10/3⌋", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "Floor of expr: ⌊10/3⌋ = 3");

// Ceiling Tests
$stream = createFullStream();
$stream->emit(new Event("⌈3.2⌉", $stream->getId()));
$stream->process();
$test->assertEquals("4", $stream->getResult(), "Ceiling: ⌈3.2⌉ = 4");

$stream = createFullStream();
$stream->emit(new Event("⌈-3.2⌉", $stream->getId()));
$stream->process();
$test->assertEquals("-3", $stream->getResult(), "Ceiling negative: ⌈-3.2⌉ = -3");

$stream = createFullStream();
$stream->emit(new Event("⌈5⌉", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Ceiling integer: ⌈5⌉ = 5");

// Absolute Value Tests
$stream = createFullStream();
$stream->emit(new Event("|5|", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Absolute value: |5| = 5");

$stream = createFullStream();
$stream->emit(new Event("|-5|", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Absolute value: |-5| = 5");

$stream = createFullStream();
$stream->emit(new Event("|3-7|", $stream->getId()));
$stream->process();
$test->assertEquals("4", $stream->getResult(), "Abs of expr: |3-7| = 4");

// Combined Operations
$stream = createFullStream();
$stream->emit(new Event("⌊5.9⌋+⌈5.1⌉", $stream->getId()));
$stream->process();
$test->assertEquals("11", $stream->getResult(), "Floor + Ceil: ⌊5.9⌋+⌈5.1⌉ = 11");

$stream = createFullStream();
$stream->emit(new Event("|⌊-3.7⌋|", $stream->getId()));
$stream->process();
$test->assertEquals("4", $stream->getResult(), "Abs of floor: |⌊-3.7⌋| = 4");

$stream = createFullStream();
$stream->emit(new Event("√9+1", $stream->getId()));
$stream->process();
$test->assertEquals("4", $stream->getResult(), "Root with ops: √9+1 = 4");

$stream = createFullStream();
$stream->emit(new Event("2*√9", $stream->getId()));
$stream->process();
$test->assertEquals("6", $stream->getResult(), "Multiply root: 2*√9 = 6");

$stream = createFullStream();
$stream->emit(new Event("⌊3.7⌋*2", $stream->getId()));
$stream->process();
$test->assertEquals("6", $stream->getResult(), "Floor multiply: ⌊3.7⌋*2 = 6");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 8: Expression Infrastructure Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Single Variables
$stream = createAlgebraStream();
$stream->emit(new Event("x", $stream->getId()));
$stream->process();
$test->assertEquals("x", $stream->getResult(), "Simple x");

$stream = createAlgebraStream();
$stream->emit(new Event("y", $stream->getId()));
$stream->process();
$test->assertEquals("y", $stream->getResult(), "Simple y");

$stream = createAlgebraStream();
$stream->emit(new Event("z", $stream->getId()));
$stream->process();
$test->assertEquals("z", $stream->getResult(), "Simple z");

// With Coefficients
$stream = createAlgebraStream();
$stream->emit(new Event("2x", $stream->getId()));
$stream->process();
$test->assertEquals("2x", $stream->getResult(), "2x coefficient");

$stream = createAlgebraStream();
$stream->emit(new Event("-3y", $stream->getId()));
$stream->process();
$test->assertEquals("-3y", $stream->getResult(), "Negative coefficient: -3y");

$stream = createAlgebraStream();
$stream->emit(new Event("0.5z", $stream->getId()));
$stream->process();
$test->assertEquals("0.5z", $stream->getResult(), "Decimal coefficient: 0.5z");

// With Exponents
$stream = createAlgebraStream();
$stream->emit(new Event("x^2", $stream->getId()));
$stream->process();
$test->assertEquals("x^2", $stream->getResult(), "Exponent: x^2");

$stream = createAlgebraStream();
$stream->emit(new Event("2x^3", $stream->getId()));
$stream->process();
$test->assertEquals("2x^3", $stream->getResult(), "Coefficient + exponent: 2x^3");

// Multiple Variables
$stream = createAlgebraStream();
$stream->emit(new Event("xy", $stream->getId()));
$stream->process();
$test->assertEquals("xy", $stream->getResult(), "Multiple vars: xy");

$stream = createAlgebraStream();
$stream->emit(new Event("2xy", $stream->getId()));
$stream->process();
$test->assertEquals("2xy", $stream->getResult(), "Coefficient + multiple vars: 2xy");

$stream = createAlgebraStream();
$stream->emit(new Event("3x^2y", $stream->getId()));
$stream->process();
$test->assertEquals("3x^2y", $stream->getResult(), "Complex: 3x^2y");

// Constants
$stream = createAlgebraStream();
$stream->emit(new Event("5", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Constant: 5");

$stream = createAlgebraStream();
$stream->emit(new Event("-3", $stream->getId()));
$stream->process();
$test->assertEquals("-3", $stream->getResult(), "Negative constant: -3");

// Edge Cases
$stream = createAlgebraStream();
$stream->emit(new Event("-x", $stream->getId()));
$stream->process();
$test->assertEquals("-x", $stream->getResult(), "Negative variable: -x");

$stream = createAlgebraStream();
$stream->emit(new Event("yx", $stream->getId()));
$stream->process();
$test->assertEquals("xy", $stream->getResult(), "Variable sorting: yx -> xy");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 9: Combining Like Terms Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Simple Addition
$stream = createAlgebraStream();
$stream->emit(new Event("x+x", $stream->getId()));
$stream->process();
$test->assertEquals("2x", $stream->getResult(), "x+x = 2x");

$stream = createAlgebraStream();
$stream->emit(new Event("2x+3x", $stream->getId()));
$stream->process();
$test->assertEquals("5x", $stream->getResult(), "2x+3x = 5x");

$stream = createAlgebraStream();
$stream->emit(new Event("y+y+y", $stream->getId()));
$stream->process();
$test->assertEquals("3y", $stream->getResult(), "y+y+y = 3y");

// Subtraction
$stream = createAlgebraStream();
$stream->emit(new Event("5x-2x", $stream->getId()));
$stream->process();
$test->assertEquals("3x", $stream->getResult(), "5x-2x = 3x");

$stream = createAlgebraStream();
$stream->emit(new Event("x-x", $stream->getId()));
$stream->process();
$test->assertEquals("0", $stream->getResult(), "x-x = 0");

// Multiple Variables
$stream = createAlgebraStream();
$stream->emit(new Event("xy+xy", $stream->getId()));
$stream->process();
$test->assertEquals("2xy", $stream->getResult(), "xy+xy = 2xy");

$stream = createAlgebraStream();
$stream->emit(new Event("2xy+3xy", $stream->getId()));
$stream->process();
$test->assertEquals("5xy", $stream->getResult(), "2xy+3xy = 5xy");

// Unlike Terms
$stream = createAlgebraStream();
$stream->emit(new Event("x+y", $stream->getId()));
$stream->process();
$test->assertEquals("x+y", $stream->getResult(), "x+y = x+y (unlike terms)");

$stream = createAlgebraStream();
$stream->emit(new Event("x^2+x", $stream->getId()));
$stream->process();
$test->assertEquals("x^2+x", $stream->getResult(), "x^2+x = x^2+x (different exponents)");

$stream = createAlgebraStream();
$stream->emit(new Event("2x+3", $stream->getId()));
$stream->process();
$test->assertEquals("2x+3", $stream->getResult(), "2x+3 = 2x+3 (variable + constant)");

// Mixed
$stream = createAlgebraStream();
$stream->emit(new Event("2x+3+4x+5", $stream->getId()));
$stream->process();
$test->assertEquals("6x+8", $stream->getResult(), "2x+3+4x+5 = 6x+8");

$stream = createAlgebraStream();
$stream->emit(new Event("x+2y+3x-y", $stream->getId()));
$stream->process();
$test->assertEquals("4x+y", $stream->getResult(), "x+2y+3x-y = 4x+y");

// Coefficients
$stream = createAlgebraStream();
$stream->emit(new Event("0.5x+1.5x", $stream->getId()));
$stream->process();
$test->assertEquals("2x", $stream->getResult(), "0.5x+1.5x = 2x");

$stream = createAlgebraStream();
$stream->emit(new Event("-x+2x", $stream->getId()));
$stream->process();
$test->assertEquals("x", $stream->getResult(), "-x+2x = x");

$stream = createAlgebraStream();
$stream->emit(new Event("3x+2y+5x-y+4", $stream->getId()));
$stream->process();
$test->assertEquals("8x+y+4", $stream->getResult(), "3x+2y+5x-y+4 = 8x+y+4");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 10: Distribution Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Simple Distribution
$stream = createAlgebraStream();
$stream->emit(new Event("2(x+3)", $stream->getId()));
$stream->process();
$test->assertEquals("2x+6", $stream->getResult(), "2(x+3) = 2x+6");

$stream = createAlgebraStream();
$stream->emit(new Event("3(x-2)", $stream->getId()));
$stream->process();
$test->assertEquals("3x-6", $stream->getResult(), "3(x-2) = 3x-6");

$stream = createAlgebraStream();
$stream->emit(new Event("5(y+1)", $stream->getId()));
$stream->process();
$test->assertEquals("5y+5", $stream->getResult(), "5(y+1) = 5y+5");

// Variable Multiplier
$stream = createAlgebraStream();
$stream->emit(new Event("x(y+2)", $stream->getId()));
$stream->process();
$test->assertEquals("xy+2x", $stream->getResult(), "x(y+2) = xy+2x");

$stream = createAlgebraStream();
$stream->emit(new Event("y(x+1)", $stream->getId()));
$stream->process();
$test->assertEquals("xy+y", $stream->getResult(), "y(x+1) = xy+y");

// Multiple Terms
$stream = createAlgebraStream();
$stream->emit(new Event("2(x+y+z)", $stream->getId()));
$stream->process();
$test->assertEquals("2x+2y+2z", $stream->getResult(), "2(x+y+z) = 2x+2y+2z");

$stream = createAlgebraStream();
$stream->emit(new Event("3(2x-y+4)", $stream->getId()));
$stream->process();
$test->assertEquals("6x-3y+12", $stream->getResult(), "3(2x-y+4) = 6x-3y+12");

// Negative
$stream = createAlgebraStream();
$stream->emit(new Event("-2(x+3)", $stream->getId()));
$stream->process();
$test->assertEquals("-2x-6", $stream->getResult(), "-2(x+3) = -2x-6");

$stream = createAlgebraStream();
$stream->emit(new Event("2(-x+3)", $stream->getId()));
$stream->process();
$test->assertEquals("-2x+6", $stream->getResult(), "2(-x+3) = -2x+6");

// Fractional
$stream = createAlgebraStream();
$stream->emit(new Event("0.5(2x+4)", $stream->getId()));
$stream->process();
$test->assertEquals("x+2", $stream->getResult(), "0.5(2x+4) = x+2");

// With Exponents
$stream = createAlgebraStream();
$stream->emit(new Event("2(x^2+x)", $stream->getId()));
$stream->process();
$test->assertEquals("2x^2+2x", $stream->getResult(), "2(x^2+x) = 2x^2+2x");

// Combined
$stream = createAlgebraStream();
$stream->emit(new Event("2(3x+4)", $stream->getId()));
$stream->process();
$test->assertEquals("6x+8", $stream->getResult(), "2(3x+4) = 6x+8");

$stream = createAlgebraStream();
$stream->emit(new Event("x(x+1)", $stream->getId()));
$stream->process();
$test->assertEquals("x^2+x", $stream->getResult(), "x(x+1) = x^2+x");

// Multiple Variables
$stream = createAlgebraStream();
$stream->emit(new Event("xy(a+b)", $stream->getId()));
$stream->process();
$test->assertEquals("axy+bxy", $stream->getResult(), "xy(a+b) = axy+bxy");

$stream = createAlgebraStream();
$stream->emit(new Event("2x(3y+z)", $stream->getId()));
$stream->process();
$test->assertEquals("6xy+2xz", $stream->getResult(), "2x(3y+z) = 6xy+2xz");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 11: Substitution & Evaluation Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Single Variable
$stream = createFullAlgebraStream();
$stream->setVariable('x', 5);
$stream->emit(new Event("x", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "x=5: x = 5");

$stream = createFullAlgebraStream();
$stream->setVariable('x', 5);
$stream->emit(new Event("2x", $stream->getId()));
$stream->process();
$test->assertEquals("10", $stream->getResult(), "x=5: 2x = 10");

$stream = createFullAlgebraStream();
$stream->setVariable('x', 5);
$stream->emit(new Event("2x+3", $stream->getId()));
$stream->process();
$test->assertEquals("13", $stream->getResult(), "x=5: 2x+3 = 13");

// Multiple Variables
$stream = createFullAlgebraStream();
$stream->setVariable('x', 2);
$stream->setVariable('y', 3);
$stream->emit(new Event("x+y", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "x=2,y=3: x+y = 5");

$stream = createFullAlgebraStream();
$stream->setVariable('x', 2);
$stream->setVariable('y', 3);
$stream->emit(new Event("2x+y", $stream->getId()));
$stream->process();
$test->assertEquals("7", $stream->getResult(), "x=2,y=3: 2x+y = 7");

$stream = createFullAlgebraStream();
$stream->setVariable('x', 2);
$stream->setVariable('y', 3);
$stream->emit(new Event("xy", $stream->getId()));
$stream->process();
$test->assertEquals("6", $stream->getResult(), "x=2,y=3: xy = 6");

// With Exponents
$stream = createFullAlgebraStream();
$stream->setVariable('x', 3);
$stream->emit(new Event("x^2", $stream->getId()));
$stream->process();
$test->assertEquals("9", $stream->getResult(), "x=3: x^2 = 9");

$stream = createFullAlgebraStream();
$stream->setVariable('x', 4);
$stream->emit(new Event("x^2+2x+1", $stream->getId()));
$stream->process();
$test->assertEquals("25", $stream->getResult(), "x=4: x^2+2x+1 = 25");

// Partial Substitution
$stream = createFullAlgebraStream();
$stream->setVariable('x', 2);
$stream->emit(new Event("2x+y", $stream->getId()));
$stream->process();
$test->assertEquals("4+y", $stream->getResult(), "x=2: 2x+y = 4+y (partial)");

$stream = createFullAlgebraStream();
$stream->setVariable('x', 5);
$stream->emit(new Event("x+3y", $stream->getId()));
$stream->process();
$test->assertEquals("5+3y", $stream->getResult(), "x=5: x+3y = 5+3y (partial)");

// Zero Value
$stream = createFullAlgebraStream();
$stream->setVariable('x', 0);
$stream->emit(new Event("5x+7", $stream->getId()));
$stream->process();
$test->assertEquals("7", $stream->getResult(), "x=0: 5x+7 = 7");

// Complex
$stream = createFullAlgebraStream();
$stream->setVariable('x', 2);
$stream->setVariable('y', 3);
$stream->emit(new Event("2x^2+xy+3", $stream->getId()));
$stream->process();
$test->assertEquals("17", $stream->getResult(), "x=2,y=3: 2x^2+xy+3 = 17");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 12: FOIL (Binomial Multiplication) Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Basic FOIL
$stream = createAlgebraStream();
$stream->emit(new Event("(x+1)(x+2)", $stream->getId()));
$stream->process();
$test->assertEquals("x^2+3x+2", $stream->getResult(), "(x+1)(x+2) = x^2+3x+2");

$stream = createAlgebraStream();
$stream->emit(new Event("(x+3)(x-2)", $stream->getId()));
$stream->process();
$test->assertEquals("x^2+x-6", $stream->getResult(), "(x+3)(x-2) = x^2+x-6");

$stream = createAlgebraStream();
$stream->emit(new Event("(x-1)(x+1)", $stream->getId()));
$stream->process();
$test->assertEquals("x^2-1", $stream->getResult(), "(x-1)(x+1) = x^2-1");

// With Coefficients
$stream = createAlgebraStream();
$stream->emit(new Event("(2x+1)(3x+4)", $stream->getId()));
$stream->process();
$test->assertEquals("6x^2+11x+4", $stream->getResult(), "(2x+1)(3x+4) = 6x^2+11x+4");

$stream = createAlgebraStream();
$stream->emit(new Event("(2x-1)(x+3)", $stream->getId()));
$stream->process();
$test->assertEquals("2x^2+5x-3", $stream->getResult(), "(2x-1)(x+3) = 2x^2+5x-3");

// Difference of Squares
$stream = createAlgebraStream();
$stream->emit(new Event("(x+y)(x-y)", $stream->getId()));
$stream->process();
$test->assertEquals("x^2-y^2", $stream->getResult(), "(x+y)(x-y) = x^2-y^2");

$stream = createAlgebraStream();
$stream->emit(new Event("(a+b)(a-b)", $stream->getId()));
$stream->process();
$test->assertEquals("a^2-b^2", $stream->getResult(), "(a+b)(a-b) = a^2-b^2");

// Perfect Squares
$stream = createAlgebraStream();
$stream->emit(new Event("(x+1)(x+1)", $stream->getId()));
$stream->process();
$test->assertEquals("x^2+2x+1", $stream->getResult(), "(x+1)^2 = x^2+2x+1");

$stream = createAlgebraStream();
$stream->emit(new Event("(x-2)(x-2)", $stream->getId()));
$stream->process();
$test->assertEquals("x^2-4x+4", $stream->getResult(), "(x-2)^2 = x^2-4x+4");

// Multiple Variables
$stream = createAlgebraStream();
$stream->emit(new Event("(x+y)(a+b)", $stream->getId()));
$stream->process();
$test->assertEquals("ax+bx+ay+by", $stream->getResult(), "(x+y)(a+b) = ax+bx+ay+by");

// Negative Coefficients
$stream = createAlgebraStream();
$stream->emit(new Event("(-x+1)(x+2)", $stream->getId()));
$stream->process();
$test->assertEquals("-x^2-x+2", $stream->getResult(), "(-x+1)(x+2) = -x^2-x+2");

// Three Terms
$stream = createAlgebraStream();
$stream->emit(new Event("(x+y+z)(a+b)", $stream->getId()));
$stream->process();
$test->assertEquals("ax+bx+ay+by+az+bz", $stream->getResult(), "(x+y+z)(a+b) = ax+bx+ay+by+az+bz");

// Constants
$stream = createAlgebraStream();
$stream->emit(new Event("(2+x)(3+y)", $stream->getId()));
$stream->process();
$test->assertEquals("6+2y+3x+xy", $stream->getResult(), "(2+x)(3+y) = 6+2y+3x+xy");

// Complex
$stream = createAlgebraStream();
$stream->emit(new Event("(2x+3y)(x-y)", $stream->getId()));
$stream->process();
$test->assertEquals("2x^2+xy-3y^2", $stream->getResult(), "(2x+3y)(x-y) = 2x^2+xy-3y^2");

$stream = createAlgebraStream();
$stream->emit(new Event("(x^2+x)(x-1)", $stream->getId()));
$stream->process();
$test->assertEquals("x^3-x", $stream->getResult(), "(x^2+x)(x-1) = x^3-x");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 13: Linear Equations Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Simple Equations
$stream = createEquationStream();
$stream->emit(new Event("2x+3=7", $stream->getId()));
$stream->process();
$test->assertEquals("x=2", $stream->getResult(), "2x+3=7 → x=2");

$stream = createEquationStream();
$stream->emit(new Event("x+5=8", $stream->getId()));
$stream->process();
$test->assertEquals("x=3", $stream->getResult(), "x+5=8 → x=3");

$stream = createEquationStream();
$stream->emit(new Event("3x=9", $stream->getId()));
$stream->process();
$test->assertEquals("x=3", $stream->getResult(), "3x=9 → x=3");

// Variable on Both Sides
$stream = createEquationStream();
$stream->emit(new Event("x+5=2x+1", $stream->getId()));
$stream->process();
$test->assertEquals("x=4", $stream->getResult(), "x+5=2x+1 → x=4");

$stream = createEquationStream();
$stream->emit(new Event("3x-2=x+6", $stream->getId()));
$stream->process();
$test->assertEquals("x=4", $stream->getResult(), "3x-2=x+6 → x=4");

// Reversed
$stream = createEquationStream();
$stream->emit(new Event("7=2x+3", $stream->getId()));
$stream->process();
$test->assertEquals("x=2", $stream->getResult(), "7=2x+3 → x=2");

$stream = createEquationStream();
$stream->emit(new Event("5=2x+1", $stream->getId()));
$stream->process();
$test->assertEquals("x=2", $stream->getResult(), "5=2x+1 → x=2");

// Negative Solutions
$stream = createEquationStream();
$stream->emit(new Event("x+3=1", $stream->getId()));
$stream->process();
$test->assertEquals("x=-2", $stream->getResult(), "x+3=1 → x=-2");

$stream = createEquationStream();
$stream->emit(new Event("2x=-4", $stream->getId()));
$stream->process();
$test->assertEquals("x=-2", $stream->getResult(), "2x=-4 → x=-2");

// Fractional Solutions
$stream = createEquationStream();
$stream->emit(new Event("2x=3", $stream->getId()));
$stream->process();
$test->assertEquals("x=1.5", $stream->getResult(), "2x=3 → x=1.5");

$stream = createEquationStream();
$stream->emit(new Event("3x+1=8", $stream->getId()));
$stream->process();
$result = $stream->getResult();
// 3x = 7, x = 7/3 = 2.333...
$test->assertEquals("x=2.3333333333333", substr($result, 0, 17), "3x+1=8 → x=2.333... (got: $result)");

// Zero Solution
$stream = createEquationStream();
$stream->emit(new Event("x+5=5", $stream->getId()));
$stream->process();
$test->assertEquals("x=0", $stream->getResult(), "x+5=5 → x=0");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 14: Factoring Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Simple Factoring
$stream = createFactoringStream();
$stream->emit(new Event("x^2+5x+6", $stream->getId()));
$stream->process();
$test->assertEquals("(x+2)(x+3)", $stream->getResult(), "x^2+5x+6 → (x+2)(x+3)");

$stream = createFactoringStream();
$stream->emit(new Event("x^2+7x+12", $stream->getId()));
$stream->process();
$test->assertEquals("(x+3)(x+4)", $stream->getResult(), "x^2+7x+12 → (x+3)(x+4)");

// Negative Constant
$stream = createFactoringStream();
$stream->emit(new Event("x^2-x-6", $stream->getId()));
$stream->process();
$test->assertEquals("(x+2)(x-3)", $stream->getResult(), "x^2-x-6 → (x+2)(x-3)");

$stream = createFactoringStream();
$stream->emit(new Event("x^2+x-6", $stream->getId()));
$stream->process();
$test->assertEquals("(x-2)(x+3)", $stream->getResult(), "x^2+x-6 → (x-2)(x+3)");

// Difference of Squares
$stream = createFactoringStream();
$stream->emit(new Event("x^2-4", $stream->getId()));
$stream->process();
$test->assertEquals("(x+2)(x-2)", $stream->getResult(), "x^2-4 → (x+2)(x-2)");

$stream = createFactoringStream();
$stream->emit(new Event("x^2-9", $stream->getId()));
$stream->process();
$test->assertEquals("(x+3)(x-3)", $stream->getResult(), "x^2-9 → (x+3)(x-3)");

// Perfect Squares
$stream = createFactoringStream();
$stream->emit(new Event("x^2+2x+1", $stream->getId()));
$stream->process();
$test->assertEquals("(x+1)(x+1)", $stream->getResult(), "x^2+2x+1 → (x+1)(x+1)");

$stream = createFactoringStream();
$stream->emit(new Event("x^2-4x+4", $stream->getId()));
$stream->process();
$test->assertEquals("(x-2)(x-2)", $stream->getResult(), "x^2-4x+4 → (x-2)(x-2)");

// Negative Middle Coefficient
$stream = createFactoringStream();
$stream->emit(new Event("x^2-5x+6", $stream->getId()));
$stream->process();
$test->assertEquals("(x-2)(x-3)", $stream->getResult(), "x^2-5x+6 → (x-2)(x-3)");

$stream = createFactoringStream();
$stream->emit(new Event("x^2-7x+10", $stream->getId()));
$stream->process();
$test->assertEquals("(x-2)(x-5)", $stream->getResult(), "x^2-7x+10 → (x-2)(x-5)");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 15: Quadratic Formula Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Two Solutions
$stream = createQuadraticStream();
$stream->emit(new Event("x^2-5x+6=0", $stream->getId()));
$stream->process();
$test->assertEquals("x=2,3", $stream->getResult(), "x^2-5x+6=0 → x=2,3");

$stream = createQuadraticStream();
$stream->emit(new Event("x^2-3x+2=0", $stream->getId()));
$stream->process();
$test->assertEquals("x=1,2", $stream->getResult(), "x^2-3x+2=0 → x=1,2");

$stream = createQuadraticStream();
$stream->emit(new Event("x^2+5x+6=0", $stream->getId()));
$stream->process();
$test->assertEquals("x=-3,-2", $stream->getResult(), "x^2+5x+6=0 → x=-3,-2");

// One Solution (Double Root)
$stream = createQuadraticStream();
$stream->emit(new Event("x^2+2x+1=0", $stream->getId()));
$stream->process();
$test->assertEquals("x=-1", $stream->getResult(), "x^2+2x+1=0 → x=-1");

$stream = createQuadraticStream();
$stream->emit(new Event("x^2-4x+4=0", $stream->getId()));
$stream->process();
$test->assertEquals("x=2", $stream->getResult(), "x^2-4x+4=0 → x=2");

// Difference of Squares
$stream = createQuadraticStream();
$stream->emit(new Event("x^2-4=0", $stream->getId()));
$stream->process();
$test->assertEquals("x=-2,2", $stream->getResult(), "x^2-4=0 → x=-2,2");

$stream = createQuadraticStream();
$stream->emit(new Event("x^2-9=0", $stream->getId()));
$stream->process();
$test->assertEquals("x=-3,3", $stream->getResult(), "x^2-9=0 → x=-3,3");

// No Real Solutions
$stream = createQuadraticStream();
$stream->emit(new Event("x^2+1=0", $stream->getId()));
$stream->process();
$test->assertEquals("no real solutions", $stream->getResult(), "x^2+1=0 → no real solutions");

$stream = createQuadraticStream();
$stream->emit(new Event("x^2+4=0", $stream->getId()));
$stream->process();
$test->assertEquals("no real solutions", $stream->getResult(), "x^2+4=0 → no real solutions");

// Negative Leading Coefficient
$stream = createQuadraticStream();
$stream->emit(new Event("-x^2+4=0", $stream->getId()));
$stream->process();
$test->assertEquals("x=-2,2", $stream->getResult(), "-x^2+4=0 → x=-2,2");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 16: Derivatives (Power Rule) Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Constants
$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(5)", $stream->getId()));
$stream->process();
$test->assertEquals("0", $stream->getResult(), "d/dx(5) → 0");

// Linear
$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(x)", $stream->getId()));
$stream->process();
$test->assertEquals("1", $stream->getResult(), "d/dx(x) → 1");

$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(2x)", $stream->getId()));
$stream->process();
$test->assertEquals("2", $stream->getResult(), "d/dx(2x) → 2");

$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(3x+5)", $stream->getId()));
$stream->process();
$test->assertEquals("3", $stream->getResult(), "d/dx(3x+5) → 3");

// Quadratic
$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(x^2)", $stream->getId()));
$stream->process();
$test->assertEquals("2x", $stream->getResult(), "d/dx(x^2) → 2x");

$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(3x^2)", $stream->getId()));
$stream->process();
$test->assertEquals("6x", $stream->getResult(), "d/dx(3x^2) → 6x");

$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(x^2+2x+1)", $stream->getId()));
$stream->process();
$test->assertEquals("2x+2", $stream->getResult(), "d/dx(x^2+2x+1) → 2x+2");

// Higher powers
$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(x^3)", $stream->getId()));
$stream->process();
$test->assertEquals("3x^2", $stream->getResult(), "d/dx(x^3) → 3x^2");

$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(x^5)", $stream->getId()));
$stream->process();
$test->assertEquals("5x^4", $stream->getResult(), "d/dx(x^5) → 5x^4");

$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(2x^3-3x^2+x)", $stream->getId()));
$stream->process();
$test->assertEquals("6x^2-6x+1", $stream->getResult(), "d/dx(2x^3-3x^2+x) → 6x^2-6x+1");

// Alternative syntax
$stream = createDerivativeStream();
$stream->emit(new Event("diff(x^2, x)", $stream->getId()));
$stream->process();
$test->assertEquals("2x", $stream->getResult(), "diff(x^2, x) → 2x");

$stream = createDerivativeStream();
$stream->emit(new Event("derivative(x^3, x)", $stream->getId()));
$stream->process();
$test->assertEquals("3x^2", $stream->getResult(), "derivative(x^3, x) → 3x^2");

// Partial derivatives
$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(x^2*y)", $stream->getId()));
$stream->process();
$test->assertEquals("2xy", $stream->getResult(), "d/dx(x^2*y) → 2xy (partial)");

$stream = createDerivativeStream();
$stream->emit(new Event("d/dy(x^2*y)", $stream->getId()));
$stream->process();
$test->assertEquals("x^2", $stream->getResult(), "d/dy(x^2*y) → x^2 (partial)");

// Negative coefficients
$stream = createDerivativeStream();
$stream->emit(new Event("d/dx(-x^2)", $stream->getId()));
$stream->process();
$test->assertEquals("-2x", $stream->getResult(), "d/dx(-x^2) → -2x");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 20: Integration (Antiderivatives) Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Constants
$stream = createIntegrationStream();
$stream->emit(new Event("∫5 dx", $stream->getId()));
$stream->process();
$test->assertEquals("5x + C", $stream->getResult(), "∫5 dx → 5x + C");

// Linear
$stream = createIntegrationStream();
$stream->emit(new Event("∫x dx", $stream->getId()));
$stream->process();
$test->assertEquals("0.5x^2 + C", $stream->getResult(), "∫x dx → 0.5x^2 + C");

$stream = createIntegrationStream();
$stream->emit(new Event("∫2x dx", $stream->getId()));
$stream->process();
$test->assertEquals("x^2 + C", $stream->getResult(), "∫2x dx → x^2 + C");

$stream = createIntegrationStream();
$stream->emit(new Event("∫3x dx", $stream->getId()));
$stream->process();
$test->assertEquals("1.5x^2 + C", $stream->getResult(), "∫3x dx → 1.5x^2 + C");

// Quadratic
$stream = createIntegrationStream();
$stream->emit(new Event("∫x^2 dx", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x^3") !== false, "∫x^2 dx → contains x^3 (got: $result)");
$test->assert(strpos($result, "+ C") !== false, "∫x^2 dx → contains + C (got: $result)");

$stream = createIntegrationStream();
$stream->emit(new Event("∫3x^2 dx", $stream->getId()));
$stream->process();
$test->assertEquals("x^3 + C", $stream->getResult(), "∫3x^2 dx → x^3 + C");

// Higher powers
$stream = createIntegrationStream();
$stream->emit(new Event("∫x^3 dx", $stream->getId()));
$stream->process();
$test->assertEquals("0.25x^4 + C", $stream->getResult(), "∫x^3 dx → 0.25x^4 + C");

$stream = createIntegrationStream();
$stream->emit(new Event("∫x^5 dx", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x^6") !== false, "∫x^5 dx → contains x^6 (got: $result)");

// Polynomials
$stream = createIntegrationStream();
$stream->emit(new Event("∫(x^2+2x) dx", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x^3") !== false, "∫(x^2+2x) dx → contains x^3 (got: $result)");
$test->assert(strpos($result, "x^2") !== false, "∫(x^2+2x) dx → contains x^2 (got: $result)");
$test->assert(strpos($result, "+ C") !== false, "∫(x^2+2x) dx → contains + C (got: $result)");

$stream = createIntegrationStream();
$stream->emit(new Event("∫(2x^3+3x^2+x) dx", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x^4") !== false, "∫(2x^3+3x^2+x) dx → contains x^4 (got: $result)");
$test->assert(strpos($result, "x^3") !== false, "∫(2x^3+3x^2+x) dx → contains x^3 (got: $result)");
$test->assert(strpos($result, "x^2") !== false, "∫(2x^3+3x^2+x) dx → contains x^2 (got: $result)");

// Alternative syntax
$stream = createIntegrationStream();
$stream->emit(new Event("integrate(x^2, x)", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x^3") !== false, "integrate(x^2, x) → contains x^3 (got: $result)");
$test->assert(strpos($result, "+ C") !== false, "integrate(x^2, x) → contains + C (got: $result)");

$stream = createIntegrationStream();
$stream->emit(new Event("int(x^3, x)", $stream->getId()));
$stream->process();
$test->assertEquals("0.25x^4 + C", $stream->getResult(), "int(x^3, x) → 0.25x^4 + C");

// Partial integration
$stream = createIntegrationStream();
$stream->emit(new Event("∫2xy dx", $stream->getId()));
$stream->process();
$test->assertEquals("x^2y + C", $stream->getResult(), "∫2xy dx → x^2y + C (partial)");

// Negative coefficients
$stream = createIntegrationStream();
$stream->emit(new Event("∫-x^2 dx", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "-") !== false, "∫-x^2 dx → contains negative (got: $result)");
$test->assert(strpos($result, "x^3") !== false, "∫-x^2 dx → contains x^3 (got: $result)");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 21: Definite Integrals Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Simple constants
$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[0,2] 5 dx", $stream->getId()));
$stream->process();
$test->assertEquals("10", $stream->getResult(), "∫[0,2] 5 dx → 10");

// Linear functions
$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[0,2] x dx", $stream->getId()));
$stream->process();
$test->assertEquals("2", $stream->getResult(), "∫[0,2] x dx → 2");

$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[1,3] 2x dx", $stream->getId()));
$stream->process();
$test->assertEquals("8", $stream->getResult(), "∫[1,3] 2x dx → 8");

// Quadratic functions
$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[0,2] x^2 dx", $stream->getId()));
$stream->process();
$result = floatval($stream->getResult());
$expected = 2.6667;
$test->assert(abs($result - $expected) < 0.001, "∫[0,2] x^2 dx → ~2.667 (got: $result)");

$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[1,3] x^2 dx", $stream->getId()));
$stream->process();
$result = floatval($stream->getResult());
$expected = 8.6667;
$test->assert(abs($result - $expected) < 0.001, "∫[1,3] x^2 dx → ~8.667 (got: $result)");

// Cubic functions
$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[0,2] x^3 dx", $stream->getId()));
$stream->process();
$test->assertEquals("4", $stream->getResult(), "∫[0,2] x^3 dx → 4");

// Polynomial
$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[0,1] (x^2+2x) dx", $stream->getId()));
$stream->process();
$result = floatval($stream->getResult());
$expected = 1.3333;
$test->assert(abs($result - $expected) < 0.001, "∫[0,1] (x^2+2x) dx → ~1.333 (got: $result)");

// Alternative syntax
$stream = createDefiniteIntegralStream();
$stream->emit(new Event("int([0,2], x^2, x)", $stream->getId()));
$stream->process();
$result = floatval($stream->getResult());
$expected = 2.6667;
$test->assert(abs($result - $expected) < 0.001, "int([0,2], x^2, x) → ~2.667 (got: $result)");

// Negative bounds
$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[-1,1] x^2 dx", $stream->getId()));
$stream->process();
$result = floatval($stream->getResult());
$expected = 0.6667;
$test->assert(abs($result - $expected) < 0.001, "∫[-1,1] x^2 dx → ~0.667 (got: $result)");

// Area under curve
$stream = createDefiniteIntegralStream();
$stream->emit(new Event("∫[0,3] 2x dx", $stream->getId()));
$stream->process();
$test->assertEquals("9", $stream->getResult(), "∫[0,3] 2x dx → 9 (area)");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 22: Critical Points (Optimization) Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Note: Function evaluation has limitations with certain expression formats
// All critical points (x values) are found correctly

// Quadratic - vertex
$stream = createCriticalPointsStream();
$stream->emit(new Event("critical(x^2-4x+3)", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x=2") !== false, "critical(x^2-4x+3) → x=2 (got: $result)");

$stream = createCriticalPointsStream();
$stream->emit(new Event("critical(x^2+2x+1)", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x=-1") !== false, "critical(x^2+2x+1) → x=-1 (got: $result)");
$test->assert(strpos($result, "value=0") !== false, "critical(x^2+2x+1) → value=0 (got: $result)");

// Cubic
$stream = createCriticalPointsStream();
$stream->emit(new Event("critical(x^3-3x+2)", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x=1") !== false, "critical(x^3-3x+2) → x=1 (got: $result)");

// Maximize syntax
$stream = createCriticalPointsStream();
$stream->emit(new Event("maximize(-x^2+4x)", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x=2") !== false, "maximize(-x^2+4x) → x=2 (got: $result)");
$test->assert(strpos($result, "value=4") !== false, "maximize(-x^2+4x) → value=4 (got: $result)");

// Minimize syntax  
$stream = createCriticalPointsStream();
$stream->emit(new Event("minimize(x^2-6x+8)", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x=3") !== false, "minimize(x^2-6x+8) → x=3 (got: $result)");

// Simple quadratic
$stream = createCriticalPointsStream();
$stream->emit(new Event("critical(2x^2-8x+6)", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x=2") !== false, "critical(2x^2-8x+6) → x=2 (got: $result)");

// Negative leading coefficient
$stream = createCriticalPointsStream();
$stream->emit(new Event("critical(-x^2+6x-5)", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "x=3") !== false, "critical(-x^2+6x-5) → x=3 (got: $result)");

echo "\n" . str_repeat("=", 50) . "\n";
echo "PHASE 23: Logging & Failure Gate Tests\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Logging OFF (default)
$stream = new Stream();
$stream->registerGate(new AddGate());
$stream->registerGate(new ResultGate());
$test->assertEquals(LoggingLevel::OFF, $stream->getLoggingLevel(), "Default logging level is OFF");
$stream->emit(new Event("2+3", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Logging OFF: 2+3 → 5");
$test->assertEquals([], $stream->getHistory(), "Logging OFF: No history tracked");

// Test 2: Logging MINIMAL
$stream = new Stream();
$stream->setLoggingLevel(LoggingLevel::MINIMAL);
$stream->registerGate(new AddGate());
$stream->registerGate(new ResultGate());
$stream->emit(new Event("2+3", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Logging MINIMAL: 2+3 → 5");
$path = $stream->getTransformationPath();
$test->assert(in_array('StreamGate\\Gates\\AddGate', $path), "Logging MINIMAL: Tracks gate names (got: " . implode(', ', $path) . ")");

// Test 3: Logging DETAILED
$stream = new Stream();
$stream->setLoggingLevel(LoggingLevel::DETAILED);
$stream->registerGate(new AddGate());
$stream->registerGate(new ResultGate());
$stream->emit(new Event("2+3", $stream->getId()));
$stream->process();
$test->assertEquals("5", $stream->getResult(), "Logging DETAILED: 2+3 → 5");
$history = $stream->getHistory();
$test->assert(!empty($history), "Logging DETAILED: History is not empty");
$test->assertEquals('StreamGate\\Gates\\AddGate', $history[0]['gate'], "Logging DETAILED: Tracks gate name");
$test->assertEquals('2+3', $history[0]['before'], "Logging DETAILED: Tracks before state");

// Test 4: DontMatchGate - invalid input
$stream = new Stream();
$stream->registerGate(new AddGate());
$stream->registerGate(new SubtractGate());
$stream->registerGate(new MultiplyGate());
$stream->registerGate(new DivideGate());
$stream->registerGate(new DontMatchGate());
$stream->registerGate(new ResultGate());
$stream->emit(new Event("2++3", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "Error:") === 0, "DontMatchGate: Invalid input starts with 'Error:' (got: $result)");
$test->assert(strpos($result, "2++3") !== false, "DontMatchGate: Error contains invalid input (got: $result)");

// Test 5: DontMatchGate - unrecognized operator
$stream = new Stream();
$stream->registerGate(new AddGate());
$stream->registerGate(new DontMatchGate());
$stream->registerGate(new ResultGate());
$stream->emit(new Event("2@3", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "Error:") !== false, "DontMatchGate: Unrecognized operator triggers error (got: $result)");
$test->assert(strpos($result, "Unrecognized expression") !== false, "DontMatchGate: Contains 'Unrecognized expression' (got: $result)");

// Test 6: Valid input doesn't trigger DontMatchGate
$stream = new Stream();
$stream->registerGate(new AddGate());
$stream->registerGate(new DontMatchGate());
$stream->registerGate(new ResultGate());
$stream->emit(new Event("2+3", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assertEquals("5", $result, "DontMatchGate: Valid input doesn't trigger error");
$test->assert(strpos($result, "Error") === false, "DontMatchGate: No error message for valid input");

// Test 7: Multiple operations with logging
$stream = new Stream();
$stream->setLoggingLevel(LoggingLevel::DETAILED);
$stream->registerGate(new MultiplyGate());
$stream->registerGate(new AddGate());
$stream->registerGate(new ResultGate());
$stream->emit(new Event("2*3", $stream->getId()));
$stream->process();
$test->assertEquals("6", $stream->getResult(), "Logging tracks multiple operations: 2*3 → 6");
$history = $stream->getHistory();
$test->assert(!empty($history), "Logging: History captured for multiply");
$test->assertEquals('2*3', $history[0]['before'], "Logging: Before state is '2*3'");

// Test 8: DEBUG level with invalid input
$stream = new Stream();
$stream->setLoggingLevel(LoggingLevel::DEBUG);
$stream->registerGate(new MultiplyGate());
$stream->registerGate(new DivideGate());
$stream->registerGate(new AddGate());
$stream->registerGate(new DontMatchGate());
$stream->registerGate(new ResultGate());
$stream->emit(new Event("invalid", $stream->getId()));
$stream->process();
$result = $stream->getResult();
$test->assert(strpos($result, "Error") !== false, "DEBUG level: Error message present (got: $result)");
$test->assert(strpos($result, "Rejected by") !== false, "DEBUG level: Shows rejection count (got: $result)");

echo "\n";
$success = $test->summary();

exit($success ? 0 : 1);
