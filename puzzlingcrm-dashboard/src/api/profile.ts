import { apiPostForm } from "./client"

export async function updateProfile(data: {
  first_name: string
  last_name: string
  pzl_mobile_phone?: string
  password?: string
  password_confirm?: string
  pzl_profile_picture?: File
}) {
  const formData = new FormData()
  formData.set("first_name", data.first_name)
  formData.set("last_name", data.last_name)
  if (data.pzl_mobile_phone != null) formData.set("pzl_mobile_phone", data.pzl_mobile_phone)
  if (data.password?.trim()) formData.set("password", data.password)
  if (data.password_confirm?.trim()) formData.set("password_confirm", data.password_confirm)
  if (data.pzl_profile_picture) formData.set("pzl_profile_picture", data.pzl_profile_picture)

  return apiPostForm<{ message?: string; reload?: boolean }>(
    "puzzling_update_my_profile",
    formData
  )
}
