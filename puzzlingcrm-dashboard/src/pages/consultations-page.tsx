import { useEffect, useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { DatePicker } from "@/components/ui/date-picker"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { getConfigOrNull } from "@/api/client"
import {
  getConsultations,
  manageConsultation,
  convertConsultationToProject,
  type Consultation,
} from "@/api/consultations"
import { cn } from "@/lib/utils"
import { Plus, Loader2, Pencil, FolderInput } from "lucide-react"

export function ConsultationsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [consultations, setConsultations] = useState<Consultation[]>([])
  const [statuses, setStatuses] = useState<{ slug: string; name: string }[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    name: "",
    phone: "",
    email: "",
    type: "phone",
    date: "",
    time: "",
    status: "in-progress",
    notes: "",
  })

  const load = async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getConsultations()
      if (res.success && res.data) {
        const d = res.data as { consultations: Consultation[]; statuses: { slug: string; name: string }[] }
        setConsultations(d.consultations ?? [])
        setStatuses(d.statuses ?? [])
      } else {
        setError(res.message ?? "خطا در بارگذاری مشاوره‌ها")
      }
    } catch {
      setError("خطا در بارگذاری مشاوره‌ها")
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [])

  const openNew = () => {
    setEditingId(null)
    setForm({
      name: "",
      phone: "",
      email: "",
      type: "phone",
      date: "",
      time: "",
      status: "in-progress",
      notes: "",
    })
    setDialogOpen(true)
  }

  const openEdit = (c: Consultation) => {
    setEditingId(c.id)
    const dt = c.datetime ? new Date(c.datetime) : null
    setForm({
      name: c.name,
      phone: c.phone,
      email: c.email ?? "",
      type: c.type,
      date: dt ? dt.toISOString().slice(0, 10) : "",
      time: dt ? dt.toTimeString().slice(0, 5) : "",
      status: c.status_slug,
      notes: c.notes ?? "",
    })
    setDialogOpen(true)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.name.trim() || !form.phone.trim()) {
      setError("نام و شماره تماس الزامی هستند.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await manageConsultation({
        consultation_id: editingId ?? undefined,
        name: form.name.trim(),
        phone: form.phone.trim(),
        email: form.email.trim() || undefined,
        type: form.type,
        date: form.date || undefined,
        time: form.time || undefined,
        status: form.status,
        notes: form.notes.trim() || undefined,
      })
      if (res.success) {
        setDialogOpen(false)
        load()
        if ((res.data as { reload?: boolean })?.reload) window.location.reload()
      } else {
        setError(res.message ?? "خطا در ذخیره")
      }
    } catch {
      setError("خطا در ذخیره")
    } finally {
      setSubmitting(false)
    }
  }

  const handleConvert = async (id: number) => {
    if (!confirm("آیا مطمئن هستید؟ یک کاربر، قرارداد و پروژه جدید ایجاد خواهد شد.")) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await convertConsultationToProject(id)
      if (res.success) {
        const url = (res.data as { redirect_url?: string })?.redirect_url
        if (url) {
          window.location.href = url
        } else {
          load()
        }
      } else {
        setError(res.message ?? "خطا در تبدیل")
      }
    } catch {
      setError("خطا در تبدیل")
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">مدیریت مشاوره‌ها</h1>
        <Button onClick={openNew}>
          <Plus className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
          افزودن مشاوره جدید
        </Button>
      </div>
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : consultations.length === 0 ? (
            <div className="py-12 text-center text-muted-foreground">
              هیچ درخواست مشاوره‌ای ثبت نشده است.
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>نام درخواست کننده</TableHead>
                  <TableHead>شماره تماس</TableHead>
                  <TableHead>نوع مشاوره</TableHead>
                  <TableHead>تاریخ و ساعت</TableHead>
                  <TableHead>نتیجه</TableHead>
                  <TableHead>عملیات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {consultations.map((c) => (
                  <TableRow key={c.id}>
                    <TableCell className="font-medium">{c.name}</TableCell>
                    <TableCell dir="ltr">{c.phone}</TableCell>
                    <TableCell>{c.type_label}</TableCell>
                    <TableCell>{c.datetime_display || "---"}</TableCell>
                    <TableCell>
                      <Badge variant={c.status_slug === "converted" ? "default" : "secondary"}>
                        {c.status_name}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex gap-2">
                        <Button variant="outline" size="sm" onClick={() => openEdit(c)}>
                          <Pencil className="h-4 w-4" />
                        </Button>
                        {c.status_slug !== "converted" && (
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleConvert(c.id)}
                            disabled={submitting}
                          >
                            <FolderInput className="h-4 w-4" />
                            تبدیل به پروژه
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {editingId ? "ویرایش مشاوره" : "ثبت مشاوره جدید"}
            </DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="name">نام درخواست کننده *</Label>
                <Input
                  id="name"
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">شماره تماس *</Label>
                <Input
                  id="phone"
                  dir="ltr"
                  value={form.phone}
                  onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="email">ایمیل</Label>
                <Input
                  id="email"
                  type="email"
                  dir="ltr"
                  value={form.email}
                  onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="type">نوع مشاوره</Label>
                <Select
                  value={form.type}
                  onValueChange={(v) => setForm((f) => ({ ...f, type: v }))}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="phone">تلفنی</SelectItem>
                    <SelectItem value="in-person">حضوری</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="date">تاریخ قرار</Label>
                <DatePicker
                  id="date"
                  value={form.date}
                  onChange={(v) => setForm((f) => ({ ...f, date: v }))}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="time">ساعت قرار</Label>
                <Input
                  id="time"
                  type="time"
                  value={form.time}
                  onChange={(e) => setForm((f) => ({ ...f, time: e.target.value }))}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">نتیجه مشاوره</Label>
              <Select
                value={form.status}
                onValueChange={(v) => setForm((f) => ({ ...f, status: v }))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {statuses.map((s) => (
                    <SelectItem key={s.slug} value={s.slug}>
                      {s.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="notes">یادداشت‌ها</Label>
              <textarea
                id="notes"
                className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                value={form.notes}
                onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
                rows={4}
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                انصراف
              </Button>
              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                {editingId ? "ذخیره تغییرات" : "ثبت مشاوره"}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
