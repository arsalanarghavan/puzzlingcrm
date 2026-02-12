/**
 * API client for WordPress admin-ajax.php
 */

function getConfig() {
  const c = window.puzzlingcrm
  if (!c?.ajaxUrl || !c?.nonce) {
    throw new Error("PuzzlingCRM: ajaxUrl or nonce not found")
  }
  return { ajaxUrl: c.ajaxUrl, nonce: c.nonce }
}

export interface AjaxResponse<T = unknown> {
  success: boolean
  data?: T
  message?: string
}

function getServerErrorMessage(): string {
  return window.puzzlingcrm?.i18n?.server_error ?? "خطای سرور"
}

export async function apiPostForm<T = unknown>(
  action: string,
  formData: FormData
): Promise<AjaxResponse<T>> {
  try {
    const { ajaxUrl, nonce } = getConfig()
    formData.set("action", action)
    formData.set("security", nonce)
    const res = await fetch(ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
    let json: AjaxResponse<T>
    try {
      json = (await res.json()) as AjaxResponse<T>
    } catch {
      return { success: false, message: getServerErrorMessage() }
    }
    if (!res.ok) {
      return { success: false, message: json.message ?? getServerErrorMessage() }
    }
    return json
  } catch (e) {
    const message = e instanceof Error ? e.message : getServerErrorMessage()
    return { success: false, message }
  }
}

export async function apiPost<T = unknown>(
  action: string,
  params: Record<string, string | number | boolean | undefined> = {}
): Promise<AjaxResponse<T>> {
  try {
    const { ajaxUrl, nonce } = getConfig()
    const filtered = Object.entries(params)
      .filter(([, v]) => v !== undefined && v !== "")
      .map(([k, v]) => [k, String(v)] as const)
    const body = new URLSearchParams({
      action,
      security: nonce,
      ...Object.fromEntries(filtered),
    } as Record<string, string>)
    const res = await fetch(ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body.toString(),
      credentials: "same-origin",
    })
    let json: AjaxResponse<T>
    try {
      json = (await res.json()) as AjaxResponse<T>
    } catch {
      return { success: false, message: getServerErrorMessage() }
    }
    if (!res.ok) {
      return { success: false, message: json.message ?? getServerErrorMessage() }
    }
    return json
  } catch (e) {
    const message = e instanceof Error ? e.message : getServerErrorMessage()
    return { success: false, message }
  }
}

export function getConfigOrNull() {
  return window.puzzlingcrm ?? null
}

export function isLoggedIn(): boolean {
  const c = window.puzzlingcrm
  return Boolean(c?.user?.id)
}
