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
 * Solves linear equations: isolate variable on one side
 * 
 * Examples:
 *   2x+3=7      → x=2
 *   x+5=2x+1    → x=4
 *   3x-2=x+6    → x=4
 *   5=2x+1      → x=2
 */
class EquationGate extends Gate {
    public function matches(Event $event): bool {
        // Must have = sign and at least one variable
        $hasEquals = strpos($event->data, '=') !== false;
        $hasVar = preg_match('/[a-z]/', $event->data) === 1;
        
        if (!$hasEquals || !$hasVar) {
            return false;
        }
        
        // Don't match if already in solved form: "x=number" or "x=number,number"
        // Single solution: x=2
        if (preg_match('/^[a-z]=-?\d+\.?\d*$/', $event->data)) {
            return false;
        }
        
        // Multiple solutions: x=2,3 or x=-2,2
        if (preg_match('/^[a-z]=-?\d+\.?\d*,-?\d+\.?\d*$/', $event->data)) {
            return false;
        }
        
        return true;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // Split on =
        $parts = explode('=', $event->data);
        if (count($parts) !== 2) {
            $stream->emit($event); // Invalid equation, pass through
            return;
        }
        
        $leftStr = trim($parts[0]);
        $rightStr = trim($parts[1]);
        
        // Parse both sides
        $left = $this->parseExpression($leftStr);
        $right = $this->parseExpression($rightStr);
        
        // Solve: move all variable terms to left, constants to right
        $solution = $this->solve($left, $right);
        
        if ($solution === null) {
            // Couldn't solve (no variable, or contradiction)
            $stream->emit($event);
            return;
        }
        
        // Emit solution
        $stream->emit(new Event($solution, $stream->getId()));
    }
    
    /**
     * Solve equation: move terms to isolate variable
     */
    private function solve(Expression $left, Expression $right): ?string {
        // Combine all terms: left - right = 0
        // Move everything to left side by subtracting right
        $allTerms = [];
        
        // Add left terms
        foreach ($left->terms as $term) {
            $allTerms[] = $term;
        }
        
        // Subtract right terms (negate them)
        foreach ($right->terms as $term) {
            $allTerms[] = new Term(-$term->coefficient, $term->variables);
        }
        
        $combined = new Expression($allTerms);
        $combined = $combined->combineTerms();
        
        // Separate into variable terms and constant terms
        $varTerms = [];
        $constTerms = [];
        
        foreach ($combined->terms as $term) {
            if ($term->isConstant()) {
                $constTerms[] = $term;
            } else {
                $varTerms[] = $term;
            }
        }
        
        // Should have exactly one variable term (linear equation)
        if (count($varTerms) === 0) {
            return null; // No variable
        }
        
        if (count($varTerms) > 1) {
            // Multiple different variable types (e.g., x and y)
            // Can't solve single equation for multiple unknowns
            return null;
        }
        
        $varTerm = $varTerms[0];
        
        // Calculate constant sum
        $constantSum = 0;
        foreach ($constTerms as $term) {
            $constantSum += $term->coefficient;
        }
        
        // Equation is now: varTerm + constantSum = 0
        // So: varTerm = -constantSum
        // If varTerm is ax, then x = -constantSum/a
        
        $coefficient = $varTerm->coefficient;
        if ($coefficient == 0) {
            return null; // No solution or infinite solutions
        }
        
        $solution = -$constantSum / $coefficient;
        
        // Get variable name
        $varName = $varTerm->variables[0]->name;
        
        // Format solution
        if ($solution == floor($solution)) {
            return $varName . '=' . intval($solution);
        } else {
            return $varName . '=' . $solution;
        }
    }
    
    /**
     * Parse expression (reused from other gates)
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
