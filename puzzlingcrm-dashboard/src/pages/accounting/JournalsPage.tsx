import { useState, useEffect } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { getConfigOrNull } from "@/api/client"
import { accountingJournalList, accountingFiscalYears } from "@/api/accounting"
import type { JournalEntry } from "@/api/accounting"
import { FileText, Loader2 } from "lucide-react"

export function JournalsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<JournalEntry[]>([])
  const [total, setTotal] = useState(0)
  const [fiscalYears, setFiscalYears] = useState<{ id: number; name: string }[]>([])
  const [fiscalYearId, setFiscalYearId] = useState<number>(0)
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    accountingFiscalYears().then((res) => {
      if (cancelled) return
      if (res.success && res.data?.items?.length) {
        setFiscalYears(res.data.items)
        if (!fiscalYearId && res.data.items[0]) setFiscalYearId(res.data.items[0].id)
      }
    })
    return () => { cancelled = true }
  }, [])

  useEffect(() => {
    if (!fiscalYearId) return
    setLoading(true)
    accountingJournalList({ fiscal_year_id: fiscalYearId, page, per_page: 15 }).then((res) => {
      if (res.success && res.data) {
        setItems(res.data.items ?? [])
        setTotal(res.data.total ?? 0)
      }
      setLoading(false)
    })
  }, [fiscalYearId, page])

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div>
        <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
          <FileText className="h-5 w-5" />
          اسناد حسابداری
        </h2>
        <p className="text-muted-foreground text-sm">ثبت و مشاهده اسناد (سند حسابداری)</p>
      </div>

      {fiscalYears.length > 0 && (
        <div className="flex gap-2 items-center">
          <label className="text-sm">سال مالی:</label>
          <select
            className="border rounded px-2 py-1 text-sm"
            value={fiscalYearId}
            onChange={(e) => { setFiscalYearId(Number(e.target.value)); setPage(1); }}
          >
            {fiscalYears.map((fy) => (
              <option key={fy.id} value={fy.id}>{fy.name}</option>
            ))}
          </select>
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="text-base">لیست اسناد</CardTitle>
          <p className="text-sm text-muted-foreground">مجموع: {total}</p>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center gap-2 text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              در حال بارگذاری...
            </div>
          ) : items.length === 0 ? (
            <p className="text-muted-foreground">سندی یافت نشد.</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>شماره سند</TableHead>
                    <TableHead>تاریخ</TableHead>
                    <TableHead>شرح</TableHead>
                    <TableHead>وضعیت</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell className="font-mono">{row.voucher_no}</TableCell>
                      <TableCell>{row.voucher_date}</TableCell>
                      <TableCell>{row.description || "—"}</TableCell>
                      <TableCell>
                        <Badge variant={row.status === "posted" ? "default" : "secondary"}>
                          {row.status === "posted" ? "ثبت‌شده" : "پیش‌نویس"}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {total > 15 && (
                <div className="flex gap-2 mt-4">
                  <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                    قبلی
                  </Button>
                  <span className="py-2 text-sm">صفحه {page}</span>
                  <Button variant="outline" size="sm" disabled={page * 15 >= total} onClick={() => setPage((p) => p + 1)}>
                    بعدی
                  </Button>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
