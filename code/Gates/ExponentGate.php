<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class ExponentGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: number^number (e.g., 2^3, 5^2, 2.5^1.5)
        return preg_match('/^-?\d+(\.\d+)?\s*\^\s*-?\d+(\.\d+)?$/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        preg_match('/^(-?\d+(?:\.\d+)?)\s*\^\s*(-?\d+(?:\.\d+)?)$/', $event->data, $matches);
        $base = floatval($matches[1]);
        $exponent = floatval($matches[2]);
        
        // Handle special cases
        if ($base == 0 && $exponent == 0) {
            // 0^0 is mathematically undefined, but by convention we use 1
            $result = 1;
        } elseif ($base == 0 && $exponent < 0) {
            throw new \Exception("Cannot raise 0 to a negative power");
        } else {
            $result = pow($base, $exponent);
            
            // Check for invalid result (NaN or Inf)
            if (!is_finite($result)) {
                if (is_nan($result)) {
                    throw new \Exception("Invalid exponent operation (result is NaN)");
                } else {
                    throw new \Exception("Exponent result overflow (infinity)");
                }
            }
        }
        
        $stream->emit(new Event((string)$result, $stream->getId()));
    }
}
