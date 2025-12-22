<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\AlgebraicEvent;
use StreamGate\Stream;
use StreamGate\Algebra\Term;
use StreamGate\Algebra\Expression;

/**
 * Substitutes variable values into expressions.
 * Converts algebraic expressions to numeric when all variables have values.
 * 
 * Examples (with x=5):
 *   "x"       → "5"
 *   "2x"      → "10"
 *   "2x+3"    → "10+3" (then arithmetic gates handle)
 *   
 * Examples (with x=2, y=3):
 *   "x+y"     → "2+3" → "5"
 *   "xy"      → "6"
 *   
 * Partial substitution (x=5, y unknown):
 *   "2x+y"    → "10+y" (stays algebraic)
 */
class SubstitutionGate extends Gate {
    public function matches(Event $event): bool {
        // Match if it's an algebraic event with variables
        // We'll check for actual variable values in transform()
        
        if (!($event instanceof AlgebraicEvent) || !$event->expression) {
            return false;
        }
        
        // Check if expression has any variables
        foreach ($event->expression->terms as $term) {
            if (!$term->isConstant()) {
                return true; // Has at least one variable
            }
        }
        
        return false; // No variables to substitute
    }
    
    public function transform(Event $event, Stream $stream): void {
        $expression = $event->expression;
        
        // Substitute each term (if stream has no variables, terms stay unchanged)
        $newTerms = [];
        foreach ($expression->terms as $term) {
            if ($term->isConstant()) {
                // No variables, keep as-is
                $newTerms[] = $term->copy();
            } else {
                // Has variables, try to substitute
                $newTerms[] = $this->substituteTerm($term, $stream);
            }
        }
        
        $result = new Expression($newTerms);
        
        // Convert to string
        $stringResult = (string)$result;
        
        // Check if fully numeric now
        if ($this->isFullyNumeric($result)) {
            // All variables substituted! Hand to arithmetic gates
            $stream->emit(new Event($stringResult, $stream->getId()));
        } else {
            // Still has variables, stay algebraic
            $stream->emit(new AlgebraicEvent(
                $stringResult,
                $stream->getId(),
                $result
            ));
        }
    }
    
    /**
     * Substitute variables in a term with their values
     * Example: Term(2, [x]) with x=5 → Term(10, [])
     */
    private function substituteTerm(Term $term, Stream $stream): Term {
        $coefficient = $term->coefficient;
        $remainingVars = [];
        
        foreach ($term->variables as $var) {
            $value = $stream->getVariable($var->name);
            
            if ($value !== null) {
                // Substitute: multiply coefficient by value^exponent
                $coefficient *= pow($value, $var->exponent);
            } else {
                // Variable value not provided, keep it
                $remainingVars[] = $var->copy();
            }
        }
        
        return new Term($coefficient, $remainingVars);
    }
    
    /**
     * Check if expression has no variables (all constants)
     */
    private function isFullyNumeric(Expression $expr): bool {
        foreach ($expr->terms as $term) {
            if (!$term->isConstant()) {
                return false;
            }
        }
        return true;
    }
}
