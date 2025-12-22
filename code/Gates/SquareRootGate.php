<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class SquareRootGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: √ NOT preceded by a digit (to avoid matching nth roots like 3√27)
        // We want √9 but not 3√27
        if (strpos($event->data, '√') === false) {
            return false;
        }
        
        // Check if there's a digit immediately before any √
        // If so, it's an nth root, not a square root
        return preg_match('/\d√/', $event->data) === 0;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $expression = $event->data;
        
        // Find the first √ and extract what follows
        if (preg_match('/√(\([^)]+\)|[\d.]+)/', $expression, $matches, PREG_OFFSET_CAPTURE)) {
            $operand = $matches[1][0];
            $fullMatch = $matches[0][0]; // e.g., "√9" or "√(4+5)"
            $position = $matches[0][1];
            
            // Convert √x to x^0.5
            // If operand has parentheses, keep them: √(4+5) → (4+5)^0.5
            // If operand is number, add parentheses for safety: √9 → (9)^0.5
            if (strpos($operand, '(') === 0) {
                // Already has parentheses
                $converted = $operand . '^0.5';
            } else {
                // Add parentheses
                $converted = '(' . $operand . ')^0.5';
            }
            
            // Replace in expression
            $before = substr($expression, 0, $position);
            $after = substr($expression, $position + strlen($fullMatch));
            $newExpression = $before . $converted . $after;
            
            // Emit converted expression
            $stream->emit(new Event($newExpression, $stream->getId()));
        }
    }
}
