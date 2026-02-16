import { Link, useLocation, useNavigate } from "react-router-dom"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Sheet,
  SheetContent,
  SheetTrigger,
} from "@/components/ui/sheet"
import { ScrollArea } from "@/components/ui/scroll-area"
import {
  Menu,
  LayoutDashboard,
  FolderOpen,
  FileText,
  ListOrdered,
  Headphones,
  CheckSquare,
  Calendar,
  UserPlus,
  Key,
  Users,
  UserCog,
  MessageCircle,
  BarChart3,
  FileStack,
  User,
  Settings,
  Package,
  Megaphone,
  type LucideIcon,
} from "lucide-react"
import type { PuzzlingCRMMenuItem } from "@/types/global"

function useDir(): "rtl" | "ltr" {
  if (typeof document === "undefined") return "rtl"
  const dir = document.documentElement.getAttribute("dir")
  return dir === "ltr" ? "ltr" : "rtl"
}

const iconMap: Record<string, LucideIcon> = {
  dashboard: LayoutDashboard,
  projects: FolderOpen,
  contracts: FileText,
  invoices: ListOrdered,
  tickets: Headphones,
  tasks: CheckSquare,
  appointments: Calendar,
  leads: UserPlus,
  licenses: Key,
  customers: Users,
  staff: UserCog,
  consultations: MessageCircle,
  reports: BarChart3,
  logs: FileStack,
  settings: Settings,
  profile: User,
  services: Package,
  campaigns: Megaphone,
}

interface SidebarProps {
  menuItems: PuzzlingCRMMenuItem[]
  logoUrl: string
  siteName: string
  className?: string
  mobileOpen?: boolean
  onMobileOpenChange?: (open: boolean) => void
  isRtl?: boolean
}

function getToFromItem(item: PuzzlingCRMMenuItem): string {
  const href = item.url ?? ""
  try {
    const pathOnly = href.startsWith("http")
      ? new URL(href).pathname
      : href
    const slug = pathOnly.replace(/^\/dashboard\/?/, "").replace(/^\//, "").trim() || ""
    if (slug !== "" && slug !== "dashboard") return `/${slug}`
    if (item.id) return item.id === "dashboard" ? "/" : `/${item.id}`
    return "/"
  } catch {
    if (item.id) return item.id === "dashboard" ? "/" : `/${item.id}`
    return "/"
  }
}

export function Sidebar({ menuItems, logoUrl, siteName, className, mobileOpen, onMobileOpenChange, isRtl }: SidebarProps) {
  const location = useLocation()
  const navigate = useNavigate()
  const pathname = location.pathname.replace(/^\/dashboard\/?/, "").replace(/^\//, "") || "dashboard"
  const dir: "rtl" | "ltr" =
    typeof isRtl === "boolean" ? (isRtl ? "rtl" : "ltr") : useDir()
  const textAlign = dir === "rtl" ? "text-right" : "text-left"

  const nav = (
    <nav dir={dir} className={cn("flex flex-col gap-1 px-2 py-4", textAlign)} style={{ textAlign: dir === "rtl" ? "right" : "left" }}>
      {menuItems.map((item, i) => {
        if (item.type === "category") {
          return (
            <div
              key={`cat-${i}`}
              dir={dir}
              className={cn("px-2 py-2 text-xs font-semibold text-muted-foreground", textAlign)}
              style={{ textAlign: dir === "rtl" ? "right" : "left" }}
            >
              {item.title}
            </div>
          )
        }
        const to = getToFromItem(item)
        const isActive = item.id ? pathname === item.id || (pathname === "" && item.id === "dashboard") : false
        const handleClick = () => {
          onMobileOpenChange?.(false)
          navigate(to)
        }
        return (
          <button
            type="button"
            key={item.id ?? i}
            onClick={handleClick}
            dir={dir}
            className={cn(
              "w-full cursor-pointer border-0 bg-transparent flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground",
              "justify-start",
              textAlign,
              isActive ? "bg-accent text-accent-foreground" : "text-muted-foreground"
            )}
            style={{ textAlign: dir === "rtl" ? "right" : "left" }}
          >
            {(() => {
              const Icon = item.id ? iconMap[item.id] : null
              return Icon ? <Icon className="h-5 w-5 shrink-0" /> : null
            })()}
            <span>{item.title}</span>
          </button>
        )
      })}
    </nav>
  )

  return (
    <>
      {/* Desktop sidebar */}
      <aside
        dir={dir}
        className={cn(
          "hidden w-64 flex-col bg-card md:flex",
          dir === "rtl" ? "border-l text-right" : "border-r text-left",
          className
        )}
        style={dir === "rtl" ? { textAlign: "right" } : { textAlign: "left" }}
      >
        <div className="flex h-14 items-center justify-start border-b px-4">
          <Link to="/" className={cn("flex items-center gap-2", dir === "rtl" && "flex-row-reverse")}>
            {logoUrl ? (
              <img src={logoUrl} alt={siteName} className="h-8 max-w-[140px] object-contain" />
            ) : (
              <span className="font-semibold">{siteName}</span>
            )}
          </Link>
        </div>
        <ScrollArea className="flex-1">
          {nav}
        </ScrollArea>
      </aside>

      {/* Mobile: sheet (controlled when mobileOpen/onMobileOpenChange provided) */}
      <Sheet
        open={mobileOpen}
        onOpenChange={onMobileOpenChange}
      >
        {!onMobileOpenChange && (
          <SheetTrigger asChild>
            <Button variant="ghost" size="icon" className="md:hidden">
              <Menu className="h-5 w-5" />
            </Button>
          </SheetTrigger>
        )}
        <SheetContent side={dir === "rtl" ? "right" : "left"} className="w-64 p-0" dir={dir}>
          <div className={cn("flex h-14 items-center border-b px-4", dir === "rtl" && "justify-end")}>
            <Link to="/" className={cn("flex items-center gap-2", dir === "rtl" && "flex-row-reverse")} onClick={() => onMobileOpenChange?.(false)}>
              {logoUrl ? (
                <img src={logoUrl} alt={siteName} className="h-8 max-w-[140px] object-contain" />
              ) : (
                <span className="font-semibold">{siteName}</span>
              )}
            </Link>
          </div>
          {nav}
        </SheetContent>
      </Sheet>
    </>
  )
}

