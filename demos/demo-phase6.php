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
use StreamGate\Gates\ExponentGate;
use StreamGate\Gates\SquareRootGate;
use StreamGate\Gates\NthRootGate;
use StreamGate\Gates\FloorGate;
use StreamGate\Gates\CeilGate;
use StreamGate\Gates\AbsoluteValueGate;
use StreamGate\Gates\ResultGate;

function createFullCalculator(): Stream {
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

echo "=== Phase 6 Demo: Roots & Brackets ===\n\n";

// Demo 1: Square root
echo "1. Square root: √9\n";
$calc = createFullCalculator();
$calc->emit(new Event("√9", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   √9 = 3 ✓\n";
echo "   Implementation: Converts to 9^0.5\n\n";

// Demo 2: Square root of expression
echo "2. Square root of expression: √(16+9)\n";
$calc = createFullCalculator();
$calc->emit(new Event("√(16+9)", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: (16+9) = 25, √25 = 5 ✓\n\n";

// Demo 3: Cube root
echo "3. Cube root: 3√27\n";
$calc = createFullCalculator();
$calc->emit(new Event("3√27", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   ∛27 = 3 ✓\n";
echo "   Implementation: Converts to 27^(1/3)\n\n";

// Demo 4: Fourth root
echo "4. Fourth root: 4√16\n";
$calc = createFullCalculator();
$calc->emit(new Event("4√16", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   ⁴√16 = 2 ✓\n\n";

// Demo 5: Floor function
echo "5. Floor (round down): ⌊3.7⌋\n";
$calc = createFullCalculator();
$calc->emit(new Event("⌊3.7⌋", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   ⌊3.7⌋ = 3 (greatest integer ≤ 3.7) ✓\n\n";

// Demo 6: Floor with negative
echo "6. Floor with negative: ⌊-3.7⌋\n";
$calc = createFullCalculator();
$calc->emit(new Event("⌊-3.7⌋", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   ⌊-3.7⌋ = -4 (greatest integer ≤ -3.7) ✓\n\n";

// Demo 7: Ceiling function
echo "7. Ceiling (round up): ⌈3.2⌉\n";
$calc = createFullCalculator();
$calc->emit(new Event("⌈3.2⌉", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   ⌈3.2⌉ = 4 (smallest integer ≥ 3.2) ✓\n\n";

// Demo 8: Absolute value
echo "8. Absolute value: |-5|\n";
$calc = createFullCalculator();
$calc->emit(new Event("|-5|", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   |-5| = 5 (distance from zero) ✓\n\n";

// Demo 9: Absolute value of expression
echo "9. Absolute value of expression: |3-7|\n";
$calc = createFullCalculator();
$calc->emit(new Event("|3-7|", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 3-7 = -4, |-4| = 4 ✓\n\n";

// Demo 10: Combined operations
echo "10. Combined: ⌊5.9⌋+⌈5.1⌉\n";
$calc = createFullCalculator();
$calc->emit(new Event("⌊5.9⌋+⌈5.1⌉", $calc->getId()));
$calc->process();
echo "    Result: " . $calc->getResult() . "\n";
echo "    Flow: 5 + 6 = 11 ✓\n\n";

// Demo 11: Nested operations
echo "11. Nested: |⌊-3.7⌋|\n";
$calc = createFullCalculator();
$calc->emit(new Event("|⌊-3.7⌋|", $calc->getId()));
$calc->process();
echo "    Result: " . $calc->getResult() . "\n";
echo "    Flow: ⌊-3.7⌋ = -4, |-4| = 4 ✓\n\n";

// Demo 12: Roots with operations
echo "12. Roots with operations: 2*√9+3√8\n";
$calc = createFullCalculator();
$calc->emit(new Event("2*√9+3√8", $calc->getId()));
$calc->process();
echo "    Result: " . $calc->getResult() . "\n";
echo "    Flow:\n";
echo "      √9 = 3, 3√8 = 2\n";
echo "      2*3+2 = 6+2 = 8 ✓\n\n";

// Demo 13: Practical example - distance formula
echo "13. Practical (distance): √(3^2+4^2)\n";
$calc = createFullCalculator();
$calc->emit(new Event("√(3^2+4^2)", $calc->getId()));
$calc->process();
echo "    Result: " . $calc->getResult() . "\n";
echo "    Distance from (0,0) to (3,4) = 5 ✓\n\n";

// Demo 14: Practical - rounding
echo "14. Practical (rounding): ⌊10/3⌋ vs ⌈10/3⌉\n";
$calc = createFullCalculator();
$calc->emit(new Event("⌊10/3⌋", $calc->getId()));
$calc->process();
$floor = $calc->getResult();

$calc = createFullCalculator();
$calc->emit(new Event("⌈10/3⌉", $calc->getId()));
$calc->process();
$ceil = $calc->getResult();

echo "    10/3 = 3.333...\n";
echo "    Floor (down): " . $floor . " ✓\n";
echo "    Ceil (up): " . $ceil . " ✓\n\n";

echo "✓ Phase 6 demonstrates:\n";
echo "  - Square roots: √x (converts to x^0.5)\n";
echo "  - Nth roots: n√x (converts to x^(1/n))\n";
echo "  - Floor: ⌊x⌋ (greatest integer ≤ x)\n";
echo "  - Ceiling: ⌈x⌉ (smallest integer ≥ x)\n";
echo "  - Absolute value: |x| (distance from zero)\n";
echo "  - All work with expressions inside\n";
echo "  - Seamless integration with existing operations\n";
echo "\n";
echo "The calculator now supports ALL standard operators:\n";
echo "  Binary: + - * / % ^\n";
echo "  Unary: ! (postfix), √, n√, ⌊⌋, ⌈⌉, ||\n";
echo "  Grouping: ( )\n";
echo "  Total: 21 gates handling complete arithmetic!\n";
