<?php

namespace StreamGate\Algebra;

/**
 * Represents an algebraic expression: a sum of terms.
 * Examples:
 *   2x+3      → Expression([Term(2,[x]), Term(3,[])])
 *   x^2+2x+1  → Expression([Term(1,[x^2]), Term(2,[x]), Term(1,[])])
 *   5x+3x     → Expression([Term(5,[x]), Term(3,[x])])  (can be combined)
 */
class Expression {
    public array $terms;              // Array of Term objects
    
    public function __construct(array $terms) {
        // Filter out zero terms
        $this->terms = array_filter($terms, fn($t) => $t->coefficient != 0);
        // Re-index array to ensure sequential keys
        $this->terms = array_values($this->terms);
    }
    
    /**
     * String representation: terms joined by + or -
     * Example: "2x+3" or "x^2-2x+1"
     */
    public function __toString(): string {
        if (empty($this->terms)) {
            return '0';
        }
        
        $parts = [];
        foreach ($this->terms as $i => $term) {
            $termStr = (string)$term;
            
            if ($i === 0) {
                // First term: show as-is
                $parts[] = $termStr;
            } else {
                // Subsequent terms: add + or - appropriately
                if ($term->coefficient >= 0) {
                    $parts[] = '+' . $termStr;
                } else {
                    // Negative coefficient already includes minus sign
                    $parts[] = $termStr;
                }
            }
        }
        
        return implode('', $parts);
    }
    
    /**
     * Check if expression has terms that can be combined
     * Example: 2x+3x has like terms, 2x+3y does not
     */
    public function hasLikeTerms(): bool {
        for ($i = 0; $i < count($this->terms); $i++) {
            for ($j = $i + 1; $j < count($this->terms); $j++) {
                if ($this->terms[$i]->isLikeTerm($this->terms[$j])) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Combine all like terms in the expression
     * Example: 2x+3x+5 → 5x+5
     */
    public function combineTerms(): Expression {
        $combined = [];
        $used = [];
        
        foreach ($this->terms as $i => $term) {
            if (in_array($i, $used)) continue;
            
            $newCoeff = $term->coefficient;
            $used[] = $i;
            
            // Find all like terms and combine their coefficients
            foreach ($this->terms as $j => $otherTerm) {
                if ($i !== $j && !in_array($j, $used)) {
                    if ($term->isLikeTerm($otherTerm)) {
                        $newCoeff += $otherTerm->coefficient;
                        $used[] = $j;
                    }
                }
            }
            
            // Only include if coefficient is not zero
            if ($newCoeff != 0) {
                $combined[] = new Term($newCoeff, $term->variables);
            }
        }
        
        return new Expression($combined);
    }
    
    /**
     * Create a deep copy
     */
    public function copy(): Expression {
        $terms = array_map(fn($t) => $t->copy(), $this->terms);
        return new Expression($terms);
    }
}
