import { apiPost } from "./client"

export interface Subscription {
  id: number
  customer_id: number
  customer_name: string
  status: string
  status_name: string
  total_formatted: string
  start_date: string
  next_payment: string
  already_converted: boolean
}

export interface Product {
  id: number
  name: string
  type: string
  price: string
  task_template_id: number | null
  service_task_type: string
  task_template_title: string
}

export interface TaskTemplate {
  id: number
  title: string
  is_recurring: boolean
  recurring_type: string
}

export interface ListSubscriptionsResponse {
  subscriptions: Subscription[]
  wc_subscriptions_active: boolean
  message?: string
}

export interface ListProductsResponse {
  products: Product[]
  task_templates: TaskTemplate[]
  wc_active: boolean
  message?: string
}

export interface ConvertResponse {
  contract_id: number
  message: string
}

export interface UpdateProductTaskTemplateResponse {
  message: string
}

export async function listSubscriptions() {
  return apiPost<ListSubscriptionsResponse>("puzzlingcrm_list_subscriptions", {})
}

export async function listProducts() {
  return apiPost<ListProductsResponse>("puzzlingcrm_list_products", {})
}

export async function convertSubscriptionToContract(subscriptionId: number) {
  return apiPost<ConvertResponse>("puzzlingcrm_convert_subscription_to_contract", {
    subscription_id: subscriptionId,
  })
}

export async function updateProductTaskTemplate(data: {
  product_id: number
  task_template_id: number | null
  service_task_type: string
}) {
  return apiPost<UpdateProductTaskTemplateResponse>("puzzlingcrm_update_product_task_template", {
    product_id: data.product_id,
    task_template_id: data.task_template_id ?? 0,
    service_task_type: data.service_task_type,
  })
}

export async function getTaskTemplates() {
  return apiPost<{ task_templates: TaskTemplate[] }>("puzzlingcrm_get_task_templates", {})
}
