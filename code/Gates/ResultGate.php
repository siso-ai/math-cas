<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\AlgebraicEvent;
use StreamGate\Stream;

class ResultGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: single number (computation complete)
        // OR: algebraic expression (AlgebraicEvent)
        // OR: solved equation (variable=number or variable=number,number)
        if ($event instanceof AlgebraicEvent && $event->expression !== null) {
            return true; // Algebraic result
        }
        
        // Match pure numbers
        if (preg_match('/^-?\d+(\.\d+)?$/', $event->data) === 1) {
            return true;
        }
        
        // Match solved equations: x=2, y=-3.5, etc.
        if (preg_match('/^[a-z]=-?\d+\.?\d*$/', $event->data) === 1) {
            return true;
        }
        
        // Match multiple solutions: x=2,3 or x=-2,2
        if (preg_match('/^[a-z]=-?\d+\.?\d*,-?\d+\.?\d*$/', $event->data) === 1) {
            return true;
        }
        
        // Match "no real solutions"
        if ($event->data === 'no real solutions') {
            return true;
        }
        
        // Match integration results: anything ending with " + C"
        if (preg_match('/ \+ C$/', $event->data) === 1) {
            return true;
        }
        
        return false;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // Check if this is a private stream
        $parentStream = $stream->getParentStream();
        
        if ($parentStream !== null) {
            // This is a private stream - return result to parent
            $stream->returnToParent($event);
        } else {
            // This is the main stream - re-emit the event so it stays as the final result
            $stream->emit($event);
        }
    }
}
