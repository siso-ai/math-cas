<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\AlgebraicEvent;
use StreamGate\Stream;
use StreamGate\Algebra\Term;
use StreamGate\Algebra\Variable;
use StreamGate\Algebra\Expression;
use StreamGate\Gates\MultiplyGate;

/**
 * Implements product rule for derivatives: d/dx(f*g) = f'g + fg'
 * 
 * Examples:
 *   d/dx((x+1)(x+2))     → (x+1) + (x+2) = 2x+3
 *   d/dx((x^2)(x^3))     → 2x*x^3 + x^2*3x^2 = 5x^4
 *   d/dx((2x)(x+1))      → 2*(x+1) + 2x*1 = 4x+2
 */
class ProductRuleGate extends Gate {
    public function matches(Event $event): bool {
        $data = $event->data;
        
        // Match: d/dx((...)(...)) - derivative of a product
        // Look for d/dx followed by two parenthesized expressions
        if (preg_match('/^d\/d[a-z]\s*\(\s*\([^)]+\)\s*\([^)]+\)\s*\)$/', $data)) {
            return true;
        }
        
        // Also match diff((f)(g), x) format
        if (preg_match('/^diff\s*\(\s*\([^)]+\)\s*\([^)]+\)\s*,\s*[a-z]\s*\)$/', $data)) {
            return true;
        }
        
        return false;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $data = $event->data;
        
        // Extract variable, f, and g
        $variable = null;
        $f = null;
        $g = null;
        
        // Pattern 1: d/dx((f)(g))
        if (preg_match('/^d\/d([a-z])\s*\(\s*\(([^)]+)\)\s*\(([^)]+)\)\s*\)$/', $data, $matches)) {
            $variable = $matches[1];
            $f = $matches[2];
            $g = $matches[3];
        }
        // Pattern 2: diff((f)(g), x)
        elseif (preg_match('/^diff\s*\(\s*\(([^)]+)\)\s*\(([^)]+)\)\s*,\s*([a-z])\s*\)$/', $data, $matches)) {
            $f = $matches[1];
            $g = $matches[2];
            $variable = $matches[3];
        }
        
        if ($variable === null || $f === null || $g === null) {
            $stream->emit($event); // Can't parse, pass through
            return;
        }
        
        // Apply product rule: d/dx(f*g) = f'g + fg'
        // We need to compute f', g', then build f'g + fg'
        
        // Use private streams to compute derivatives
        $fPrime = $this->computeDerivative($f, $variable, $stream);
        $gPrime = $this->computeDerivative($g, $variable, $stream);
        
        echo "  f=$f, f'=$fPrime\n";
        echo "  g=$g, g'=$gPrime\n";
        
        // Build the product rule result: f'g + fg'
        // We need to multiply the results
        
        // Compute f'g (optimize for f'=0 or f'=1)
        $term1Result = ($fPrime === '1') ? $g : (($fPrime === '0') ? '0' : $this->multiplyExpressions($fPrime, $g, $variable, $stream));
        
        // Compute fg' (optimize for g'=0 or g'=1)
        $term2Result = ($gPrime === '1') ? $f : (($gPrime === '0') ? '0' : $this->multiplyExpressions($f, $gPrime, $variable, $stream));
        
        echo "  f'g=$term1Result\n";
        echo "  fg'=$term2Result\n";
        
        // Combine: f'g + fg'
        // Handle zeros
        if ($term1Result === '0' && $term2Result === '0') {
            $result = '0';
        } elseif ($term1Result === '0') {
            $result = $term2Result;
        } elseif ($term2Result === '0') {
            $result = $term1Result;
        } else {
            $result = $term1Result . "+" . $term2Result;
        }
        
        echo "  combined=$result\n\n";
        
        // Emit the result for further simplification
        $stream->emit(new Event($result, $stream->getId()));
    }
    
    /**
     * Multiply two expressions using a private stream
     */
    private function multiplyExpressions(string $expr1, string $expr2, string $variable, Stream $parentStream): string {
        // Create expression to multiply
        $multExpr = "(" . $expr1 . ")(" . $expr2 . ")";
        
        echo "    multiplying: $multExpr\n";
        
        // Create a private stream to compute the multiplication
        $privateStream = new Stream(uniqid('mult_'));
        
        // Register necessary gates
        $privateStream->registerGate(new TermParseGate());
        $privateStream->registerGate(new ConstantTermGate());
        $privateStream->registerGate(new MultiplyGate());  // For numeric multiplication
        $privateStream->registerGate(new DistributionGate());
        $privateStream->registerGate(new FOILGate());
        $privateStream->registerGate(new AlgebraicAddGate());
        $privateStream->registerGate(new ResultGate());
        
        // Emit multiplication
        $privateStream->emit(new Event($multExpr, $privateStream->getId()));
        $privateStream->process();
        
        $result = $privateStream->getResult();
        echo "    result: $result\n";
        
        return $result;
    }
    
    /**
     * Compute derivative using a private stream
     */
    private function computeDerivative(string $expr, string $variable, Stream $parentStream): string {
        // Create a private stream to compute the derivative
        $privateStream = new Stream(uniqid('deriv_'));
        
        // Register necessary gates
        $privateStream->registerGate(new TermParseGate());
        $privateStream->registerGate(new ConstantTermGate());
        $privateStream->registerGate(new DerivativeGate());
        $privateStream->registerGate(new AlgebraicAddGate());
        $privateStream->registerGate(new ResultGate());
        
        // Emit derivative request
        $derivExpr = "d/d" . $variable . "(" . $expr . ")";
        $privateStream->emit(new Event($derivExpr, $privateStream->getId()));
        $privateStream->process();
        
        return $privateStream->getResult();
    }
}
