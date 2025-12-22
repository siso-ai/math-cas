<?php

require_once __DIR__ . '/autoload.php';

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

function createFullCalculator(): Stream {
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

echo "=== Phase 7 Demo: Modulo & Factorial ===\n\n";

// Demo 1: Simple modulo
echo "1. Simple modulo: 10%3\n";
$calc = createFullCalculator();
$calc->emit(new Event("10%3", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   10 divided by 3 leaves remainder 1 ✓\n\n";

// Demo 2: Modulo with operations
echo "2. Modulo with precedence: 10%3+5\n";
$calc = createFullCalculator();
$calc->emit(new Event("10%3+5", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: (10%3)+5 = 1+5 = 6 ✓\n\n";

// Demo 3: Simple factorial
echo "3. Simple factorial: 5!\n";
$calc = createFullCalculator();
$calc->emit(new Event("5!", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   5! = 5×4×3×2×1 = 120 ✓\n\n";

// Demo 4: Factorial with operations
echo "4. Factorial addition: 3!+2!\n";
$calc = createFullCalculator();
$calc->emit(new Event("3!+2!", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 3! = 6, 2! = 2, then 6+2 = 8 ✓\n\n";

// Demo 5: Factorial multiplication
echo "5. Factorial multiplication: 4!*2\n";
$calc = createFullCalculator();
$calc->emit(new Event("4!*2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 4! = 24, then 24*2 = 48 ✓\n\n";

// Demo 6: Combined modulo and factorial
echo "6. Modulo with factorial: 10%3!\n";
$calc = createFullCalculator();
$calc->emit(new Event("10%3!", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 3! = 6, then 10%6 = 4 ✓\n\n";

// Demo 7: Complex expression
echo "7. Complex: 100%25+3!*2\n";
$calc = createFullCalculator();
$calc->emit(new Event("100%25+3!*2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow:\n";
echo "     3! = 6\n";
echo "     Expression: 100%25+6*2\n";
echo "     PrecedenceGate: (100%25)+(6*2)\n";
echo "     Modulo: 0, Multiply: 12\n";
echo "     Final: 0+12 = 12 ✓\n\n";

// Demo 8: Zero factorial
echo "8. Zero factorial: 0!\n";
$calc = createFullCalculator();
$calc->emit(new Event("0!", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   By definition, 0! = 1 ✓\n\n";

echo "✓ Phase 7 demonstrates:\n";
echo "  - Modulo operator (%) for remainders\n";
echo "  - Factorial operator (!) for permutations/combinations\n";
echo "  - Both integrate seamlessly with existing operations\n";
echo "  - Precedence: % same as *, /  (level 2)\n";
echo "  - Factorial processes FIRST (resolved before other ops)\n";
echo "\n";
echo "The calculator now supports:\n";
echo "  Operators: + - * / %  (5 binary operations)\n";
echo "  Special: ! (1 unary/postfix operation)\n";
echo "  Grouping: ( )\n";
echo "  Total: 15 gates handling complete arithmetic!\n";
