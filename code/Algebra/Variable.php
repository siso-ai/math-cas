<?php

namespace StreamGate\Algebra;

/**
 * Represents a single variable with its exponent.
 * Examples: x (exponent=1), x^2 (exponent=2), y^3 (exponent=3)
 */
class Variable {
    public string $name;        // 'x', 'y', 'z'
    public int $exponent;       // 1, 2, 3, etc.
    
    public function __construct(string $name, int $exponent = 1) {
        $this->name = $name;
        $this->exponent = $exponent;
    }
    
    /**
     * String representation: x or x^2
     */
    public function __toString(): string {
        if ($this->exponent === 1) {
            return $this->name;
        }
        return $this->name . '^' . $this->exponent;
    }
    
    /**
     * Check if two variables are identical (same name and exponent)
     */
    public function equals(Variable $other): bool {
        return $this->name === $other->name 
            && $this->exponent === $other->exponent;
    }
    
    /**
     * Create a deep copy
     */
    public function copy(): Variable {
        return new Variable($this->name, $this->exponent);
    }
}
