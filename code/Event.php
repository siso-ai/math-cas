<?php

namespace StreamGate;

class Event {
    public string $data;
    public string $streamId;
    public array $rejectedBy = [];
    public int $gatesInRoom = 0;
    
    // Transformation tracking
    public array $transformedBy = [];
    public array $history = [];
    
    public function __construct(string $data, string $streamId) {
        $this->data = $data;
        $this->streamId = $streamId;
    }
    
    public function reject(string $gateName): void {
        if (!in_array($gateName, $this->rejectedBy)) {
            $this->rejectedBy[] = $gateName;
        }
    }
    
    public function isRejectedByAll(): bool {
        return count($this->rejectedBy) === $this->gatesInRoom;
    }
    
    /**
     * Track a transformation by a gate
     */
    public function track(string $gateName, string $before, string $after, int $loggingLevel): void {
        // Always track gate name (minimal)
        if ($loggingLevel >= LoggingLevel::MINIMAL) {
            $this->transformedBy[] = $gateName;
        }
        
        // Track before/after at DETAILED level
        if ($loggingLevel >= LoggingLevel::DETAILED) {
            $this->history[] = [
                'gate' => $gateName,
                'before' => $before,
                'after' => $after,
                'timestamp' => microtime(true)
            ];
        }
    }
    
    /**
     * Get transformation history
     */
    public function getHistory(): array {
        return $this->history;
    }
    
    /**
     * Get list of gates that transformed this event
     */
    public function getTransformedBy(): array {
        return $this->transformedBy;
    }
}
