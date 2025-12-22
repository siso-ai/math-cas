<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;
use StreamGate\LoggingLevel;

/**
 * Handles events that don't match any other gate
 * Creates descriptive error messages for invalid input
 * 
 * This gate should be registered LAST (before ResultGate) in the stream
 * so it only processes events after all other gates have rejected them
 */
class DontMatchGate extends Gate {
    public function matches(Event $event): bool {
        // Only match if rejected by all gates AND not already an error message
        if (!$event->isRejectedByAll()) {
            return false;
        }
        
        // Don't match if this is already an error message
        if (strpos($event->data, 'Error:') === 0) {
            return false;
        }
        
        return true;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $input = $event->data;
        
        // Create descriptive error message
        $errorMessage = "Error: Unrecognized expression '{$input}'";
        
        // Add diagnostic info at DEBUG level
        if ($stream->getLoggingLevel() >= LoggingLevel::DEBUG) {
            $rejectedCount = count($event->rejectedBy);
            $errorMessage .= " (Rejected by {$rejectedCount} gates)";
        }
        
        // Emit error event directly as final result
        // This prevents the error from being processed again
        $stream->emit(new Event($errorMessage, $stream->getId()));
    }
}
