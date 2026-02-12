import { apiPost } from "./client"

export interface LogEntry {
  id: number
  title: string
  content: string
  author: string
  date: string
}

export interface GetLogsResponse {
  logs: LogEntry[]
  total: number
  total_pages: number
  current_page: number
}

export async function getLogs(params: { log_tab?: string; paged?: number }) {
  return apiPost<GetLogsResponse>("puzzlingcrm_get_logs", {
    log_tab: params.log_tab ?? "events",
    paged: params.paged ?? 1,
  })
}
