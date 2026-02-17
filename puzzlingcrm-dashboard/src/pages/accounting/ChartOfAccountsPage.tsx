import { useState, useEffect } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { getConfigOrNull } from "@/api/client"
import { accountingChartList, accountingFiscalYears } from "@/api/accounting"
import type { ChartAccount } from "@/api/accounting"
import { BookOpen, Loader2 } from "lucide-react"

export function ChartOfAccountsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<ChartAccount[]>([])
  const [fiscalYears, setFiscalYears] = useState<{ id: number; name: string }[]>([])
  const [fiscalYearId, setFiscalYearId] = useState<number>(0)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    async function load() {
      const fyRes = await accountingFiscalYears()
      if (cancelled) return
      if (fyRes.success && fyRes.data?.items?.length) {
        setFiscalYears(fyRes.data.items)
        if (!fiscalYearId && fyRes.data.items[0]) setFiscalYearId(fyRes.data.items[0].id)
      }
      setLoading(false)
    }
    load()
    return () => { cancelled = true }
  }, [])

  useEffect(() => {
    if (!fiscalYearId) return
    let cancelled = false
    setLoading(true)
    accountingChartList(fiscalYearId).then((res) => {
      if (cancelled) return
      if (res.success && res.data?.items) setItems(res.data.items)
      setLoading(false)
    })
    return () => { cancelled = true }
  }, [fiscalYearId])

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div>
        <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
          <BookOpen className="h-5 w-5" />
          نمودار حساب‌ها (کدینگ)
        </h2>
        <p className="text-muted-foreground text-sm">مدیریت حساب‌های کل و معین مطابق استاندارد ایران</p>
      </div>

      {fiscalYears.length > 0 && (
        <div className="flex gap-2 items-center">
          <label className="text-sm">سال مالی:</label>
          <select
            className="border rounded px-2 py-1 text-sm"
            value={fiscalYearId}
            onChange={(e) => setFiscalYearId(Number(e.target.value))}
          >
            {fiscalYears.map((fy) => (
              <option key={fy.id} value={fy.id}>{fy.name}</option>
            ))}
          </select>
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="text-base">لیست حساب‌ها</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center gap-2 text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              در حال بارگذاری...
            </div>
          ) : items.length === 0 ? (
            <p className="text-muted-foreground">حسابی تعریف نشده. از تنظیمات حسابداری می‌توانید کدینگ پیش‌فرض را بارگذاری کنید.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>کد</TableHead>
                  <TableHead>عنوان</TableHead>
                  <TableHead>سطح</TableHead>
                  <TableHead>نوع</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {items.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell className="font-mono">{row.code}</TableCell>
                    <TableCell style={{ paddingRight: row.level * 12 }}>{row.title}</TableCell>
                    <TableCell>{row.level}</TableCell>
                    <TableCell>{row.account_type}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
