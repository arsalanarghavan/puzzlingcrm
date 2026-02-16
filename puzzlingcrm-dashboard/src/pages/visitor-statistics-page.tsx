import { useCallback, useEffect, useState } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { DatePicker } from "@/components/ui/date-picker"
import { getConfigOrNull } from "@/api/client"
import { getVisitorStats, type VisitorStatsResponse } from "@/api/visitor-stats"
import { Loader2, LineChart, Users, Eye, Activity } from "lucide-react"

function SimpleBarChart({ data, labels, max }: { data: number[]; labels: string[]; max?: number }) {
  const m = max ?? Math.max(...data, 1)
  return (
    <div className="flex h-[180px] items-end gap-1">
      {data.map((v, i) => (
        <div key={i} className="flex flex-1 flex-col items-center gap-1">
          <div
            className="w-full min-h-[4px] rounded-t bg-primary transition-all"
            style={{ height: `${(v / m) * 100}%` }}
          />
          <span className="text-[10px] text-muted-foreground truncate max-w-full" title={labels[i]}>
            {labels[i]}
          </span>
        </div>
      ))}
    </div>
  )
}

function formatDate(s: string): string {
  try {
    return new Date(s.replace(/-/g, "/")).toLocaleDateString("fa-IR", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  } catch {
    return s
  }
}

export function VisitorStatisticsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [dateFrom, setDateFrom] = useState(() => {
    const d = new Date()
    d.setDate(d.getDate() - 30)
    return d.toISOString().slice(0, 10)
  })
  const [dateTo, setDateTo] = useState(() => new Date().toISOString().slice(0, 10))
  const [data, setData] = useState<VisitorStatsResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getVisitorStats({ date_from: dateFrom, date_to: dateTo })
      if (res.success && res.data) {
        setData(res.data)
      } else {
        setError(res.message ?? "خطا در بارگذاری آمار")
        setData(null)
      }
    } catch {
      setError("خطا در بارگذاری آمار")
      setData(null)
    } finally {
      setLoading(false)
    }
  }, [dateFrom, dateTo])

  useEffect(() => {
    load()
  }, [load])

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-xl font-semibold">آمار بازدیدکنندگان</h2>
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1">
            <label className="text-sm text-muted-foreground">از تاریخ</label>
            <DatePicker value={dateFrom} onChange={setDateFrom} className="w-[160px]" />
          </div>
          <div className="space-y-1">
            <label className="text-sm text-muted-foreground">تا تاریخ</label>
            <DatePicker value={dateTo} onChange={setDateTo} className="w-[160px]" />
          </div>
        </div>
      </div>

      {error && (
        <Card className="border-destructive">
          <CardContent className="pt-6 text-destructive">{error}</CardContent>
        </Card>
      )}

      {loading && (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      )}

      {!loading && data && (
        <>
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">کل بازدیدها</CardTitle>
                <Eye className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {Number(data.overall.total_visits).toLocaleString("fa-IR")}
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">بازدید یکتا</CardTitle>
                <Users className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {Number(data.overall.unique_visitors).toLocaleString("fa-IR")}
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">امروز</CardTitle>
                <LineChart className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {Number(data.overall.today_visits).toLocaleString("fa-IR")}
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">آنلاین (۵ دقیقه)</CardTitle>
                <Activity className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {Number(data.overall.online_now).toLocaleString("fa-IR")}
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>بازدید روزانه</CardTitle>
            </CardHeader>
            <CardContent>
              <SimpleBarChart
                data={data.daily.map((d) => d.visits)}
                labels={data.daily.map((d) => d.date)}
              />
            </CardContent>
          </Card>

          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>پربازدیدترین صفحات</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="overflow-auto max-h-[280px]">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b">
                        <th className="text-right py-2 px-2">صفحه</th>
                        <th className="text-left py-2 px-2">بازدید</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(data.top_pages || []).length === 0 ? (
                        <tr>
                          <td colSpan={2} className="text-muted-foreground py-4 text-center">
                            بدون داده
                          </td>
                        </tr>
                      ) : (
                        (data.top_pages || []).map((r, i) => (
                          <tr key={i} className="border-b">
                            <td className="py-2 px-2 truncate max-w-[200px]" title={r.page_url}>
                              {r.page_url || "–"}
                            </td>
                            <td className="py-2 px-2">{Number(r.visit_count).toLocaleString("fa-IR")}</td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>مرورگرها</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="overflow-auto max-h-[280px]">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b">
                        <th className="text-right py-2 px-2">مرورگر</th>
                        <th className="text-left py-2 px-2">تعداد</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(data.browsers || []).length === 0 ? (
                        <tr>
                          <td colSpan={2} className="text-muted-foreground py-4 text-center">
                            بدون داده
                          </td>
                        </tr>
                      ) : (
                        (data.browsers || []).map((r, i) => (
                          <tr key={i} className="border-b">
                            <td className="py-2 px-2">{r.name || "–"}</td>
                            <td className="py-2 px-2">{Number(r.count).toLocaleString("fa-IR")}</td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>سیستم‌عامل</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="overflow-auto max-h-[280px]">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b">
                        <th className="text-right py-2 px-2">سیستم‌عامل</th>
                        <th className="text-left py-2 px-2">تعداد</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(data.os || []).length === 0 ? (
                        <tr>
                          <td colSpan={2} className="text-muted-foreground py-4 text-center">
                            بدون داده
                          </td>
                        </tr>
                      ) : (
                        (data.os || []).map((r, i) => (
                          <tr key={i} className="border-b">
                            <td className="py-2 px-2">{r.name || "–"}</td>
                            <td className="py-2 px-2">{Number(r.count).toLocaleString("fa-IR")}</td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>نوع دستگاه</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="overflow-auto max-h-[280px]">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b">
                        <th className="text-right py-2 px-2">دستگاه</th>
                        <th className="text-left py-2 px-2">تعداد</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(data.devices || []).length === 0 ? (
                        <tr>
                          <td colSpan={2} className="text-muted-foreground py-4 text-center">
                            بدون داده
                          </td>
                        </tr>
                      ) : (
                        (data.devices || []).map((r, i) => (
                          <tr key={i} className="border-b">
                            <td className="py-2 px-2">{r.name || "–"}</td>
                            <td className="py-2 px-2">{Number(r.count).toLocaleString("fa-IR")}</td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>آخرین بازدیدها</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-auto max-h-[300px]">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-right py-2 px-2">زمان</th>
                      <th className="text-right py-2 px-2">صفحه</th>
                      <th className="text-right py-2 px-2">IP</th>
                      <th className="text-right py-2 px-2">مرورگر / دستگاه</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(data.recent || []).length === 0 ? (
                      <tr>
                        <td colSpan={4} className="text-muted-foreground py-4 text-center">
                          بدون داده
                        </td>
                      </tr>
                    ) : (
                      (data.recent || []).map((r, i) => (
                        <tr key={i} className="border-b">
                          <td className="py-2 px-2">{formatDate(r.visit_date)}</td>
                          <td className="py-2 px-2 truncate max-w-[180px]" title={r.page_url}>
                            {r.page_url || "–"}
                          </td>
                          <td className="py-2 px-2">{r.ip_address || "–"}</td>
                          <td className="py-2 px-2">
                            {(r.browser || "") + (r.device_type ? " / " + r.device_type : "") || "–"}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  )
}
