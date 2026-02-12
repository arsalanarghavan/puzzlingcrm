import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { LoginPage } from "@/pages/login-page";

const rootEl = document.getElementById("pzl-login-root");
if (!rootEl) {
  throw new Error("Root element #pzl-login-root not found");
}

const config = (window as unknown as { PuzzlingLoginConfig?: { dir?: string; lang?: string } })
  .PuzzlingLoginConfig;

if (config?.dir) {
  document.documentElement.setAttribute("dir", config.dir);
}
if (config?.lang) {
  document.documentElement.setAttribute("lang", config.lang);
}

createRoot(rootEl).render(
  <StrictMode>
    <LoginPage />
  </StrictMode>
);
