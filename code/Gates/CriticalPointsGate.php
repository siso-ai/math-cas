<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

/**
 * Finds critical points (max/min) of functions by:
 * 1. Taking derivative f'(x)
 * 2. Solving f'(x) = 0
 * 3. Evaluating f(x) at critical points
 * 
 * Examples:
 *   maximize(x^2+2x+1)     → x=-1, max=-0  (actually min at vertex)
 *   minimize(-x^2+4x)      → x=2, value=4
 *   critical(2x^2-8x+6)    → x=2, value=-2
 */
class CriticalPointsGate extends Gate {
    public function matches(Event $event): bool {
        $data = $event->data;
        
        // Match: maximize(...), minimize(...), or critical(...)
        if (preg_match('/^(maximize|minimize|critical)\s*\(/', $data)) {
            return true;
        }
        
        return false;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $data = $event->data;
        
        // Extract operation and expression
        $operation = null;
        $exprStr = null;
        $variable = 'x'; // Default variable
        
        // Pattern: operation(expression) or operation(expression, variable)
        if (preg_match('/^(maximize|minimize|critical)\s*\(([^,)]+)(?:,\s*([a-z]))?\)$/', $data, $matches)) {
            $operation = $matches[1];
            $exprStr = trim($matches[2]);
            if (isset($matches[3])) {
                $variable = $matches[3];
            }
        }
        
        if ($operation === null || $exprStr === null) {
            $stream->emit($event); // Can't parse, pass through
            return;
        }
        
        // Step 1: Find derivative f'(x)
        $derivative = $this->computeDerivative($exprStr, $variable, $stream);
        
        // Step 2: Solve f'(x) = 0
        $criticalPoint = $this->solveCritical($derivative, $variable, $stream);
        
        if ($criticalPoint === null || $criticalPoint === 'no real solutions') {
            $stream->emit(new Event("no critical points", $stream->getId()));
            return;
        }
        
        // Step 3: Evaluate f(x) at critical point
        $value = $this->evaluateAt($exprStr, $variable, $criticalPoint, $stream);
        
        // Format result based on operation
        $result = $this->formatResult($operation, $variable, $criticalPoint, $value);
        
        $stream->emit(new Event($result, $stream->getId()));
    }
    
    /**
     * Compute derivative using DerivativeGate
     */
    private function computeDerivative(string $expr, string $variable, Stream $parentStream): string {
        $privateStream = new Stream(uniqid('deriv_'));
        
        $privateStream->registerGate(new TermParseGate());
        $privateStream->registerGate(new ConstantTermGate());
        $privateStream->registerGate(new DerivativeGate());
        $privateStream->registerGate(new AlgebraicAddGate());
        $privateStream->registerGate(new ResultGate());
        
        $derivExpr = "d/d" . $variable . "(" . $expr . ")";
        $privateStream->emit(new Event($derivExpr, $privateStream->getId()));
        $privateStream->process();
        
        return $privateStream->getResult();
    }
    
    /**
     * Solve f'(x) = 0 for x using EquationGate
     */
    private function solveCritical(string $derivative, string $variable, Stream $parentStream): ?string {
        $privateStream = new Stream(uniqid('solve_'));
        
        $privateStream->registerGate(new TermParseGate());
        $privateStream->registerGate(new ConstantTermGate());
        $privateStream->registerGate(new AlgebraicAddGate());
        $privateStream->registerGate(new EquationGate());
        $privateStream->registerGate(new SubtractGate());
        $privateStream->registerGate(new DivideGate());
        $privateStream->registerGate(new ResultGate());
        
        // Create equation: derivative = 0
        $equation = $derivative . "=0";
        $privateStream->emit(new Event($equation, $privateStream->getId()));
        $privateStream->process();
        
        $result = $privateStream->getResult();
        
        // Handle different result formats
        if ($result === 'no real solutions') {
            return null;
        }
        
        // Extract value from "x=value" or "x=v1,v2" format
        if (preg_match('/^' . $variable . '=([^,]+)/', $result, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Evaluate expression at a specific value using SubstitutionGate
     */
    private function evaluateAt(string $expr, string $variable, string $value, Stream $parentStream): string {
        $privateStream = new Stream(uniqid('eval_'));
        
        // Register gates needed for parsing and substitution (same as DefiniteIntegralGate)
        $privateStream->registerGate(new TermParseGate());      // Parse algebraic terms
        $privateStream->registerGate(new ConstantTermGate());
        $privateStream->registerGate(new AlgebraicAddGate());    // Combine terms into Expression
        $privateStream->registerGate(new SubstitutionGate());    // Substitute variable values
        $privateStream->registerGate(new MultiplyGate());
        $privateStream->registerGate(new ExponentGate());
        $privateStream->registerGate(new AddGate());
        $privateStream->registerGate(new SubtractGate());
        $privateStream->registerGate(new ResultGate());
        
        // Set variable context
        $privateStream->variables[$variable] = floatval($value);
        
        // Emit expression for evaluation
        $privateStream->emit(new Event($expr, $privateStream->getId()));
        $privateStream->process();
        
        $result = $privateStream->getResult();
        
        // Handle null result
        if ($result === null || $result === '') {
            return '0';
        }
        
        return $result;
    }
    
    /**
     * Format the result based on operation type
     */
    private function formatResult(string $operation, string $variable, string $point, string $value): string {
        // For now, just return "x=point, value=value"
        // Could add second derivative test later for max/min determination
        return $variable . "=" . $point . ", value=" . $value;
    }
}
