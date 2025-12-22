<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class NthRootGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: digit(s) followed by √ (e.g., 3√27, 4√16)
        return preg_match('/\d+√/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $expression = $event->data;
        
        // Find the first n√x pattern
        if (preg_match('/(\d+)√(\([^)]+\)|[\d.]+)/', $expression, $matches, PREG_OFFSET_CAPTURE)) {
            $n = $matches[1][0];
            $operand = $matches[2][0];
            $fullMatch = $matches[0][0]; // e.g., "3√27" or "3√(8+19)"
            $position = $matches[0][1];
            
            // Convert n√x to x^(1/n)
            // If operand has parentheses, keep them: 3√(8+19) → (8+19)^(1/3)
            // If operand is number, add parentheses: 3√27 → (27)^(1/3)
            if (strpos($operand, '(') === 0) {
                // Already has parentheses
                $converted = $operand . '^(1/' . $n . ')';
            } else {
                // Add parentheses
                $converted = '(' . $operand . ')^(1/' . $n . ')';
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
