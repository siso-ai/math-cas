<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class FloorGate extends Gate {
    private ?Stream $parentStreamRef = null;
    private string $beforeExpression = '';
    private string $afterExpression = '';
    
    public function matches(Event $event): bool {
        // Matches: ⌊expression⌋
        return preg_match('/⌊.*⌋/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        $expression = $event->data;
        
        // Find the first ⌊...⌋ pattern
        if (preg_match('/⌊([^⌋]+)⌋/', $expression, $matches, PREG_OFFSET_CAPTURE)) {
            $content = $matches[1][0];
            $fullMatch = $matches[0][0]; // e.g., "⌊3.7⌋"
            $position = $matches[0][1];
            
            // If it's a simple number, calculate directly
            if (preg_match('/^-?\d+(\.\d+)?$/', $content)) {
                $result = floor(floatval($content));
                
                // Replace in expression
                $before = substr($expression, 0, $position);
                $after = substr($expression, $position + strlen($fullMatch));
                $newExpression = $before . $result . $after;
                
                $stream->emit(new Event($newExpression, $stream->getId()));
            } else {
                // It's an expression - need to resolve it first
                $this->parentStreamRef = $stream;
                
                // Store reconstruction info in gate instance
                $this->beforeExpression = substr($expression, 0, $position);
                $this->afterExpression = substr($expression, $position + strlen($fullMatch));
                
                // Create private stream to resolve the expression
                $privateStream = new Stream(uniqid('floor_'));
                $privateStream->setParent($stream, get_class($this));
                
                // Register all gates
                $privateStream->registerGate(new SquareRootGate());
                $privateStream->registerGate(new NthRootGate());
                $privateStream->registerGate(new ParenGate());
                $privateStream->registerGate(new AddGate());
                $privateStream->registerGate(new SubtractGate());
                $privateStream->registerGate(new MultiplyGate());
                $privateStream->registerGate(new DivideGate());
                $privateStream->registerGate(new ModuloGate());
                $privateStream->registerGate(new ExponentGate());
                $privateStream->registerGate(new PartialOperationGate());
                $privateStream->registerGate(new ResultGate());
                
                $privateStream->emit(new Event($content, $privateStream->getId()));
                $privateStream->process();
            }
        }
    }
    
    public function resume(Event $event): void {
        $result = floor(floatval($event->data));
        
        // Reconstruct the full expression using stored values
        $newExpression = $this->beforeExpression . $result . $this->afterExpression;
        
        $this->parentStreamRef->emit(new Event($newExpression, $this->parentStreamRef->getId()));
    }
}
