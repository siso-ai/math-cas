<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\AlgebraicEvent;
use StreamGate\Stream;
use StreamGate\Algebra\Term;
use StreamGate\Algebra\Expression;

/**
 * Parses constant numbers into Expression objects.
 * This allows constants to participate in algebraic operations.
 * 
 * Matches: 5, -3, 2.5 (pure numbers without operators)
 * Creates: Term with coefficient, no variables
 * 
 * NOTE: This gate should be registered AFTER algebraic operations
 * so it doesn't interfere with arithmetic operations like "2+3"
 */
class ConstantTermGate extends Gate {
    public function matches(Event $event): bool {
        // Skip if already algebraic
        if ($event instanceof AlgebraicEvent && $event->expression !== null) {
            return false;
        }
        
        // Matches: just a number (constant term)
        // But not if it's part of an operation (handled by arithmetic gates)
        $hasOps = preg_match('/[\+\*\/\%\^√⌊⌈\|!]/', $event->data);
        
        // Allow minus at start, but not in middle (that's subtraction)
        if (preg_match('/.-/', $event->data)) {
            return false;
        }
        
        $isNumber = preg_match('/^-?\d+\.?\d*$/', $event->data);
        
        return $isNumber && !$hasOps;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $coefficient = floatval($event->data);
        $term = new Term($coefficient, []);  // No variables = constant
        $expression = new Expression([$term]);
        
        // Emit AlgebraicEvent
        $algEvent = new AlgebraicEvent(
            $event->data,
            $stream->getId(),
            $expression
        );
        
        $stream->emit($algEvent);
    }
}
