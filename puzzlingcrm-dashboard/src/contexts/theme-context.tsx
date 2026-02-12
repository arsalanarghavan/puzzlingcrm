import * as React from "react"

type Theme = "light" | "dark" | "system"

interface ThemeContextValue {
  theme: Theme
  setTheme: (theme: Theme) => void
  resolved: "light" | "dark"
}

const ThemeContext = React.createContext<ThemeContextValue | null>(null)

const STORAGE_KEY = "pzl-theme"
const VALID_THEMES: Theme[] = ["light", "dark", "system"]

function getInitialTheme(): Theme {
  if (typeof window === "undefined") return "system"
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored && VALID_THEMES.includes(stored as Theme)) return stored as Theme
  } catch {
    // ignore
  }
  const fromDoc = document.documentElement.getAttribute("data-theme")
  if (fromDoc && VALID_THEMES.includes(fromDoc as Theme)) return fromDoc as Theme
  return "system"
}

function getStoredTheme(): Theme {
  if (typeof document === "undefined") return "system"
  return (document.documentElement.getAttribute("data-theme") as Theme) || getInitialTheme()
}

function getResolvedTheme(): "light" | "dark" {
  const theme = getStoredTheme()
  if (theme === "dark") return "dark"
  if (theme === "light") return "light"
  if (typeof window !== "undefined" && window.matchMedia("(prefers-color-scheme: dark)").matches) {
    return "dark"
  }
  return "light"
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const [theme, setThemeState] = React.useState<Theme>(getInitialTheme)
  const [resolved, setResolved] = React.useState<"light" | "dark">(getResolvedTheme)

  React.useEffect(() => {
    const root = document.documentElement
    root.setAttribute("data-theme", theme)
    const r = getResolvedTheme()
    setResolved(r)
    if (r === "dark") {
      root.classList.add("dark")
    } else {
      root.classList.remove("dark")
    }
    try {
      localStorage.setItem(STORAGE_KEY, theme)
    } catch {
      // ignore
    }
  }, [theme])

  React.useEffect(() => {
    const m = window.matchMedia("(prefers-color-scheme: dark)")
    const handler = () => {
      if (theme === "system") setResolved(getResolvedTheme())
    }
    m.addEventListener("change", handler)
    return () => m.removeEventListener("change", handler)
  }, [theme])

  const setTheme = React.useCallback((t: Theme) => {
    setThemeState(t)
  }, [])

  const value = React.useMemo(
    () => ({ theme, setTheme, resolved }),
    [theme, setTheme, resolved]
  )

  return (
    <ThemeContext.Provider value={value}>
      {children}
    </ThemeContext.Provider>
  )
}

export function useTheme() {
  const ctx = React.useContext(ThemeContext)
  if (!ctx) throw new Error("useTheme must be used within ThemeProvider")
  return ctx
}
