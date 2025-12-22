<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class FactorialGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: expressions containing factorial operator
        return strpos($event->data, '!') !== false;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $expression = $event->data;
        
        // Find the first factorial in the expression
        // Pattern: number (possibly with parentheses and negatives) followed by !
        // Look for: (number)! or number!
        if (preg_match('/(\([^)]+\)|[\d.]+)!/', $expression, $matches, PREG_OFFSET_CAPTURE)) {
            $numberStr = $matches[1][0];
            $factorialStr = $matches[0][0]; // e.g., "5!" or "(-5)!"
            $position = $matches[0][1];
            
            // Remove parentheses if present
            $numberStr = trim($numberStr, '()');
            $number = floatval($numberStr);
            
            // Validate
            if ($number < 0) {
                throw new \Exception("Factorial only defined for non-negative numbers");
            }
            
            if (floor($number) != $number) {
                throw new \Exception("Factorial only defined for integers");
            }
            
            $n = intval($number);
            
            if ($n > 20) {
                throw new \Exception("Factorial limited to n <= 20 to prevent overflow");
            }
            
            // Calculate factorial
            $result = $this->factorial($n);
            
            // Replace the factorial in the expression
            $before = substr($expression, 0, $position);
            $after = substr($expression, $position + strlen($factorialStr));
            $newExpression = $before . $result . $after;
            
            // Emit the new expression
            $stream->emit(new Event($newExpression, $stream->getId()));
        }
    }
    
    private function factorial(int $n): int {
        if ($n === 0 || $n === 1) {
            return 1;
        }
        
        $result = 1;
        for ($i = 2; $i <= $n; $i++) {
            $result *= $i;
        }
        
        return $result;
    }
}
