import { apiPost } from "./client"

export interface CustomerUser {
  id: number
  display_name: string
  first_name: string
  last_name: string
  email: string
  phone: string
  role_slug: string
  role_name: string
  department_name?: string
  job_title_name?: string
  registered: string
  registered_jalali: string
  avatar_url: string
  delete_nonce: string
  can_delete: boolean
}

export interface GetUsersResponse {
  users: CustomerUser[]
  stats: { total: number; customers: number; staff: number }
  roles: { slug: string; name: string }[]
  departments?: { id: number; name: string }[]
  job_titles?: { id: number; name: string; parent: number }[]
}

export async function getUsers(params?: { search?: string; role?: string }) {
  return apiPost<GetUsersResponse>("puzzlingcrm_get_users", {
    search: params?.search,
    role: params?.role,
  })
}

export async function manageUser(data: {
  user_id?: number
  first_name: string
  last_name: string
  email: string
  pzl_mobile_phone?: string
  role: string
  password?: string
  department?: number
  job_title?: number
}) {
  const body: Record<string, string | number> = {
    user_id: data.user_id ?? 0,
    first_name: data.first_name,
    last_name: data.last_name,
    email: data.email,
    pzl_mobile_phone: data.pzl_mobile_phone ?? "",
    role: data.role,
    password: data.password ?? "",
  }
  if (data.department !== undefined) body.department = data.department
  if (data.job_title !== undefined) body.job_title = data.job_title
  return apiPost<{ message?: string; reload?: boolean }>("puzzling_manage_user", body)
}

export async function deleteUser(userId: number, nonce: string) {
  return apiPost<{ message?: string }>("puzzling_delete_user", {
    user_id: String(userId),
    nonce,
  })
}

export async function sendCustomSms(userId: number, message: string) {
  return apiPost<{ message?: string }>("puzzling_send_custom_sms", {
    user_id: String(userId),
    message,
  })
}
