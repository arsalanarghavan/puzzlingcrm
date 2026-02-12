import { useEffect, useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Progress } from "@/components/ui/progress"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { getConfigOrNull } from "@/api/client"
import {
  getLicenses,
  addLicense,
  renewLicense,
  cancelLicense,
  deleteLicense,
  type License,
} from "@/api/licenses"
import { cn } from "@/lib/utils"
import { Plus, Loader2, Key, MoreVertical, RefreshCw, XCircle, Trash2, Building2 } from "lucide-react"

const STATUS_LABELS: Record<string, string> = {
  active: "فعال",
  inactive: "غیرفعال",
  expired: "منقضی شده",
  cancelled: "لغو شده",
}

export function LicensesPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [licenses, setLicenses] = useState<License[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [addOpen, setAddOpen] = useState(false)
  const [renewOpen, setRenewOpen] = useState(false)
  const [renewId, setRenewId] = useState<number | null>(null)
  const [renewDate, setRenewDate] = useState("")
  const [submitting, setSubmitting] = useState(false)
  const [addForm, setAddForm] = useState({
    project_name: "",
    domain: "",
    start_date: new Date().toISOString().slice(0, 10),
    expiry_date: "",
    logo_url: "",
    status: "active",
  })

  const load = async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getLicenses()
      if (res.success && res.data) {
        const d = res.data as { licenses: License[] }
        setLicenses(d.licenses ?? [])
      } else {
        setError(res.message ?? "خطا در بارگذاری لایسنس‌ها")
      }
    } catch {
      setError("خطا در بارگذاری لایسنس‌ها")
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [])

  const handleAdd = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!addForm.project_name.trim() || !addForm.domain.trim()) {
      setError("نام مجموعه و دامنه الزامی هستند.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await addLicense({
        project_name: addForm.project_name.trim(),
        domain: addForm.domain.trim(),
        start_date: addForm.start_date || undefined,
        expiry_date: addForm.expiry_date || undefined,
        logo_url: addForm.logo_url.trim() || undefined,
        status: addForm.status,
      })
      if (res.success) {
        setAddOpen(false)
        setAddForm({
          project_name: "",
          domain: "",
          start_date: new Date().toISOString().slice(0, 10),
          expiry_date: "",
          logo_url: "",
          status: "active",
        })
        load()
      } else {
        setError(res.message ?? "خطا در افزودن لایسنس")
      }
    } catch {
      setError("خطا در افزودن لایسنس")
    } finally {
      setSubmitting(false)
    }
  }

  const handleRenew = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!renewId || !renewDate) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await renewLicense(renewId, renewDate)
      if (res.success) {
        setRenewOpen(false)
        setRenewId(null)
        setRenewDate("")
        load()
      } else {
        setError(res.message ?? "خطا در تمدید لایسنس")
      }
    } catch {
      setError("خطا در تمدید لایسنس")
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = async (id: number) => {
    if (!confirm("آیا از لغو این لایسنس مطمئن هستید؟")) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await cancelLicense(id)
      if (res.success) load()
      else setError(res.message ?? "خطا در لغو لایسنس")
    } catch {
      setError("خطا در لغو لایسنس")
    } finally {
      setSubmitting(false)
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm("آیا از حذف این لایسنس مطمئن هستید؟ این عمل قابل بازگشت نیست.")) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await deleteLicense(id)
      if (res.success) load()
      else setError(res.message ?? "خطا در حذف لایسنس")
    } catch {
      setError("خطا در حذف لایسنس")
    } finally {
      setSubmitting(false)
    }
  }

  const openRenew = (id: number) => {
    setRenewId(id)
    setRenewDate(new Date().toISOString().slice(0, 10))
    setRenewOpen(true)
  }

  const formatExpiry = (d: string | null) =>
    d ? new Date(d).toLocaleDateString("fa-IR") : "نامحدود"

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">مدیریت لایسنس‌ها</h1>
        <Button onClick={() => setAddOpen(true)}>
          <Plus className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
          افزودن لایسنس جدید
        </Button>
      </div>
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      {loading ? (
        <Card>
          <CardContent className="flex items-center justify-center py-12">
            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
          </CardContent>
        </Card>
      ) : licenses.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12 text-center">
            <Key className="h-16 w-16 text-muted-foreground mb-4" />
            <p className="text-muted-foreground">هنوز لایسنس‌ای ثبت نشده است.</p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {licenses.map((license) => (
            <Card key={license.id} className="overflow-hidden">
              <CardContent className="pt-6">
                <div className="flex flex-col items-center text-center mb-4">
                  {license.logo_url ? (
                    <img
                      src={license.logo_url}
                      alt={license.project_name}
                      className="h-16 w-16 object-contain mb-2"
                    />
                  ) : (
                    <div className="h-16 w-16 rounded-lg bg-muted flex items-center justify-center mb-2">
                      <Building2 className="h-8 w-8 text-muted-foreground" />
                    </div>
                  )}
                  <h5 className="font-medium">{license.project_name}</h5>
                  <p className="text-sm text-muted-foreground" dir="ltr">
                    {license.domain}
                  </p>
                </div>
                <div className="space-y-2 mb-4">
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">باقی‌مانده:</span>
                    <span className="font-medium">{license.remaining_percentage.toFixed(1)}٪</span>
                  </div>
                  <Progress value={license.remaining_percentage} className="h-1.5" />
                </div>
                <div className="flex justify-between text-sm mb-2">
                  <span className="text-muted-foreground">تاریخ انقضا:</span>
                  <span className="font-medium">{formatExpiry(license.expiry_date)}</span>
                </div>
                <div className="flex justify-between text-sm mb-4">
                  <span className="text-muted-foreground">روزهای باقی‌مانده:</span>
                  {!license.expiry_date ? (
                    <Badge variant="secondary">VIP</Badge>
                  ) : (
                    <Badge variant="outline">{license.remaining_days} روز</Badge>
                  )}
                </div>
                <div className="flex items-center justify-between">
                  <Badge
                    variant={
                      license.status === "active"
                        ? "default"
                        : license.status === "expired"
                          ? "destructive"
                          : "secondary"
                    }
                  >
                    {STATUS_LABELS[license.status] ?? license.status}
                  </Badge>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon" className="h-8 w-8">
                        <MoreVertical className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem onClick={() => openRenew(license.id)}>
                        <RefreshCw className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                        تمدید
                      </DropdownMenuItem>
                      <DropdownMenuItem onClick={() => handleCancel(license.id)}>
                        <XCircle className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                        لغو لایسنس
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        className="text-destructive"
                        onClick={() => handleDelete(license.id)}
                      >
                        <Trash2 className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                        حذف
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <Dialog open={addOpen} onOpenChange={setAddOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>افزودن لایسنس جدید</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleAdd} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="project_name">نام مجموعه *</Label>
              <Input
                id="project_name"
                value={addForm.project_name}
                onChange={(e) =>
                  setAddForm((f) => ({ ...f, project_name: e.target.value }))
                }
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="domain">دامنه *</Label>
              <Input
                id="domain"
                dir="ltr"
                placeholder="example.com"
                value={addForm.domain}
                onChange={(e) =>
                  setAddForm((f) => ({ ...f, domain: e.target.value }))
                }
                required
              />
            </div>
            <Alert>
              <AlertDescription>کلید لایسنس به صورت خودکار تولید می‌شود.</AlertDescription>
            </Alert>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="start_date">تاریخ شروع</Label>
                <Input
                  id="start_date"
                  type="date"
                  value={addForm.start_date}
                  onChange={(e) =>
                    setAddForm((f) => ({ ...f, start_date: e.target.value }))
                  }
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="expiry_date">تاریخ انقضا</Label>
                <Input
                  id="expiry_date"
                  type="date"
                  value={addForm.expiry_date}
                  onChange={(e) =>
                    setAddForm((f) => ({ ...f, expiry_date: e.target.value }))
                  }
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="logo_url">آدرس لوگو</Label>
              <Input
                id="logo_url"
                type="url"
                dir="ltr"
                placeholder="https://example.com/logo.png"
                value={addForm.logo_url}
                onChange={(e) =>
                  setAddForm((f) => ({ ...f, logo_url: e.target.value }))
                }
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">وضعیت</Label>
              <select
                id="status"
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                value={addForm.status}
                onChange={(e) =>
                  setAddForm((f) => ({ ...f, status: e.target.value }))
                }
              >
                <option value="active">فعال</option>
                <option value="inactive">غیرفعال</option>
              </select>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setAddOpen(false)}>
                انصراف
              </Button>
              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                افزودن
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={renewOpen} onOpenChange={setRenewOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>تمدید لایسنس</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleRenew} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="renew_expiry_date">تاریخ انقضای جدید *</Label>
              <Input
                id="renew_expiry_date"
                type="date"
                value={renewDate}
                onChange={(e) => setRenewDate(e.target.value)}
                required
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setRenewOpen(false)}>
                انصراف
              </Button>
              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                تمدید
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
