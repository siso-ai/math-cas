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
 * Implements integration (antiderivatives): ∫x^n dx = x^(n+1)/(n+1) + C
 * 
 * Examples:
 *   ∫x^2 dx          → x^3/3 + C
 *   ∫3x^2 dx         → x^3 + C
 *   ∫(x^2+2x) dx     → x^3/3+x^2 + C
 *   integrate(x^3, x) → x^4/4 + C
 */
class IntegrationGate extends Gate {
    public function matches(Event $event): bool {
        $data = $event->data;
        
        // Match: ∫expr dx or integrate(expr, x) or int(expr, x)
        if (preg_match('/^∫.+\s+d[a-z]$/', $data)) {
            return true; // ∫expr dx
        }
        
        if (preg_match('/^integrate\s*\(/', $data)) {
            return true; // integrate(...)
        }
        
        if (preg_match('/^int\s*\(/', $data)) {
            return true; // int(...)
        }
        
        return false;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $data = $event->data;
        
        // Extract variable and expression
        $variable = null;
        $exprStr = null;
        
        // Pattern 1: ∫expression dx
        if (preg_match('/^∫(.+)\s+d([a-z])$/', $data, $matches)) {
            $exprStr = trim($matches[1]);
            $variable = $matches[2];
        }
        // Pattern 2: integrate(expression, variable)
        elseif (preg_match('/^integrate\s*\((.+),\s*([a-z])\)$/', $data, $matches)) {
            $exprStr = trim($matches[1]);
            $variable = $matches[2];
        }
        // Pattern 3: int(expression, variable)
        elseif (preg_match('/^int\s*\((.+),\s*([a-z])\)$/', $data, $matches)) {
            $exprStr = trim($matches[1]);
            $variable = $matches[2];
        }
        
        if ($variable === null || $exprStr === null) {
            $stream->emit($event); // Can't parse, pass through
            return;
        }
        
        // Parse the expression
        $expr = $this->parseExpression($exprStr);
        
        // Apply integration rule to each term
        $integralTerms = [];
        foreach ($expr->terms as $term) {
            $integralTerm = $this->integrateTerm($term, $variable);
            if ($integralTerm !== null) {
                $integralTerms[] = $integralTerm;
            }
        }
        
        if (empty($integralTerms)) {
            // Result is just constant C
            $stream->emit(new Event("C", $stream->getId()));
            return;
        }
        
        // Create expression and add " + C"
        $integral = new Expression($integralTerms);
        $result = (string)$integral . " + C";
        
        // Emit as regular event (integration results aren't algebraic events for further processing)
        $stream->emit(new Event($result, $stream->getId()));
    }
    
    /**
     * Integrate a single term with respect to a variable
     * Power rule: ∫ax^n dx = a*x^(n+1)/(n+1) + C
     */
    private function integrateTerm(Term $term, string $variable): ?Term {
        // Find the variable we're integrating with respect to
        $targetVar = null;
        $otherVars = [];
        
        foreach ($term->variables as $var) {
            if ($var->name === $variable) {
                $targetVar = $var;
            } else {
                $otherVars[] = $var;
            }
        }
        
        // Apply integration rule: ∫ax^n dx = a*x^(n+1)/(n+1)
        if ($targetVar === null) {
            // Constant term: ∫a dx = ax
            $newVars = [...$otherVars, new Variable($variable, 1)];
            return new Term($term->coefficient, $newVars);
        } else {
            // Variable term: ∫ax^n dx = a*x^(n+1)/(n+1)
            $newExponent = $targetVar->exponent + 1;
            $newCoefficient = $term->coefficient / $newExponent;
            
            // Build new variable list
            $newVars = [...$otherVars];
            $newVars[] = new Variable($variable, $newExponent);
            
            return new Term($newCoefficient, $newVars);
        }
    }
    
    /**
     * Parse expression from string (reused from DerivativeGate)
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
