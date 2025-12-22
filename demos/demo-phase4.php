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
use StreamGate\Gates\ResultGate;

function createFullCalculator(): Stream {
    $stream = new Stream();
    $stream->registerGate(new PrecedenceGate());
    $stream->registerGate(new ParenGate());
    $stream->registerGate(new AddGate());
    $stream->registerGate(new SubtractGate());
    $stream->registerGate(new MultiplyGate());
    $stream->registerGate(new DivideGate());
    $stream->registerGate(new PartialOperationGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

echo "=== Phase 4 Demo: PEMDAS / Precedence Handling ===\n\n";

// Demo 1: Multiplication before addition
echo "1. Multiplication before addition: 2+3*4\n";
$calc = createFullCalculator();
$calc->emit(new Event("2+3*4", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow:\n";
echo "     PrecedenceGate: 2+3*4 → 2+(3*4)\n";
echo "     PartialOperationGate: resolve (3*4) → 12\n";
echo "     Recombine: 2+12\n";
echo "     AddGate: 14 ✓\n\n";

// Demo 2: Division before subtraction
echo "2. Division before subtraction: 10-6/2\n";
$calc = createFullCalculator();
$calc->emit(new Event("10-6/2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 10-6/2 → 10-(6/2) → 10-3 → 7 ✓\n\n";

// Demo 3: Left-to-right same precedence
echo "3. Left-to-right evaluation: 10-5-2\n";
$calc = createFullCalculator();
$calc->emit(new Event("10-5-2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 10-5-2 → (10-5)-2 → 5-2 → 3 ✓\n";
echo "   (NOT 10-(5-2) = 10-3 = 7)\n\n";

// Demo 4: Complex mixed operations
echo "4. Complex mixed: 2+3*4-6/2\n";
$calc = createFullCalculator();
$calc->emit(new Event("2+3*4-6/2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow:\n";
echo "     PrecedenceGate normalizes:\n";
echo "       2+3*4-6/2 → 2+(3*4)-6/2 (group first *)\n";
echo "       2+(3*4)-6/2 → 2+(3*4)-(6/2) (group /)\n";
echo "       2+(3*4)-(6/2) → (2+(3*4))-(6/2) (group +)\n";
echo "     Now: (2+(3*4))-(6/2)\n";
echo "     PartialOperationGate: both sides unresolved\n";
echo "       Resolve left: (2+(3*4))\n";
echo "         Resolve inner: (3*4) → 12\n";
echo "         Then: 2+12 → 14\n";
echo "       Recombine: 14-(6/2)\n";
echo "       Resolve right: (6/2) → 3\n";
echo "       Final: 14-3 → 11 ✓\n\n";

// Demo 5: Parentheses override precedence
echo "5. Parentheses override: (2+3)*4\n";
$calc = createFullCalculator();
$calc->emit(new Event("(2+3)*4", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: (2+3)*4 → 5*4 → 20 ✓\n";
echo "   (NOT 2+(3*4) = 2+12 = 14)\n\n";

// Demo 6: Multiple operations same precedence
echo "6. Multiple multiplications: 2*3*4\n";
$calc = createFullCalculator();
$calc->emit(new Event("2*3*4", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 2*3*4 → (2*3)*4 → 6*4 → 24 ✓\n\n";

// Demo 7: Real-world calculation
echo "7. Real-world: 5+3*2-8/4\n";
$calc = createFullCalculator();
$calc->emit(new Event("5+3*2-8/4", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 5+3*2-8/4 → 5+(3*2)-(8/4) → 5+6-2 → 9 ✓\n\n";

echo "✓ Phase 4 demonstrates:\n";
echo "  - Multiplication and division before addition and subtraction\n";
echo "  - Left-to-right evaluation for same precedence\n";
echo "  - Parentheses override natural precedence\n";
echo "  - Complex expressions with multiple operators\n";
echo "  - PEMDAS emerges from precedence-based normalization\n";
echo "\n";
echo "The key insight: PrecedenceGate NORMALIZES by adding parentheses,\n";
echo "then existing gates handle the rest. No special evaluation order\n";
echo "coded - it emerges from the normalized shape!\n";
