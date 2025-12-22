<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

/**
 * Implements definite integrals using the fundamental theorem of calculus:
 * ∫[a,b] f(x) dx = F(b) - F(a)
 * 
 * Examples:
 *   ∫[0,2] x^2 dx        → 2.667 (evaluates to number)
 *   ∫[1,3] 2x dx         → 8
 *   int([0,1], x^3, x)   → 0.25
 */
class DefiniteIntegralGate extends Gate {
    public function matches(Event $event): bool {
        $data = $event->data;
        
        // Match: ∫[a,b] expr dx or int([a,b], expr, x)
        if (preg_match('/^∫\[.+,.+\].+\s+d[a-z]$/', $data)) {
            return true; // ∫[a,b] expr dx
        }
        
        if (preg_match('/^int\s*\(\s*\[.+,.+\]\s*,/', $data)) {
            return true; // int([a,b], ...)
        }
        
        return false;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $data = $event->data;
        
        // Extract bounds, variable, and expression
        $lowerBound = null;
        $upperBound = null;
        $variable = null;
        $exprStr = null;
        
        // Pattern 1: ∫[a,b] expression dx
        if (preg_match('/^∫\[([^,]+),([^\]]+)\](.+)\s+d([a-z])$/', $data, $matches)) {
            $lowerBound = trim($matches[1]);
            $upperBound = trim($matches[2]);
            $exprStr = trim($matches[3]);
            $variable = $matches[4];
        }
        // Pattern 2: int([a,b], expression, variable)
        elseif (preg_match('/^int\s*\(\s*\[([^,]+),([^\]]+)\]\s*,\s*(.+?)\s*,\s*([a-z])\s*\)$/', $data, $matches)) {
            $lowerBound = trim($matches[1]);
            $upperBound = trim($matches[2]);
            $exprStr = trim($matches[3]);
            $variable = $matches[4];
        }
        
        if ($lowerBound === null || $upperBound === null || $variable === null || $exprStr === null) {
            $stream->emit($event); // Can't parse, pass through
            return;
        }
        
        // Step 1: Find the antiderivative using a private stream
        $antiderivative = $this->computeIntegral($exprStr, $variable, $stream);
        
        // Remove " + C" from antiderivative (constants cancel in definite integrals)
        $antiderivative = str_replace(' + C', '', $antiderivative);
        
        // Step 2: Evaluate at upper bound: F(b)
        $upperValue = $this->evaluateAt($antiderivative, $variable, $upperBound, $stream);
        
        // Step 3: Evaluate at lower bound: F(a)
        $lowerValue = $this->evaluateAt($antiderivative, $variable, $lowerBound, $stream);
        
        // Step 4: Compute F(b) - F(a)
        $result = $this->subtract($upperValue, $lowerValue, $stream);
        
        // Emit the final numeric result
        $stream->emit(new Event($result, $stream->getId()));
    }
    
    /**
     * Compute the indefinite integral using IntegrationGate
     */
    private function computeIntegral(string $expr, string $variable, Stream $parentStream): string {
        $privateStream = new Stream(uniqid('integral_'));
        
        // Register gates needed for integration
        $privateStream->registerGate(new ResultGate());
        $privateStream->registerGate(new TermParseGate());
        $privateStream->registerGate(new ConstantTermGate());
        $privateStream->registerGate(new IntegrationGate());
        $privateStream->registerGate(new AlgebraicAddGate());
        
        // Compute indefinite integral
        $integralExpr = "∫" . $expr . " d" . $variable;
        $privateStream->emit(new Event($integralExpr, $privateStream->getId()));
        $privateStream->process();
        
        return $privateStream->getResult();
    }
    
    /**
     * Evaluate expression at a specific value using SubstitutionGate
     */
    private function evaluateAt(string $expr, string $variable, string $value, Stream $parentStream): string {
        $privateStream = new Stream(uniqid('eval_'));
        
        // Register gates needed for parsing and substitution
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
     * Subtract two values using a private stream
     */
    private function subtract(string $a, string $b, Stream $parentStream): string {
        $privateStream = new Stream(uniqid('subtract_'));
        
        // Register arithmetic gates
        $privateStream->registerGate(new SubtractGate());
        $privateStream->registerGate(new ResultGate());
        
        // Compute a - b
        $expr = $a . '-' . $b;
        $privateStream->emit(new Event($expr, $privateStream->getId()));
        $privateStream->process();
        
        return $privateStream->getResult();
    }
}
