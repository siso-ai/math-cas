<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class ModuloGate extends Gate {
    public function matches(Event $event): bool {
        // Matches: number%number
        return preg_match('/^-?\d+(\.\d+)?\s*%\s*-?\d+(\.\d+)?$/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        preg_match('/^(-?\d+(?:\.\d+)?)\s*%\s*(-?\d+(?:\.\d+)?)$/', $event->data, $matches);
        $dividend = floatval($matches[1]);
        $divisor = floatval($matches[2]);
        
        if ($divisor == 0) {
            throw new \Exception("Modulo by zero");
        }
        
        // Use fmod for floating point modulo
        $result = fmod($dividend, $divisor);
        $stream->emit(new Event((string)$result, $stream->getId()));
    }
}
