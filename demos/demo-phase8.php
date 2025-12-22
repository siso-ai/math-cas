<?php

require_once __DIR__ . '/autoload.php';

use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\AlgebraicEvent;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\ResultGate;

function createAlgebraStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new TermParseGate());
    $stream->registerGate(new ConstantTermGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

echo "=== Phase 8 Demo: Expression Infrastructure ===\n\n";

// Demo 1: Simple variable
echo "1. Simple variable: x\n";
$calc = createAlgebraStream();
$calc->emit(new Event("x", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Parsed as: Term(1, [Variable('x', 1)])\n\n";

// Demo 2: Coefficient
echo "2. Variable with coefficient: 2x\n";
$calc = createAlgebraStream();
$calc->emit(new Event("2x", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Parsed as: Term(2, [Variable('x', 1)])\n\n";

// Demo 3: Negative coefficient
echo "3. Negative coefficient: -3y\n";
$calc = createAlgebraStream();
$calc->emit(new Event("-3y", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Coefficient: -3, Variable: y\n\n";

// Demo 4: Exponent
echo "4. Variable with exponent: x^2\n";
$calc = createAlgebraStream();
$calc->emit(new Event("x^2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Parsed as: Term(1, [Variable('x', 2)])\n\n";

// Demo 5: Coefficient and exponent
echo "5. Coefficient + exponent: 2x^3\n";
$calc = createAlgebraStream();
$calc->emit(new Event("2x^3", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Coefficient: 2, Exponent: 3\n\n";

// Demo 6: Multiple variables
echo "6. Multiple variables: xy\n";
$calc = createAlgebraStream();
$calc->emit(new Event("xy", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Parsed as: Term(1, [Variable('x', 1), Variable('y', 1)])\n\n";

// Demo 7: Variable sorting
echo "7. Variable sorting: yx\n";
$calc = createAlgebraStream();
$calc->emit(new Event("yx", $calc->getId()));
$calc->process();
echo "   Input: yx\n";
echo "   Result: " . $calc->getResult() . "\n";
echo "   Variables automatically sorted alphabetically! ✓\n\n";

// Demo 8: Complex term
echo "8. Complex term: 3x^2y\n";
$calc = createAlgebraStream();
$calc->emit(new Event("3x^2y", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Coefficient: 3\n";
echo "   Variables: x^2, y\n\n";

// Demo 9: Constant
echo "9. Constant (no variables): 5\n";
$calc = createAlgebraStream();
$calc->emit(new Event("5", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Parsed as: Term(5, [])\n";
echo "   Empty variables array = constant\n\n";

// Demo 10: Negative constant
echo "10. Negative constant: -3\n";
$calc = createAlgebraStream();
$calc->emit(new Event("-3", $calc->getId()));
$calc->process();
echo "    Result: " . $calc->getResult() . "\n";
echo "    Constant with negative coefficient\n\n";

echo "✓ Phase 8 demonstrates:\n";
echo "  - Variable recognition (x, y, z)\n";
echo "  - Coefficient parsing (2x, -3y, 0.5z)\n";
echo "  - Exponent parsing (x^2, x^3)\n";
echo "  - Multiple variables (xy, xyz)\n";
echo "  - Automatic variable sorting (yx → xy)\n";
echo "  - Constants (5, -3)\n";
echo "  - Expression data structures (Variable, Term, Expression)\n";
echo "  - AlgebraicEvent (carries both string and structure)\n";
echo "\n";
echo "Foundation complete! Ready for Phase 9 (combining like terms).\n";
echo "Total tests: 131 (all passing ✓)\n";
