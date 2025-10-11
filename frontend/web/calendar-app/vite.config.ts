import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";
import { componentTagger } from "lovable-tagger";

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  const isProduction = mode === "production";

  return {
    server: {
      host: "::",
      port: 9000,
    },
    plugins: [react(), mode === "development" && componentTagger()].filter(
      Boolean
    ),
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "./src"),
      },
    },
    define: {
      // Definisci le variabili di ambiente per il browser
      "process.env": "{}",
      "process.env.NODE_ENV": JSON.stringify(
        mode === "development" ? "development" : "production"
      ),
      global: "globalThis",
    },
    build: {
      outDir: "./dist",
      emptyOutDir: true,
    },
  };
});
