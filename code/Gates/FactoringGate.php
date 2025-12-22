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
 * Factors quadratic expressions: x^2+bx+c → (x+p)(x+q)
 * 
 * Examples:
 *   x^2+5x+6    → (x+2)(x+3)
 *   x^2-x-6     → (x-3)(x+2)
 *   x^2-4       → (x-2)(x+2)
 *   x^2+2x+1    → (x+1)(x+1)
 */
class FactoringGate extends Gate {
    public function matches(Event $event): bool {
        // Match if has x^2 pattern (quadratic)
        // Can be regular Event or AlgebraicEvent
        $data = $event->data;
        
        // Must have ^2 (quadratic term)
        if (strpos($data, '^2') === false) {
            return false;
        }
        
        // Must have a variable
        if (!preg_match('/[a-z]/', $data)) {
            return false;
        }
        
        // Don't match if it's a FOIL pattern (...)(...) - that would be redundant
        if (preg_match('/^\([^)]+\)\([^)]+\)$/', $data)) {
            return false; // Already factored
        }
        
        return true;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // Parse expression if not already algebraic
        $expr = null;
        if ($event instanceof AlgebraicEvent && $event->expression) {
            $expr = $event->expression;
        } else {
            // Parse the string into an Expression
            $expr = $this->parseExpression($event->data);
        }
        
        // Extract coefficients: ax^2 + bx + c
        $a = 0; // coefficient of x^2
        $b = 0; // coefficient of x
        $c = 0; // constant
        $varName = null;
        
        foreach ($expr->terms as $term) {
            if (count($term->variables) === 0) {
                // Constant term
                $c += $term->coefficient;
            } elseif (count($term->variables) === 1) {
                $var = $term->variables[0];
                if ($varName === null) {
                    $varName = $var->name;
                }
                
                if ($var->exponent === 2) {
                    // x^2 term
                    $a += $term->coefficient;
                } elseif ($var->exponent === 1) {
                    // x term
                    $b += $term->coefficient;
                }
            }
        }
        
        // For now, only handle cases where a=1 (monic quadratics)
        if ($a != 1) {
            // Can't factor (or too complex for now)
            $stream->emit($event);
            return;
        }
        
        // Try to factor: x^2 + bx + c = (x+p)(x+q)
        // where p*q = c and p+q = b
        $factors = $this->findFactorPair($c, $b);
        
        if ($factors === null) {
            // Can't factor (or prime)
            $stream->emit($event);
            return;
        }
        
        [$p, $q] = $factors;
        
        // Build factored form: (x+p)(x+q)
        $factor1 = $this->formatFactor($varName, $p);
        $factor2 = $this->formatFactor($varName, $q);
        
        $result = "({$factor1})({$factor2})";
        
        $stream->emit(new Event($result, $stream->getId()));
    }
    
    /**
     * Find two numbers p and q such that:
     * p * q = product
     * p + q = sum
     */
    private function findFactorPair(float $product, float $sum): ?array {
        // Handle negative product (one positive, one negative)
        // or positive product (both same sign)
        
        // Get all factor pairs of |product|
        $absProduct = abs($product);
        
        // Check integers up to sqrt of product
        $limit = (int)ceil(sqrt($absProduct)) + 1;
        
        for ($i = 1; $i <= $limit; $i++) {
            if ($absProduct != 0 && abs($absProduct - round($absProduct)) < 0.0001) {
                // Product is effectively an integer
                if (round($absProduct) % $i === 0) {
                    $j = round($absProduct) / $i;
                    
                    // Try all sign combinations
                    $pairs = [];
                    if ($product > 0) {
                        // Both same sign
                        $pairs[] = [$i, $j];
                        $pairs[] = [-$i, -$j];
                    } else {
                        // Different signs
                        $pairs[] = [$i, -$j];
                        $pairs[] = [-$i, $j];
                    }
                    
                    foreach ($pairs as $pair) {
                        if (abs($pair[0] + $pair[1] - $sum) < 0.0001) {
                            return $pair;
                        }
                    }
                }
            }
        }
        
        // Special case: product is 0
        if (abs($product) < 0.0001) {
            // One factor must be 0
            return [0, $sum];
        }
        
        return null;
    }
    
    /**
     * Format a factor: x+p or x-p
     */
    private function formatFactor(string $varName, float $value): string {
        if ($value == 0) {
            return $varName;
        } elseif ($value > 0) {
            if ($value == floor($value)) {
                return $varName . '+' . intval($value);
            } else {
                return $varName . '+' . $value;
            }
        } else {
            if ($value == floor($value)) {
                return $varName . intval($value);
            } else {
                return $varName . $value;
            }
        }
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
