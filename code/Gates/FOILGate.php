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
 * Handles FOIL (binomial multiplication): (a+b)(c+d) = ac+ad+bc+bd
 * 
 * Examples:
 *   (x+1)(x+2)    → x^2+3x+2
 *   (x+3)(x-2)    → x^2+x-6
 *   (2x+1)(3x+4)  → 6x^2+11x+4
 *   (x+y)(x-y)    → x^2-y^2
 */
class FOILGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: (expr)(expr) where both have variables
        // Pattern: (...)(...)
        
        if (!preg_match('/[a-z]/', $event->data)) {
            return false; // No variables
        }
        
        // Match pattern: (stuff)(stuff)
        return preg_match('/^\([^)]+\)\([^)]+\)$/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // Parse: "(x+1)(x+2)" → expr1="x+1", expr2="x+2"
        preg_match('/^\(([^)]+)\)\(([^)]+)\)$/', $event->data, $matches);
        $expr1Str = $matches[1];
        $expr2Str = $matches[2];
        
        // Parse both expressions
        $expr1 = $this->parseExpression($expr1Str);
        $expr2 = $this->parseExpression($expr2Str);
        
        // FOIL: multiply each term in expr1 by each term in expr2
        $resultTerms = [];
        foreach ($expr1->terms as $term1) {
            foreach ($expr2->terms as $term2) {
                $resultTerms[] = $this->multiplyTerms($term1, $term2);
            }
        }
        
        $result = new Expression($resultTerms);
        
        // Emit result (will be combined by AlgebraicAddGate)
        $algEvent = new AlgebraicEvent(
            (string)$result,
            $stream->getId(),
            $result
        );
        
        $stream->emit($algEvent);
    }
    
    /**
     * Parse expression (same as AlgebraicAddGate and DistributionGate)
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
     * Multiply two terms together (same as DistributionGate)
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
