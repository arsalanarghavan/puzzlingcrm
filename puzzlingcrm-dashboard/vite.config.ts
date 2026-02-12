import path from "path"
import { defineConfig } from "vite"
import react from "@vitejs/plugin-react"

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  base: "./",
  build: {
    outDir: "../assets/dashboard-build",
    emptyOutDir: true,
    rollupOptions: {
      input: {
        index: path.resolve(__dirname, "index.html"),
        login: path.resolve(__dirname, "login.html"),
      },
      output: {
        entryFileNames: (chunkInfo) =>
          chunkInfo.name === "login" ? "login.js" : "dashboard-[name].js",
        chunkFileNames: "dashboard-[name].js",
        assetFileNames: (assetInfo) => {
          const name = String(assetInfo.name || "");
          if (name.includes("login") && name.endsWith(".css")) return "login.css";
          return "dashboard-[name].[ext]";
        },
      },
    },
  },
})
