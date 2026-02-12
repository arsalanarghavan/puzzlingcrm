/**
 * Language switching: cookie, localStorage, user preference API, then reload.
 */

const COOKIE_NAME = "pzl_language"
const COOKIE_MAX_AGE = 60 * 60 * 24 * 30 // 30 days

function normalizeLanguage(lang: string | null | undefined): "fa" | "en" {
  if (!lang) return "en"
  const v = String(lang).toLowerCase().trim()
  if (v.startsWith("en") || v === "english") return "en"
  if (v.startsWith("fa") || v === "persian") return "fa"
  return "en"
}

function setLanguageCookie(lang: "fa" | "en") {
  try {
    document.cookie = `${COOKIE_NAME}=${lang}; path=/; max-age=${COOKIE_MAX_AGE}; SameSite=Lax`
  } catch {
    // ignore
  }
}

export function changeLanguage(lang: "fa" | "en") {
  const normalized = normalizeLanguage(lang)
  const currentLang = normalizeLanguage(
    typeof document !== "undefined" ? document.documentElement.getAttribute("lang") : null
  )
  const currentDir = (typeof document !== "undefined" ? document.documentElement.getAttribute("dir") : "ltr") ?? "ltr"

  if (typeof localStorage !== "undefined") {
    localStorage.setItem(COOKIE_NAME, normalized)
  }
  setLanguageCookie(normalized)

  const ajaxUrl = typeof window !== "undefined" ? window.puzzlingcrm?.ajaxUrl : undefined
  const nonce = typeof window !== "undefined" ? window.puzzlingcrm?.nonce : undefined
  if (ajaxUrl && nonce) {
    const formData = new FormData()
    formData.set("action", "puzzling_save_user_preference")
    formData.set("nonce", nonce)
    formData.set("preference", "pzl_language")
    formData.set("value", normalized)
    fetch(ajaxUrl, { method: "POST", body: formData }).catch(() => {})
  }

  if (typeof document !== "undefined") {
    document.documentElement.setAttribute("lang", normalized === "fa" ? "fa" : "en")
    document.documentElement.setAttribute("dir", normalized === "fa" ? "rtl" : "ltr")
  }

  const needsReload =
    normalized !== currentLang ||
    (normalized === "fa" && currentDir !== "rtl") ||
    (normalized === "en" && currentDir !== "ltr")
  if (needsReload && typeof window !== "undefined") {
    window.location.reload()
  }
}

export function getCurrentLanguage(): "fa" | "en" {
  if (typeof document === "undefined") return "fa"
  return normalizeLanguage(document.documentElement.getAttribute("lang"))
}
