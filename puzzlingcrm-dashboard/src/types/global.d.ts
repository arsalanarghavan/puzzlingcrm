export interface PuzzlingCRMUser {
  id: number
  name: string
  email: string
  role: string
  roleSlug?: string
  avatar?: string
  avatarLarge?: string
  first_name?: string
  last_name?: string
  pzl_mobile_phone?: string
}

export interface PuzzlingCRMRoute {
  id: string
  title: string
  icon: string
  roles: string[]
  path: string
}

export interface PuzzlingCRMMenuItem {
  type?: "category"
  id?: string
  title: string
  url?: string
  icon?: string
}

export interface PuzzlingCRMI18n {
  success_title?: string
  error_title?: string
  ok_button?: string
  server_error?: string
  [key: string]: string | undefined
}

export interface PuzzlingCRMProfileMenuItem {
  title: string
  url: string
  icon?: string
}

export interface PuzzlingCRMGlobal {
  ajaxUrl: string
  nonce: string
  user: PuzzlingCRMUser | null
  routes: Record<string, PuzzlingCRMRoute>
  menuItems: PuzzlingCRMMenuItem[]
  profileMenu?: PuzzlingCRMProfileMenuItem[]
  i18n: PuzzlingCRMI18n
  siteUrl: string
  dashboardBasePath?: string
  embedBaseUrl?: string
  loginUrl: string
  logoutUrl?: string
  logoUrl?: string
  isRtl: boolean
  locale: string
}

declare global {
  interface Window {
    puzzlingcrm?: PuzzlingCRMGlobal
  }
}

export {}
