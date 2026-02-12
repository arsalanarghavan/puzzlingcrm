import { apiPost } from "./client"

export interface License {
  id: number
  project_name: string
  domain: string
  remaining_percentage: number
  remaining_days: number
  expiry_date: string | null
  logo_url?: string
  card_color: string
  status: "active" | "inactive" | "expired" | "cancelled"
}

export interface GetLicensesResponse {
  licenses: License[]
}

function withNonce(params: Record<string, string | number | boolean | undefined>) {
  const c = typeof window !== "undefined" ? window.puzzlingcrm : null
  return { ...params, nonce: c?.nonce ?? "" }
}

export async function getLicenses() {
  return apiPost<GetLicensesResponse>("puzzlingcrm_get_licenses", withNonce({}))
}

export async function addLicense(data: {
  project_name: string
  domain: string
  start_date?: string
  expiry_date?: string
  logo_url?: string
  status?: string
}) {
  return apiPost<{ message?: string; license?: License }>(
    "puzzlingcrm_add_license",
    withNonce({
      project_name: data.project_name,
      domain: data.domain,
      start_date: data.start_date ?? new Date().toISOString().slice(0, 10),
      expiry_date: data.expiry_date ?? "",
      logo_url: data.logo_url ?? "",
      status: data.status ?? "active",
    })
  )
}

export async function renewLicense(id: number, expiry_date: string) {
  return apiPost<{ message?: string }>(
    "puzzlingcrm_renew_license",
    withNonce({ id: String(id), expiry_date })
  )
}

export async function cancelLicense(id: number) {
  return apiPost<{ message?: string }>(
    "puzzlingcrm_cancel_license",
    withNonce({ id: String(id) })
  )
}

export async function deleteLicense(id: number) {
  return apiPost<{ message?: string }>(
    "puzzlingcrm_delete_license",
    withNonce({ id: String(id) })
  )
}
