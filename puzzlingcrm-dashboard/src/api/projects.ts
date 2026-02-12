import { apiPost, apiPostForm } from "./client"

export interface Project {
  id: number
  title: string
  contract_id: number
  customer_name: string
  status_name: string
  status_id: number
}

export interface ProjectTaskItem {
  id: number
  title: string
  status_slug: string
  status_name: string
}

export interface ProjectDetail {
  id: number
  title: string
  content: string
  contract_id: number
  project_manager: number
  project_status: number
  status_name?: string
  start_date: string
  end_date: string
  priority: string
  delete_nonce?: string
  customer_id?: number
  customer_name?: string
  manager_name?: string
  assigned_members?: { id: number; display_name: string }[]
  tasks?: ProjectTaskItem[]
  total_tasks?: number
  completed_tasks?: number
  completion_percentage?: number
}

export interface GetProjectsResponse {
  projects: Project[]
  contracts: { id: number; title: string; customer_name: string }[]
  statuses: { id: number; name: string }[]
  managers: { id: number; display_name: string }[]
  total_pages: number
  current_page: number
}

export interface GetProjectResponse {
  project: ProjectDetail
  managers: { id: number; display_name: string }[]
  contracts: { id: number; title: string; customer_name: string }[]
  statuses: { id: number; name: string }[]
}

export async function getProjects(params?: {
  s?: string
  contract_id?: number
  status_filter?: number
  paged?: number
}) {
  return apiPost<GetProjectsResponse>("puzzlingcrm_get_projects", {
    s: params?.s,
    contract_id: params?.contract_id,
    status_filter: params?.status_filter,
    paged: params?.paged ?? 1,
  })
}

export async function getProject(projectId: number) {
  return apiPost<GetProjectResponse>("puzzlingcrm_get_project", {
    project_id: String(projectId),
  })
}

export async function manageProject(data: {
  project_id?: number
  project_title: string
  contract_id: number
  project_content?: string
  project_status: number
  project_manager?: number
  project_start_date?: string
  project_end_date?: string
  project_priority?: string
}) {
  const form = new FormData()
  form.set("project_id", String(data.project_id ?? 0))
  form.set("project_title", data.project_title)
  form.set("contract_id", String(data.contract_id))
  form.set("project_content", data.project_content ?? "")
  form.set("project_status", String(data.project_status))
  form.set("project_manager", String(data.project_manager ?? 0))
  form.set("project_start_date", data.project_start_date ?? "")
  form.set("project_end_date", data.project_end_date ?? "")
  form.set("project_priority", data.project_priority ?? "medium")
  return apiPostForm<{ message?: string; reload?: boolean }>(
    "puzzling_manage_project",
    form
  )
}

export async function deleteProject(projectId: number, nonce: string) {
  return apiPost<{ message?: string }>("puzzling_delete_project", {
    project_id: String(projectId),
    nonce,
  })
}
