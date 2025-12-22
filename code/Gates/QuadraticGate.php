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
 * Solves quadratic equations: ax^2+bx+c=0
 * Uses quadratic formula: x = (-b ± √(b²-4ac)) / 2a
 * 
 * Examples:
 *   x^2-5x+6=0   → x=2,3
 *   x^2+2x+1=0   → x=-1
 *   x^2-4=0      → x=-2,2
 *   x^2+1=0      → no real solutions
 */
class QuadraticGate extends Gate {
    public function matches(Event $event): bool {
        // Must be an equation with = and have x^2 term
        $data = $event->data;
        
        if (strpos($data, '=') === false) {
            return false; // Not an equation
        }
        
        if (strpos($data, '^2') === false) {
            return false; // Not quadratic
        }
        
        // Don't match if already solved (x=number or x=number,number)
        if (preg_match('/^[a-z]=-?\d+\.?\d*(,-?\d+\.?\d*)?$/', $data)) {
            return false; // Already solved
        }
        
        return true;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // Split on =
        $parts = explode('=', $event->data);
        if (count($parts) !== 2) {
            $stream->emit($event);
            return;
        }
        
        $leftStr = trim($parts[0]);
        $rightStr = trim($parts[1]);
        
        // Parse both sides
        $left = $this->parseExpression($leftStr);
        $right = $this->parseExpression($rightStr);
        
        // Move everything to left: left - right = 0
        $allTerms = [];
        foreach ($left->terms as $term) {
            $allTerms[] = $term;
        }
        foreach ($right->terms as $term) {
            $allTerms[] = new Term(-$term->coefficient, $term->variables);
        }
        
        $combined = new Expression($allTerms);
        $combined = $combined->combineTerms();
        
        // Extract coefficients: ax^2 + bx + c = 0
        $a = 0;
        $b = 0;
        $c = 0;
        $varName = null;
        
        foreach ($combined->terms as $term) {
            if (count($term->variables) === 0) {
                $c += $term->coefficient;
            } elseif (count($term->variables) === 1) {
                $var = $term->variables[0];
                if ($varName === null) {
                    $varName = $var->name;
                }
                
                if ($var->exponent === 2) {
                    $a += $term->coefficient;
                } elseif ($var->exponent === 1) {
                    $b += $term->coefficient;
                }
            }
        }
        
        if ($varName === null) {
            $stream->emit($event); // No variable found
            return;
        }
        
        // Solve using quadratic formula
        $solutions = $this->solveQuadratic($a, $b, $c);
        
        if ($solutions === null) {
            // No real solutions
            $result = "no real solutions";
            $stream->emit(new Event($result, $stream->getId()));
            return;
        }
        
        // Format solutions
        if (count($solutions) === 1) {
            // One solution (double root)
            $val = $solutions[0];
            if ($val == floor($val)) {
                $result = $varName . '=' . intval($val);
            } else {
                $result = $varName . '=' . $val;
            }
        } else {
            // Two solutions
            $vals = [];
            foreach ($solutions as $val) {
                if ($val == floor($val)) {
                    $vals[] = intval($val);
                } else {
                    $vals[] = $val;
                }
            }
            $result = $varName . '=' . $vals[0] . ',' . $vals[1];
        }
        
        $stream->emit(new Event($result, $stream->getId()));
    }
    
    /**
     * Solve quadratic equation using quadratic formula
     * Returns array of solutions or null if no real solutions
     */
    private function solveQuadratic(float $a, float $b, float $c): ?array {
        if ($a == 0) {
            // Not actually quadratic, it's linear
            // bx + c = 0 → x = -c/b
            if ($b == 0) {
                return null; // No solution or infinite solutions
            }
            $solution = -$c / $b;
            return [$solution];
        }
        
        // Calculate discriminant: b^2 - 4ac
        $discriminant = ($b * $b) - (4 * $a * $c);
        
        if ($discriminant < 0) {
            return null; // No real solutions
        }
        
        if ($discriminant == 0) {
            // One solution (double root)
            $x = -$b / (2 * $a);
            return [$x];
        }
        
        // Two solutions
        $sqrtDisc = sqrt($discriminant);
        $x1 = (-$b + $sqrtDisc) / (2 * $a);
        $x2 = (-$b - $sqrtDisc) / (2 * $a);
        
        // Return in ascending order
        if ($x1 > $x2) {
            return [$x2, $x1];
        } else {
            return [$x1, $x2];
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
