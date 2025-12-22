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
 * Implements power rule for derivatives: d/dx(x^n) = nx^(n-1)
 * 
 * Examples:
 *   d/dx(x^2)        → 2x
 *   d/dx(3x^5)       → 15x^4
 *   d/dx(x^2+3x-5)   → 2x+3
 *   diff(x^3, x)     → 3x^2
 */
class DerivativeGate extends Gate {
    public function matches(Event $event): bool {
        $data = $event->data;
        
        // Match: d/dx(...) or diff(..., x) or derivative(..., x)
        if (preg_match('/^d\/d[a-z]\s*\(/', $data)) {
            return true; // d/dx(...)
        }
        
        if (preg_match('/^diff\s*\(/', $data)) {
            return true; // diff(...)
        }
        
        if (preg_match('/^derivative\s*\(/', $data)) {
            return true; // derivative(...)
        }
        
        return false;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $data = $event->data;
        
        // Extract variable and expression
        $variable = null;
        $exprStr = null;
        
        // Pattern 1: d/dx(expression)
        if (preg_match('/^d\/d([a-z])\s*\((.*)\)$/', $data, $matches)) {
            $variable = $matches[1];
            $exprStr = $matches[2];
        }
        // Pattern 2: diff(expression, variable)
        elseif (preg_match('/^diff\s*\((.+),\s*([a-z])\)$/', $data, $matches)) {
            $exprStr = $matches[1];
            $variable = $matches[2];
        }
        // Pattern 3: derivative(expression, variable)
        elseif (preg_match('/^derivative\s*\((.+),\s*([a-z])\)$/', $data, $matches)) {
            $exprStr = $matches[1];
            $variable = $matches[2];
        }
        
        if ($variable === null || $exprStr === null) {
            $stream->emit($event); // Can't parse, pass through
            return;
        }
        
        // Parse the expression
        $expr = $this->parseExpression(trim($exprStr));
        
        // Apply power rule to each term
        $derivativeTerms = [];
        foreach ($expr->terms as $term) {
            $derivativeTerm = $this->differentiateTerm($term, $variable);
            if ($derivativeTerm !== null) {
                $derivativeTerms[] = $derivativeTerm;
            }
        }
        
        if (empty($derivativeTerms)) {
            // Derivative is 0
            $stream->emit(new Event("0", $stream->getId()));
            return;
        }
        
        // Create expression and emit as AlgebraicEvent
        $derivative = new Expression($derivativeTerms);
        $stream->emit(new AlgebraicEvent((string)$derivative, $stream->getId(), $derivative));
    }
    
    /**
     * Differentiate a single term with respect to a variable
     * Power rule: d/dx(ax^n) = a*n*x^(n-1)
     */
    private function differentiateTerm(Term $term, string $variable): ?Term {
        // Find the variable we're differentiating with respect to
        $targetVar = null;
        $otherVars = [];
        
        foreach ($term->variables as $var) {
            if ($var->name === $variable) {
                $targetVar = $var;
            } else {
                $otherVars[] = $var;
            }
        }
        
        // If this term doesn't contain the variable, derivative is 0
        if ($targetVar === null) {
            return null; // Constant term
        }
        
        // Apply power rule: ax^n → a*n*x^(n-1)
        $newCoefficient = $term->coefficient * $targetVar->exponent;
        $newExponent = $targetVar->exponent - 1;
        
        // Build new variable list
        $newVars = [...$otherVars]; // Other variables stay as-is
        
        if ($newExponent > 0) {
            // x^(n-1) where n-1 > 0
            $newVars[] = new Variable($variable, $newExponent);
        }
        // If newExponent == 0, the variable disappears (becomes constant)
        
        return new Term($newCoefficient, $newVars);
    }
    
    /**
     * Parse expression from string
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
}
