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
use StreamGate\Gates\ResultGate;

function createFullStream(): Stream {
    $stream = new Stream();
    $stream->registerGate(new ParenGate());
    $stream->registerGate(new AddGate());
    $stream->registerGate(new SubtractGate());
    $stream->registerGate(new MultiplyGate());
    $stream->registerGate(new DivideGate());
    $stream->registerGate(new PartialOperationGate());
    $stream->registerGate(new ResultGate());
    return $stream;
}

echo "=== Phase 3 Demo: Partial Operations & Decomposition ===\n\n";

// Demo 1: Simple partial operation
echo "1. Partial operation: (2*3)+5\n";
$stream = createFullStream();
$stream->emit(new Event("(2*3)+5", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow:\n";
echo "     Main: (2*3)+5 → PartialOperationGate recognizes pattern\n";
echo "     Private stream created for (2*3)\n";
echo "       Private: (2*3) → ParenGate → 2*3\n";
echo "       Private: 2*3 → MultiplyGate → 6\n";
echo "       Private: 6 → ResultGate → return to parent\n";
echo "     PartialOperationGate recombines: 6+5\n";
echo "     Main: 6+5 → AddGate → 11\n\n";

// Demo 2: Partial on right side
echo "2. Partial on right: 4*(2+3)\n";
$stream = createFullStream();
$stream->emit(new Event("4*(2+3)", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow:\n";
echo "     Private stream resolves (2+3) → 5\n";
echo "     Recombines as 4*5 → 20\n\n";

// Demo 3: Nested partial operations
echo "3. Nested decomposition: ((2+3)*2)+1\n";
$stream = createFullStream();
$stream->emit(new Event("((2+3)*2)+1", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow:\n";
echo "     Main: ((2+3)*2)+1 → PartialOperationGate\n";
echo "       Private-1: ((2+3)*2)\n";
echo "         Private-1: (2+3)*2 → PartialOperationGate\n";
echo "           Private-2: (2+3) → 2+3 → 5\n";
echo "         Private-1: 5*2 → 10\n";
echo "       Returns 10 to main\n";
echo "     Main: 10+1 → 11\n\n";

// Demo 4: Deep nesting
echo "4. Deep nesting: (((2+3)*2)+1)*2\n";
$stream = createFullStream();
$stream->emit(new Event("(((2+3)*2)+1)*2", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow:\n";
echo "     Layer 1: (((2+3)*2)+1)*2\n";
echo "     Layer 2: ((2+3)*2)+1\n";
echo "     Layer 3: (2+3)*2\n";
echo "     Layer 4: 2+3 → 5\n";
echo "     Layer 3: 5*2 → 10\n";
echo "     Layer 2: 10+1 → 11\n";
echo "     Layer 1: 11*2 → 22\n\n";

// Demo 5: Complex expression
echo "5. Complex: (2*6)*3\n";
$stream = createFullStream();
$stream->emit(new Event("(2*6)*3", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow: Private stream for (2*6) → 12, then 12*3 → 36\n\n";

// Demo 6: With subtraction ordering
echo "6. Subtraction order matters: 10-(3+2)\n";
$stream = createFullStream();
$stream->emit(new Event("10-(3+2)", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow: (3+2) → 5, then 10-5 → 5\n\n";

echo "✓ Phase 3 demonstrates:\n";
echo "  - Partial operations decompose unresolved parts\n";
echo "  - Private streams isolate sub-computations\n";
echo "  - Suspension and resumption of parent gate\n";
echo "  - Recursive decomposition (private streams create more private streams)\n";
echo "  - Natural ordering preserved (left vs right operand)\n";
echo "  - Pure emergence - no explicit control flow or recursion\n";
echo "\n";
echo "The key insight: Complex expressions naturally decompose through\n";
echo "pattern matching alone. Gates don't 'call' each other - they just\n";
echo "recognize shapes and create private streams when needed.\n";
