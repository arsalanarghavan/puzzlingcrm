import { useState, useEffect, useCallback } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { getConfigOrNull } from "@/api/client"
import {
  getSettings,
  saveSettings,
  saveAuthSettings,
  manageCannedResponse,
  deleteCannedResponse,
  managePosition,
  deletePosition,
  manageTaskCategory,
  deleteTaskCategory,
  type SettingsTab,
  type CannedResponseItem,
  type PositionItem,
  type TaskCategoryItem,
} from "@/api/settings"
import {
  Shield,
  Palette,
  CreditCard,
  MessageSquare,
  GitBranch,
  Network,
  Tag,
  Bot,
  Bell,
  FileText,
  MessageCircle,
  UserPlus,
  Loader2,
  Plus,
  Pencil,
  Trash2,
} from "lucide-react"
import { cn } from "@/lib/utils"
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import type { AuthSettings, StyleSettings, PaymentSettings, SmsSettings, NotificationSettings } from "@/api/settings"

const TABS: { id: SettingsTab; title: string; icon: React.ComponentType<{ className?: string }> }[] = [
  { id: "authentication", title: "احراز هویت", icon: Shield },
  { id: "style", title: "ظاهر و استایل", icon: Palette },
  { id: "payment", title: "درگاه پرداخت", icon: CreditCard },
  { id: "sms", title: "سامانه پیامک", icon: MessageSquare },
  { id: "notifications", title: "اطلاع‌رسانی‌ها", icon: Bell },
  { id: "workflow", title: "گردش کار", icon: GitBranch },
  { id: "positions", title: "جایگاه‌های شغلی", icon: Network },
  { id: "task_categories", title: "دسته‌بندی وظایف", icon: Tag },
  { id: "automations", title: "اتوماسیون", icon: Bot },
  { id: "canned_responses", title: "پاسخ‌های آماده", icon: MessageCircle },
  { id: "forms", title: "فرم‌ها", icon: FileText },
  { id: "leads", title: "وضعیت‌های لید", icon: UserPlus },
]

export function SettingsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [activeTab, setActiveTab] = useState<string>("authentication")
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div>
        <h2 className="text-xl font-semibold tracking-tight">تنظیمات</h2>
        <p className="text-muted-foreground text-sm">
          بخش‌های مختلف تنظیمات را از تب‌های زیر انتخاب و ویرایش کنید.
        </p>
      </div>

      {error && (
        <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive text-sm">
          {error}
        </div>
      )}
      {message && (
        <div className="rounded-lg border border-green-500/50 bg-green-500/10 px-4 py-3 text-green-700 dark:text-green-400 text-sm">
          {message}
        </div>
      )}

      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="flex flex-wrap h-auto gap-1 p-1 bg-muted/50">
          {TABS.map((tab) => {
            const Icon = tab.icon
            return (
              <TabsTrigger key={tab.id} value={tab.id} className="gap-2">
                <Icon className="h-4 w-4" />
                {tab.title}
              </TabsTrigger>
            )
          })}
        </TabsList>

        {TABS.map((tab) => (
          <TabsContent key={tab.id} value={tab.id} className="mt-4">
            {activeTab === tab.id ? (
              <SettingsTabContent
                tab={tab.id}
                isRtl={isRtl}
                onError={setError}
                onMessage={setMessage}
                loading={loading}
                saving={saving}
                setLoading={setLoading}
                setSaving={setSaving}
              />
            ) : null}
          </TabsContent>
        ))}
      </Tabs>
    </div>
  )
}

function SettingsTabContent({
  tab,
  isRtl,
  onError,
  onMessage,
  loading,
  saving,
  setLoading,
  setSaving,
}: {
  tab: SettingsTab
  isRtl: boolean
  onError: (s: string | null) => void
  onMessage: (s: string | null) => void
  loading: boolean
  saving: boolean
  setLoading: (b: boolean) => void
  setSaving: (b: boolean) => void
}) {
  const [data, setData] = useState<Record<string, unknown> | null>(null)
  const [form, setForm] = useState<Record<string, unknown>>({})

  const load = useCallback(async () => {
    setLoading(true)
    onError(null)
    try {
      const res = await getSettings(tab)
      if (res.success && res.data) {
        setData(res.data as Record<string, unknown>)
        const items = (res.data as { items?: unknown[] }).items
        if (items) {
          setForm({})
        } else {
          setForm(res.data as Record<string, unknown>)
        }
      } else {
        onError(res.message ?? "خطا در بارگذاری")
      }
    } catch {
      onError("خطا در بارگذاری تنظیمات")
    } finally {
      setLoading(false)
    }
  }, [tab, onError, setLoading])

  useEffect(() => {
    load()
  }, [load])

  const handleSave = useCallback(async () => {
    setSaving(true)
    onError(null)
    onMessage(null)
    try {
      if (tab === "authentication") {
        const res = await saveAuthSettings(form as Record<string, string | number | boolean | undefined>)
        if (res.success) {
          onMessage(res.message ?? "ذخیره شد.")
          load()
        } else onError(res.message ?? "خطا در ذخیره")
        return
      }
      if (["style", "payment", "sms", "notifications"].includes(tab)) {
        const res = await saveSettings(tab, form as Record<string, string | number | boolean | undefined>)
        if (res.success) {
          onMessage(res.message ?? "ذخیره شد.")
          load()
        } else onError(res.message ?? "خطا در ذخیره")
        return
      }
      onMessage("برای این تب ذخیره از طریق فرم زیر هر آیتم انجام می‌شود.")
    } finally {
      setSaving(false)
    }
  }, [tab, form, onError, onMessage, load, setSaving])

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    )
  }

  if (tab === "authentication") {
    const f = form as unknown as AuthSettings
    return (
      <Card>
        <CardHeader>
          <CardTitle>احراز هویت</CardTitle>
          <CardDescription>تنظیمات ورود، OTP و مسیرهای بازگردانی</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>پترن شماره موبایل (Regex)</Label>
              <Input
                value={(f.login_phone_pattern as string) ?? ""}
                onChange={(e) => setForm((p) => ({ ...p, login_phone_pattern: e.target.value }))}
                placeholder="^09[0-9]{9}$"
              />
            </div>
            <div className="space-y-2">
              <Label>طول شماره موبایل</Label>
              <Input
                type="number"
                value={(f.login_phone_length as number) ?? 11}
                onChange={(e) => setForm((p) => ({ ...p, login_phone_length: parseInt(e.target.value, 10) || 11 }))}
              />
            </div>
          </div>
          <div className="space-y-2">
            <Label>قالب پیامک ورود (%CODE% برای کد)</Label>
            <Input
              value={(f.login_sms_template as string) ?? ""}
              onChange={(e) => setForm((p) => ({ ...p, login_sms_template: e.target.value }))}
              placeholder="کد ورود شما: %CODE%"
            />
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="space-y-2">
              <Label>انقضای OTP (دقیقه)</Label>
              <Input
                type="number"
                value={(f.otp_expiry_minutes as number) ?? 5}
                onChange={(e) => setForm((p) => ({ ...p, otp_expiry_minutes: parseInt(e.target.value, 10) || 5 }))}
              />
            </div>
            <div className="space-y-2">
              <Label>حداکثر تلاش OTP</Label>
              <Input
                type="number"
                value={(f.otp_max_attempts as number) ?? 3}
                onChange={(e) => setForm((p) => ({ ...p, otp_max_attempts: parseInt(e.target.value, 10) || 3 }))}
              />
            </div>
            <div className="space-y-2">
              <Label>طول کد OTP</Label>
              <Input
                type="number"
                value={(f.otp_length as number) ?? 6}
                onChange={(e) => setForm((p) => ({ ...p, otp_length: parseInt(e.target.value, 10) || 6 }))}
              />
            </div>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>آدرس بازگردانی پس از ورود</Label>
              <Input
                value={(f.login_redirect_url as string) ?? ""}
                onChange={(e) => setForm((p) => ({ ...p, login_redirect_url: e.target.value }))}
                placeholder="https://..."
              />
            </div>
            <div className="space-y-2">
              <Label>آدرس بازگردانی پس از خروج</Label>
              <Input
                value={(f.logout_redirect_url as string) ?? ""}
                onChange={(e) => setForm((p) => ({ ...p, logout_redirect_url: e.target.value }))}
                placeholder="https://..."
              />
            </div>
          </div>
          <div className="flex flex-wrap gap-4">
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={!!f.enable_password_login}
                onChange={(e) => setForm((p) => ({ ...p, enable_password_login: e.target.checked }))}
                className="rounded border-input"
              />
              <span className="text-sm">ورود با رمز عبور</span>
            </label>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={!!f.enable_sms_login}
                onChange={(e) => setForm((p) => ({ ...p, enable_sms_login: e.target.checked }))}
                className="rounded border-input"
              />
              <span className="text-sm">ورود با پیامک (OTP)</span>
            </label>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={!!f.force_logout_inactive}
                onChange={(e) => setForm((p) => ({ ...p, force_logout_inactive: e.target.checked }))}
                className="rounded border-input"
              />
              <span className="text-sm">خروج خودکار در صورت عدم فعالیت</span>
            </label>
          </div>
          <div className="space-y-2">
            <Label>مدت عدم فعالیت قبل از خروج (دقیقه)</Label>
            <Input
              type="number"
              value={(f.inactive_timeout_minutes as number) ?? 30}
              onChange={(e) => setForm((p) => ({ ...p, inactive_timeout_minutes: parseInt(e.target.value, 10) || 30 }))}
            />
          </div>
          <Button onClick={handleSave} disabled={saving}>
            {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            ذخیره تنظیمات احراز هویت
          </Button>
        </CardContent>
      </Card>
    )
  }

  if (tab === "style") {
    const f = form as unknown as StyleSettings
    return (
      <Card>
        <CardHeader>
          <CardTitle>ظاهر و استایل</CardTitle>
          <CardDescription>رنگ اصلی و فونت داشبورد</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>رنگ اصلی</Label>
              <div className="flex gap-2">
                <input
                  type="color"
                  value={(f.primary_color as string) ?? "#845adf"}
                  onChange={(e) => setForm((p) => ({ ...p, primary_color: e.target.value }))}
                  className="h-10 w-14 cursor-pointer rounded border"
                />
                <Input
                  value={(f.primary_color as string) ?? "#845adf"}
                  onChange={(e) => setForm((p) => ({ ...p, primary_color: e.target.value }))}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label>خانواده فونت</Label>
              <Input
                value={(f.font_family as string) ?? "IRANSans"}
                onChange={(e) => setForm((p) => ({ ...p, font_family: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label>اندازه فونت پایه</Label>
              <Input
                type="number"
                value={(f.font_size as number) ?? 14}
                onChange={(e) => setForm((p) => ({ ...p, font_size: parseInt(e.target.value, 10) || 14 }))}
              />
            </div>
          </div>
          <Button onClick={handleSave} disabled={saving}>
            {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            ذخیره
          </Button>
        </CardContent>
      </Card>
    )
  }

  if (tab === "payment") {
    const f = form as unknown as PaymentSettings
    return (
      <Card>
        <CardHeader>
          <CardTitle>درگاه پرداخت</CardTitle>
          <CardDescription>تنظیمات زرین‌پال و سایر درگاه‌ها</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label>مرچنت زرین‌پال</Label>
            <Input
              value={(f.zarinpal_merchant as string) ?? ""}
              onChange={(e) => setForm((p) => ({ ...p, zarinpal_merchant: e.target.value }))}
              placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
            />
          </div>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={!!f.zarinpal_sandbox}
              onChange={(e) => setForm((p) => ({ ...p, zarinpal_sandbox: e.target.checked }))}
              className="rounded border-input"
            />
            <span className="text-sm">حالت سندباکس</span>
          </label>
          <Button onClick={handleSave} disabled={saving}>
            {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            ذخیره
          </Button>
        </CardContent>
      </Card>
    )
  }

  if (tab === "sms") {
    const f = form as unknown as SmsSettings
    return (
      <Card>
        <CardHeader>
          <CardTitle>سامانه پیامک</CardTitle>
          <CardDescription>سرویس و اطلاعات اتصال پیامک</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label>سرویس پیامک</Label>
            <Input
              value={(f.sms_service as string) ?? ""}
              onChange={(e) => setForm((p) => ({ ...p, sms_service: e.target.value }))}
              placeholder="melipayamak, parsgreen, ..."
            />
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>نام کاربری</Label>
              <Input
                value={(f.sms_username as string) ?? ""}
                onChange={(e) => setForm((p) => ({ ...p, sms_username: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label>رمز عبور</Label>
              <Input
                type="password"
                value={(f.sms_password as string) ?? ""}
                onChange={(e) => setForm((p) => ({ ...p, sms_password: e.target.value }))}
              />
            </div>
          </div>
          <div className="space-y-2">
            <Label>شماره فرستنده</Label>
            <Input
              value={(f.sms_sender as string) ?? ""}
              onChange={(e) => setForm((p) => ({ ...p, sms_sender: e.target.value }))}
            />
          </div>
          <Button onClick={handleSave} disabled={saving}>
            {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            ذخیره
          </Button>
        </CardContent>
      </Card>
    )
  }

  if (tab === "notifications") {
    const f = form as unknown as NotificationSettings
    return (
      <Card>
        <CardHeader>
          <CardTitle>اطلاع‌رسانی‌ها</CardTitle>
          <CardDescription>تلگرام و سایر کانال‌های اعلان</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label>توکن ربات تلگرام</Label>
            <Input
              value={(f.telegram_bot_token as string) ?? ""}
              onChange={(e) => setForm((p) => ({ ...p, telegram_bot_token: e.target.value }))}
              placeholder="..."
            />
          </div>
          <div className="space-y-2">
            <Label>شناسه چت تلگرام</Label>
            <Input
              value={(f.telegram_chat_id as string) ?? ""}
              onChange={(e) => setForm((p) => ({ ...p, telegram_chat_id: e.target.value }))}
              placeholder="..."
            />
          </div>
          <Button onClick={handleSave} disabled={saving}>
            {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            ذخیره
          </Button>
        </CardContent>
      </Card>
    )
  }

  if (tab === "canned_responses") {
    const items = ((data?.items as CannedResponseItem[]) ?? []) as CannedResponseItem[]
    return (
      <CannedResponsesSection
        items={items}
        onError={onError}
        onMessage={onMessage}
        onReload={load}
        isRtl={isRtl}
      />
    )
  }

  if (tab === "positions") {
    const items = ((data?.items as PositionItem[]) ?? []) as PositionItem[]
    return (
      <PositionsSection
        items={items}
        onError={onError}
        onMessage={onMessage}
        onReload={load}
        isRtl={isRtl}
      />
    )
  }

  if (tab === "task_categories") {
    const items = ((data?.items as TaskCategoryItem[]) ?? []) as TaskCategoryItem[]
    return (
      <TaskCategoriesSection
        items={items}
        onError={onError}
        onMessage={onMessage}
        onReload={load}
        isRtl={isRtl}
      />
    )
  }

  // workflow, automations, forms, leads: show placeholder
  return (
    <Card>
      <CardHeader>
        <CardTitle>
          {tab === "workflow" && "گردش کار"}
          {tab === "automations" && "اتوماسیون"}
          {tab === "forms" && "فرم‌ها"}
          {tab === "leads" && "وضعیت‌های لید"}
        </CardTitle>
        <CardDescription>
          این بخش از تنظیمات در نسخهٔ فعلی از طریق پنل وردپرس یا صفحهٔ embed در دسترس است. به‌زودی در همین داشبورد کامل خواهد شد.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <p className="text-muted-foreground text-sm">
          برای تنظیمات پیشرفتهٔ گردش کار، اتوماسیون، فرم‌ها و وضعیت لیدها از منوی وردپرس PuzzlingCRM استفاده کنید.
        </p>
      </CardContent>
    </Card>
  )
}

function CannedResponsesSection({
  items,
  onError,
  onMessage,
  onReload,
  isRtl,
}: {
  items: CannedResponseItem[]
  onError: (s: string | null) => void
  onMessage: (s: string | null) => void
  onReload: () => void
  isRtl: boolean
}) {
  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<CannedResponseItem | null>(null)
  const [title, setTitle] = useState("")
  const [content, setContent] = useState("")
  const [submitting, setSubmitting] = useState(false)

  const openNew = () => {
    setEditing(null)
    setTitle("")
    setContent("")
    setOpen(true)
  }
  const openEdit = (item: CannedResponseItem) => {
    setEditing(item)
    setTitle(item.title)
    setContent(item.content)
    setOpen(true)
  }
  const handleSubmit = async () => {
    if (!title.trim() || !content.trim()) {
      onError("عنوان و محتوا الزامی است.")
      return
    }
    setSubmitting(true)
    onError(null)
    try {
      const res = await manageCannedResponse({
        response_id: editing?.id,
        response_title: title.trim(),
        response_content: content.trim(),
      })
      if (res.success) {
        onMessage(res.message ?? "ذخیره شد.")
        setOpen(false)
        onReload()
      } else onError(res.message ?? "خطا")
    } catch {
      onError("خطا در ذخیره")
    } finally {
      setSubmitting(false)
    }
  }
  const handleDelete = async (id: number) => {
    if (!confirm("حذف این پاسخ آماده؟")) return
    setSubmitting(true)
    onError(null)
    try {
      const res = await deleteCannedResponse(id)
      if (res.success) {
        onMessage("حذف شد.")
        onReload()
      } else onError(res.message ?? "خطا")
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>پاسخ‌های آماده</CardTitle>
          <CardDescription>مدیریت پاسخ‌های از پیش تعریف‌شده برای تیکت‌ها</CardDescription>
        </div>
        <Button onClick={openNew} className="gap-2">
          <Plus className="h-4 w-4" />
          افزودن
        </Button>
      </CardHeader>
      <CardContent>
        <ul className="space-y-2">
          {items.length === 0 && (
            <li className="text-muted-foreground text-sm py-4">پاسخی تعریف نشده است.</li>
          )}
          {items.map((item) => (
            <li
              key={item.id}
              className="flex items-center justify-between rounded-lg border p-3"
            >
              <div>
                <p className="font-medium">{item.title}</p>
                <p className="text-muted-foreground text-sm line-clamp-1">{item.content}</p>
              </div>
              <div className={cn("flex gap-2", isRtl && "flex-row-reverse")}>
                <Button variant="ghost" size="sm" onClick={() => openEdit(item)}>
                  <Pencil className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => handleDelete(item.id)}>
                  <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
              </div>
            </li>
          ))}
        </ul>
      </CardContent>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editing ? "ویرایش پاسخ آماده" : "پاسخ آماده جدید"}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>عنوان</Label>
              <Input value={title} onChange={(e) => setTitle(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>محتوا</Label>
              <textarea
                className="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={content}
                onChange={(e) => setContent(e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>انصراف</Button>
            <Button onClick={handleSubmit} disabled={submitting}>
              {submitting ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
              ذخیره
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </Card>
  )
}

function PositionsSection({
  items,
  onError,
  onMessage,
  onReload,
  isRtl,
}: {
  items: PositionItem[]
  onError: (s: string | null) => void
  onMessage: (s: string | null) => void
  onReload: () => void
  isRtl: boolean
}) {
  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<PositionItem | null>(null)
  const [title, setTitle] = useState("")
  const [submitting, setSubmitting] = useState(false)

  const openNew = () => {
    setEditing(null)
    setTitle("")
    setOpen(true)
  }
  const openEdit = (item: PositionItem) => {
    setEditing(item)
    setTitle(item.title)
    setOpen(true)
  }
  const handleSubmit = async () => {
    if (!title.trim()) {
      onError("عنوان الزامی است.")
      return
    }
    setSubmitting(true)
    onError(null)
    try {
      const res = await managePosition({
        position_id: editing?.id,
        position_title: title.trim(),
        position_permissions: editing?.permissions ?? [],
      })
      if (res.success) {
        onMessage(res.message ?? "ذخیره شد.")
        setOpen(false)
        onReload()
      } else onError(res.message ?? "خطا")
    } catch {
      onError("خطا در ذخیره")
    } finally {
      setSubmitting(false)
    }
  }
  const handleDelete = async (id: number) => {
    if (!confirm("حذف این جایگاه شغلی؟")) return
    setSubmitting(true)
    onError(null)
    try {
      const res = await deletePosition(id)
      if (res.success) {
        onMessage("حذف شد.")
        onReload()
      } else onError(res.message ?? "خطا")
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>جایگاه‌های شغلی</CardTitle>
          <CardDescription>سمت‌ها و نقش‌های کاربری</CardDescription>
        </div>
        <Button onClick={openNew} className="gap-2">
          <Plus className="h-4 w-4" />
          افزودن
        </Button>
      </CardHeader>
      <CardContent>
        <ul className="space-y-2">
          {items.length === 0 && (
            <li className="text-muted-foreground text-sm py-4">جایگاهی تعریف نشده است.</li>
          )}
          {items.map((item) => (
            <li
              key={item.id}
              className="flex items-center justify-between rounded-lg border p-3"
            >
              <p className="font-medium">{item.title}</p>
              <div className={cn("flex gap-2", isRtl && "flex-row-reverse")}>
                <Button variant="ghost" size="sm" onClick={() => openEdit(item)}>
                  <Pencil className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => handleDelete(item.id)}>
                  <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
              </div>
            </li>
          ))}
        </ul>
      </CardContent>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editing ? "ویرایش جایگاه شغلی" : "جایگاه شغلی جدید"}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>عنوان</Label>
              <Input value={title} onChange={(e) => setTitle(e.target.value)} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>انصراف</Button>
            <Button onClick={handleSubmit} disabled={submitting}>
              {submitting ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
              ذخیره
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </Card>
  )
}

function TaskCategoriesSection({
  items,
  onError,
  onMessage,
  onReload,
  isRtl,
}: {
  items: TaskCategoryItem[]
  onError: (s: string | null) => void
  onMessage: (s: string | null) => void
  onReload: () => void
  isRtl: boolean
}) {
  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<TaskCategoryItem | null>(null)
  const [name, setName] = useState("")
  const [color, setColor] = useState("#845adf")
  const [submitting, setSubmitting] = useState(false)

  const openNew = () => {
    setEditing(null)
    setName("")
    setColor("#845adf")
    setOpen(true)
  }
  const openEdit = (item: TaskCategoryItem) => {
    setEditing(item)
    setName(item.name)
    setColor(item.color || "#845adf")
    setOpen(true)
  }
  const handleSubmit = async () => {
    if (!name.trim()) {
      onError("نام دسته‌بندی الزامی است.")
      return
    }
    setSubmitting(true)
    onError(null)
    try {
      const res = await manageTaskCategory({
        category_id: editing?.id,
        category_name: name.trim(),
        category_color: color,
      })
      if (res.success) {
        onMessage(res.message ?? "ذخیره شد.")
        setOpen(false)
        onReload()
      } else onError(res.message ?? "خطا")
    } catch {
      onError("خطا در ذخیره")
    } finally {
      setSubmitting(false)
    }
  }
  const handleDelete = async (id: number) => {
    if (!confirm("حذف این دسته‌بندی؟")) return
    setSubmitting(true)
    onError(null)
    try {
      const res = await deleteTaskCategory(id)
      if (res.success) {
        onMessage("حذف شد.")
        onReload()
      } else onError(res.message ?? "خطا")
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>دسته‌بندی وظایف</CardTitle>
          <CardDescription>دسته‌بندی و برچسب‌های وظایف</CardDescription>
        </div>
        <Button onClick={openNew} className="gap-2">
          <Plus className="h-4 w-4" />
          افزودن
        </Button>
      </CardHeader>
      <CardContent>
        <ul className="space-y-2">
          {items.length === 0 && (
            <li className="text-muted-foreground text-sm py-4">دسته‌بندی تعریف نشده است.</li>
          )}
          {items.map((item) => (
            <li
              key={item.id}
              className="flex items-center justify-between rounded-lg border p-3"
            >
              <div className="flex items-center gap-2">
                <span
                  className="h-4 w-4 rounded-full border"
                  style={{ backgroundColor: item.color || "#845adf" }}
                />
                <p className="font-medium">{item.name}</p>
              </div>
              <div className={cn("flex gap-2", isRtl && "flex-row-reverse")}>
                <Button variant="ghost" size="sm" onClick={() => openEdit(item)}>
                  <Pencil className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => handleDelete(item.id)}>
                  <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
              </div>
            </li>
          ))}
        </ul>
      </CardContent>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editing ? "ویرایش دسته‌بندی" : "دسته‌بندی جدید"}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>نام</Label>
              <Input value={name} onChange={(e) => setName(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>رنگ</Label>
              <div className="flex gap-2">
                <input
                  type="color"
                  value={color}
                  onChange={(e) => setColor(e.target.value)}
                  className="h-10 w-14 cursor-pointer rounded border"
                />
                <Input value={color} onChange={(e) => setColor(e.target.value)} />
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>انصراف</Button>
            <Button onClick={handleSubmit} disabled={submitting}>
              {submitting ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
              ذخیره
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </Card>
  )
}
