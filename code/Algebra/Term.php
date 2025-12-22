<?php

namespace StreamGate\Algebra;

/**
 * Represents a term: coefficient multiplied by variables.
 * Examples: 
 *   2x    → Term(2, [Variable('x', 1)])
 *   3xy   → Term(3, [Variable('x', 1), Variable('y', 1)])
 *   -5x^2 → Term(-5, [Variable('x', 2)])
 *   7     → Term(7, [])  (constant)
 */
class Term {
    public float $coefficient;     // 2, -3, 0.5, etc.
    public array $variables;       // Array of Variable objects
    
    public function __construct(float $coefficient, array $variables = []) {
        $this->coefficient = $coefficient;
        $this->variables = $variables;
        $this->sortVariables();
    }
    
    /**
     * Check if this is a constant (no variables)
     */
    public function isConstant(): bool {
        return empty($this->variables);
    }
    
    /**
     * Check if two terms are "like terms" (can be combined)
     * Like terms have identical variables with identical exponents
     */
    public function isLikeTerm(Term $other): bool {
        // Must have same number of variables
        if (count($this->variables) !== count($other->variables)) {
            return false;
        }
        
        // Each variable must match (after sorting)
        foreach ($this->variables as $i => $var) {
            if (!$var->equals($other->variables[$i])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * String representation
     * Examples: "2x", "3xy", "-5x^2", "7"
     */
    public function __toString(): string {
        if ($this->coefficient == 0) {
            return '0';
        }
        
        if ($this->isConstant()) {
            return (string)$this->coefficient;
        }
        
        $result = '';
        
        // Handle coefficient
        if ($this->coefficient === 1.0 && !empty($this->variables)) {
            // Implicit 1, don't show it (just "x" not "1x")
        } elseif ($this->coefficient === -1.0 && !empty($this->variables)) {
            $result = '-';  // Show minus sign ("-x")
        } else {
            $result = (string)$this->coefficient;
        }
        
        // Add variables
        foreach ($this->variables as $var) {
            $result .= (string)$var;
        }
        
        return $result;
    }
    
    /**
     * Sort variables alphabetically for consistent representation
     * xy and yx should both be represented as xy
     */
    private function sortVariables(): void {
        usort($this->variables, fn($a, $b) => $a->name <=> $b->name);
    }
    
    /**
     * Create a deep copy
     */
    public function copy(): Term {
        $vars = array_map(fn($v) => $v->copy(), $this->variables);
        return new Term($this->coefficient, $vars);
    }
}
