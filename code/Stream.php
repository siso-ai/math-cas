<?php

namespace StreamGate;

class Stream {
    private string $id;
    private array $events = [];
    private array $gates = [];
    private ?Stream $parentStream = null;
    private ?string $returnToGate = null;
    private int $loggingLevel = LoggingLevel::OFF;
    private array $transformationLog = [];  // Track transformations at stream level
    
    /**
     * Variable context for substitution
     * Maps variable names to their numeric values
     * Example: ['x' => 5, 'y' => 3]
     */
    public array $variables = [];
    
    public function __construct(string $id = null) {
        $this->id = $id ?? uniqid('stream_');
    }
    
    public function getId(): string {
        return $this->id;
    }
    
    public function registerGate(Gate $gate): void {
        $this->gates[] = $gate;
    }
    
    public function emit(Event $event): void {
        $event->streamId = $this->id;
        $event->gatesInRoom = count($this->gates);
        $this->events[] = $event;
    }
    
    /**
     * Set logging level for this stream
     */
    public function setLoggingLevel(int $level): void {
        $this->loggingLevel = $level;
    }
    
    /**
     * Get current logging level
     */
    public function getLoggingLevel(): int {
        return $this->loggingLevel;
    }
    
    /**
     * Log a transformation (called by Gate)
     */
    public function logTransformation(string $gateName, string $before, string $after): void {
        if ($this->loggingLevel >= LoggingLevel::MINIMAL) {
            $entry = [
                'gate' => $gateName,
                'before' => $before,
                'after' => $after
            ];
            
            if ($this->loggingLevel >= LoggingLevel::DEBUG) {
                $entry['timestamp'] = microtime(true);
            }
            
            $this->transformationLog[] = $entry;
        }
    }
    
    public function process(): void {
        $maxIterations = 1000; // Safety limit
        $iteration = 0;
        $stableCount = 0; // Count consecutive iterations with no changes
        $lastEventCount = 0;
        
        while (count($this->events) > 0 && $iteration < $maxIterations) {
            $iteration++;
            $currentEventCount = count($this->events);
            
            // Check if we have a stable final result (single number or algebraic expression that hasn't changed)
            if ($currentEventCount === 1) {
                $event = $this->events[0];
                $isNumeric = preg_match('/^-?\d+(\.\d+)?$/', $event->data);
                $isAlgebraic = $event instanceof AlgebraicEvent && $event->expression !== null;
                
                // Check if algebraic event has variables that might be substituted
                $hasSubstitutableVars = false;
                if ($isAlgebraic && $this->hasVariableValues()) {
                    foreach ($event->expression->terms as $term) {
                        if (!$term->isConstant()) {
                            $hasSubstitutableVars = true;
                            break;
                        }
                    }
                }
                
                // Check if algebraic event has like terms that need combining
                $hasLikeTerms = false;
                if ($isAlgebraic) {
                    $hasLikeTerms = $event->expression->hasLikeTerms();
                }
                
                // Only stop if it's fully processed (numeric, or algebraic with no more work to do)
                if ($isNumeric || ($isAlgebraic && !$hasSubstitutableVars && !$hasLikeTerms)) {
                    // For main streams, we can stop here
                    // For private streams, we need to let ResultGate process it to return to parent
                    if ($this->parentStream === null) {
                        // Main stream - we have a final result, stop processing
                        break;
                    }
                    // Private stream - continue to let ResultGate process it
                }
            }
            
            // Check if stream is stable (no changes)
            if ($currentEventCount === $lastEventCount) {
                $stableCount++;
                if ($stableCount > 10) {
                    // Stream hasn't changed in 10 iterations, likely stuck
                    break;
                }
            } else {
                $stableCount = 0;
            }
            $lastEventCount = $currentEventCount;
            
            $event = array_shift($this->events);
            $consumed = false;
            
            foreach ($this->gates as $gate) {
                $result = $gate->process($event, $this);
                
                if ($result === 'consumed') {
                    $consumed = true;
                    break;
                } elseif ($result === 'rejected') {
                    $event->reject(get_class($gate));
                }
            }
            
            // If not consumed and not rejected by all, put back for next iteration
            if (!$consumed && !$event->isRejectedByAll()) {
                $this->events[] = $event;
            }
            
            // Check if rejected by all
            if ($event->isRejectedByAll()) {
                // Check if this is a final result (single number or algebraic expression)
                $isNumeric = preg_match('/^-?\d+(\.\d+)?$/', $event->data);
                $isAlgebraic = $event instanceof AlgebraicEvent && $event->expression !== null;
                $isError = strpos($event->data, 'Error:') === 0;
                
                if ($isNumeric || $isAlgebraic || $isError) {
                    // This is a final result - keep it in the stream
                    $this->events[] = $event;
                } else {
                    // Try to let DontMatchGate handle it
                    $handled = false;
                    foreach ($this->gates as $gate) {
                        if (get_class($gate) === 'StreamGate\\Gates\\DontMatchGate') {
                            $result = $gate->process($event, $this);
                            if ($result === 'consumed') {
                                $handled = true;
                            }
                            break;
                        }
                    }
                    
                    // If DontMatchGate didn't handle it, drop the event
                    // (This shouldn't happen if DontMatchGate is registered)
                }
            }
        }
        
        if ($iteration >= $maxIterations) {
            throw new \Exception("Maximum iterations exceeded - possible infinite loop");
        }
    }
    
    public function setParent(Stream $parent, string $returnToGate): void {
        $this->parentStream = $parent;
        $this->returnToGate = $returnToGate;
    }
    
    public function getParentStream(): ?Stream {
        return $this->parentStream;
    }
    
    public function returnToParent(Event $event): void {
        if ($this->parentStream && $this->returnToGate) {
            // Find the gate that's waiting
            foreach ($this->parentStream->gates as $gate) {
                if (get_class($gate) === $this->returnToGate) {
                    $gate->resume($event);
                    return;
                }
            }
        }
    }
    
    public function hasEvents(): bool {
        return count($this->events) > 0;
    }
    
    public function getEventCount(): int {
        return count($this->events);
    }
    
    public function getResult(): ?string {
        // After processing, if we have one event left, that's the result
        if (count($this->events) === 1) {
            return $this->events[0]->data;
        }
        return null;
    }
    
    /**
     * Get transformation history
     */
    public function getHistory(): array {
        return $this->transformationLog;
    }
    
    /**
     * Get list of gates that transformed events in this stream
     */
    public function getTransformationPath(): array {
        $gates = [];
        foreach ($this->transformationLog as $entry) {
            if (!in_array($entry['gate'], $gates)) {
                $gates[] = $entry['gate'];
            }
        }
        return $gates;
    }
    
    /**
     * Get formatted history for display
     */
    public function getFormattedHistory(): string {
        if (empty($this->transformationLog)) {
            return "No history available (logging level: {$this->loggingLevel})";
        }
        
        $output = "Transformation History:\n";
        $output .= str_repeat("=", 50) . "\n";
        
        foreach ($this->transformationLog as $i => $step) {
            $gateName = str_replace('StreamGate\\Gates\\', '', $step['gate']);
            $output .= sprintf(
                "%d. %s\n   Before: %s\n   After:  %s\n",
                $i + 1,
                $gateName,
                $step['before'],
                $step['after']
            );
            
            if ($this->loggingLevel >= LoggingLevel::DEBUG && isset($step['timestamp'])) {
                $output .= sprintf("   Time:   %.4fms\n", $step['timestamp'] * 1000);
            }
            
            $output .= "\n";
        }
        
        return $output;
    }
    
    /**
     * Get rejected gates (DEBUG level)
     */
    public function getRejectedGates(): array {
        if (count($this->events) === 1 && $this->loggingLevel >= LoggingLevel::DEBUG) {
            return $this->events[0]->rejectedBy;
        }
        return [];
    }
    
    public function getGate(string $className): ?Gate {
        foreach ($this->gates as $gate) {
            if (get_class($gate) === $className) {
                return $gate;
            }
        }
        return null;
    }
    
    /**
     * Set a variable value for substitution
     */
    public function setVariable(string $name, float $value): void {
        $this->variables[$name] = $value;
    }
    
    /**
     * Get a variable value (null if not set)
     */
    public function getVariable(string $name): ?float {
        return $this->variables[$name] ?? null;
    }
    
    /**
     * Check if any variable values are set
     */
    public function hasVariableValues(): bool {
        return !empty($this->variables);
    }
    
    /**
     * Clear all variable values
     */
    public function clearVariables(): void {
        $this->variables = [];
    }
}
