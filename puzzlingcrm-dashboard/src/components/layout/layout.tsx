import { useState } from "react"
import { Outlet, useLocation, Link } from "react-router-dom"
import { cn } from "@/lib/utils"
import { Sidebar } from "./sidebar"
import { Header } from "./header"
import { ChevronLeft, ChevronRight } from "lucide-react"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { getConfigOrNull } from "@/api/client"
import { ErrorBoundary } from "@/components/error-boundary"

const PAGE_TITLES: Record<string, string> = {
  dashboard: "داشبورد",
  projects: "پروژه‌ها",
  contracts: "قراردادها",
  invoices: "پیش‌فاکتورها",
  tickets: "تیکت‌ها",
  tasks: "وظایف",
  appointments: "قرار ملاقات‌ها",
  leads: "سرنخ‌ها",
  licenses: "لایسنس‌ها",
  customers: "مشتریان",
  staff: "کارکنان",
  consultations: "مشاوره‌ها",
  reports: "گزارشات",
  logs: "لاگ‌ها",
  settings: "تنظیمات",
  profile: "پروفایل من",
}

function usePageTitle(): string {
  const location = useLocation()
  const path = location.pathname.replace(/^\/dashboard\/?/, "").replace(/^\//, "") || "dashboard"
  return PAGE_TITLES[path] ?? path
}

export function Layout() {
  const config = getConfigOrNull()
  const location = useLocation()
  const pageTitle = usePageTitle()
  if (!config) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <p className="text-muted-foreground">در حال بارگذاری...</p>
      </div>
    )
  }

  const { user, menuItems, loginUrl, siteUrl, logoUrl: configLogo, isRtl } = config
  const logoUrl = configLogo ?? ""
  let siteName = "PuzzlingCRM"
  if (siteUrl?.trim()) {
    try {
      siteName = new URL(siteUrl).hostname || siteName
    } catch {
      // keep default
    }
  }
  const profileMenu = config.profileMenu ?? [
    { title: "پروفایل من", url: "/dashboard/profile", icon: "user" },
  ]
  const isRtlLayout = isRtl === true
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false)

  return (
    <div
      className="flex min-h-screen flex-col md:flex-row"
      dir={isRtlLayout ? "rtl" : "ltr"}
    >
      <Sidebar
        menuItems={menuItems}
        logoUrl={logoUrl}
        siteName={siteName}
        mobileOpen={mobileMenuOpen}
        onMobileOpenChange={setMobileMenuOpen}
        isRtl={isRtlLayout}
      />
      <div className={cn("flex min-w-0 flex-1 flex-col overflow-visible", isRtlLayout && "text-right")} dir={isRtlLayout ? "rtl" : "ltr"}>
        <Header
          user={user ?? null}
          profileMenu={profileMenu}
          loginUrl={loginUrl}
          logoutUrl={config.logoutUrl}
          onOpenMobileMenu={() => setMobileMenuOpen(true)}
          isRtl={isRtlLayout}
        />
        <main className={cn("flex-1 overflow-auto p-4", isRtlLayout ? "text-right" : "text-left")} dir={isRtlLayout ? "rtl" : "ltr"}>
          <Breadcrumb dir={isRtlLayout ? "rtl" : "ltr"} className="mb-4 [&_ol]:justify-start">
            <BreadcrumbList>
              <BreadcrumbItem>
                <BreadcrumbLink asChild>
                  <Link to="/">داشبورد</Link>
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator>{isRtlLayout ? <ChevronLeft className="h-3.5 w-3.5" /> : <ChevronRight className="h-3.5 w-3.5" />}</BreadcrumbSeparator>
              <BreadcrumbItem>
                <BreadcrumbPage>{pageTitle}</BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
          <ErrorBoundary>
            <Outlet key={location.pathname} />
          </ErrorBoundary>
        </main>
      </div>
    </div>
  )
}
