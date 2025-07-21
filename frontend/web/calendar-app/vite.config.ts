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
      port: 8080,
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
      ...(isProduction
        ? {
            // Configurazione per produzione (bundle IIFE)
            lib: {
              entry: path.resolve(__dirname, "src/main.tsx"),
              name: "CalendarApp",
              fileName: () => "index.js",
              formats: ["iife"],
            },
            rollupOptions: {
              external: [], // Non esternalizzare nulla per il bundle IIFE
              output: {
                globals: {},
              },
            },
          }
        : {
            // Configurazione per sviluppo (ES modules normale)
            rollupOptions: {
              output: {
                entryFileNames: "index.js",
                chunkFileNames: "[name]-[hash].js",
                assetFileNames: (assetInfo) => {
                  if (assetInfo.name === "index.css") return "index.css";
                  return "[name]-[hash][extname]";
                },
              },
            },
          }),
    },
  };
});
