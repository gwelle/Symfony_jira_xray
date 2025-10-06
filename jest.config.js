export default {
  testEnvironment: 'node',
  setupFiles: ['<rootDir>/jest.setup.js'],
  transform: {}, // Pas besoin de Babel si on utilise ESM
  moduleFileExtensions: ['js', 'mjs', 'cjs', 'json'],
  testMatch: ['**/src/tests/**/*.test.js'],
  verbose: true
};