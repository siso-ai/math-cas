<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\AlgebraicEvent;
use StreamGate\Stream;
use StreamGate\Algebra\Term;
use StreamGate\Algebra\Variable;
use StreamGate\Algebra\Expression;

/**
 * Parses individual variable terms into Expression objects.
 * 
 * Matches:
 *   x      → Term(1, [Variable('x', 1)])
 *   2x     → Term(2, [Variable('x', 1)])
 *   -3y    → Term(-3, [Variable('y', 1)])
 *   xy     → Term(1, [Variable('x', 1), Variable('y', 1)])
 *   2x^3   → Term(2, [Variable('x', 3)])
 *   3xy^2  → Term(3, [Variable('x', 1), Variable('y', 2)])
 */
class TermParseGate extends Gate {
    public function matches(Event $event): bool {
        // Skip if already algebraic
        if ($event instanceof AlgebraicEvent && $event->expression !== null) {
            return false;
        }
        
        // Matches: variable terms like x, 2x, 3y^2, xy, -x, -3y
        // Must have at least one letter
        // But NOT if it has operators (those are handled by other gates)
        if (preg_match('/[\+\*\/\%=\s]/', $event->data)) {
            return false; // Has arithmetic operators, equals, or whitespace
        }
        
        // Check for minus in the middle (like "x-y") which is subtraction
        if (preg_match('/.+-/', $event->data)) {
            return false; // Minus not at start = subtraction operator
        }
        
        // Must have at least one variable letter
        return preg_match('/[a-z]/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $term = $this->parseTerm($event->data);
        $expression = new Expression([$term]);
        
        // Emit AlgebraicEvent with Expression's string representation
        // This ensures proper variable sorting and coefficient formatting
        $algEvent = new AlgebraicEvent(
            (string)$expression,  // Use Expression's toString, not original data
            $stream->getId(),
            $expression
        );
        
        $stream->emit($algEvent);
    }
    
    /**
     * Parse a term string into a Term object
     * Examples: "2x" → Term(2, [Variable('x', 1)])
     */
    private function parseTerm(string $input): Term {
        // Parse coefficient
        preg_match('/^(-?\d*\.?\d*)/', $input, $matches);
        $coeffStr = $matches[1];
        
        if ($coeffStr === '' || $coeffStr === '+') {
            $coefficient = 1.0;
        } elseif ($coeffStr === '-') {
            $coefficient = -1.0;
        } else {
            $coefficient = floatval($coeffStr);
        }
        
        // Parse variables with exponents
        // Matches: x, x^2, y^3, etc.
        $variables = [];
        preg_match_all('/([a-z])(\^(\d+))?/', $input, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $varName = $match[1];
            $exponent = isset($match[3]) ? intval($match[3]) : 1;
            $variables[] = new Variable($varName, $exponent);
        }
        
        return new Term($coefficient, $variables);
    }
}
