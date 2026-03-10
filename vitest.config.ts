import { defineConfig } from "vitest/config";

export default defineConfig({
  test: {
    environment: "jsdom",
    globals: true,
    root: ".",
    include: ["tests/js/**/*.test.ts"],
    setupFiles: ["tests/js/setup.ts"],
    css: false,
    coverage: {
      provider: "v8",
      include: ["assets/**/*.ts"],
      exclude: ["assets/bootstrap.js", "assets/controllers.json"],
      reporter: ["text", "lcov"],
      reportsDirectory: "build/coverage-js",
    },
  },
});
