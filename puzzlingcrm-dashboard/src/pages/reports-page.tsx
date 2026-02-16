import { useCallback, useEffect, useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { DatePicker } from "@/components/ui/date-picker"
import { getConfigOrNull } from "@/api/client"
import { getReports } from "@/api/reports"
import {
  Loader2,
  BarChart3,
  DollarSign,
  ListTodo,
  MessageSquare,
  Rocket,
  FolderOpen,
  FileText,
  UserPlus,
  Users,
  Ticket,
  FileDown,
  FileSpreadsheet,
} from "lucide-react"

const TABS = [
  { id: "overview", label: "نمای کلی", icon: BarChart3 },
  { id: "finance", label: "گزارش مالی", icon: DollarSign },
  { id: "tasks", label: "گزارش وظایف", icon: ListTodo },
  { id: "tickets", label: "گزارش تیکت‌ها", icon: MessageSquare },
  { id: "agile", label: "گزارش اَجایل", icon: Rocket },
] as const

export function ReportsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [tab, setTab] = useState<string>("overview")
  const [dateFrom, setDateFrom] = useState(() => {
    const d = new Date()
    d.setDate(1)
    return d.toISOString().slice(0, 10)
  })
  const [dateTo, setDateTo] = useState(() => new Date().toISOString().slice(0, 10))
  const [data, setData] = useState<Record<string, unknown> | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getReports({
        tab,
        date_from: dateFrom,
        date_to: dateTo,
      })
      if (res.success && res.data?.stats) {
        setData(res.data.stats as Record<string, unknown>)
      } else {
        setError(res.message ?? "خطا در بارگذاری گزارش")
        setData(null)
      }
    } catch {
      setError("خطا در بارگذاری گزارش")
      setData(null)
    } finally {
      setLoading(false)
    }
  }, [tab, dateFrom, dateTo])

  useEffect(() => {
    load()
  }, [load])

  const setPresetThisMonth = () => {
    const d = new Date()
    setDateFrom(new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10))
    setDateTo(new Date(d.getFullYear(), d.getMonth() + 1, 0).toISOString().slice(0, 10))
  }

  const setPresetLastMonth = () => {
    const d = new Date()
    setDateFrom(new Date(d.getFullYear(), d.getMonth() - 1, 1).toISOString().slice(0, 10))
    setDateTo(new Date(d.getFullYear(), d.getMonth(), 0).toISOString().slice(0, 10))
  }

  const handleExportCsv = (type: "contracts" | "tasks") => {
    const c = window.puzzlingcrm
    if (!c?.ajaxUrl || !c?.nonce) return
    const params = new URLSearchParams({
      action: "puzzlingcrm_export_reports_csv",
      security: c.nonce,
      type,
      date_from: dateFrom,
      date_to: dateTo,
    })
    window.open(`${c.ajaxUrl}?${params.toString()}`, "_blank")
  }

  const handleExportPdf = () => {
    if (!data) return
    import("jspdf").then(({ jsPDF }) => {
      const doc = new jsPDF("p", "mm", "a4")
      const pageW = doc.getPageWidth()
      let y = 20
      doc.setFontSize(18)
      doc.text("گزارش PuzzlingCRM", pageW / 2, y, { align: "center" })
      y += 12
      doc.setFontSize(11)
      doc.text(`بازه: ${dateFrom} تا ${dateTo}`, pageW / 2, y, { align: "center" })
      y += 10
      doc.text(`تاریخ تهیه: ${new Date().toLocaleDateString("fa-IR")}`, pageW / 2, y, { align: "center" })
      y += 15
      doc.setFontSize(10)
      const labels: Record<string, string> = {
        total_projects: "کل پروژه‌ها",
        total_tasks: "کل وظایف",
        completed_tasks: "وظایف تکمیل‌شده",
        total_tickets: "تیکت‌ها",
        total_leads: "سرنخ‌ها",
        total_customers: "مشتریان",
        total_contracts: "قراردادها",
        total_revenue: "کل درآمد",
      }
      Object.entries(data).forEach(([key, value]) => {
        if (key === "project_by_status" || key === "task_by_status" || key === "currency") return
        const label = labels[key] ?? key
        const text = `${label}: ${value != null ? String(value) : "-"}`
        if (y > 270) {
          doc.addPage()
          y = 20
        }
        doc.text(text, 20, y)
        y += 8
      })
      if (Array.isArray(data.project_by_status)) {
        doc.text("پروژه به وضعیت:", 20, y)
        y += 7
        ;(data.project_by_status as { name: string; count: number }[]).forEach((item) => {
          if (y > 270) {
            doc.addPage()
            y = 20
          }
          doc.text(`  ${item.name}: ${item.count}`, 20, y)
          y += 7
        })
      }
      if (Array.isArray(data.task_by_status)) {
        y += 5
        doc.text("وظیفه به وضعیت:", 20, y)
        y += 7
        ;(data.task_by_status as { name: string; count: number }[]).forEach((item) => {
          if (y > 270) {
            doc.addPage()
            y = 20
          }
          doc.text(`  ${item.name}: ${item.count}`, 20, y)
          y += 7
        })
      }
      doc.save(`report-${dateFrom}-${dateTo}.pdf`)
    })
  }

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-xl font-semibold">گزارشات</h2>
        <div className="flex flex-wrap items-center gap-2">
          <Button variant="outline" size="sm" onClick={() => handleExportCsv("contracts")} className="gap-2">
            <FileSpreadsheet className="h-4 w-4" />
            خروجی CSV (قراردادها)
          </Button>
          <Button variant="outline" size="sm" onClick={() => handleExportCsv("tasks")} className="gap-2">
            <FileSpreadsheet className="h-4 w-4" />
            خروجی CSV (وظایف)
          </Button>
          <Button variant="outline" size="sm" onClick={handleExportPdf} disabled={!data} className="gap-2">
            <FileDown className="h-4 w-4" />
            خروجی PDF
          </Button>
        </div>
      </div>

      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-wrap items-end gap-3 mb-4">
            <div className="space-y-1">
              <label className="text-sm text-muted-foreground">از تاریخ</label>
              <DatePicker
                value={dateFrom}
                onChange={setDateFrom}
                className="w-[160px]"
              />
            </div>
            <div className="space-y-1">
              <label className="text-sm text-muted-foreground">تا تاریخ</label>
              <DatePicker
                value={dateTo}
                onChange={setDateTo}
                className="w-[160px]"
              />
            </div>
            <Button onClick={load}>اعمال فیلتر</Button>
            <Button variant="outline" onClick={setPresetThisMonth}>
              این ماه
            </Button>
            <Button variant="outline" onClick={setPresetLastMonth}>
              ماه قبل
            </Button>
          </div>

          <div className="flex flex-wrap gap-2 border-b mb-4">
            {TABS.map((t) => {
              const Icon = t.icon
              return (
                <Button
                  key={t.id}
                  variant={tab === t.id ? "default" : "ghost"}
                  size="sm"
                  className="gap-2"
                  onClick={() => setTab(t.id)}
                >
                  <Icon className="h-4 w-4" />
                  {t.label}
                </Button>
              )
            })}
          </div>

          {error && (
            <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive text-sm mb-4">
              {error}
            </div>
          )}

          {loading ? (
            <div className="flex items-center justify-center py-12">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : data ? (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {"total_projects" in data && (
                <Card>
                  <CardContent className="pt-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                        <FolderOpen className="h-5 w-5 text-primary" />
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">پروژه‌ها</p>
                        <p className="text-xl font-semibold">{String(data.total_projects ?? 0)}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
              {"total_tasks" in data && (
                <Card>
                  <CardContent className="pt-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10">
                        <ListTodo className="h-5 w-5 text-amber-600" />
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">وظایف</p>
                        <p className="text-xl font-semibold">{String(data.total_tasks ?? 0)}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
              {"completed_tasks" in data && (
                <Card>
                  <CardContent className="pt-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/10">
                        <ListTodo className="h-5 w-5 text-green-600" />
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">وظایف تکمیل‌شده</p>
                        <p className="text-xl font-semibold">{String(data.completed_tasks ?? 0)}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
              {"total_tickets" in data && (
                <Card>
                  <CardContent className="pt-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/10">
                        <Ticket className="h-5 w-5 text-blue-600" />
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">تیکت‌ها</p>
                        <p className="text-xl font-semibold">{String(data.total_tickets ?? 0)}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
              {"total_leads" in data && (
                <Card>
                  <CardContent className="pt-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-500/10">
                        <UserPlus className="h-5 w-5 text-violet-600" />
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">سرنخ‌ها</p>
                        <p className="text-xl font-semibold">{String(data.total_leads ?? 0)}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
              {"total_customers" in data && (
                <Card>
                  <CardContent className="pt-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-500/10">
                        <Users className="h-5 w-5 text-cyan-600" />
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">مشتریان</p>
                        <p className="text-xl font-semibold">{String(data.total_customers ?? 0)}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
              {"total_contracts" in data && (
                <Card>
                  <CardContent className="pt-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-500/10">
                        <FileText className="h-5 w-5 text-orange-600" />
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">قراردادها</p>
                        <p className="text-xl font-semibold">{String(data.total_contracts ?? 0)}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
              {"total_revenue" in data && (
                <Card>
                  <CardContent className="pt-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10">
                        <DollarSign className="h-5 w-5 text-emerald-600" />
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">کل درآمد</p>
                        <p className="text-xl font-semibold">
                          {Number(data.total_revenue ?? 0).toLocaleString("fa-IR")}{" "}
                          {data.currency ? String(data.currency) : ""}
                        </p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
              {Array.isArray(data.project_by_status) &&
                (data.project_by_status as { name: string; count: number }[]).map((item, i) => (
                  <Card key={i}>
                    <CardContent className="pt-6">
                      <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                          <FolderOpen className="h-5 w-5 text-muted-foreground" />
                        </div>
                        <div>
                          <p className="text-sm text-muted-foreground">پروژه ({item.name})</p>
                          <p className="text-xl font-semibold">{item.count}</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              {Array.isArray(data.task_by_status) &&
                (data.task_by_status as { name: string; count: number }[]).map((item, i) => (
                  <Card key={i}>
                    <CardContent className="pt-6">
                      <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                          <ListTodo className="h-5 w-5 text-muted-foreground" />
                        </div>
                        <div>
                          <p className="text-sm text-muted-foreground">وظیفه ({item.name})</p>
                          <p className="text-xl font-semibold">{item.count}</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))}
            </div>
          ) : !loading && !error && (
            <div className="py-12 text-center text-muted-foreground">داده‌ای برای این بازه موجود نیست.</div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
