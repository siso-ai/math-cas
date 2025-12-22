<?php

require_once __DIR__ . '/autoload.php';

use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\ParenGate;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\MultiplyGate;

echo "=== Phase 2 Demo: Parentheses Handling ===\n\n";

// Demo 1: Simple parentheses stripping
echo "1. Simple strip: (5)\n";
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->emit(new Event("(5)", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow: (5) → ParenGate strips → 5\n\n";

// Demo 2: Parentheses with operation
echo "2. Parentheses with operation: (2+3)\n";
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new AddGate());
$stream->emit(new Event("(2+3)", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow: (2+3) → ParenGate strips → 2+3 → AddGate → 5\n\n";

// Demo 3: Nested parentheses
echo "3. Nested parentheses: ((2*5))\n";
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new MultiplyGate());
$stream->emit(new Event("((2*5))", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow: ((2*5)) → ParenGate strips → (2*5)\n";
echo "         (2*5) → ParenGate strips → 2*5\n";
echo "         2*5 → MultiplyGate → 10\n\n";

// Demo 4: Triple nested
echo "4. Triple nested: (((7+3)))\n";
$stream = new Stream();
$stream->registerGate(new ParenGate());
$stream->registerGate(new AddGate());
$stream->emit(new Event("(((7+3)))", $stream->getId()));
$stream->process();
echo "   Result: " . $stream->getResult() . "\n";
echo "   Flow: (((7+3))) → (ParenGate × 3) → 7+3 → AddGate → 10\n\n";

echo "✓ Phase 2 demonstrates:\n";
echo "  - ParenGate strips outer parentheses\n";
echo "  - Handles arbitrary nesting depth\n";
echo "  - Works seamlessly with operation gates\n";
echo "  - Pure emergence - no explicit control flow\n";
