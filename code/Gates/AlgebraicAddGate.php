<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\AlgebraicEvent;
use StreamGate\Stream;
use StreamGate\Algebra\Expression;
use StreamGate\Algebra\Term;
use StreamGate\Algebra\Variable;

/**
 * Handles addition/subtraction of algebraic expressions.
 * Combines like terms when possible.
 * 
 * Examples:
 *   2x+3x     → 5x
 *   5y-2y     → 3y
 *   2x+3y     → 2x+3y (unchanged - different variables)
 *   x+x+x     → 3x
 *   2xy+3xy   → 5xy
 */
class AlgebraicAddGate extends Gate {
    public function matches(Event $event): bool {
        // Skip if already processed algebraic event with no like terms
        if ($event instanceof AlgebraicEvent && $event->expression !== null) {
            // Already algebraic - only process if has like terms to combine
            return $event->expression->hasLikeTerms();
        }
        
        // For regular events, match if has + or - AND has variable
        $data = $event->data;
        
        // Skip equations (has = sign)
        if (strpos($data, '=') !== false) {
            return false;
        }
        
        // Must have + or -
        if (strpos($data, '+') === false && strpos($data, '-') === false) {
            return false;
        }
        
        // Must have at least one variable
        return preg_match('/[a-z]/', $data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // If already algebraic, just combine
        if ($event instanceof AlgebraicEvent && $event->expression !== null) {
            $combined = $event->expression->combineTerms();
            
            $algEvent = new AlgebraicEvent(
                (string)$combined,
                $stream->getId(),
                $combined
            );
            
            $stream->emit($algEvent);
            return;
        }
        
        // Parse string into expression
        $expression = $this->parseExpression($event->data);
        
        // Combine like terms
        $combined = $expression->combineTerms();
        
        // Emit combined result
        $algEvent = new AlgebraicEvent(
            (string)$combined,
            $stream->getId(),
            $combined
        );
        
        $stream->emit($algEvent);
    }
    
    /**
     * Parse a string with + and - into an Expression
     * Example: "2x+3y-z" → Expression([Term(2,[x]), Term(3,[y]), Term(-1,[z])])
     */
    private function parseExpression(string $input): Expression {
        $terms = [];
        $current = '';
        $sign = 1;
        
        // Handle leading sign
        if (strlen($input) > 0 && ($input[0] === '+' || $input[0] === '-')) {
            $sign = ($input[0] === '+') ? 1 : -1;
            $input = substr($input, 1); // Remove the sign
        }
        
        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            
            if (($char === '+' || $char === '-') && $i > 0) {
                // Found operator - process accumulated term
                if ($current !== '') {
                    $terms[] = $this->parseSingleTerm($current, $sign);
                    $current = '';
                }
                $sign = ($char === '+') ? 1 : -1;
            } else {
                $current .= $char;
            }
        }
        
        // Process last term
        if ($current !== '') {
            $terms[] = $this->parseSingleTerm($current, $sign);
        }
        
        return new Expression($terms);
    }
    
    /**
     * Parse a single term (without + or - prefix)
     * Example: "2x" → Term(2, [Variable('x', 1)])
     */
    private function parseSingleTerm(string $termStr, int $sign): Term {
        // Remove leading + or -
        $termStr = ltrim($termStr, '+-');
        
        // If empty after trimming, it was just a sign (like "+x" became "x")
        if ($termStr === '') {
            $termStr = '1';
        }
        
        // Check if it's just a number (constant)
        if (preg_match('/^\d+\.?\d*$/', $termStr)) {
            return new Term(floatval($termStr) * $sign, []);
        }
        
        // Parse coefficient
        preg_match('/^(\d*\.?\d*)/', $termStr, $matches);
        $coeffStr = $matches[1];
        
        $coefficient = 1.0;
        if ($coeffStr !== '') {
            $coefficient = floatval($coeffStr);
        }
        $coefficient *= $sign;
        
        // Parse variables
        $variables = [];
        preg_match_all('/([a-z])(\^(\d+))?/', $termStr, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $varName = $match[1];
            $exponent = isset($match[3]) ? intval($match[3]) : 1;
            $variables[] = new Variable($varName, $exponent);
        }
        
        return new Term($coefficient, $variables);
    }
}
