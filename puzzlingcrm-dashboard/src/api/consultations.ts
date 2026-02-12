import { apiPost } from "./client"

export interface Consultation {
  id: number
  name: string
  phone: string
  email: string
  type: string
  type_label: string
  datetime: string
  datetime_display: string
  status_slug: string
  status_name: string
  notes: string
}

export interface GetConsultationsResponse {
  consultations: Consultation[]
  statuses: { slug: string; name: string }[]
}

export async function getConsultations() {
  return apiPost<GetConsultationsResponse>("puzzlingcrm_get_consultations", {})
}

export async function manageConsultation(data: {
  consultation_id?: number
  name: string
  phone: string
  email?: string
  type: string
  date?: string
  time?: string
  status: string
  notes?: string
}) {
  return apiPost<{ message?: string; reload?: boolean }>(
    "puzzling_manage_consultation",
    {
      consultation_id: data.consultation_id ?? 0,
      name: data.name,
      phone: data.phone,
      email: data.email ?? "",
      type: data.type,
      date: data.date ?? "",
      time: data.time ?? "",
      status: data.status,
      notes: data.notes ?? "",
    }
  )
}

export async function convertConsultationToProject(consultationId: number) {
  return apiPost<{ message?: string; redirect_url?: string }>(
    "puzzling_convert_consultation_to_project",
    { consultation_id: String(consultationId) }
  )
}
