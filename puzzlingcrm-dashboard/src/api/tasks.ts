import { apiPost } from "./client"

export interface TaskItem {
  id: number
  title: string
  content: string
  status_slug: string
  status_name: string
  priority_slug: string
  priority_name: string
  labels: { slug: string; name: string }[]
  due_date: string
  project_id: number
  project_title: string
  assigned_to: number
  assigned_name: string
  post_date: string
}

export interface TaskStatus {
  id: number
  slug: string
  name: string
  count?: number
}

export interface GetTasksResponse {
  tasks: TaskItem[]
  statuses: TaskStatus[]
  priorities: { id: number; slug: string; name: string }[]
  labels: { id: number; slug: string; name: string }[]
  projects: { id: number; title: string }[]
  staff: { id: number; display_name: string }[]
  total_pages: number
  total: number
  stats: {
    total_tasks: number
    active_tasks: number
    completed_tasks: number
    total_projects: number
  }
}

export async function getTasks(params?: {
  s?: string
  project_filter?: number
  staff_filter?: number
  priority_filter?: string
  label_filter?: string
  status?: string
  paged?: number
  per_page?: number
}) {
  return apiPost<GetTasksResponse>("puzzlingcrm_get_tasks", {
    s: params?.s,
    project_filter: params?.project_filter,
    staff_filter: params?.staff_filter,
    priority_filter: params?.priority_filter,
    label_filter: params?.label_filter,
    status: params?.status,
    paged: params?.paged ?? 1,
    per_page: params?.per_page ?? 50,
  })
}

export async function updateTaskStatus(taskId: number, statusSlug: string) {
  return apiPost<{ message?: string }>("puzzling_update_task_status", {
    task_id: String(taskId),
    status: statusSlug,
  })
}

export async function deleteTask(taskId: number) {
  return apiPost<{ message?: string }>("puzzling_delete_task", {
    task_id: String(taskId),
  })
}

export async function addTask(data: {
  title: string
  content?: string
  project_id: number
  assigned_to: number
  due_date?: string
  status_slug?: string
}) {
  const body: Record<string, string> = {
    title: data.title,
    content: data.content ?? "",
    project_id: String(data.project_id),
    assigned_to: String(data.assigned_to),
  }
  if (data.due_date) body.due_date = data.due_date
  if (data.status_slug) body.status_slug = data.status_slug
  return apiPost<{ message?: string; reload?: boolean }>("puzzling_quick_add_task", body)
}

export async function quickAddTask(data: {
  title: string
  project_id: number
  assigned_to: number
  status_slug: string
}) {
  return apiPost<{ message?: string; task_html?: string }>("puzzling_quick_add_task", {
    title: data.title,
    project_id: String(data.project_id),
    assigned_to: String(data.assigned_to),
    status_slug: data.status_slug,
  })
}
