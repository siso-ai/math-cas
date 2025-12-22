<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class AddGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: "number+number" (no parentheses, no extra operators)
        return preg_match('/^-?\d+(\.\d+)?\s*\+\s*-?\d+(\.\d+)?$/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        preg_match('/^(-?\d+(?:\.\d+)?)\s*\+\s*(-?\d+(?:\.\d+)?)$/', $event->data, $matches);
        $left = floatval($matches[1]);
        $right = floatval($matches[2]);
        $result = $left + $right;
        
        // Format result: remove unnecessary decimals
        $resultStr = (floor($result) == $result) ? (string)intval($result) : (string)$result;
        
        $stream->emit(new Event($resultStr, $stream->getId()));
    }
}
