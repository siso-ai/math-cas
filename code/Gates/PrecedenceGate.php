<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class PrecedenceGate extends Gate {
    private const PRECEDENCE = [
        '^' => 3,  // Exponent - Highest precedence
        '*' => 2,
        '/' => 2,
        '%' => 2,
        '+' => 1,
        '-' => 1,  // Lowest
    ];
    
    public function matches(Event $event): bool {
        $data = $event->data;
        
        // Skip if it's just a number
        if (preg_match('/^-?\d+(\.\d+)?$/', $data)) {
            return false;
        }
        
        // Skip if already has outer parentheses protecting everything
        if ($this->hasCompleteOuterParens($data)) {
            return false;
        }
        
        // Check if has multiple operators at different precedence levels
        return $this->needsPrecedenceHandling($data);
    }
    
    private function hasCompleteOuterParens(string $data): bool {
        if (!str_starts_with($data, '(') || !str_ends_with($data, ')')) {
            return false;
        }
        
        $level = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            if ($data[$i] === '(') $level++;
            if ($data[$i] === ')') $level--;
            
            // If we hit 0 before the end, outer parens don't cover everything
            if ($level === 0 && $i < strlen($data) - 1) {
                return false;
            }
        }
        
        return $level === 0;
    }
    
    private function needsPrecedenceHandling(string $data): bool {
        // Find all operators not inside parentheses
        $operators = $this->findTopLevelOperators($data);
        
        // Need at least 2 operators to need precedence handling
        if (count($operators) < 2) {
            return false;
        }
        
        // We need precedence handling if we have multiple operators
        // (even at same level - for left-to-right evaluation)
        return true;
    }
    
    private function findTopLevelOperators(string $data): array {
        $operators = [];
        $level = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            
            if ($char === '(') {
                $level++;
            } elseif ($char === ')') {
                $level--;
            } elseif ($level === 0 && in_array($char, ['+', '-', '*', '/', '%', '^'])) {
                // Skip if this is a negative sign at the start
                if ($char === '-' && $i === 0) {
                    continue;
                }
                // Skip if this is a negative sign after an operator
                if ($char === '-' && $i > 0 && in_array($data[$i-1], ['+', '-', '*', '/', '%', '^'])) {
                    continue;
                }
                
                $operators[] = [
                    'operator' => $char,
                    'position' => $i,
                    'precedence' => self::PRECEDENCE[$char]
                ];
            }
        }
        
        return $operators;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $expression = $event->data;
        $maxIterations = 10; // Safety limit
        $iteration = 0;
        
        // Keep normalizing until we have at most one top-level operator
        while ($iteration < $maxIterations) {
            $operators = $this->findTopLevelOperators($expression);
            
            if (count($operators) <= 1) {
                // Done normalizing
                break;
            }
            
            $expression = $this->addParenthesesByPrecedence($expression);
            $iteration++;
        }
        
        $stream->emit(new Event($expression, $stream->getId()));
    }
    
    private function addParenthesesByPrecedence(string $expression): string {
        // Find all top-level operators
        $operators = $this->findTopLevelOperators($expression);
        
        if (empty($operators)) {
            return $expression;
        }
        
        // Find the highest precedence
        $maxPrecedence = max(array_map(fn($op) => $op['precedence'], $operators));
        
        // Find the target operator with highest precedence
        // For exponents (right-associative): find LAST occurrence
        // For other operators (left-associative): find FIRST occurrence
        $targetOp = null;
        
        if ($maxPrecedence === 3) {
            // Right-to-left for exponents (^)
            // Find the LAST operator at this precedence
            foreach ($operators as $op) {
                if ($op['precedence'] === $maxPrecedence) {
                    $targetOp = $op; // Keep updating to get last one
                }
            }
        } else {
            // Left-to-right for other operators
            // Find the FIRST operator at this precedence
            foreach ($operators as $op) {
                if ($op['precedence'] === $maxPrecedence) {
                    $targetOp = $op;
                    break; // Stop at first
                }
            }
        }
        
        if (!$targetOp) {
            return $expression;
        }
        
        // Extract the operation around this operator
        $pos = $targetOp['position'];
        
        // Find left operand (work backwards from operator)
        $leftStart = $this->findLeftOperandStart($expression, $pos);
        $leftOperand = substr($expression, $leftStart, $pos - $leftStart);
        
        // Find right operand (work forwards from operator)
        $rightEnd = $this->findRightOperandEnd($expression, $pos);
        $rightOperand = substr($expression, $pos + 1, $rightEnd - $pos - 1);
        
        // Build the grouped operation
        $operation = $leftOperand . $targetOp['operator'] . $rightOperand;
        $grouped = '(' . $operation . ')';
        
        // Replace in original expression
        $before = substr($expression, 0, $leftStart);
        $after = substr($expression, $rightEnd);
        
        return $before . $grouped . $after;
    }
    
    private function findLeftOperandStart(string $expr, int $opPos): int {
        $i = $opPos - 1;
        
        // Skip whitespace
        while ($i >= 0 && $expr[$i] === ' ') {
            $i--;
        }
        
        if ($i < 0) {
            return 0;
        }
        
        // If we hit a closing paren, find matching opening paren
        if ($expr[$i] === ')') {
            $level = 1;
            $i--;
            while ($i >= 0 && $level > 0) {
                if ($expr[$i] === ')') $level++;
                if ($expr[$i] === '(') $level--;
                $i--;
            }
            $i++; // Back to the opening paren
        } else {
            // It's a number, work backwards to find the start
            $start = $i;
            while ($i >= 0) {
                $char = $expr[$i];
                
                // Stop at operators (but not negative sign at start of number)
                if (in_array($char, ['+', '*', '/', '%', '^'])) {
                    $i++; // Back to after the operator
                    break;
                }
                
                // Handle minus sign - could be subtraction or negative number
                if ($char === '-') {
                    // If there's something before it, check if it's an operator
                    if ($i > 0) {
                        $prevChar = $expr[$i-1];
                        if (in_array($prevChar, ['+', '-', '*', '/', '%', '^'])) {
                            // This minus is part of a negative number, continue
                            $i--;
                            continue;
                        } else {
                            // This minus is subtraction operator, stop here
                            $i++; // Back to after the minus
                            break;
                        }
                    } else {
                        // Minus at start of expression, part of negative number
                        $i--;
                        break;
                    }
                }
                
                // Valid number characters
                if (ctype_digit($char) || $char === '.') {
                    $i--;
                    continue;
                }
                
                // Invalid character, stop
                $i++;
                break;
            }
            
            if ($i < 0) $i = 0;
        }
        
        return $i;
    }
    
    private function findRightOperandEnd(string $expr, int $opPos): int {
        $i = $opPos + 1;
        $len = strlen($expr);
        
        // Skip whitespace
        while ($i < $len && $expr[$i] === ' ') {
            $i++;
        }
        
        if ($i >= $len) {
            return $len;
        }
        
        // Handle negative numbers
        if ($expr[$i] === '-') {
            $i++;
        }
        
        // If we hit an opening paren, find matching closing paren
        if ($i < $len && $expr[$i] === '(') {
            $level = 1;
            $i++;
            while ($i < $len && $level > 0) {
                if ($expr[$i] === '(') $level++;
                if ($expr[$i] === ')') $level--;
                $i++;
            }
        } else {
            // It's a number, work forwards to find the end
            while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                $i++;
            }
        }
        
        return $i;
    }
}
