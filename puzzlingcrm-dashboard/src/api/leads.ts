import { apiPost } from "./client"

export interface Lead {
  id: number
  first_name: string
  last_name: string
  mobile: string
  email?: string
  business_name?: string
  gender?: string
  status_name: string
  status_slug: string
  date: string
}

export interface GetLeadsResponse {
  leads: Lead[]
  total: number
  pages: number
  statuses: { slug: string; name: string }[]
}

export async function getLeads(params: {
  paged?: number
  per_page?: number
  s?: string
  status_filter?: string
}) {
  const res = await apiPost<GetLeadsResponse>("puzzling_get_leads", params)
  return res
}

export async function addLead(data: {
  first_name: string
  last_name: string
  mobile: string
  email?: string
  business_name?: string
  gender?: string
  notes?: string
}) {
  const payload: Record<string, string> = {
    first_name: data.first_name,
    last_name: data.last_name,
    mobile: data.mobile,
  }
  if (data.email?.trim()) payload.email = data.email.trim()
  if (data.business_name?.trim()) payload.business_name = data.business_name.trim()
  if (data.gender?.trim()) payload.gender = data.gender.trim()
  if (data.notes?.trim()) payload.notes = data.notes.trim()
  return apiPost("puzzling_add_lead", payload)
}

export async function editLead(leadId: number, data: Record<string, string>) {
  return apiPost("puzzling_edit_lead", { lead_id: String(leadId), ...data })
}

export async function deleteLead(leadId: number) {
  return apiPost("puzzling_delete_lead", { lead_id: String(leadId) })
}
