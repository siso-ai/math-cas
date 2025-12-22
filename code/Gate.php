<?php

namespace StreamGate;

abstract class Gate {
    abstract public function matches(Event $event): bool;
    abstract public function transform(Event $event, Stream $stream): void;
    
    public function process(Event $event, Stream $stream): string {
        if ($this->matches($event)) {
            // Track before state
            $before = $event->data;
            
            // Perform transformation
            $this->transform($event, $stream);
            
            // Log transformation to stream
            if ($stream->getLoggingLevel() > LoggingLevel::OFF) {
                $stream->logTransformation(get_class($this), $before, 'transformed');
            }
            
            return 'consumed';
        }
        return 'rejected';
    }
    
    public function resume(Event $event): void {
        // Override in gates that need to resume (like PartialOperationGate)
    }
}
