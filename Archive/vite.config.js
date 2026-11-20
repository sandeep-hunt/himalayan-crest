import { defineConfig } from "vite";
// es2022: main.js waits for the CMS content with a top-level await
export default defineConfig({ build: { chunkSizeWarningLimit: 900, target: "es2022" } });
