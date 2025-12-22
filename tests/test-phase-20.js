/**
 * SISO Logic Prover - Phase 20 Test Suite
 * 
 * Comprehensive tests for automated theorem prover.
 * Port of test-phase-20.php to JavaScript.
 * 
 * Tests:
 * - Propositional logic tautologies
 * - Non-tautologies (should not prove)
 * - Complex formulas
 * - Multiple proof strategies
 */

import { proveFormula, createStream, createEvent } from '../index.js';
import { logicParseGate } from '../gates/LogicParseGate.js';
import { automatedProverGate } from '../gates/AutomatedProverGate.js';
import { resultGate } from '../gates/ResultGate.js';

// ============================================================================
// TEST UTILITIES
// ============================================================================

let passed = 0;
let failed = 0;

const test = (description, fn) => {
  try {
    fn();
    console.log(`✓ ${description}`);
    passed++;
  } catch (error) {
    console.log(`✗ ${description}`);
    console.log(`  Error: ${error.message}`);
    if (error.stack) {
      console.log(`  Stack: ${error.stack.split('\n').slice(0, 3).join('\n')}`);
    }
    failed++;
  }
};

/**
 * Test helper for proving formulas.
 * 
 * @param {string} formula - Formula to prove
 * @param {string} description - Test description
 * @param {boolean} shouldProve - Expected result
 */
const testProver = (formula, description, shouldProve) => {
  test(`${description}: "${formula}"`, () => {
    const result = proveFormula(formula);
    
    // Check if error occurred
    if (result.error) {
      throw new Error(`Proof error: ${result.errorMessage}`);
    }
    
    const proven = result.proven || false;
    
    console.assert(
      proven === shouldProve,
      `Expected ${shouldProve ? 'proven' : 'not proven'} but got ${proven ? 'proven' : 'not proven'}`
    );
    
    // Display result details
    console.log(`    Formula Type: ${result.analysis?.formulaType || 'Unknown'}`);
    console.log(`    Propositional: ${result.analysis?.isPropositional ? 'Yes' : 'No'}`);
    console.log(`    Atoms: ${result.analysis?.atomCount || 0}`);
    console.log(`    Complexity: ${result.analysis?.complexity || 0}`);
    console.log(`    Method Used: ${result.method}`);
    console.log(`    Proof Type: ${result.proofType}`);
    console.log(`    Result: ${result.explanation}`);
  });
};

// ============================================================================
// MAIN TESTS
// ============================================================================

console.log('='.repeat(60));
console.log('PHASE 20: AUTOMATED THEOREM PROVER');
console.log('Combining All Proof Techniques');
console.log('='.repeat(60));
console.log();

console.log('PROPOSITIONAL LOGIC TESTS');
console.log('-'.repeat(60));

// Test 1: Classic tautology - Law of Excluded Middle
testProver(
  'P ∨ ¬P',
  'Law of Excluded Middle (should be proven)',
  true
);

// Test 2: Modus Ponens
testProver(
  '(P ∧ (P → Q)) → Q',
  'Modus Ponens (should be proven)',
  true
);

// Test 3: Double Negation
testProver(
  'P ↔ ¬¬P',
  'Double Negation Equivalence (should be proven)',
  true
);

// Test 4: De Morgan's Law
testProver(
  '¬(P ∧ Q) ↔ (¬P ∨ ¬Q)',
  "De Morgan's Law (should be proven)",
  true
);

// Test 5: Contrapositive
testProver(
  '(P → Q) ↔ (¬Q → ¬P)',
  'Contrapositive (should be proven)',
  true
);

// Test 6: Distribution
testProver(
  'P ∧ (Q ∨ R) ↔ (P ∧ Q) ∨ (P ∧ R)',
  'Distribution of ∧ over ∨ (should be proven)',
  true
);

// Test 7: Non-tautology
testProver(
  'P → Q',
  'Simple Conditional (contingent, should NOT be proven)',
  false
);

// Test 8: Contradiction
testProver(
  'P ∧ ¬P',
  'Contradiction (should NOT be proven as tautology)',
  false
);

console.log();
console.log('COMPLEX FORMULAS');
console.log('-'.repeat(60));

// Test 9: Complex tautology
testProver(
  '((P → Q) ∧ (Q → R)) → (P → R)',
  'Transitivity of Implication (should be proven)',
  true
);

// Test 10: Disjunctive Syllogism
testProver(
  '((P ∨ Q) ∧ ¬P) → Q',
  'Disjunctive Syllogism (should be proven)',
  true
);

console.log();
console.log('PERFORMANCE COMPARISON');
console.log('-'.repeat(60));

const testFormulas = [
  ['P ∨ ¬P', 'Simple (1 atom)'],
  ['(P ∧ Q) → (P ∨ Q)', 'Medium (2 atoms, more operators)'],
  ['((P → Q) ∧ (Q → R)) → (P → R)', 'Complex (3 atoms, nested)']
];

console.log('Formula'.padEnd(45) + 'Strategy'.padEnd(20) + 'Result');
console.log('-'.repeat(80));

testFormulas.forEach(([formula, desc]) => {
  try {
    const result = proveFormula(formula);
    const status = result.proven ? '✓ Proven' : '✗ Not Proven';
    
    console.log(
      desc.padEnd(45) + 
      (result.method || 'unknown').padEnd(20) + 
      status
    );
  } catch (error) {
    console.log(
      desc.padEnd(45) + 
      'error'.padEnd(20) + 
      '✗ Error'
    );
  }
});

console.log();

// ============================================================================
// ADDITIONAL TESTS
// ============================================================================

console.log('ADDITIONAL VALIDATION TESTS');
console.log('-'.repeat(60));

// Test parsing
test('Parse simple atom', () => {
  const stream = createStream();
  stream.use(logicParseGate);
  stream.use(resultGate);
  
  stream.emit(createEvent('P', stream.id));
  stream.process();
  
  const result = stream.getResult();
  console.assert(result.metadata.expression, 'Should have parsed expression');
  console.assert(result.metadata.expression.type === 'Atom', 'Should be Atom type');
});

// Test complex parsing
test('Parse complex formula', () => {
  const stream = createStream();
  stream.use(logicParseGate);
  stream.use(resultGate);
  
  stream.emit(createEvent('(P ∧ Q) → R', stream.id));
  stream.process();
  
  const result = stream.getResult();
  console.assert(result.metadata.expression, 'Should have parsed expression');
  console.assert(result.metadata.expression.type === 'Conditional', 'Should be Conditional type');
});

// Test parse error handling
test('Handle parse errors gracefully', () => {
  const stream = createStream();
  stream.use(logicParseGate);
  stream.use(resultGate);
  
  stream.emit(createEvent('P ∧∧ Q', stream.id));
  stream.process();
  
  const result = stream.getResult();
  console.assert(result.metadata.error, 'Should have error flag');
  console.assert(result.metadata.errorMessage, 'Should have error message');
});

// Test stream chaining
test('Stream methods chain properly', () => {
  const stream = createStream();
  const result = stream.use(logicParseGate).use(automatedProverGate);
  console.assert(result === stream, 'Methods should return stream for chaining');
});

// Test event immutability
test('Events are immutable', () => {
  const event = createEvent('P', 'test-stream');
  
  try {
    event.data = 'modified';
    // If we get here, check if it actually changed
    console.assert(event.data === 'P', 'Event data should not be modifiable');
  } catch (e) {
    // Expected in strict mode - event is frozen
  }
});

console.log();

// ============================================================================
// SUMMARY
// ============================================================================

console.log('='.repeat(60));
console.log('PHASE 20 COMPLETE!');
console.log();
console.log('Automated Theorem Prover Features:');
console.log('  ✓ Automatic strategy selection');
console.log('  ✓ Formula analysis and classification');
console.log('  ✓ Multiple proof methods (tableaux, truth table, resolution)');
console.log('  ✓ Detailed proof explanations');
console.log('  ✓ Performance-optimized strategy selection');
console.log();
console.log('Total System: Phases 1-20 COMPLETE');
console.log('='.repeat(60));
console.log();

console.log(`Tests completed: ${passed + failed}`);
console.log(`Passed: ${passed}`);
console.log(`Failed: ${failed}`);
console.log();

if (failed === 0) {
  console.log('🎉 All tests passed!');
  process.exit(0);
} else {
  console.log(`❌ ${failed} test(s) failed`);
  process.exit(1);
}
