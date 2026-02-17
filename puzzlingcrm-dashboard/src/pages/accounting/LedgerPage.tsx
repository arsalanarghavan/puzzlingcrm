import { useState, useEffect } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { getConfigOrNull } from "@/api/client"
import { accountingLedger, accountingChartList, accountingFiscalYears } from "@/api/accounting"
import { Book, Loader2 } from "lucide-react"

export function LedgerPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [fiscalYears, setFiscalYears] = useState<{ id: number; name: string }[]>([])
  const [accounts, setAccounts] = useState<{ id: number; code: string; title: string }[]>([])
  const [fiscalYearId, setFiscalYearId] = useState<number>(0)
  const [accountId, setAccountId] = useState<number>(0)
  const [dateFrom, setDateFrom] = useState("")
  const [dateTo, setDateTo] = useState("")
  const [data, setData] = useState<{
    rows: Array<{ voucher_no: string; voucher_date: string; debit: number; credit: number; line_description?: string }>
    debit_total: number
    credit_total: number
    balance_debit: number
    balance_credit: number
  } | null>(null)
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    accountingFiscalYears().then((res) => {
      if (res.success && res.data?.items?.length) {
        setFiscalYears(res.data.items)
        if (!fiscalYearId && res.data.items[0]) setFiscalYearId(res.data.items[0].id)
      }
    })
  }, [])

  useEffect(() => {
    if (!fiscalYearId) return
    accountingChartList(fiscalYearId).then((res) => {
      if (res.success && res.data?.items) setAccounts(res.data.items)
    })
  }, [fiscalYearId])

  const runReport = () => {
    if (!accountId || !fiscalYearId) return
    setLoading(true)
    accountingLedger(accountId, fiscalYearId, dateFrom || undefined, dateTo || undefined).then((res) => {
      setLoading(false)
      if (res.success && res.data) setData(res.data as typeof data)
      else setData(null)
    })
  }

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div>
        <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
          <Book className="h-5 w-5" />
          دفتر کل / معین
        </h2>
        <p className="text-muted-foreground text-sm">گردش و مانده حساب</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">فیلتر</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <Label>سال مالی</Label>
              <select
                className="w-full mt-1 border rounded px-2 py-1.5 text-sm"
                value={fiscalYearId}
                onChange={(e) => setFiscalYearId(Number(e.target.value))}
              >
                {fiscalYears.map((fy) => (
                  <option key={fy.id} value={fy.id}>{fy.name}</option>
                ))}
              </select>
            </div>
            <div>
              <Label>حساب</Label>
              <select
                className="w-full mt-1 border rounded px-2 py-1.5 text-sm"
                value={accountId}
                onChange={(e) => setAccountId(Number(e.target.value))}
              >
                <option value={0}>انتخاب حساب</option>
                {accounts.map((a) => (
                  <option key={a.id} value={a.id}>{a.code} - {a.title}</option>
                ))}
              </select>
            </div>
            <div>
              <Label>از تاریخ</Label>
              <Input type="date" className="mt-1" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
            </div>
            <div>
              <Label>تا تاریخ</Label>
              <Input type="date" className="mt-1" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
            </div>
          </div>
          <Button onClick={runReport} disabled={!accountId || !fiscalYearId || loading}>
            {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            نمایش گردش
          </Button>
        </CardContent>
      </Card>

      {data && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">گردش حساب</CardTitle>
            <p className="text-sm text-muted-foreground">
              جمع بدهکار: {data.debit_total} — جمع بستانکار: {data.credit_total} — مانده بدهکار: {data.balance_debit} — مانده بستانکار: {data.balance_credit}
            </p>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>شماره سند</TableHead>
                  <TableHead>تاریخ</TableHead>
                  <TableHead>بدهکار</TableHead>
                  <TableHead>بستانکار</TableHead>
                  <TableHead>شرح</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.rows.map((row, i) => (
                  <TableRow key={i}>
                    <TableCell className="font-mono">{row.voucher_no}</TableCell>
                    <TableCell>{row.voucher_date}</TableCell>
                    <TableCell>{row.debit}</TableCell>
                    <TableCell>{row.credit}</TableCell>
                    <TableCell>{row.line_description || "—"}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
