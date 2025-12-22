<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class ParenGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: "(anything)" - single pair of outer parentheses
        return $this->hasBalancedOuterParens($event->data);
    }
    
    private function hasBalancedOuterParens(string $data): bool {
        // Must start with ( and end with )
        if (!str_starts_with($data, '(') || !str_ends_with($data, ')')) {
            return false;
        }
        
        // Check if outer parens are the matching pair
        $level = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            if ($data[$i] === '(') $level++;
            if ($data[$i] === ')') $level--;
            
            // If we hit 0 before the end, outer parens don't match
            if ($level === 0 && $i < strlen($data) - 1) {
                return false;
            }
        }
        
        return $level === 0;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // Strip outer parentheses
        $content = substr($event->data, 1, -1);
        $stream->emit(new Event($content, $stream->getId()));
    }
}
