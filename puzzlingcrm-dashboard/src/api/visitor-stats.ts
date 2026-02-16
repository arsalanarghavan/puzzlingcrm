import { apiPost } from "./client"

export interface VisitorOverall {
  total_visits: number
  unique_visitors: number
  today_visits: number
  online_now: number
}

export interface VisitorDailyItem {
  date: string
  visits: number
}

export interface VisitorStatsResponse {
  overall: VisitorOverall
  daily: VisitorDailyItem[]
  top_pages: { page_url: string; page_title?: string; visit_count: number }[]
  browsers: { name: string; count: number }[]
  os: { name: string; count: number }[]
  devices: { name: string; count: number }[]
  referrers: { name: string; count: number }[]
  recent: {
    id: number
    visitor_id: number
    page_url: string
    page_title?: string
    visit_date: string
    ip_address: string
    browser: string
    os: string
    device_type: string
  }[]
  online: { visitor_id: number; visit_date: string; page_url: string }[]
}

export async function getVisitorStats(params: {
  date_from: string
  date_to: string
}) {
  return apiPost<VisitorStatsResponse>("puzzlingcrm_get_visitor_stats", {
    date_from: params.date_from,
    date_to: params.date_to,
  })
}
