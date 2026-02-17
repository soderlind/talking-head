/**
 * Global test setup — jest-dom matchers and global wp mock.
 *
 * WordPress module mocks are handled by resolve.alias in vitest.config.js,
 * pointing each @wordpress/* package to files in tests/js/mocks/.
 */
import '@testing-library/jest-dom/vitest';
import { vi } from 'vitest';

// Global wp object (used by source files for wp.apiFetch).
globalThis.wp = {
	apiFetch: vi.fn( () => Promise.resolve( {} ) ),
};
