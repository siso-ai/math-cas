<?php

namespace StreamGate\Gates;

use StreamGate\Gate;
use StreamGate\Event;
use StreamGate\Stream;

class PartialOperationGate extends Gate {
    private ?Event $waitingEvent = null;
    private ?string $operator = null;
    private ?string $resolvedOperand = null;
    private ?string $position = null; // 'left' or 'right'
    private ?Stream $parentStreamRef = null;
    
    public function matches(Event $event): bool {
        // Matches: "(anything) operator number" or "number operator (anything)"
        // OR: "(anything) operator (anything)"
        return preg_match('/^\(.*\)\s*[\+\-\*\/%\^]\s*-?\d+(\.\d+)?$/', $event->data) === 1
            || preg_match('/^-?\d+(\.\d+)?\s*[\+\-\*\/%\^]\s*\(.*\)$/', $event->data) === 1
            || preg_match('/^\(.*\)\s*[\+\-\*\/%\^]\s*\(.*\)$/', $event->data) === 1;
    }
    
    public function transform(Event $event, Stream $stream): void {
        // Parse the expression to identify operator, resolved, and unresolved parts
        if (preg_match('/^(\(.*?\))\s*([\+\-\*\/%\^])\s*(-?\d+(?:\.\d+)?)$/', $event->data, $matches)) {
            // Left side is unresolved: (n) op 2
            $unresolved = $matches[1];
            $this->operator = $matches[2];
            $this->resolvedOperand = $matches[3];
            $this->position = 'left';
        } elseif (preg_match('/^(-?\d+(?:\.\d+)?)\s*([\+\-\*\/%\^])\s*(\(.*?\))$/', $event->data, $matches)) {
            // Right side is unresolved: 2 op (n)
            $this->resolvedOperand = $matches[1];
            $this->operator = $matches[2];
            $unresolved = $matches[3];
            $this->position = 'right';
        } elseif (preg_match('/^(\(.*?\))\s*([\+\-\*\/%\^])\s*(\(.*?\))$/', $event->data, $matches)) {
            // Both sides unresolved: (n) op (m)
            // Resolve left side first, then re-emit with resolved left
            $leftUnresolved = $matches[1];
            $operator = $matches[2];
            $rightUnresolved = $matches[3];
            
            // Store state to reconstruct after left resolves
            $this->operator = $operator;
            $this->resolvedOperand = $rightUnresolved; // Store right side
            $this->position = 'both-left'; // Special marker
            $this->waitingEvent = $event;
            $this->parentStreamRef = $stream;
            
            // Create private stream for LEFT operand
            $privateStream = new Stream(uniqid('private_'));
            $privateStream->setParent($stream, get_class($this));
            
            $privateStream->registerGate(new ParenGate());
            $privateStream->registerGate(new AddGate());
            $privateStream->registerGate(new SubtractGate());
            $privateStream->registerGate(new MultiplyGate());
            $privateStream->registerGate(new DivideGate());
            $privateStream->registerGate(new ModuloGate());
            $privateStream->registerGate(new ExponentGate());
            $privateStream->registerGate(new PartialOperationGate());
            $privateStream->registerGate(new ResultGate());
            
            $privateStream->emit(new Event($leftUnresolved, $privateStream->getId()));
            $privateStream->process();
            return;
        } else {
            return;
        }
        
        // Store this event as waiting
        $this->waitingEvent = $event;
        $this->parentStreamRef = $stream;
        
        // Create private stream for the unresolved part
        $privateStream = new Stream(uniqid('private_'));
        $privateStream->setParent($stream, get_class($this));
        
        // Register all gates in private stream (need full computation capability)
        $privateStream->registerGate(new ParenGate());
        $privateStream->registerGate(new AddGate());
        $privateStream->registerGate(new SubtractGate());
        $privateStream->registerGate(new MultiplyGate());
        $privateStream->registerGate(new DivideGate());
        $privateStream->registerGate(new ModuloGate());
        $privateStream->registerGate(new ExponentGate());
        $privateStream->registerGate(new PartialOperationGate()); // Recursive!
        $privateStream->registerGate(new ResultGate());
        
        // Emit unresolved part to private stream
        $privateStream->emit(new Event($unresolved, $privateStream->getId()));
        
        // Process private stream until complete
        $privateStream->process();
    }
    
    public function resume(Event $event): void {
        if ($this->waitingEvent === null || $this->parentStreamRef === null) {
            return;
        }
        
        // Reconstruct expression with resolved value
        if ($this->position === 'left') {
            $newExpression = $event->data . $this->operator . $this->resolvedOperand;
        } elseif ($this->position === 'right') {
            $newExpression = $this->resolvedOperand . $this->operator . $event->data;
        } elseif ($this->position === 'both-left') {
            // Left side is now resolved, right side still unresolved
            // Emit: resolvedLeft operator (rightUnresolved)
            $newExpression = $event->data . $this->operator . $this->resolvedOperand;
        } else {
            return;
        }
        
        // Emit back to parent stream
        $this->parentStreamRef->emit(new Event($newExpression, $this->parentStreamRef->getId()));
        
        // Reset state
        $this->waitingEvent = null;
        $this->operator = null;
        $this->resolvedOperand = null;
        $this->position = null;
        $this->parentStreamRef = null;
    }
}
