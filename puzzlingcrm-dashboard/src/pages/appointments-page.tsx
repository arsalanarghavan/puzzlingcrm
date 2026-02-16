import { useCallback, useEffect, useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { getConfigOrNull } from "@/api/client"
import { getUsers } from "@/api/customers"
import {
  getAppointments,
  getAppointment,
  getAppointmentsCalendar,
  manageAppointment,
  deleteAppointment,
  type AppointmentItem,
  type CalendarEvent,
} from "@/api/appointments"
import { cn } from "@/lib/utils"
import { Loader2, Calendar as CalendarIcon, List, Plus, Pencil, Trash2 } from "lucide-react"

const STATUS_COLORS: Record<string, string> = {
  pending: "bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200",
  confirmed: "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200",
  cancelled: "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200",
  completed: "bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200",
}

export function AppointmentsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [view, setView] = useState<"list" | "calendar">("list")
  const [appointments, setAppointments] = useState<AppointmentItem[]>([])
  const [events, setEvents] = useState<CalendarEvent[]>([])
  const [statuses, setStatuses] = useState<{ slug: string; name: string }[]>([])
  const [customers, setCustomers] = useState<{ id: number; display_name: string }[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [formOpen, setFormOpen] = useState(false)
  const [editId, setEditId] = useState<number | null>(null)
  const [deleteId, setDeleteId] = useState<number | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    customer_id: 0,
    title: "",
    date: "",
    time: "10:00",
    status_slug: "pending",
    notes: "",
  })
  const [calendarMonth, setCalendarMonth] = useState(() => {
    const d = new Date()
    return { year: d.getFullYear(), month: d.getMonth() }
  })

  const loadList = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getAppointments({ per_page: 100 })
      if (res.success && res.data) {
        setAppointments(res.data.appointments ?? [])
        setStatuses(res.data.statuses ?? [])
      } else {
        setError(res.message ?? "خطا در بارگذاری قرار ملاقات‌ها")
      }
    } catch {
      setError("خطا در بارگذاری قرار ملاقات‌ها")
    } finally {
      setLoading(false)
    }
  }, [])

  const loadCalendar = useCallback(async () => {
    const start = new Date(calendarMonth.year, calendarMonth.month, 1)
    const end = new Date(calendarMonth.year, calendarMonth.month + 1, 0)
    const startStr = start.toISOString().slice(0, 10)
    const endStr = end.toISOString().slice(0, 10)
    try {
      const res = await getAppointmentsCalendar(startStr, endStr)
      if (res.success && res.data?.events) {
        setEvents(res.data.events)
      }
    } catch {
      setEvents([])
    }
  }, [calendarMonth])

  const loadCustomers = useCallback(async () => {
    const res = await getUsers({ role: "customer" })
    if (res.success && res.data?.users) {
      setCustomers(
        res.data.users.map((u) => ({ id: u.id, display_name: u.display_name || u.email }))
      )
    }
  }, [])

  useEffect(() => {
    loadList()
  }, [loadList])

  useEffect(() => {
    if (view === "calendar") loadCalendar()
  }, [view, loadCalendar])

  useEffect(() => {
    if (formOpen) loadCustomers()
  }, [formOpen, loadCustomers])

  useEffect(() => {
    if (editId && formOpen) {
      getAppointment(editId).then((res) => {
        if (res.success && res.data?.appointment) {
          const a = res.data.appointment
          setForm({
            customer_id: a.customer_id,
            title: a.title,
            date: a.date || "",
            time: a.time || "10:00",
            status_slug: a.status_slug || "pending",
            notes: a.notes || "",
          })
        }
      })
    } else if (!formOpen) {
      setForm({
        customer_id: 0,
        title: "",
        date: "",
        time: "10:00",
        status_slug: "pending",
        notes: "",
      })
      setEditId(null)
    }
  }, [editId, formOpen])

  const openCreate = () => {
    setEditId(null)
    setForm({
      customer_id: 0,
      title: "",
      date: "",
      time: "10:00",
      status_slug: "pending",
      notes: "",
    })
    setFormOpen(true)
  }

  const openEdit = (a: AppointmentItem) => {
    setEditId(a.id)
    setForm({
      customer_id: a.customer_id,
      title: a.title,
      date: a.datetime ? a.datetime.slice(0, 10) : "",
      time: a.datetime ? a.datetime.slice(11, 16) : "10:00",
      status_slug: a.status_slug,
      notes: a.notes || "",
    })
    setFormOpen(true)
  }

  const handleSubmit = useCallback(async () => {
    if (!form.customer_id || !form.title.trim() || !form.date) {
      setError("مشتری، موضوع و تاریخ الزامی هستند.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await manageAppointment({
        appointment_id: editId ?? undefined,
        customer_id: form.customer_id,
        title: form.title.trim(),
        date: form.date,
        time: form.time,
        status: form.status_slug,
        notes: form.notes,
      })
      if (res.success) {
        setFormOpen(false)
        loadList()
        if (view === "calendar") loadCalendar()
      } else {
        setError(res.message ?? "خطا در ذخیره")
      }
    } catch {
      setError("خطا در ذخیره")
    } finally {
      setSubmitting(false)
    }
  }, [form, editId, loadList, loadCalendar, view])

  const handleDelete = useCallback(async () => {
    if (deleteId == null) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await deleteAppointment(deleteId)
      if (res.success) {
        setDeleteId(null)
        loadList()
        if (view === "calendar") loadCalendar()
      } else {
        setError(res.message ?? "خطا در حذف")
      }
    } catch {
      setError("خطا در حذف")
    } finally {
      setSubmitting(false)
    }
  }, [deleteId, loadList, loadCalendar, view])

  const daysInMonth = new Date(calendarMonth.year, calendarMonth.month + 1, 0).getDate()
  const firstDayJs = new Date(calendarMonth.year, calendarMonth.month, 1).getDay()
  const firstDay = (firstDayJs + 1) % 7
  const monthName = new Date(calendarMonth.year, calendarMonth.month, 1).toLocaleDateString("fa-IR", {
    month: "long",
    year: "numeric",
  })
  const eventsByDate: Record<string, CalendarEvent[]> = {}
  events.forEach((ev) => {
    const d = ev.start.slice(0, 10)
    if (!eventsByDate[d]) eventsByDate[d] = []
    eventsByDate[d].push(ev)
  })

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-xl font-semibold">قرار ملاقات‌ها</h2>
        <div className="flex gap-2">
          <Button
            variant={view === "list" ? "default" : "outline"}
            size="sm"
            onClick={() => setView("list")}
            className="gap-2"
          >
            <List className="h-4 w-4" />
            لیست
          </Button>
          <Button
            variant={view === "calendar" ? "default" : "outline"}
            size="sm"
            onClick={() => setView("calendar")}
            className="gap-2"
          >
            <CalendarIcon className="h-4 w-4" />
            تقویم
          </Button>
          <Button size="sm" onClick={openCreate} className="gap-2">
            <Plus className="h-4 w-4" />
            قرار جدید
          </Button>
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive text-sm">
          {error}
        </div>
      )}

      {loading && view === "list" ? (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : view === "list" ? (
        <Card>
          <CardContent className="pt-6">
            {appointments.length === 0 ? (
              <div className="py-12 text-center text-muted-foreground">
                هیچ قرار ملاقاتی ثبت نشده است.
              </div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>موضوع</TableHead>
                    <TableHead>مشتری</TableHead>
                    <TableHead>تاریخ و ساعت</TableHead>
                    <TableHead>وضعیت</TableHead>
                    <TableHead className="w-[120px]">عملیات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {appointments.map((a) => (
                    <TableRow key={a.id}>
                      <TableCell className="font-medium">{a.title}</TableCell>
                      <TableCell>{a.customer_name}</TableCell>
                      <TableCell>
                        {a.datetime
                          ? new Date(a.datetime).toLocaleString("fa-IR", {
                              dateStyle: "short",
                              timeStyle: "short",
                            })
                          : "—"}
                      </TableCell>
                      <TableCell>
                        <Badge className={cn(STATUS_COLORS[a.status_slug] ?? "bg-muted")}>
                          {a.status_name}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex gap-1">
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8"
                            onClick={() => openEdit(a)}
                          >
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 text-destructive hover:text-destructive"
                            onClick={() => setDeleteId(a.id)}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-medium">{monthName}</h3>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    setCalendarMonth((m) =>
                      m.month === 0 ? { year: m.year - 1, month: 11 } : { year: m.year, month: m.month - 1 }
                    )
                  }
                >
                  قبلی
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    setCalendarMonth((m) =>
                      m.month === 11 ? { year: m.year + 1, month: 0 } : { year: m.year, month: m.month + 1 }
                    )
                  }
                >
                  بعدی
                </Button>
              </div>
            </div>
            <div className="grid grid-cols-7 gap-1 text-center text-sm">
              {["ش", "ی", "د", "س", "چ", "پ", "ج"].map((day) => (
                <div key={day} className="font-medium text-muted-foreground py-1">
                  {day}
                </div>
              ))}
              {Array.from({ length: firstDay }, (_, i) => (
                <div key={`empty-${i}`} />
              ))}
              {Array.from({ length: daysInMonth }, (_, i) => {
                const day = i + 1
                const dateStr = `${calendarMonth.year}-${String(calendarMonth.month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`
                const dayEvents = eventsByDate[dateStr] ?? []
                return (
                  <div
                    key={day}
                    className="min-h-[80px] rounded border bg-muted/30 p-1 text-left"
                  >
                    <span className="text-muted-foreground">{day}</span>
                    <div className="mt-1 space-y-0.5">
                      {dayEvents.slice(0, 3).map((ev) => (
                        <div
                          key={ev.id}
                          className="truncate rounded px-1 py-0.5 text-xs cursor-pointer"
                          style={{
                            backgroundColor: ev.backgroundColor ?? "#e5e7eb",
                            color: ev.borderColor ?? "#374151",
                          }}
                          title={ev.title}
                        >
                          {ev.title}
                        </div>
                      ))}
                      {dayEvents.length > 3 && (
                        <span className="text-xs text-muted-foreground">+{dayEvents.length - 3}</span>
                      )}
                    </div>
                  </div>
                )
              })}
            </div>
          </CardContent>
        </Card>
      )}

      <Dialog open={formOpen} onOpenChange={setFormOpen}>
        <DialogContent className="sm:max-w-md" dir={isRtl ? "rtl" : "ltr"}>
          <DialogHeader>
            <DialogTitle>{editId ? "ویرایش قرار ملاقات" : "قرار ملاقات جدید"}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <label className="text-sm font-medium">مشتری *</label>
              <Select
                value={form.customer_id ? String(form.customer_id) : ""}
                onValueChange={(v) => setForm((f) => ({ ...f, customer_id: parseInt(v, 10) || 0 }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="انتخاب مشتری" />
                </SelectTrigger>
                <SelectContent>
                  {customers.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>{c.display_name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">موضوع *</label>
              <Input
                value={form.title}
                onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
                placeholder="موضوع قرار"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">تاریخ *</label>
                <Input
                  type="date"
                  value={form.date}
                  onChange={(e) => setForm((f) => ({ ...f, date: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">ساعت *</label>
                <Input
                  type="time"
                  value={form.time}
                  onChange={(e) => setForm((f) => ({ ...f, time: e.target.value }))}
                />
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">وضعیت</label>
              <Select
                value={form.status_slug}
                onValueChange={(v) => setForm((f) => ({ ...f, status_slug: v }))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {statuses.map((s) => (
                    <SelectItem key={s.slug} value={s.slug}>{s.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">یادداشت</label>
              <textarea
                className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                value={form.notes}
                onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
                rows={3}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setFormOpen(false)}>انصراف</Button>
            <Button onClick={handleSubmit} disabled={submitting}>
              {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
              {editId ? "ذخیره" : "ایجاد"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={deleteId !== null} onOpenChange={(open: boolean) => !open && setDeleteId(null)}>
        <AlertDialogContent dir={isRtl ? "rtl" : "ltr"}>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف قرار ملاقات</AlertDialogTitle>
            <AlertDialogDescription>
              آیا از حذف این قرار ملاقات اطمینان دارید؟
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>انصراف</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              onClick={handleDelete}
              disabled={submitting}
            >
              {submitting ? <Loader2 className="h-4 w-4 animate-spin" /> : "حذف"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
