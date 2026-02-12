import { apiPost } from "./client"

export interface ReportsResponse {
  tab: string
  date_from: string
  date_to: string
  stats: Record<string, unknown>
}

export async function getReports(params: {
  tab: string
  date_from?: string
  date_to?: string
}) {
  return apiPost<ReportsResponse>("puzzlingcrm_get_reports", {
    tab: params.tab,
    date_from: params.date_from,
    date_to: params.date_to,
  })
}
