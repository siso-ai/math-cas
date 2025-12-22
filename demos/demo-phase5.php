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
    $stream->registerGate(new ExponentGate());
    $stream->registerGate(new PartialOperationGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

echo "=== Phase 5 Demo: Exponents (^) ===\n\n";

// Demo 1: Simple exponent
echo "1. Simple exponent: 2^3\n";
$calc = createFullCalculator();
$calc->emit(new Event("2^3", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   2³ = 2×2×2 = 8 ✓\n\n";

// Demo 2: Square
echo "2. Square: 5^2\n";
$calc = createFullCalculator();
$calc->emit(new Event("5^2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   5² = 5×5 = 25 ✓\n\n";

// Demo 3: Negative exponent
echo "3. Negative exponent: 2^-2\n";
$calc = createFullCalculator();
$calc->emit(new Event("2^-2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   2⁻² = 1/(2²) = 1/4 = 0.25 ✓\n\n";

// Demo 4: Fractional exponent (square root)
echo "4. Fractional exponent (square root): 9^0.5\n";
$calc = createFullCalculator();
$calc->emit(new Event("9^0.5", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   9^0.5 = √9 = 3 ✓\n\n";

// Demo 5: Precedence - exponent before multiplication
echo "5. Precedence: 2*3^2\n";
$calc = createFullCalculator();
$calc->emit(new Event("2*3^2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 2*(3^2) = 2*9 = 18 ✓\n";
echo "   (NOT (2*3)^2 = 6^2 = 36)\n\n";

// Demo 6: RIGHT-TO-LEFT ASSOCIATIVITY (The Key Demo!)
echo "6. Right-to-left associativity: 2^2^3\n";
$calc = createFullCalculator();
$calc->emit(new Event("2^2^3", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow:\n";
echo "     PrecedenceGate finds two ^ operators\n";
echo "     Right-to-left: groups rightmost first\n";
echo "     2^2^3 → 2^(2^3)\n";
echo "     Inner: 2^3 = 8\n";
echo "     Outer: 2^8 = 256 ✓\n";
echo "   (NOT left-to-right: (2^2)^3 = 4^3 = 64)\n\n";

// Demo 7: Another right-to-left example
echo "7. Right-to-left: 2^3^2\n";
$calc = createFullCalculator();
$calc->emit(new Event("2^3^2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 2^(3^2) = 2^9 = 512 ✓\n";
echo "   (NOT (2^3)^2 = 8^2 = 64)\n\n";

// Demo 8: Parentheses override associativity
echo "8. Parentheses override: (2^2)^3\n";
$calc = createFullCalculator();
$calc->emit(new Event("(2^2)^3", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: (4)^3 = 64 ✓\n";
echo "   Parentheses force left-to-right!\n\n";

// Demo 9: Complex expression
echo "9. Complex: 2^3+3^2\n";
$calc = createFullCalculator();
$calc->emit(new Event("2^3+3^2", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow: 8+9 = 17 ✓\n\n";

// Demo 10: With factorial
echo "10. With factorial: 2^3!\n";
$calc = createFullCalculator();
$calc->emit(new Event("2^3!", $calc->getId()));
$calc->process();
echo "   Result: " . $calc->getResult() . "\n";
echo "   Flow:\n";
echo "     FactorialGate processes first: 3! = 6\n";
echo "     Then: 2^6 = 64 ✓\n\n";

// Demo 11: Practical - compound interest
echo "11. Practical example (compound interest): 100*(1.05)^10\n";
$calc = createFullCalculator();
$calc->emit(new Event("100*(1.05)^10", $calc->getId()));
$calc->process();
$result = $calc->getResult();
echo "   Result: " . $result . "\n";
echo "   \$100 at 5% interest for 10 years ≈ \$" . round($result, 2) . " ✓\n\n";

// Demo 12: Zero and one edge cases
echo "12. Edge cases:\n";

$calc = createFullCalculator();
$calc->emit(new Event("5^0", $calc->getId()));
$calc->process();
echo "   5^0 = " . $calc->getResult() . " (any number to power 0 is 1)\n";

$calc = createFullCalculator();
$calc->emit(new Event("5^1", $calc->getId()));
$calc->process();
echo "   5^1 = " . $calc->getResult() . " (any number to power 1 is itself)\n";

$calc = createFullCalculator();
$calc->emit(new Event("0^5", $calc->getId()));
$calc->process();
echo "   0^5 = " . $calc->getResult() . " (zero to any positive power is 0)\n";

$calc = createFullCalculator();
$calc->emit(new Event("1^999", $calc->getId()));
$calc->process();
echo "   1^999 = " . $calc->getResult() . " (one to any power is 1)\n\n";

echo "✓ Phase 5 demonstrates:\n";
echo "  - Exponent operator (^) for powers\n";
echo "  - Highest precedence (above *, /, %)\n";
echo "  - RIGHT-TO-LEFT associativity (unique!)\n";
echo "  - Negative exponents (reciprocals)\n";
echo "  - Fractional exponents (roots)\n";
echo "  - Integration with all existing operations\n";
echo "\n";
echo "Precedence table (updated):\n";
echo "  Level 3: ^          (right-to-left)\n";
echo "  Level 2: *, /, %    (left-to-right)\n";
echo "  Level 1: +, -       (left-to-right)\n";
echo "  Special: !          (postfix, processed first)\n";
echo "  Wrappers: ( )       (highest priority)\n";
echo "\n";
echo "The calculator now handles:\n";
echo "  - All basic arithmetic\n";
echo "  - Powers and roots\n";
echo "  - Factorials\n";
echo "  - Complete PEMDAS with correct associativity!\n";
