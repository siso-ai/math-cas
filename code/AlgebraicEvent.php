<?php

namespace StreamGate;

use StreamGate\Algebra\Expression;

/**
 * Event that carries algebraic data (Expression objects).
 * Extends Event to add Expression while keeping string data.
 * 
 * This maintains backward compatibility:
 * - data: String representation (for display, arithmetic gates)
 * - expression: Structured data (for algebraic gates)
 */
class AlgebraicEvent extends Event {
    public ?Expression $expression = null;
    
    public function __construct(
        string $data, 
        string $streamId, 
        ?Expression $expression = null
    ) {
        parent::__construct($data, $streamId);
        $this->expression = $expression;
    }
}
