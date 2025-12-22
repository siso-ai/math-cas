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
 * Handles distribution (multiplication of term across sum).
 * 
 * Examples:
 *   2(x+3)      → 2x+6
 *   x(y+2)      → xy+2x
 *   3(2x-4)     → 6x-12
 *   -2(x+3)     → -2x-6
 *   x(x+1)      → x^2+x
 */
class DistributionGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: term(expression) or number(expression)
        // Must have variable somewhere
        if (!preg_match('/[a-z]/', $event->data)) {
            return false;
        }
        
        // Pattern: something(stuff)
        return preg_match('/^[^()]+\([^)]+\)$/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // Parse multiplier and inner expression
        preg_match('/^([^(]+)\(([^)]+)\)$/', $event->data, $matches);
        $multiplierStr = $matches[1];
        $innerStr = $matches[2];
        
        // Parse multiplier as term
        $multiplier = $this->parseTerm($multiplierStr);
        
        // Parse inner expression
        $innerExpr = $this->parseExpression($innerStr);
        
        // Distribute: multiply each inner term by multiplier
        $resultTerms = [];
        foreach ($innerExpr->terms as $term) {
            $resultTerms[] = $this->multiplyTerms($multiplier, $term);
        }
        
        $result = new Expression($resultTerms);
        
        // Emit result
        $algEvent = new AlgebraicEvent(
            (string)$result,
            $stream->getId(),
            $result
        );
        
        $stream->emit($algEvent);
    }
    
    /**
     * Parse a term (number or variable term)
     */
    private function parseTerm(string $input): Term {
        // Handle pure number
        if (preg_match('/^-?\d+\.?\d*$/', $input)) {
            return new Term(floatval($input), []);
        }
        
        // Parse coefficient
        preg_match('/^(-?\d*\.?\d*)/', $input, $matches);
        $coeffStr = $matches[1];
        
        $coefficient = 1.0;
        if ($coeffStr === '-') {
            $coefficient = -1.0;
        } elseif ($coeffStr !== '' && $coeffStr !== '+') {
            $coefficient = floatval($coeffStr);
        }
        
        // Parse variables
        $variables = [];
        preg_match_all('/([a-z])(\^(\d+))?/', $input, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $varName = $match[1];
            $exponent = isset($match[3]) ? intval($match[3]) : 1;
            $variables[] = new Variable($varName, $exponent);
        }
        
        return new Term($coefficient, $variables);
    }
    
    /**
     * Parse expression (same as AlgebraicAddGate)
     */
    private function parseExpression(string $input): Expression {
        $terms = [];
        $current = '';
        $sign = 1;
        
        // Handle leading sign
        if (strlen($input) > 0 && ($input[0] === '+' || $input[0] === '-')) {
            $sign = ($input[0] === '+') ? 1 : -1;
            $input = substr($input, 1);
        }
        
        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            
            if (($char === '+' || $char === '-') && $i > 0) {
                if ($current !== '') {
                    $terms[] = $this->parseSingleTerm($current, $sign);
                    $current = '';
                }
                $sign = ($char === '+') ? 1 : -1;
            } else {
                $current .= $char;
            }
        }
        
        if ($current !== '') {
            $terms[] = $this->parseSingleTerm($current, $sign);
        }
        
        return new Expression($terms);
    }
    
    private function parseSingleTerm(string $termStr, int $sign): Term {
        $termStr = ltrim($termStr, '+-');
        
        if ($termStr === '') {
            $termStr = '1';
        }
        
        // Check if it's just a number
        if (preg_match('/^\d+\.?\d*$/', $termStr)) {
            return new Term(floatval($termStr) * $sign, []);
        }
        
        preg_match('/^(\d*\.?\d*)/', $termStr, $matches);
        $coeffStr = $matches[1];
        
        $coefficient = 1.0;
        if ($coeffStr !== '') {
            $coefficient = floatval($coeffStr);
        }
        $coefficient *= $sign;
        
        $variables = [];
        preg_match_all('/([a-z])(\^(\d+))?/', $termStr, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $varName = $match[1];
            $exponent = isset($match[3]) ? intval($match[3]) : 1;
            $variables[] = new Variable($varName, $exponent);
        }
        
        return new Term($coefficient, $variables);
    }
    
    /**
     * Multiply two terms together
     * Multiplies coefficients, combines variables (adds exponents)
     * 
     * Example: 2x * 3y = 6xy
     * Example: x * x = x^2
     */
    private function multiplyTerms(Term $a, Term $b): Term {
        // Multiply coefficients
        $newCoeff = $a->coefficient * $b->coefficient;
        
        // Combine variables (add exponents for same variable)
        $varMap = [];
        
        foreach ($a->variables as $var) {
            if (!isset($varMap[$var->name])) {
                $varMap[$var->name] = 0;
            }
            $varMap[$var->name] += $var->exponent;
        }
        
        foreach ($b->variables as $var) {
            if (!isset($varMap[$var->name])) {
                $varMap[$var->name] = 0;
            }
            $varMap[$var->name] += $var->exponent;
        }
        
        // Create Variable objects
        $variables = [];
        foreach ($varMap as $name => $exp) {
            if ($exp > 0) {
                $variables[] = new Variable($name, $exp);
            }
        }
        
        return new Term($newCoeff, $variables);
    }
}
