import { Link } from "react-router-dom"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { useTheme } from "@/contexts/theme-context"
import { Search, Moon, Sun, User, LogOut, ExternalLink, Languages, Bell, Maximize, Menu } from "lucide-react"
import { cn } from "@/lib/utils"
import { changeLanguage } from "@/lib/language"
import type { PuzzlingCRMUser } from "@/types/global"
import type { PuzzlingCRMProfileMenuItem } from "@/types/global"

function toggleFullscreen() {
  if (
    !document.fullscreenElement &&
    !(document as unknown as { webkitFullscreenElement?: Element }).webkitFullscreenElement &&
    !(document as unknown as { mozFullScreenElement?: Element }).mozFullScreenElement
  ) {
    const doc = document.documentElement
    if (doc.requestFullscreen) doc.requestFullscreen()
    else if ((doc as unknown as { webkitRequestFullscreen?: () => Promise<void> }).webkitRequestFullscreen) (doc as unknown as { webkitRequestFullscreen: () => Promise<void> }).webkitRequestFullscreen()
    else if ((doc as unknown as { mozRequestFullScreen?: () => void }).mozRequestFullScreen) (doc as unknown as { mozRequestFullScreen: () => void }).mozRequestFullScreen()
  } else {
    if (document.exitFullscreen) document.exitFullscreen()
    else if ((document as unknown as { webkitExitFullscreen?: () => Promise<void> }).webkitExitFullscreen) (document as unknown as { webkitExitFullscreen: () => Promise<void> }).webkitExitFullscreen()
    else if ((document as unknown as { mozCancelFullScreen?: () => void }).mozCancelFullScreen) (document as unknown as { mozCancelFullScreen: () => void }).mozCancelFullScreen()
  }
}

interface HeaderProps {
  user: PuzzlingCRMUser | null
  profileMenu: PuzzlingCRMProfileMenuItem[]
  loginUrl: string
  logoutUrl?: string
  onOpenMobileMenu?: () => void
  isRtl?: boolean
}

function profileMenuTo(item: PuzzlingCRMProfileMenuItem): string {
  let raw: string
  if (item.url.startsWith("http")) {
    try {
      raw = new URL(item.url).pathname
    } catch {
      raw = item.url
    }
  } else {
    raw = item.url
  }
  return raw.replace(/^\/dashboard\/?/, "") || "/"
}

export function Header({ user, profileMenu, loginUrl, logoutUrl, onOpenMobileMenu, isRtl }: HeaderProps) {
  const { setTheme, resolved } = useTheme()

  return (
    <header
      dir={isRtl ? "rtl" : "ltr"}
      className="sticky top-0 z-40 flex h-14 w-full items-center gap-4 overflow-visible border-b bg-background px-4"
    >
      <div className="flex min-w-0 flex-1 items-center gap-4 overflow-hidden shrink">
        {onOpenMobileMenu && (
          <Button variant="ghost" size="icon" className="md:hidden" onClick={onOpenMobileMenu} aria-label="منو">
            <Menu className="h-5 w-5" />
          </Button>
        )}
        <div className="relative w-full max-w-sm">
          <Search className={cn("absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground", isRtl ? "right-3" : "left-3")} />
          <Input
            type="search"
            placeholder="جستجو..."
            className={cn(isRtl ? "pr-9" : "pl-9")}
          />
        </div>
      </div>
      <div className="flex flex-none shrink-0 items-center gap-2 overflow-visible" role="toolbar" aria-label="ابزارهای هدر">
        <a
          href={typeof window !== "undefined" ? (window.puzzlingcrm?.siteUrl ?? "/") : "/"}
          target="_blank"
          rel="noopener noreferrer"
          className="text-muted-foreground hover:text-foreground"
          aria-label="View Site"
        >
          <ExternalLink className="h-4 w-4" />
        </a>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" aria-label="زبان">
              <Languages className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => changeLanguage("fa")}>
              <span className={cn("font-medium", isRtl ? "ml-2" : "mr-2")}>FA</span>
              فارسی
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => changeLanguage("en")}>
              <span className={cn("font-medium", isRtl ? "ml-2" : "mr-2")}>EN</span>
              English
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" aria-label="اعلان‌ها">
              <Bell className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-72">
            <DropdownMenuLabel>اعلان‌ها</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div className="py-4 text-center text-sm text-muted-foreground">
              موردی یافت نشد.
            </div>
          </DropdownMenuContent>
        </DropdownMenu>
        <Button variant="ghost" size="icon" onClick={toggleFullscreen} aria-label="تمام‌صفحه">
          <Maximize className="h-4 w-4" />
        </Button>
        <Button
          variant="ghost"
          size="icon"
          onClick={() => setTheme(resolved === "dark" ? "light" : "dark")}
        >
          {resolved === "dark" ? (
            <Sun className="h-4 w-4" />
          ) : (
            <Moon className="h-4 w-4" />
          )}
        </Button>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="relative h-8 w-8 rounded-full">
              <Avatar className="h-8 w-8">
                <AvatarImage src={user?.avatar} alt={user?.name} />
                <AvatarFallback>
                  {user?.name?.slice(0, 2)?.toUpperCase() ?? "U"}
                </AvatarFallback>
              </Avatar>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-56">
            <DropdownMenuLabel>
              <div className="flex flex-col">
                <span>{user?.name ?? "User"}</span>
                <span className="text-xs font-normal text-muted-foreground">
                  {user?.role ?? ""}
                </span>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            {profileMenu.map((item, idx) => (
              <DropdownMenuItem key={`${item.url}-${item.title}-${idx}`} asChild>
                <Link to={profileMenuTo(item)}>
                  <User className={cn("h-4 w-4", isRtl ? "ml-2" : "mr-2")} />
                  {item.title}
                </Link>
              </DropdownMenuItem>
            ))}
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
              <a href={logoutUrl ?? loginUrl}>
                <LogOut className={cn("h-4 w-4", isRtl ? "ml-2" : "mr-2")} />
                خروج
              </a>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  )
}
